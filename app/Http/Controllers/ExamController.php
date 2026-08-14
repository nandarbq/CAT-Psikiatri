<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SaveAnswerRequest;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Services\SaveAnswerService;
use App\Services\SubmitExamService;
use App\Services\ValidateSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function show(
        Request $request,
        ValidateSessionService $validateSessionService
    ): View|RedirectResponse {
        /** @var ExamAttempt|null $attempt */
        $attempt = $request->attributes->get('examAttempt');

        if ($attempt === null) {
            return redirect()->route('exam.access');
        }

        $moduleName = (string) (
            $attempt->session?->module?->name ?? 'Modul'
        );

        $questions = Question::query()
            ->where('module_id', $attempt->session->module_id)
            ->where('is_active', true)
            ->orderBy('order_no')
            ->get([
                'id',
                'question',
                'order_no',
            ])
            ->map(fn (Question $question): array => [
                'id' => (int) $question->id,
                'category' => $moduleName,
                'question' => $question->question,
                'order_no' => (int) $question->order_no,
            ])
            ->all();

        $randomizer = new \Random\Randomizer(
            new \Random\Engine\Mt19937($attempt->id)
        );

        $questions = collect(
            $randomizer->shuffleArray($questions)
        );

        $answers = $attempt->answers()
            ->get([
                'question_id',
                'answer',
            ])
            ->mapWithKeys(
                fn ($item): array => [
                    (int) $item->question_id => (bool) $item->answer,
                ]
            )
            ->all();

        if ($answers === []) {
            $currentIndex = 0;
        } else {
            $foundIndex = $questions->search(
                fn (array $question): bool =>
                    $question['order_no'] === $attempt->current_question
            );

            if ($foundIndex !== false) {
                $currentIndex = $foundIndex;
            } else {

                $answeredQuestionIds = array_map(
                    'intval',
                    array_keys($answers)
                );

                $answeredOrderNos = collect($answeredQuestionIds)
                    ->map(
                        fn (int $qid) =>
                            $questions->firstWhere(
                                'id',
                                $qid
                            )['order_no'] ?? null
                    )
                    ->filter()
                    ->map(fn ($orderNo): int => (int) $orderNo)
                    ->all();

                if ($answeredOrderNos === []) {
                    $currentIndex = 0;
                } else {
                    $maxOrder = max($answeredOrderNos);

                    $foundIndex = $questions->search(
                        fn (array $question): bool =>
                            $question['order_no'] === $maxOrder
                    );

                    $currentIndex = $foundIndex === false
                        ? 0
                        : $foundIndex;
                }
            }
        }

        return view('exam.index', [
            'attempt' => $attempt,
            'questions' => $questions,
            'answers' => $answers,
            'currentIndex' => $currentIndex,
            'deadlineAt' => $validateSessionService
                ->effectiveDeadline($attempt)
                ->toIso8601String(),
            'requiresLocation' => $attempt->session->isOnsiteRadiusOnly(),
        ]);
    }

    public function saveAnswer(
        SaveAnswerRequest $request,
        SaveAnswerService $saveAnswerService
    ): JsonResponse {
        $requestStartedAt = hrtime(true);

        /** @var ExamAttempt $attempt */
        $attempt = $request->attributes->get('examAttempt');

        $validated = $request->validated();

        $questionLookupStartedAt = hrtime(true);

        $question = $this->resolveQuestionForAttempt(
            $attempt,
            (int) $validated['question_id']
        );

        $questionLookupDurationMs = $this->durationMs(
            $questionLookupStartedAt
        );

        $saveStartedAt = hrtime(true);

        $saveAnswerService->save(
            $attempt,
            $question,
            (bool) $validated['answer']
        );

        $saveDurationMs = $this->durationMs(
            $saveStartedAt
        );

        $totalDurationMs = $this->durationMs(
            $requestStartedAt
        );

        if ($totalDurationMs >= 500) {
            Log::warning(
                'Slow save-answer request detected.',
                [
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'question_lookup_ms' => $questionLookupDurationMs,
                    'save_ms' => $saveDurationMs,
                    'total_ms' => $totalDurationMs,
                ]
            );
        }

        $responsePayload = [
            'success' => true,
            'message' => 'Jawaban tersimpan.',
        ];

        if (config('app.debug')) {
            $responsePayload['meta'] = [
                'question_lookup_ms' => $questionLookupDurationMs,
                'save_ms' => $saveDurationMs,
                'total_ms' => $totalDurationMs,
            ];
        }

        $response = response()->json(
            $responsePayload
        );

        $response->headers->set(
            'X-Save-Answer-Duration-Ms',
            number_format(
                $totalDurationMs,
                2,
                '.',
                ''
            )
        );

        $response->headers->set(
            'Server-Timing',
            implode(', ', [
                'question_lookup;dur=' .
                    number_format(
                        $questionLookupDurationMs,
                        2,
                        '.',
                        ''
                    ),

                'save_answer;dur=' .
                    number_format(
                        $saveDurationMs,
                        2,
                        '.',
                        ''
                    ),

                'total;dur=' .
                    number_format(
                        $totalDurationMs,
                        2,
                        '.',
                        ''
                    ),
            ])
        );

        return $response;
    }

    public function submit(
        Request $request,
        SubmitExamService $submitExamService
    ): JsonResponse {
        /** @var ExamAttempt $attempt */
        $attempt = $request->attributes->get('examAttempt');

        $submitExamService->submit($attempt);

        $request->session()->forget([
            'exam_attempt_id',
            'exam_session_token',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tes berhasil disubmit.',
            'redirect' => route('exam.complete'),
        ]);
    }

    public function complete(
        Request $request
    ): View|RedirectResponse {
        $participantId = (int) $request->session()->get(
            'participant_id',
            0
        );

        if ($participantId === 0) {
            return redirect()->route(
                'participant.login'
            );
        }

        return view('exam.complete');
    }

    private function resolveQuestionForAttempt(
        ExamAttempt $attempt,
        int $questionId
    ): Question {
        $question = Question::query()
            ->select([
                'id',
                'question',
                'order_no',
                'module_id',
                'is_active',
            ])
            ->where(
                'module_id',
                $attempt->session->module_id
            )
            ->where(
                'is_active',
                true
            )
            ->whereKey($questionId)
            ->first();

        if ($question === null) {
            abort(
                404,
                'Soal tidak tersedia untuk sesi ujian ini.'
            );
        }

        return $question;
    }

    private function durationMs(
        int $startAt
    ): float {
        return (
            hrtime(true) - $startAt
        ) / 1_000_000;
    }
}
