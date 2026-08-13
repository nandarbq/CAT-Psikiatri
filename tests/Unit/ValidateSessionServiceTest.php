<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\ExamAttempt;
use App\Models\ExamLocationSetting;
use App\Models\ExamSession;
use App\Models\Participant;
use App\Services\ValidateSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class ValidateSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ValidateSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ValidateSessionService::class);
    }

    // --- ensureSessionActive ---

    public function test_ensure_session_active_passes_for_ongoing_session(): void
    {
        $session = ExamSession::factory()->create([
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'status' => true,
        ]);

        $this->service->ensureSessionActive($session);

        $this->assertTrue(true);
    }

    public function test_ensure_session_active_rejects_inactive_session(): void
    {
        $session = ExamSession::factory()->create([
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'status' => false,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->ensureSessionActive($session);
    }

    public function test_ensure_session_active_rejects_session_not_started_yet(): void
    {
        $session = ExamSession::factory()->notStartedYet()->create();

        $this->expectException(ValidationException::class);

        $this->service->ensureSessionActive($session);
    }

    public function test_ensure_session_active_rejects_ended_session(): void
    {
        $session = ExamSession::factory()->ended()->create();

        $this->expectException(ValidationException::class);

        $this->service->ensureSessionActive($session);
    }

    // --- ensureSessionLocationAccess ---

    public function test_location_check_is_skipped_for_online_session(): void
    {
        $session = ExamSession::factory()->create([
            'access_mode' => ExamSession::ACCESS_MODE_ONLINE,
        ]);

        // Tidak kirim lokasi sama sekali -- tetap harus lolos, karena sesi
        // online tidak butuh verifikasi lokasi sama sekali.
        $this->service->ensureSessionLocationAccess($session, null, null, null);

        $this->assertTrue(true);
    }

    public function test_location_check_rejects_missing_coordinates_for_onsite_session(): void
    {
        $session = ExamSession::factory()->onsiteRadius()->create();

        $this->expectException(ValidationException::class);

        $this->service->ensureSessionLocationAccess($session, null, null, null);
    }

    public function test_location_check_rejects_when_no_location_setting_configured(): void
    {
        $session = ExamSession::factory()->onsiteRadius()->create();

        $this->expectException(ValidationException::class);

        $this->service->ensureSessionLocationAccess($session, -8.219, 114.369, 10);
    }

    public function test_location_check_passes_within_radius(): void
    {
        ExamLocationSetting::query()->create([
            'center_latitude' => -8.219,
            'center_longitude' => 114.369,
            'allowed_radius_meters' => 500,
        ]);
        $session = ExamSession::factory()->onsiteRadius()->create();

        // ~50 meter dari titik pusat, masih di dalam radius 500m.
        $this->service->ensureSessionLocationAccess($session, -8.2194, 114.369, 10);

        $this->assertTrue(true);
    }

    public function test_location_check_rejects_outside_radius(): void
    {
        ExamLocationSetting::query()->create([
            'center_latitude' => -8.219,
            'center_longitude' => 114.369,
            'allowed_radius_meters' => 500,
        ]);
        $session = ExamSession::factory()->onsiteRadius()->create();

        // ~5.5 km dari titik pusat (0.05 derajat lintang ~= 5.5 km).
        $this->expectException(ValidationException::class);

        $this->service->ensureSessionLocationAccess($session, -8.269, 114.369, 10);
    }

    public function test_h7_large_accuracy_no_longer_shrinks_effective_distance(): void
    {
        // Regression test untuk H7: sebelum diperbaiki, klien tinggal
        // kirim accuracy sangat besar (mis. 50 km) supaya jarak efektif
        // jadi 0 dan lolos verifikasi radius dari lokasi mana pun. Sekarang
        // accuracy besar justru langsung ditolak sebagai quality gate --
        // bukan dipakai mengurangi jarak yang dibandingkan ke radius.
        ExamLocationSetting::query()->create([
            'center_latitude' => -8.219,
            'center_longitude' => 114.369,
            'allowed_radius_meters' => 500,
        ]);
        $session = ExamSession::factory()->onsiteRadius()->create();

        $this->expectException(ValidationException::class);

        // Titik ~5.5km di luar radius, dikirim bersama accuracy 50km
        // seolah mau "menutupi" jaraknya seperti perilaku bug lama.
        $this->service->ensureSessionLocationAccess($session, -8.269, 114.369, 50_000);
    }

    public function test_location_check_rejects_poor_accuracy_even_when_within_radius(): void
    {
        ExamLocationSetting::query()->create([
            'center_latitude' => -8.219,
            'center_longitude' => 114.369,
            'allowed_radius_meters' => 500,
        ]);
        $session = ExamSession::factory()->onsiteRadius()->create();

        $this->expectException(ValidationException::class);

        // Titik-nya sebenarnya persis di pusat (jarak 0), tapi accuracy
        // GPS-nya buruk (200m, di atas ambang default 150m) -- harus tetap
        // ditolak karena pembacaannya tidak bisa dipercaya.
        $this->service->ensureSessionLocationAccess($session, -8.219, 114.369, 200);
    }

    // --- ensureAttemptAccessible ---

    public function test_ensure_attempt_accessible_passes_for_valid_attempt(): void
    {
        $participant = Participant::factory()->create();
        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->create(['started_at' => now()->subMinutes(5)]);

        $this->service->ensureAttemptAccessible($attempt->load('session'));

        $this->assertTrue(true);
    }

    public function test_ensure_attempt_accessible_rejects_already_submitted_attempt(): void
    {
        $participant = Participant::factory()->create();
        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->submitted()
            ->create();

        $this->expectException(ValidationException::class);

        $this->service->ensureAttemptAccessible($attempt->load('session'));
    }

    public function test_ensure_attempt_accessible_rejects_when_duration_exceeded(): void
    {
        $session = ExamSession::factory()->create([
            'duration_minutes' => 30,
            'start_time' => now()->subHours(2),
            'end_time' => now()->addHours(2),
        ]);
        $participant = Participant::factory()->forSession($session)->create();
        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->create(['started_at' => now()->subMinutes(45)]); // melebihi durasi 30 menit

        $this->expectException(ValidationException::class);

        $this->service->ensureAttemptAccessible($attempt->load('session'));
    }

    public function test_effective_deadline_uses_the_earlier_of_duration_or_session_end(): void
    {
        $session = ExamSession::factory()->create([
            'duration_minutes' => 120,
            'start_time' => now()->subHour(),
            'end_time' => now()->addMinutes(10), // sesi berakhir lebih cepat dari durasi tes
        ]);
        $participant = Participant::factory()->forSession($session)->create();
        $attempt = ExamAttempt::factory()
            ->forParticipant($participant)
            ->create(['started_at' => now()->subMinutes(5)]);

        $deadline = $this->service->effectiveDeadline($attempt->load('session'));

        // Deadline seharusnya mengikuti waktu selesai sesi (lebih ketat),
        // bukan started_at + 120 menit.
        $this->assertTrue($deadline->lessThan($attempt->started_at->addMinutes(120)));
        $this->assertEqualsWithDelta(
            $session->end_time->timestamp,
            $deadline->timestamp,
            1
        );
    }
}
