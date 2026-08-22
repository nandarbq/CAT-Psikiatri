<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\Participant;
use App\Models\ParticipantAnswer;
use App\Models\Question;
use App\Services\SaveAnswerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class SaveAnswerServiceTest extends TestCase
{
    use RefreshDatabase;

    private SaveAnswerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SaveAnswerService::class);
    }

    public function test_save_creates_answer_with_question_snapshot_and_answered_at(): void
    {
        $attempt = $this->createAttempt();
        $question = $this->createQuestion($attempt);

        $this->service->save(
            $attempt,
            $question,
            true,
        );

        $this->assertDatabaseHas('participant_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_snapshot' => $question->question,
            'answer' => 1,
        ]);

        $savedAnswer = ParticipantAnswer::query()
            ->where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->firstOrFail();

        $this->assertNotNull($savedAnswer->answered_at);
    }

    public function test_save_can_save_false_answer(): void
    {
        $attempt = $this->createAttempt();
        $question = $this->createQuestion($attempt);

        $this->service->save(
            $attempt,
            $question,
            false,
        );

        $this->assertDatabaseHas('participant_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer' => 0,
        ]);
    }

    public function test_save_updates_existing_answer_instead_of_creating_duplicate(): void
    {
        $attempt = $this->createAttempt();
        $question = $this->createQuestion($attempt);

        $this->service->save(
            $attempt,
            $question,
            true,
        );

        $this->service->save(
            $attempt->fresh(),
            $question,
            false,
        );

        $this->assertDatabaseCount('participant_answers', 1);

        $this->assertDatabaseHas('participant_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer' => 0,
        ]);
    }

    public function test_save_updates_current_question_when_question_order_is_higher(): void
    {
        $attempt = $this->createAttempt([
            'current_question' => 1,
        ]);

        $question = $this->createQuestion(
            $attempt,
            [
                'order_no' => 5,
            ],
        );

        $this->service->save(
            $attempt,
            $question,
            true,
        );

        $this->assertSame(
            5,
            (int) $attempt->fresh()->current_question
        );
    }

    public function test_save_does_not_move_current_question_backward(): void
    {
        $attempt = $this->createAttempt([
            'current_question' => 5,
        ]);

        $question = $this->createQuestion(
            $attempt,
            [
                'order_no' => 3,
            ],
        );

        $this->service->save(
            $attempt,
            $question,
            true,
        );

        $this->assertSame(
            5,
            (int) $attempt->fresh()->current_question
        );
    }

    public function test_save_rejects_submitted_attempt(): void
    {
        $attempt = $this->createAttempt([
            'is_submitted' => true,
            'finished_at' => now(),
        ]);

        $question = $this->createQuestion($attempt);

        $this->expectException(ValidationException::class);

        $this->service->save(
            $attempt,
            $question,
            true,
        );
    }

    public function test_save_rejects_answer_when_attempt_duration_has_expired(): void
    {
        // M5 — Menjawab dibatasi oleh deadline (ensureAttemptAnswerable),
        // berbeda dari submit yang tetap diizinkan lewat deadline. Setelah
        // durasi attempt habis, peserta tidak boleh lagi mengubah jawaban.
        $session = ExamSession::factory()->create([
            'duration_minutes' => 30,
        ]);
        $participant = Participant::factory()->forSession($session)->create();
        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->create([
                'started_at' => now()->subHour(),
                'is_submitted' => false,
            ]);
        $question = $this->createQuestion($attempt);

        $this->expectException(ValidationException::class);

        $this->service->save($attempt, $question, true);
    }

    public function test_save_accepts_answer_when_within_deadline(): void
    {
        // M5 — Selama masih dalam deadline, jawaban tetap bisa disimpan.
        $session = ExamSession::factory()->create([
            'duration_minutes' => 60,
        ]);
        $participant = Participant::factory()->forSession($session)->create();
        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->create([
                'started_at' => now()->subMinutes(5),
                'is_submitted' => false,
            ]);
        $question = $this->createQuestion($attempt);

        $this->service->save($attempt, $question, true);

        $this->assertDatabaseHas('participant_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer' => 1,
        ]);
    }

    public function test_save_rejects_question_from_another_module(): void
    {
        $attempt = $this->createAttempt();

        $otherModuleQuestion = Question::factory()->create([
            'is_active' => true,
        ]);

        $this->expectException(HttpException::class);

        $this->service->save(
            $attempt,
            $otherModuleQuestion,
            true,
        );
    }

    public function test_save_rejects_inactive_question(): void
    {
        $attempt = $this->createAttempt();

        $question = $this->createQuestion(
            $attempt,
            [
                'is_active' => false,
            ],
        );

        $this->expectException(HttpException::class);

        $this->service->save(
            $attempt,
            $question,
            true,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAttempt(array $attributes = []): ExamAttempt
    {
        $participant = Participant::factory()->create();

        return ExamAttempt::factory()
            ->forParticipant($participant)
            ->create(array_merge([
                'started_at' => now()->subMinutes(5),
                'is_submitted' => false,
            ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createQuestion(
        ExamAttempt $attempt,
        array $attributes = [],
    ): Question {
        return Question::factory()
            ->forModule($attempt->session->module)
            ->create(array_merge([
                'is_active' => true,
                'order_no' => 1,
            ], $attributes));
    }
}
