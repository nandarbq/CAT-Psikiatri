<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\ExamLocationSetting;
use App\Models\ExamSession;
use App\Support\GeoDistance;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class ValidateSessionService
{
    public function ensureSessionActive(ExamSession $session): void
    {
        $now = CarbonImmutable::now();

        if (! $session->status) {
            throw ValidationException::withMessages([
                'session' => 'Sesi tes saat ini tidak aktif.',
            ]);
        }

        if (
            $now->lt(
                CarbonImmutable::instance($session->start_time)
            )
        ) {
            throw ValidationException::withMessages([
                'session' => 'Sesi tes belum dimulai.',
            ]);
        }

        if (
            $now->gt(
                CarbonImmutable::instance($session->end_time)
            )
        ) {
            throw ValidationException::withMessages([
                'session' => 'Sesi tes sudah berakhir.',
            ]);
        }
    }

    public function ensureSessionLocationAccess(
        ExamSession $session,
        ?float $latitude,
        ?float $longitude,
        ?float $accuracyMeters = null
    ): void {

        if (! $session->isOnsiteRadiusOnly()) {
            return;
        }

        $maxAccuracyMeters = (float) config(
            'exam.max_accuracy_meters',
            150
        );

        if (
            is_numeric($accuracyMeters) &&
            (float) $accuracyMeters > $maxAccuracyMeters
        ) {
            throw ValidationException::withMessages([
                'session' => 'Sinyal lokasi perangkat kurang akurat '.
                    'untuk verifikasi radius RSUD. '.
                    'Coba pindah ke area dengan sinyal GPS '.
                    'yang lebih baik.',
            ]);
        }

        $setting = ExamLocationSetting::query()->first();

        if ($setting === null) {
            throw ValidationException::withMessages([
                'session' => 'Konfigurasi radius lokasi RSUD belum diatur. '.
                    'Hubungi admin.',
            ]);
        }

        $distanceMeters = GeoDistance::metersBetween(
            (float) $latitude,
            (float) $longitude,
            (float) $setting->center_latitude,
            (float) $setting->center_longitude,
        );

        if (
            $distanceMeters >
            (float) $setting->allowed_radius_meters
        ) {
            throw ValidationException::withMessages([
                'session' => 'Sesi ini hanya bisa diakses dalam '.
                    'radius lokasi RSUD.',
            ]);
        }
    }

    public function ensureAttemptAccessible(
        ExamAttempt $attempt
    ): void {
        if ($attempt->is_submitted) {
            throw ValidationException::withMessages([
                'attempt' => 'Tes sudah disubmit dan terkunci.',
            ]);
        }

        $this->ensureSessionActive($attempt->session);

        /*
         * M5 — Submit diizinkan meski melewati deadline.
         * Pengecekan deadline dihapus agar submit yang telat tetap
         * diterima server. Ini menghindari loop popup error di client
         * (timer terus trigger submit → server reject → loop popup).
         */
    }

    public function effectiveDeadline(
        ExamAttempt $attempt
    ): CarbonImmutable {
        $durationMinutes =
            $this->resolveDurationMinutes($attempt);

        $deadlineByDuration =
            CarbonImmutable::instance($attempt->started_at)
                ->addMinutes($durationMinutes);

        $sessionEnd =
            CarbonImmutable::instance(
                $attempt->session->end_time
            );

        return $deadlineByDuration->min($sessionEnd);
    }

    private function resolveDurationMinutes(
        ExamAttempt $attempt
    ): int {
        $rawDuration =
            $attempt->session->duration_minutes;

        if (
            ! is_numeric($rawDuration) ||
            (float) $rawDuration <= 0
        ) {
            throw ValidationException::withMessages([
                'session' => 'Durasi sesi tes tidak valid. '.
                    'Hubungi admin.',
            ]);
        }

        return max(1, (int) $rawDuration);
    }
}
