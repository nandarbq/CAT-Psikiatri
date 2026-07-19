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
            if ($attempt->is_submitted) {
                return $attempt;
            }

            $attempt->forceFill([
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

            return $attempt;
        });
    }
}
