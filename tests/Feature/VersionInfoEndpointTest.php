<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class VersionInfoEndpointTest extends TestCase
{
    /**
     * @var array<string, bool>
     */
    private array $originalExists = [];

    /**
     * @var array<string, string>
     */
    private array $originalContent = [];

    /**
     * @var array<int, string>
     */
    private array $markerFiles = [
        'REVISION',
        '.commit_sha',
        'VERSION',
        '.version',
        'DEPLOYED_AT',
        '.deployed_at',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->markerFiles as $markerFile) {
            $absolutePath = base_path($markerFile);
            $this->originalExists[$markerFile] = is_file($absolutePath);
            $this->originalContent[$markerFile] = $this->originalExists[$markerFile]
                ? (string) file_get_contents($absolutePath)
                : '';
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->markerFiles as $markerFile) {
            $absolutePath = base_path($markerFile);
            if ($this->originalExists[$markerFile] ?? false) {
                file_put_contents($absolutePath, $this->originalContent[$markerFile]);
                continue;
            }

            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }

        parent::tearDown();
    }

    public function test_version_endpoint_uses_config_values_when_present(): void
    {
        config()->set('monitoring.app_name', 'CAT Test Monitoring');
        config()->set('monitoring.commit_sha', str_repeat('a', 40));
        config()->set('monitoring.version', 'release-2026.05.16');
        config()->set('monitoring.deployed_at', '2026-05-16T09:00:00+07:00');

        $response = $this->getJson('/version');
        $response->assertOk();
        $response->assertJsonPath('app', 'CAT Test Monitoring');
        $response->assertJsonPath('version', 'release-2026.05.16');
        $response->assertJsonPath('commit', str_repeat('a', 40));
        $response->assertJsonPath('commit_short', 'aaaaaaa');
        $response->assertJsonPath('deployed_at', '2026-05-16T09:00:00+07:00');
        $response->assertJsonPath('live_commit_sha', str_repeat('a', 40));
        $response->assertJsonPath('sha', str_repeat('a', 40));

        $aliasResponse = $this->getJson('/build-info');
        $aliasResponse->assertOk();
        $this->assertSame($response->json('commit'), $aliasResponse->json('commit'));
        $this->assertSame($response->json('version'), $aliasResponse->json('version'));
    }

    public function test_version_endpoint_reads_fallback_from_marker_files(): void
    {
        config()->set('monitoring.commit_sha', null);
        config()->set('monitoring.version', null);
        config()->set('monitoring.deployed_at', null);

        file_put_contents(base_path('REVISION'), str_repeat('b', 40)."\n");
        file_put_contents(base_path('VERSION'), "release-from-file\n");
        file_put_contents(base_path('DEPLOYED_AT'), "2026-05-15T19:00:00+07:00\n");

        $response = $this->getJson('/version');
        $response->assertOk();
        $response->assertJsonPath('commit', str_repeat('b', 40));
        $response->assertJsonPath('version', 'release-from-file');
        $response->assertJsonPath('deployed_at', '2026-05-15T19:00:00+07:00');
    }
}
