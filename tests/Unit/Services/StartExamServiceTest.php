<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\ExamAttempt;
use App\Models\ExamLocationSetting;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Participant;
use App\Models\Question;
use App\Services\StartExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class StartExamServiceTest extends TestCase
{
    use RefreshDatabase;

    private StartExamService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StartExamService::class);
    }

    public function test_start_creates_new_attempt_with_valid_token(): void
    {
        $session = ExamSession::factory()->create();
        $participant = Participant::factory()
            ->forSession($session)
            ->create();

        Question::factory()
            ->forModule($session->module)
            ->create([
                'is_active' => true,
            ]);

        ExamToken::factory()
            ->forSession($session)
            ->create([
                'is_active' => true,
                'expires_at' => now()->addHour(),
            ]);

        $request = $this->makeRequest();

        $attempt = $this->service->startOrResume(
            $participant,
            ExamToken::query()->first()->token,
            $request,
        );

        $this->assertInstanceOf(
            ExamAttempt::class,
            $attempt
        );

        $this->assertSame(
            $participant->id,
            $attempt->participant_id
        );

        $this->assertSame(
            $session->id,
            $attempt->session_id
        );

        $this->assertFalse($attempt->is_submitted);
        $this->assertNotNull($attempt->started_at);
        $this->assertNotNull($attempt->session_token);

        $this->assertDatabaseHas('exam_attempts', [
            'id' => $attempt->id,
            'participant_id' => $participant->id,
            'session_id' => $session->id,
            'is_submitted' => 0,
        ]);
    }

    public function test_start_rejects_invalid_token(): void
    {
        $session = ExamSession::factory()->create();
        $participant = Participant::factory()
            ->forSession($session)
            ->create();

        $this->expectException(ValidationException::class);

        $this->service->startOrResume(
            $participant,
            '999999',
            $this->makeRequest(),
        );
    }

    public function test_start_rejects_inactive_token(): void
    {
        $session = ExamSession::factory()->create();
        $participant = Participant::factory()
            ->forSession($session)
            ->create();

        ExamToken::factory()
            ->forSession($session)
            ->create([
                'is_active' => false,
                'expires_at' => now()->addHour(),
            ]);

        $token = ExamToken::query()->first()->token;

        $this->expectException(ValidationException::class);

        $this->service->startOrResume(
            $participant,
            $token,
            $this->makeRequest(),
        );
    }

    public function test_start_rejects_expired_token(): void
    {
        $session = ExamSession::factory()->create();
        $participant = Participant::factory()
            ->forSession($session)
            ->create();

        ExamToken::factory()
            ->forSession($session)
            ->create([
                'is_active' => true,
                'expires_at' => now()->subMinute(),
            ]);

        $token = ExamToken::query()->first()->token;

        $this->expectException(ValidationException::class);

        $this->service->startOrResume(
            $participant,
            $token,
            $this->makeRequest(),
        );
    }

    public function test_start_rejects_when_no_active_questions_exist(): void
    {
        $session = ExamSession::factory()->create();
        $participant = Participant::factory()
            ->forSession($session)
            ->create();

        ExamToken::factory()
            ->forSession($session)
            ->create([
                'is_active' => true,
                'expires_at' => now()->addHour(),
            ]);

        $token = ExamToken::query()->first()->token;

        $this->expectException(ValidationException::class);

        $this->service->startOrResume(
            $participant,
            $token,
            $this->makeRequest(),
        );
    }

    public function test_start_resumes_existing_active_attempt(): void
    {
        $session = ExamSession::factory()->create();
        $participant = Participant::factory()
            ->forSession($session)
            ->create();

        Question::factory()
            ->forModule($session->module)
            ->create([
                'is_active' => true,
            ]);

        ExamToken::factory()
            ->forSession($session)
            ->create([
                'is_active' => true,
                'expires_at' => now()->addHour(),
            ]);

        $existingAttempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->create([
                'is_submitted' => false,
                'started_at' => now()->subMinutes(5),
                'session_token' => 'old-session-token',
            ]);

        $token = ExamToken::query()->first()->token;

        $result = $this->service->startOrResume(
            $participant,
            $token,
            $this->makeRequest(),
        );

        $this->assertSame(
            $existingAttempt->id,
            $result->id
        );

        $this->assertFalse($result->is_submitted);

        $this->assertNotSame(
            'old-session-token',
            $result->session_token
        );

        $this->assertDatabaseCount('exam_attempts', 1);
    }

    public function test_start_rejects_already_submitted_attempt(): void
    {
        $session = ExamSession::factory()->create();
        $participant = Participant::factory()
            ->forSession($session)
            ->create();

        Question::factory()
            ->forModule($session->module)
            ->create([
                'is_active' => true,
            ]);

        ExamToken::factory()
            ->forSession($session)
            ->create([
                'is_active' => true,
                'expires_at' => now()->addHour(),
            ]);

        ExamAttempt::factory()
            ->forParticipant($participant)
            ->create([
                'is_submitted' => true,
                'finished_at' => now(),
                'started_at' => now()->subMinutes(5),
            ]);

        $token = ExamToken::query()->first()->token;

        $this->expectException(ValidationException::class);

        $this->service->startOrResume(
            $participant,
            $token,
            $this->makeRequest(),
        );
    }

    public function test_start_updates_token_last_used_at(): void
    {
        $session = ExamSession::factory()->create();
        $participant = Participant::factory()
            ->forSession($session)
            ->create();

        Question::factory()
            ->forModule($session->module)
            ->create([
                'is_active' => true,
            ]);

        $token = ExamToken::factory()
            ->forSession($session)
            ->create([
                'is_active' => true,
                'expires_at' => now()->addHour(),
                'last_used_at' => null,
            ]);

        $this->service->startOrResume(
            $participant,
            $token->token,
            $this->makeRequest(),
        );

        $this->assertNotNull(
            $token->fresh()->last_used_at
        );
    }

    public function test_start_rejects_onsite_session_without_coordinates(): void
    {
        // Setting lokasi dibuat supaya penolakan benar-benar berasal dari
        // logika koordinat (null → 0,0 → di luar radius), bukan dari cabang
        // "konfigurasi radius belum diatur".
        ExamLocationSetting::query()->create([
            'center_latitude' => -8.219,
            'center_longitude' => 114.369,
            'allowed_radius_meters' => 500,
        ]);
        $session = ExamSession::factory()->onsiteRadius()->create();
        $participant = Participant::factory()
            ->forSession($session)
            ->create();

        Question::factory()
            ->forModule($session->module)
            ->create([
                'is_active' => true,
            ]);

        ExamToken::factory()
            ->forSession($session)
            ->create([
                'is_active' => true,
                'expires_at' => now()->addHour(),
            ]);

        $token = ExamToken::query()->first()->token;

        $this->expectException(ValidationException::class);

        $this->service->startOrResume(
            $participant,
            $token,
            $this->makeRequest(),
        );
    }

    public function test_start_succeeds_for_onsite_session_with_valid_coordinates(): void
    {
        ExamLocationSetting::query()->create([
            'center_latitude' => -8.219,
            'center_longitude' => 114.369,
            'allowed_radius_meters' => 500,
        ]);
        $session = ExamSession::factory()->onsiteRadius()->create();
        $participant = Participant::factory()
            ->forSession($session)
            ->create();

        Question::factory()
            ->forModule($session->module)
            ->create([
                'is_active' => true,
            ]);

        ExamToken::factory()
            ->forSession($session)
            ->create([
                'is_active' => true,
                'expires_at' => now()->addHour(),
            ]);

        $token = ExamToken::query()->first()->token;

        // ~50 meter dari pusat, masih di dalam radius 500m.
        $attempt = $this->service->startOrResume(
            $participant,
            $token,
            $this->makeRequest(-8.2194, 114.369, 10),
        );

        $this->assertInstanceOf(ExamAttempt::class, $attempt);
        $this->assertSame($session->id, $attempt->session_id);
        $this->assertSame($participant->id, $attempt->participant_id);
        $this->assertFalse($attempt->is_submitted);
        $this->assertNotNull($attempt->session_token);
    }

    public function test_start_rejects_token_from_different_session(): void
    {
        $session = ExamSession::factory()->create();
        $participant = Participant::factory()
            ->forSession($session)
            ->create();

        $otherSession = ExamSession::factory()->create();

        Question::factory()
            ->forModule($session->module)
            ->create([
                'is_active' => true,
            ]);

        ExamToken::factory()
            ->forSession($otherSession)
            ->create([
                'is_active' => true,
                'expires_at' => now()->addHour(),
            ]);

        $token = ExamToken::query()->first()->token;

        $this->expectException(ValidationException::class);

        $this->service->startOrResume(
            $participant,
            $token,
            $this->makeRequest(),
        );
    }

    private function makeRequest(
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracy = null,
    ): Request {
        return Request::create(
            '/exam/start',
            'POST',
            array_filter([
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
            ], static fn ($value) => $value !== null),
        );
    }
}
