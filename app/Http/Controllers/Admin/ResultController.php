<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Support\Concerns\HandlesPagination;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ResultController extends Controller
{
    use HandlesPagination;

    public static function csvSafe(string $value): string
    {
        $value = trim($value);

        foreach (['=', '+', '-', '@', "\t", "\r"] as $prefix) {
            if ($value !== '' && str_starts_with($value, $prefix)) {
                return ' ' . $value;
            }
        }

        return $value;
    }

    public function index(Request $request): View
    {
        $query = ExamAttempt::query()
            ->with(['participant', 'session'])
            ->withCount('answers');

        $attempts = $this->paginateQuery(
            $query->latest('id'),
            $request
        );

        return view('admin.results', [
            'attempts' => $attempts,
        ]);
    }

    public function show(ExamAttempt $attempt): View
    {
        $attempt->load([
            'participant',
            'session',
            'answers' => fn ($query) => $query->with('question')->orderBy('question_id'),
        ]);

        $answersByQuestionId = $attempt->answers->keyBy('question_id');
        $moduleName = (string) ($attempt->session?->module?->name ?? 'Modul');

        $reviewQuestions = Question::query()
            ->where('module_id', $attempt->session->module_id)
            ->where('is_active', true)
            ->orderBy('order_no')
            ->get(['id', 'question', 'order_no'])
            ->map(function (Question $question) use ($answersByQuestionId, $moduleName): array {
                $answer = $answersByQuestionId->get($question->id);

                return [
                    'id' => (int) $question->id,
                    'category' => $moduleName,
                    'question' => ($answer?->question_snapshot !== null && $answer->question_snapshot !== '')
                        ? $answer->question_snapshot
                        : $question->question,
                    'order_no' => (int) $question->order_no,
                    'answer' => $answer?->answer,
                    'answered_at' => $answer?->answered_at?->format('d M Y H:i:s'),
                ];
            });

        $activeQuestionIds = $reviewQuestions->pluck('id');

        $archivedAnsweredQuestions = $attempt->answers
            ->filter(fn ($answer): bool => ! $activeQuestionIds->contains((int) $answer->question_id))
            ->values()
            ->map(fn ($answer, int $index): array => [
                'id' => (int) $answer->question_id,
                'category' => 'Arsip Soal',
                'question' => $answer->question_snapshot,
                'order_no' => $reviewQuestions->count() + $index + 1,
                'answer' => $answer->answer,
                'answered_at' => $answer->answered_at?->format('d M Y H:i:s'),
            ]);

        $reviewQuestions = $reviewQuestions->concat($archivedAnsweredQuestions)->values();
        $reviewAnswers = $reviewQuestions
            ->mapWithKeys(fn (array $question): array => [$question['id'] => $question['answer']])
            ->reject(fn ($answer): bool => $answer === null)
            ->all();
        $reviewAnsweredAt = $reviewQuestions
            ->mapWithKeys(fn (array $question): array => [$question['id'] => $question['answered_at']])
            ->reject(fn ($answeredAt): bool => $answeredAt === null)
            ->all();

        return view('admin.result-detail', [
            'attempt' => $attempt,
            'reviewQuestions' => $reviewQuestions,
            'reviewAnswers' => $reviewAnswers,
            'reviewAnsweredAt' => $reviewAnsweredAt,
        ]);
    }

    public function exportSummaryCsv(): Response
    {
        $filename = 'exam-results-summary-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM helps Excel open Indonesian text correctly.
            fwrite($handle, "\xEF\xBB\xBF");

            // Tells Excel to use ";" as the column separator no matter what
            // the user's Windows/Excel regional "list separator" setting is.
            // Without this, Excel may ignore our delimiter and dump every
            // row into a single column A.
            fwrite($handle, "sep=;\r\n");

            fputcsv($handle, [
                'ID Pengerjaan',
                'Nama Peserta',
                'Username Peserta',
                'NIK',
                'Judul Sesi',
                'Mulai',
                'Selesai',
                'Jumlah Jawaban',
                'Sudah Dikirim',
            ], ';');

            ExamAttempt::query()
                ->with(['participant', 'session'])
                ->withCount('answers')
                ->orderBy('id')
                ->chunk(200, static function ($attempts) use ($handle): void {
                    foreach ($attempts as $attempt) {
                        $nik = (string) ($attempt->participant->nik ?? '');

                        fputcsv($handle, [
                            $attempt->id,
                            self::csvSafe((string) ($attempt->participant->name ?? '')),
                            self::csvSafe((string) ($attempt->participant->username ?? '')),
                            // Force Excel to treat NIK as text so leading zeros
                            // and long digit strings aren't mangled/rounded.
                            $nik !== '' ? '="'.$nik.'"' : '',
                            self::csvSafe((string) ($attempt->session->title ?? '')),
                            self::csvSafe((string) $attempt->started_at),
                            self::csvSafe((string) ($attempt->finished_at ?? '')),
                            $attempt->answers_count,
                            self::csvSafe($attempt->is_submitted ? 'Ya' : 'Tidak'),
                        ], ';');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportAttemptCsv(ExamAttempt $attempt): Response
    {
        $attempt->load([
            'participant',
            'session',
            'answers' => fn ($query) => $query->with('question')->orderBy('question_id'),
        ]);

        $filename = 'attempt-'.$attempt->id.'-answers-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($attempt): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            // See exportSummaryCsv() for why this line is needed.
            fwrite($handle, "sep=;\r\n");

            fputcsv($handle, [
                'ID Pengerjaan',
                'Nama Peserta',
                'Judul Sesi',
                'ID Soal',
                'Urutan Soal',
                'Snapshot Soal',
                'Jawaban',
                'Waktu Menjawab',
            ], ';');

            foreach ($attempt->answers as $answer) {
                fputcsv($handle, [
                    $attempt->id,
                    self::csvSafe((string) ($attempt->participant->name ?? '')),
                    self::csvSafe((string) ($attempt->session->title ?? '')),
                    $answer->question_id,
                    $answer->question?->order_no,
                    self::csvSafe((string) ($answer->question_snapshot ?? '')),
                    self::csvSafe($answer->answer ? 'YA' : 'TIDAK'),
                    self::csvSafe((string) $answer->answered_at),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportSummaryExcel(): Response
    {
        $attempts = ExamAttempt::query()
            ->with(['participant', 'session'])
            ->withCount('answers')
            ->orderBy('id')
            ->get();

        $rows = $attempts->map(static fn (ExamAttempt $attempt): string => sprintf(
            '<tr>
                <td>%d</td>
                <td>%s</td>
                <td>%s</td>
                <td style="mso-number-format:\'\\@\';">%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%d</td>
                <td>%s</td>
            </tr>',
            $attempt->id,
            e($attempt->participant->name),
            e($attempt->participant->username),
            e($attempt->participant->nik),
            e($attempt->session->title),
            e((string) $attempt->started_at),
            e((string) ($attempt->finished_at ?? '')),
            $attempt->answers_count,
            $attempt->is_submitted ? 'Ya' : 'Tidak',
        ))->implode('');

        $html = <<<HTML
            <table border="1">
                <thead>
                    <tr>
                        <th>ID Pengerjaan</th>
                        <th>Nama Peserta</th>
                        <th>Username Peserta</th>
                        <th>NIK</th>
                        <th>Judul Sesi</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Jumlah Jawaban</th>
                        <th>Sudah Dikirim</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
            </table>
        HTML;

        $filename = 'exam-results-summary-'.now()->format('Ymd-His').'.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
