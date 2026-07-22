<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExamAttempt;
use App\Models\ExamLocationSetting;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Module;
use App\Models\Participant;
use App\Models\ParticipantAnswer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'username' => 'admin_test',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($admin);
    }

    private ?int $defaultModuleId = null;

    private function defaultModuleId(): int
    {
        if ($this->defaultModuleId !== null) {
            return $this->defaultModuleId;
        }

        $module = Module::query()->firstOrCreate(
            ['name' => 'Modul Uji Coba'],
            ['status' => true],
        );

        return $this->defaultModuleId = $module->id;
    }

    public function test_guest_is_redirected_to_admin_login_for_protected_routes(): void
    {
        $this->get('/admin/sessions')->assertRedirect(route('participant.login'));
    }

    public function test_admin_can_login_and_access_admin_pages(): void
    {
        User::query()->create([
            'name' => 'Admin',
            'username' => 'admin01',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password123'),
        ]);

        $this->post('/login', [
            'username' => 'admin01',
            'password' => 'password123',
        ])->assertRedirect(route('admin.results'));

        $this->get('/admin/results')->assertOk();
        $this->get('/admin/sessions')->assertOk();
        $this->get(route('admin.settings.index', ['tab' => 'location']))->assertOk();
        $this->get('/admin/participants')->assertOk();
        $this->get('/admin/tokens')->assertOk();
        $this->get('/admin/modules')->assertOk();
    }

    public function test_admin_can_export_summary_csv_and_excel(): void
    {
        $this->actingAsAdmin();

        $session = ExamSession::query()->create([
            'title' => 'Sesi Export',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => true,
        ]);

        $participant = Participant::query()->create([
            'session_id' => $session->id,
            'nik' => '987654321',
            'username' => 'exportuser',
            'password' => 'password123',
            'code' => 'EXPORT-01',
            'name' => 'Export User',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'is_finished' => false,
        ]);

        $question = Question::query()->create([
            'module_id' => $session->module_id,
            'question' => 'Q?',
            'order_no' => 1,
            'is_active' => true,
        ]);

        $attempt = ExamAttempt::query()->create([
            'participant_id' => $participant->id,
            'session_id' => $session->id,
            'started_at' => now()->subMinutes(10),
            'current_question' => 1,
            'is_submitted' => true,
            'session_token' => 'tokentest',
        ]);

        ParticipantAnswer::query()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_snapshot' => $question->question,
            'answer' => true,
            'answered_at' => now(),
        ]);

        $csv = $this->get(route('admin.results.export.csv'));
        $csv->assertOk();
        $this->assertStringContainsString('.csv', (string) $csv->headers->get('content-disposition'));

        $excel = $this->get(route('admin.results.export.excel'));
        $excel->assertOk();
        $this->assertStringContainsString('.xls', (string) $excel->headers->get('content-disposition'));
    }

    public function test_admin_can_update_global_location_setting(): void
    {
        $this->actingAsAdmin();

        $this->put(route('admin.settings.location.update'), [
            'center_latitude' => -6.2000000,
            'center_longitude' => 106.8100000,
            'allowed_radius_meters' => 750,
        ])->assertRedirect(route('admin.settings.index', ['tab' => 'location']));

        $this->assertDatabaseHas('exam_location_settings', [
            'center_latitude' => -6.2000000,
            'center_longitude' => 106.8100000,
            'allowed_radius_meters' => 750,
        ]);
    }

    public function test_admin_can_deactivate_session_with_attempt_history(): void
    {
        $this->actingAsAdmin();

        $session = ExamSession::query()->create([
            'title' => 'Sesi Ditutup',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHours(2),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => true,
            'access_mode' => ExamSession::ACCESS_MODE_ONLINE,
        ]);

        $participant = Participant::query()->create([
            'session_id' => $session->id,
            'nik' => '9900112233',
            'username' => 'status-lock-user',
            'password' => 'password123',
            'code' => 'STATUS-001',
            'name' => 'Status Lock User',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'is_finished' => true,
        ]);

        ExamAttempt::query()->create([
            'participant_id' => $participant->id,
            'session_id' => $session->id,
            'started_at' => now()->subMinutes(20),
            'current_question' => 120,
            'is_submitted' => true,
            'session_token' => 'status-lock-token',
            'submitted_at' => now()->subMinutes(5),
        ]);

        $response = $this->post(route('admin.sessions.status', $session), [
            'status' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', function (mixed $message): bool {
            return is_string($message) && str_contains($message, 'dinonaktifkan');
        });
        $this->assertDatabaseHas('exam_sessions', [
            'id' => $session->id,
            'status' => false,
        ]);
    }

    public function test_admin_can_reactivate_closed_session(): void
    {
        $this->actingAsAdmin();

        $session = ExamSession::query()->create([
            'title' => 'Sesi Dibuka Ulang',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHours(2),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => false,
            'access_mode' => ExamSession::ACCESS_MODE_ONLINE,
        ]);

        $response = $this->post(route('admin.sessions.status', $session), [
            'status' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', function (mixed $message): bool {
            return is_string($message) && str_contains($message, 'diaktifkan');
        });
        $this->assertDatabaseHas('exam_sessions', [
            'id' => $session->id,
            'status' => true,
        ]);
    }

    public function test_admin_can_bulk_move_existing_participants_to_session(): void
    {
        $this->actingAsAdmin();

        $sourceSession = ExamSession::query()->create([
            'title' => 'Sesi Sumber',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => true,
            'access_mode' => ExamSession::ACCESS_MODE_ONLINE,
        ]);
        $targetSession = ExamSession::query()->create([
            'title' => 'Sesi Tujuan',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(2),
            'duration_minutes' => 90,
            'status' => true,
            'access_mode' => ExamSession::ACCESS_MODE_ONLINE,
        ]);

        $participantA = Participant::query()->create([
            'session_id' => $sourceSession->id,
            'nik' => '1111000011110001',
            'username' => 'bulk-user-01',
            'password' => 'password123',
            'code' => 'BULK-001',
            'name' => 'User Satu',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'phone' => '081100001111',
            'address' => 'Jl. Satu',
            'is_finished' => false,
        ]);
        $participantB = Participant::query()->create([
            'session_id' => $sourceSession->id,
            'nik' => '1111000011110002',
            'username' => 'bulk-user-02',
            'password' => 'password123',
            'code' => 'BULK-002',
            'name' => 'User Dua',
            'birth_date' => '2001-02-02',
            'gender' => 'female',
            'phone' => '081100002222',
            'address' => 'Jl. Dua',
            'is_finished' => false,
        ]);

        $response = $this->post(route('admin.sessions.participants.bulk.store', $targetSession), [
            'participant_ids' => [$participantA->id, $participantB->id],
        ]);

        $response->assertRedirect(route('admin.sessions.participants.bulk.form', $targetSession));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('participants', [
            'id' => $participantA->id,
            'session_id' => $targetSession->id,
            'username' => 'bulk-user-01',
        ]);
        $this->assertDatabaseHas('participants', [
            'id' => $participantB->id,
            'session_id' => $targetSession->id,
            'username' => 'bulk-user-02',
        ]);
    }

    public function test_bulk_move_participants_skips_active_attempt(): void
    {
        $this->actingAsAdmin();

        $sourceSession = ExamSession::query()->create([
            'title' => 'Sesi Sumber Locked',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => true,
            'access_mode' => ExamSession::ACCESS_MODE_ONLINE,
        ]);
        $targetSession = ExamSession::query()->create([
            'title' => 'Sesi Tujuan Locked',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(2),
            'duration_minutes' => 90,
            'status' => true,
            'access_mode' => ExamSession::ACCESS_MODE_ONLINE,
        ]);

        $participant = Participant::query()->create([
            'session_id' => $sourceSession->id,
            'nik' => '2222000011110001',
            'username' => 'locked-bulk-user',
            'password' => 'password123',
            'code' => 'LOCK-BULK-001',
            'name' => 'Locked Bulk User',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'is_finished' => false,
        ]);

        ExamAttempt::query()->create([
            'participant_id' => $participant->id,
            'session_id' => $sourceSession->id,
            'started_at' => now()->subMinutes(5),
            'current_question' => 1,
            'is_submitted' => false,
            'session_token' => 'bulk-lock-token',
        ]);

        $response = $this->post(route('admin.sessions.participants.bulk.store', $targetSession), [
                'participant_ids' => [$participant->id],
            ]);

        $response->assertRedirect(route('admin.sessions.participants.bulk.form', $targetSession));
        $response->assertSessionHas('success', function (mixed $message): bool {
            return is_string($message) && str_contains($message, 'sedang mengerjakan ujian');
        });
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'session_id' => $sourceSession->id,
        ]);
    }

    public function test_participant_with_active_attempt_cannot_change_session(): void
    {
        $this->actingAsAdmin();

        $sessionA = ExamSession::query()->create([
            'title' => 'Sesi A',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => true,
        ]);
        $sessionB = ExamSession::query()->create([
            'title' => 'Sesi B',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(2),
            'duration_minutes' => 90,
            'status' => true,
        ]);

        $participant = Participant::query()->create([
            'session_id' => $sessionA->id,
            'nik' => '1122334455',
            'username' => 'lockeduser',
            'password' => 'password123',
            'code' => 'LOCK-01',
            'name' => 'Locked User',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'is_finished' => false,
        ]);

        ExamAttempt::query()->create([
            'participant_id' => $participant->id,
            'session_id' => $sessionA->id,
            'started_at' => now()->subMinutes(5),
            'current_question' => 1,
            'is_submitted' => false,
            'session_token' => 'token-lock',
        ]);

        $response = $this->put(route('admin.participants.update', $participant), [
            'session_id' => $sessionB->id,
            'nik' => $participant->nik,
            'username' => $participant->username,
            'password' => '',
            'code' => 'NEW-CODE',
            'name' => $participant->name,
            'birth_date' => $participant->birth_date?->format('Y-m-d'),
            'gender' => $participant->gender,
            'phone' => $participant->phone,
            'address' => $participant->address,
            'is_finished' => 0,
        ]);

        $response->assertSessionHasErrors('participant');
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'session_id' => $sessionA->id,
            'code' => 'LOCK-01',
        ]);
    }

    public function test_participant_with_submitted_attempt_can_move_to_new_session(): void
    {
        $this->actingAsAdmin();

        $sessionA = ExamSession::query()->create([
            'title' => 'Sesi Lama',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHours(3),
            'end_time' => now()->subHour(),
            'duration_minutes' => 60,
            'status' => true,
        ]);
        $sessionB = ExamSession::query()->create([
            'title' => 'Sesi Baru',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(2),
            'duration_minutes' => 90,
            'status' => true,
        ]);

        $participant = Participant::query()->create([
            'session_id' => $sessionA->id,
            'nik' => '1100220033',
            'username' => 'resession-user',
            'password' => 'password123',
            'code' => 'MOVE-01',
            'name' => 'Move User',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'is_finished' => true,
        ]);

        ExamAttempt::query()->create([
            'participant_id' => $participant->id,
            'session_id' => $sessionA->id,
            'started_at' => now()->subMinutes(30),
            'current_question' => 10,
            'is_submitted' => true,
            'session_token' => 'token-submitted',
            'submitted_at' => now()->subMinutes(5),
        ]);

        $response = $this->put(route('admin.participants.update', $participant), [
            'session_id' => $sessionB->id,
            'nik' => $participant->nik,
            'username' => $participant->username,
            'password' => '',
            'code' => $participant->code,
            'name' => $participant->name,
            'birth_date' => $participant->birth_date?->format('Y-m-d'),
            'gender' => $participant->gender,
            'phone' => $participant->phone,
            'address' => $participant->address,
            'is_finished' => 1,
        ]);

        $response->assertRedirect(route('admin.participants.index'));
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'session_id' => $sessionB->id,
        ]);
    }

    public function test_session_time_config_cannot_change_after_attempt_exists(): void
    {
        $this->actingAsAdmin();

        $session = ExamSession::query()->create([
            'title' => 'Locked Session',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => true,
        ]);

        $participant = Participant::query()->create([
            'session_id' => $session->id,
            'nik' => '6655443322',
            'username' => 'sessionuser',
            'password' => 'password123',
            'code' => 'SESSION-01',
            'name' => 'Session User',
            'birth_date' => '2000-01-01',
            'gender' => 'female',
            'is_finished' => false,
        ]);

        ExamAttempt::query()->create([
            'participant_id' => $participant->id,
            'session_id' => $session->id,
            'started_at' => now()->subMinutes(12),
            'current_question' => 1,
            'is_submitted' => false,
            'session_token' => 'token-session',
        ]);

        $newStart = now()->subHours(2)->format('Y-m-d H:i:s');
        $newEnd = now()->addHours(2)->format('Y-m-d H:i:s');

        $response = $this->put(route('admin.sessions.update', $session), [
            'title' => $session->title,
            'start_time' => $newStart,
            'end_time' => $newEnd,
            'duration_minutes' => 120,
            'status' => 1,
            'access_mode' => ExamSession::ACCESS_MODE_ONLINE,
        ]);

        $response->assertSessionHasErrors('session');
        $this->assertDatabaseHas('exam_sessions', [
            'id' => $session->id,
            'duration_minutes' => 60,
        ]);
    }

    public function test_admin_can_create_exam_token_with_generator_endpoint(): void
    {
        $this->actingAsAdmin();

        $session = ExamSession::query()->create([
            'title' => 'Token Session',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => true,
        ]);

        $participant = Participant::query()->create([
            'session_id' => $session->id,
            'nik' => '789789789',
            'username' => 'tokenuser',
            'password' => 'password123',
            'code' => 'LEGACY-TOKEN',
            'name' => 'Token User',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'is_finished' => false,
        ]);

        $generated = $this->postJson(route('admin.tokens.generate'))
            ->assertOk()
            ->assertJsonStructure(['token'])
            ->json('token');

        $this->assertIsString($generated);

        $this->post(route('admin.tokens.store'), [
            'session_id' => $session->id,
            'participant_id' => $participant->id,
            'token' => $generated,
            'is_active' => 1,
            'expires_at' => null,
        ])->assertRedirect(route('admin.tokens.index'));

        $this->assertDatabaseHas('exam_tokens', [
            'session_id' => $session->id,
            'token' => $generated,
            'is_active' => 1,
        ]);
    }

    public function test_token_creation_does_not_move_participant_between_sessions(): void
    {
        $this->actingAsAdmin();

        $sessionA = ExamSession::query()->create([
            'title' => 'Session A',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => true,
        ]);
        $sessionB = ExamSession::query()->create([
            'title' => 'Session B',
            'module_id' => $this->defaultModuleId(),
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'status' => true,
        ]);

        $participant = Participant::query()->create([
            'session_id' => $sessionA->id,
            'nik' => '4545454545',
            'username' => 'mismatch',
            'password' => 'password123',
            'code' => 'LEGACY-MISMATCH',
            'name' => 'Mismatch User',
            'birth_date' => '2000-01-01',
            'gender' => 'female',
            'is_finished' => false,
        ]);

        $response = $this->post(route('admin.tokens.store'), [
            'session_id' => $sessionB->id,
            'participant_id' => $participant->id,
            'token' => '123456',
            'is_active' => 1,
            'expires_at' => null,
        ]);

        $response->assertRedirect(route('admin.tokens.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'session_id' => $sessionA->id,
        ]);
        $this->assertDatabaseHas('exam_tokens', [
            'token' => '123456',
            'session_id' => $sessionB->id,
        ]);
    }
}
