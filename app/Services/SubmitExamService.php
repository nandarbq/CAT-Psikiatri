<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\ExamToken;
use Illuminate\Support\Facades\DB;

class SubmitExamService
{
    public function __construct(private readonly ValidateSessionService $validateSessionService)
    {
    }

    public function submit(ExamAttempt $attempt): ExamAttempt
    {
        $attempt->loadMissing(['participant', 'session']);
        $this->validateSessionService->ensureAttemptAccessible($attempt);

        return DB::transaction(function () use ($attempt): ExamAttempt {
            // Kunci baris attempt ini di level database (SELECT ... FOR UPDATE).
            // Kalau ada request submit lain untuk attempt yang sama datang
            // hampir bersamaan (double-klik, retry jaringan), request kedua
            // akan menunggu di sini sampai transaction pertama selesai,
            // lalu membaca status is_submitted yang sudah ter-update —
            // bukan lagi membaca nilai lama yang belum tersinkron.
            /** @var ExamAttempt $lockedAttempt */
            $lockedAttempt = ExamAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAttempt->is_submitted) {
                // Sudah disubmit duluan oleh request lain yang menang race.
                // Jangan ulangi efek samping (update token, dsb) — cukup
                // sinkronkan instance yang dipegang caller lalu kembalikan.
                $attempt->forceFill([
                    'is_submitted' => true,
                    'finished_at' => $lockedAttempt->finished_at,
                ]);

                return $attempt;
            }

            $lockedAttempt->forceFill([
                'is_submitted' => true,
                'finished_at' => now(),
            ])->save();

            $attempt->participant->forceFill([
                'is_finished' => true,
            ])->save();

            ExamToken::query()
                ->where('session_id', $attempt->session_id)
                ->where('is_active', true)
                ->update([
                    'last_used_at' => now(),
                ]);

            $attempt->forceFill([
                'is_submitted' => true,
                'finished_at' => $lockedAttempt->finished_at,
            ]);

            return $attempt;
        });
    }
}
