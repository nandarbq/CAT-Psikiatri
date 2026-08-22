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
        return DB::transaction(function () use ($attempt): ExamAttempt {
            /**
             * Lock attempt terlebih dahulu.
             *
             * Request submit kedua akan menunggu sampai request pertama
             * selesai melakukan perubahan terhadap attempt.
             */
            /** @var ExamAttempt $lockedAttempt */
            $lockedAttempt = ExamAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAttempt->loadMissing(['participant', 'session']);

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

            $lockedAttempt->participant()->update([
                'is_finished' => true,
            ]);

            ExamToken::query()
                ->where('session_id', $lockedAttempt->session_id)
                ->where('is_active', true)
                ->update([
                    'last_used_at' => now(),
                ]);

            return $lockedAttempt->loadMissing(['participant', 'session']);
        });
    }
}
