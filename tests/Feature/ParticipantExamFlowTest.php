<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExamLocationSetting;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Module;
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
        $module = Module::query()->create([
            'name' => 'Modul General',
            'status' => true,
        ]);

        $session = ExamSession::query()->create([
            'title' => 'Session Test',
            'module_id' => $module->id,
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
            'module_id' => $module->id,
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

    public function test_participant_cannot_save_answer_for_question_from_other_module(): void
    {
        $allowedModule = Module::query()->create([
            'name' => 'Modul Diperbolehkan',
            'status' => true,
        ]);

        $blockedModule = Module::query()->create([
            'name' => 'Modul Dilarang',
            'status' => true,
        ]);

        $session = ExamSession::query()->create([
            'title' => 'Session Modul Terbatas',
            'module_id' => $allowedModule->id,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => true,
        ]);

        $participant = Participant::query()->create([
            'session_id' => $session->id,
            'nik' => '1234567891',
            'username' => 'modultester',
            'password' => 'password123',
            'code' => '123457',
            'name' => 'Modul Tester',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'is_finished' => false,
        ]);

        ExamToken::query()->create([
            'session_id' => $session->id,
            'participant_id' => $participant->id,
            'token' => '123457',
            'is_active' => true,
        ]);

        Question::query()->create([
            'module_id' => $allowedModule->id,
            'question' => 'Pertanyaan yang diperbolehkan',
            'order_no' => 1,
            'is_active' => true,
        ]);

        $blockedQuestion = Question::query()->create([
            'module_id' => $blockedModule->id,
            'question' => 'Pertanyaan dari modul lain',
            'order_no' => 1,
            'is_active' => true,
        ]);

        $this->post('/login', [
            'username' => 'modultester',
            'password' => 'password123',
        ])->assertRedirect('/dashboard');

        $this->post('/exam/access', [
            'token' => '123457',
        ])->assertRedirect('/exam');

        $this->postJson('/exam/save-answer', [
            'question_id' => $blockedQuestion->id,
            'answer' => true,
        ])->assertStatus(404)
            ->assertJsonPath('message', 'Soal tidak tersedia untuk sesi tes ini.');

        $this->assertDatabaseMissing('participant_answers', [
            'question_id' => $blockedQuestion->id,
        ]);
    }

    public function test_login_endpoint_is_rate_limited_after_repeated_attempts(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')
                ->post('/login', [
                    'username' => 'unknown',
                    'password' => 'wrong-password',
                ])
                ->assertStatus(302);
        }

        $this->from('/login')
            ->post('/login', [
                'username' => 'unknown',
                'password' => 'wrong-password',
            ])
            ->assertStatus(429);
    }

    public function test_onsite_radius_session_cannot_be_started_from_outside_rsud_radius(): void
    {
        ExamLocationSetting::query()->create([
            'center_latitude' => -6.2015000,
            'center_longitude' => 106.8166667,
            'allowed_radius_meters' => 500,
        ]);

        $module = Module::query()->create([
            'name' => 'Modul Radius',
            'status' => true,
        ]);

        Question::query()->create([
            'module_id' => $module->id,
            'question' => 'Pertanyaan radius?',
            'order_no' => 1,
            'is_active' => true,
        ]);

        $session = ExamSession::query()->create([
            'title' => 'Session Radius RSUD',
            'module_id' => $module->id,
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

        $module = Module::query()->create([
            'name' => 'Modul Radius Guard',
            'status' => true,
        ]);

        $session = ExamSession::query()->create([
            'title' => 'Session Radius Guard',
            'module_id' => $module->id,
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
            'module_id' => $module->id,
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
