<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\ExamLocationSetting;
use App\Models\ExamToken;
use App\Models\Participant;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class ParticipantExamFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_login_start_exam_autosave_and_submit(): void
    {
        $session = ExamSession::query()->create([
            'title' => 'Session Test',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => true,
        ]);

        $participant = Participant::query()->create([
            'session_id' => $session->id,
            'nik' => '1234567890',
            'username' => 'tester',
            'password' => 'password123',
            'code' => '123456',
            'name' => 'Tester',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'is_finished' => false,
        ]);

        ExamToken::query()->create([
            'session_id' => $session->id,
            'participant_id' => $participant->id,
            'token' => '123456',
            'is_active' => true,
        ]);

        $question = Question::query()->create([
            'category' => 'General',
            'question' => 'Pertanyaan tes?',
            'order_no' => 1,
            'is_active' => true,
        ]);

        $this->post('/login', [
            'username' => 'tester',
            'password' => 'password123',
        ])->assertRedirect('/dashboard');

        $this->post('/exam/access', [
            'token' => '123456',
        ])->assertRedirect('/exam');

        $this->postJson('/exam/save-answer', [
            'question_id' => $question->id,
            'answer' => true,
        ])->assertOk()->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('participant_answers', [
            'question_id' => $question->id,
            'answer' => 1,
        ]);

        $this->postJson('/exam/submit')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'redirect' => route('exam.complete'),
            ]);

        $this->assertDatabaseHas('exam_attempts', [
            'participant_id' => $participant->id,
            'is_submitted' => 1,
        ]);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'is_finished' => 1,
        ]);
    }

    public function test_onsite_radius_session_cannot_be_started_from_outside_rsud_radius(): void
    {
        ExamLocationSetting::query()->create([
            'center_latitude' => -6.2015000,
            'center_longitude' => 106.8166667,
            'allowed_radius_meters' => 500,
        ]);

        $session = ExamSession::query()->create([
            'title' => 'Session Radius RSUD',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => true,
            'access_mode' => ExamSession::ACCESS_MODE_ONSITE_RADIUS,
        ]);

        $participant = Participant::query()->create([
            'session_id' => $session->id,
            'nik' => '8889990001',
            'username' => 'lokaltester',
            'password' => 'password123',
            'code' => '234567',
            'name' => 'Local Tester',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'is_finished' => false,
        ]);

        ExamToken::query()->create([
            'session_id' => $session->id,
            'participant_id' => $participant->id,
            'token' => '234567',
            'is_active' => true,
        ]);

        $this->post('/login', [
            'username' => 'lokaltester',
            'password' => 'password123',
        ])->assertRedirect('/dashboard');

        $this->post('/exam/access', [
            'token' => '234567',
            'latitude' => -6.2500000,
            'longitude' => 106.9000000,
            'accuracy' => 15,
        ])->assertSessionHasErrors('session');

        $this->assertDatabaseMissing('exam_attempts', [
            'participant_id' => $participant->id,
        ]);
    }

    public function test_onsite_radius_session_denies_exam_requests_when_location_moves_outside_radius(): void
    {
        ExamLocationSetting::query()->create([
            'center_latitude' => -6.2015000,
            'center_longitude' => 106.8166667,
            'allowed_radius_meters' => 500,
        ]);

        $session = ExamSession::query()->create([
            'title' => 'Session Radius Guard',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => true,
            'access_mode' => ExamSession::ACCESS_MODE_ONSITE_RADIUS,
        ]);

        $participant = Participant::query()->create([
            'session_id' => $session->id,
            'nik' => '8889990002',
            'username' => 'guardtester',
            'password' => 'password123',
            'code' => '345678',
            'name' => 'Guard Tester',
            'birth_date' => '2000-01-01',
            'gender' => 'female',
            'is_finished' => false,
        ]);

        ExamToken::query()->create([
            'session_id' => $session->id,
            'participant_id' => $participant->id,
            'token' => '345678',
            'is_active' => true,
        ]);

        $question = Question::query()->create([
            'category' => 'General',
            'question' => 'Pertanyaan guard?',
            'order_no' => 1,
            'is_active' => true,
        ]);

        $this->post('/login', [
            'username' => 'guardtester',
            'password' => 'password123',
        ])->assertRedirect('/dashboard');

        $this->post('/exam/access', [
            'token' => '345678',
            'latitude' => -6.2016000,
            'longitude' => 106.8167000,
            'accuracy' => 20,
        ])->assertRedirect('/exam');

        $this->withHeaders([
            'X-Exam-Latitude' => '-6.2500000',
            'X-Exam-Longitude' => '106.9000000',
            'X-Exam-Accuracy' => '10',
        ])->postJson('/exam/save-answer', [
            'question_id' => $question->id,
            'answer' => true,
        ])->assertStatus(403)->assertJson([
            'success' => false,
            'message' => 'Sesi ini hanya bisa diakses dalam radius lokasi RSUD.',
        ]);
    }
}
