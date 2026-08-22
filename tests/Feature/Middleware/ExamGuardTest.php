<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_exam_guard_denies_attempt_from_different_session(): void
    {

        $currentSession = ExamSession::factory()->create([
            'status' => true,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
        ]);

        /*
         * Sesi lama tempat attempt dibuat.
         */
        $oldSession = ExamSession::factory()->create([
            'status' => true,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
        ]);

        $participant = Participant::factory()
            ->forSession($currentSession)
            ->create();

        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->forSession($oldSession)
            ->create([
                'is_submitted' => false,
                'started_at' => now()->subMinutes(5),
            ]);

        /*
         * Simulasikan session Laravel peserta.
         */
        $response = $this->withSession([
            'participant_id' => $participant->id,
            'exam_attempt_id' => $attempt->id,
            'exam_session_token' => $attempt->session_token,
        ])->get(
            route('exam.show')
        );

        /*
         * M11 harus menolak karena:
         *
         * attempt.session_id != participant.session_id
         */
        $response->assertRedirect(route('exam.access'));

        $response->assertSessionHasErrors([
            'session' => 'Sesi tes tidak sesuai dengan sesi peserta.',
        ]);
    }

    public function test_exam_guard_allows_attempt_from_participants_current_session(): void
    {
        /*
         * Sesi peserta saat ini.
         */
        $session = ExamSession::factory()->create([
            'status' => true,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
        ]);

        $participant = Participant::factory()
            ->forSession($session)
            ->create();

        /*
         * Attempt berasal dari sesi yang sama dengan participant.
         */
        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->forSession($session)
            ->create([
                'is_submitted' => false,
                'started_at' => now()->subMinutes(5),
            ]);

        $response = $this->withSession([
            'participant_id' => $participant->id,
            'exam_attempt_id' => $attempt->id,
            'exam_session_token' => $attempt->session_token,
        ])->get(
            route('exam.show')
        );

        $response->assertOk()
            ->assertViewIs('exam.index')
            ->assertSessionMissing('errors');
    }

    public function test_exam_guard_returns_auth_expired_code_for_json_request_when_participant_session_invalid(): void
    {
        /*
         * Sesi peserta tidak valid (participant_id tidak ada di session)
         * harus menghasilkan 403 dengan code "auth_expired" pada request JSON,
         * agar client bisa membedakan sesi yang berakhir dari error lain
         * (mis. geo yang bisa di-retry) dan mengarahkan ke login.
         */
        $response = $this->postJson('/exam/save-answer', [
            'question_id' => 1,
            'answer' => true,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'auth_expired',
            ]);
    }
}
