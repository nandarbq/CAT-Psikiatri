<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private string $envPath;
    private string $logPath;
    private bool $envExists = false;
    private bool $logExists = false;
    private string $envOriginal = '';
    private string $logOriginal = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->envPath = base_path('.env');
        $this->logPath = storage_path('logs/laravel.log');

        $this->envExists = is_file($this->envPath);
        $this->logExists = is_file($this->logPath);
        $this->envOriginal = $this->envExists ? (string) file_get_contents($this->envPath) : '';
        $this->logOriginal = $this->logExists ? (string) file_get_contents($this->logPath) : '';
    }

    protected function tearDown(): void
    {
        if ($this->envExists) {
            file_put_contents($this->envPath, $this->envOriginal);
        } elseif (is_file($this->envPath)) {
            @unlink($this->envPath);
        }

        if ($this->logExists) {
            file_put_contents($this->logPath, $this->logOriginal);
        } elseif (is_file($this->logPath)) {
            @unlink($this->logPath);
        }

        parent::tearDown();
    }

    private function actingAsAdmin(): User
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'username' => 'admin_settings',
            'email' => 'admin.settings@example.com',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_can_open_settings_tabs(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.settings.index', ['tab' => 'env']))->assertOk()->assertSee('Editor .env');
        $this->get(route('admin.settings.index', ['tab' => 'logs']))->assertOk()->assertSee('Log Harian');
        $this->get(route('admin.settings.index', ['tab' => 'location']))->assertOk()->assertSee('Lokasi RSUD');
    }

    public function test_admin_can_update_env_content(): void
    {
        $this->actingAsAdmin();

        $expected = "APP_NAME=CAT-TEST\nAPP_ENV=local";

        $this->put(route('admin.settings.env.update'), [
            'env_content' => $expected,
        ])->assertRedirect(route('admin.settings.index', ['tab' => 'env']));

        $this->assertSame($expected, rtrim((string) file_get_contents($this->envPath)));
    }

    public function test_admin_can_download_env_backup(): void
    {
        $this->actingAsAdmin();
        $fixture = "APP_NAME=BACKUP_TEST\nAPP_ENV=local";
        file_put_contents($this->envPath, $fixture);

        $response = $this->get(route('admin.settings.env.backup'));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/plain; charset=UTF-8');
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('env-backup-', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString($fixture, $response->streamedContent());
    }

    public function test_admin_can_clear_config_cache(): void
    {
        $this->actingAsAdmin();
        Artisan::spy();

        $this->post(route('admin.settings.cache.config-clear'))
            ->assertRedirect(route('admin.settings.index', ['tab' => 'env']));

        Artisan::shouldHaveReceived('call')
            ->once()
            ->with('config:clear');
    }

    public function test_admin_can_filter_and_delete_logs_by_date(): void
    {
        $this->actingAsAdmin();

        if (! is_dir(dirname($this->logPath))) {
            mkdir(dirname($this->logPath), 0777, true);
        }

        $logFixture = <<<'LOG'
[2026-05-12 09:00:00] local.INFO: Alpha message
stack line one
[2026-05-13 10:00:00] local.ERROR: Bravo message
stack line two
[2026-05-13 10:01:00] local.WARNING: Charlie message
[2026-05-14 08:30:00] local.INFO: Delta message
LOG;

        file_put_contents($this->logPath, $logFixture);

        $this->get(route('admin.settings.index', [
            'tab' => 'logs',
            'date' => '2026-05-13',
        ]))
            ->assertOk()
            ->assertSee('Bravo message')
            ->assertSee('Charlie message')
            ->assertDontSee('Alpha message');

        $this->delete(route('admin.settings.logs.delete'), [
            'date' => '2026-05-13',
        ])->assertRedirect(route('admin.settings.index', [
            'tab' => 'logs',
            'date' => '2026-05-13',
        ]));

        $contentAfterDelete = (string) file_get_contents($this->logPath);
        $this->assertStringNotContainsString('Bravo message', $contentAfterDelete);
        $this->assertStringNotContainsString('Charlie message', $contentAfterDelete);
        $this->assertStringContainsString('Alpha message', $contentAfterDelete);
        $this->assertStringContainsString('Delta message', $contentAfterDelete);
    }
}
