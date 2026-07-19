<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class AdminExamSimulationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'username' => 'admin_sim',
            'email' => 'admin.sim@example.com',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_can_start_and_open_exam_simulation(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.simulation.start'), [
            'duration_minutes' => 25,
            'question_count' => 40,
        ])->assertRedirect(route('admin.simulation.run'));

        $this->get(route('admin.simulation.run'))
            ->assertOk()
            ->assertSee('Simulasi Ujian Internal');
    }

    public function test_admin_can_save_answer_and_submit_simulation(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.simulation.start'), [
            'duration_minutes' => 20,
            'question_count' => 10,
        ])->assertRedirect(route('admin.simulation.run'));

        $this->postJson(route('admin.simulation.save-answer'), [
            'question_id' => 1,
            'answer' => true,
            'current_index' => 0,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.answered_count', 1)
            ->assertJsonPath('meta.unanswered_count', 9);

        $this->postJson(route('admin.simulation.submit'), [])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect', route('admin.simulation.result'));

        $this->get(route('admin.simulation.result'))
            ->assertOk()
            ->assertSee('Hasil Simulasi Ujian');
    }

    public function test_simulation_run_redirects_to_setting_when_session_missing(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.simulation.run'))
            ->assertRedirect(route('admin.simulation.index'))
            ->assertSessionHasErrors('simulation');
    }

}
