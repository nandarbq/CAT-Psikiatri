<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class SaveAnswerService
{
    public function __construct(private readonly ValidateSessionService $validateSessionService) {}

    public function save(ExamAttempt $attempt, Question $question, bool $answer): void
    {
        $attemptWithSession = $attempt->relationLoaded('session') ? $attempt : $attempt->load('session');
        $this->validateSessionService->ensureAttemptAccessible($attemptWithSession);

        if (
            (int) $question->module_id !== (int) $attemptWithSession->session->module_id
            || ! $question->is_active
        ) {
            abort(404, 'Soal tidak tersedia untuk sesi ujian ini.');
        }

        DB::transaction(function () use ($attempt, $question, $answer): void {
            $now = now();

            DB::table('participant_answers')->upsert(
                [[
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'question_snapshot' => $question->question,
                    'answer' => $answer,
                    'answered_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['attempt_id', 'question_id'],
                ['question_snapshot', 'answer', 'answered_at', 'updated_at'],
            );

            if ((int) $question->order_no > (int) $attempt->current_question) {
                $attempt->forceFill([
                    'current_question' => $question->order_no,
                ])->save();
            }
        });
    }
}
