<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Participant;
use App\Services\SubmitExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class SubmitExamServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubmitExamService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SubmitExamService::class);
    }

    public function test_submit_marks_attempt_and_participant_as_finished(): void
    {
        $participant = Participant::factory()->create(['is_finished' => false]);
        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->create(['started_at' => now()->subMinutes(5)]);

        $result = $this->service->submit($attempt);

        $this->assertTrue($result->is_submitted);
        $this->assertNotNull($result->finished_at);
        $this->assertDatabaseHas('exam_attempts', [
            'id' => $attempt->id,
            'is_submitted' => 1,
        ]);
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'is_finished' => 1,
        ]);
    }

    public function test_submit_updates_last_used_at_on_active_session_token(): void
    {
        $participant = Participant::factory()->create();
        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->create(['started_at' => now()->subMinutes(5)]);

        $token = ExamToken::factory()
            ->forSession($attempt->session)
            ->create(['is_active' => true, 'last_used_at' => null]);

        $this->service->submit($attempt);

        $this->assertNotNull($token->fresh()->last_used_at);
    }

    public function test_submit_does_not_touch_inactive_tokens(): void
    {
        $participant = Participant::factory()->create();
        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->create(['started_at' => now()->subMinutes(5)]);

        $inactiveToken = ExamToken::factory()
            ->forSession($attempt->session)
            ->inactive()
            ->create(['last_used_at' => null]);

        $this->service->submit($attempt);

        $this->assertNull($inactiveToken->fresh()->last_used_at);
    }

    public function test_submit_is_idempotent_when_called_twice(): void
    {
        // H3 regression test: submit dua kali berturut-turut (mensimulasikan
        // double-klik / retry jaringan) tidak boleh mengubah finished_at
        // pada panggilan kedua, dan tidak boleh melempar error.
        $participant = Participant::factory()->create();
        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->create(['started_at' => now()->subMinutes(5)]);

        $first = $this->service->submit($attempt->fresh());
        $firstFinishedAt = $first->finished_at;
        $this->assertNotNull($firstFinishedAt);

        $second = $this->service->submit($attempt->fresh());

        $this->assertTrue($second->is_submitted);
        $this->assertNotNull($second->finished_at);
        $this->assertEquals(
            $firstFinishedAt->timestamp,
            $second->finished_at->timestamp,
            'finished_at tidak boleh berubah pada submit kedua.'
        );
        $this->assertDatabaseCount('exam_attempts', 1);
    }

    public function test_submit_accepts_when_attempt_time_has_expired(): void
    {
        // M5: Submit diizinkan meski melewati deadline.
        // Ini mencegah loop popup error di client (timer terus trigger submit
        // → server reject → loop popup). Server tetap cek is_submitted dan
        // session active, tapi tidak cek deadline.
        $session = ExamSession::factory()->create([
            'duration_minutes' => 30,
        ]);
        $participant = Participant::factory()->forSession($session)->create();
        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->create(['started_at' => now()->subHour()]); // sudah lewat durasi 30 menit

        // Tidak boleh throw exception, submit harus diterima
        $result = $this->service->submit($attempt);

        $this->assertTrue($result->is_submitted);
        $this->assertNotNull($result->finished_at);
    }

    public function test_submit_rejects_when_session_already_ended(): void
    {
        $session = ExamSession::factory()->ended()->create();
        $participant = Participant::factory()->forSession($session)->create();
        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->create(['started_at' => $session->start_time]);

        $this->expectException(ValidationException::class);

        $this->service->submit($attempt);
    }
}
