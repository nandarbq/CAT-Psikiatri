<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\ExamToken;
use Illuminate\Support\Facades\DB;

class SubmitExamService
{
    public function __construct(
        private readonly ValidateSessionService $validateSessionService
    ) {}

    public function submit(ExamAttempt $attempt): ExamAttempt
    {
        $attempt->loadMissing(['participant', 'session']);

        return DB::transaction(function () use ($attempt): ExamAttempt {
            /**
             * Lock attempt terlebih dahulu.
             *
             * Request submit kedua akan menunggu sampai request pertama
             * selesai melakukan perubahan terhadap attempt.
             */
            /** @var ExamAttempt $lockedAttempt */
            $lockedAttempt = ExamAttempt::query()
                ->with(['participant', 'session'])
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAttempt->is_submitted) {
                return $lockedAttempt;
            }
            $this->validateSessionService->ensureAttemptAccessible(
                $lockedAttempt
            );

            $lockedAttempt->forceFill([
                'is_submitted' => true,
                'finished_at' => now(),
            ])->save();

            $lockedAttempt->participant->forceFill([
                'is_finished' => true,
            ])->save();

            ExamToken::query()
                ->where('session_id', $lockedAttempt->session_id)
                ->where('is_active', true)
                ->update([
                    'last_used_at' => now(),
                ]);

            return $lockedAttempt->fresh([
                'participant',
                'session',
            ]);
        });
    }
}
