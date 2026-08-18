<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Question;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamSimulationController extends Controller
{
    private const SESSION_KEY = 'admin_exam_simulation';

    public function index(Request $request): View
    {
        return view('admin.simulation.index', [
            'simulation' => $this->readSimulation($request),
            'modules' => Module::query()
                ->withCount('activeQuestions')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:300'],
        ]);

        $module = Module::query()->findOrFail((int) $payload['module_id']);

        $questions = Question::query()
            ->where('module_id', $module->id)
            ->where('is_active', true)
            ->orderBy('order_no')
            ->get(['id', 'question', 'order_no'])
            ->map(fn (Question $question): array => [
                'id' => (int) $question->id,
                'category' => $module->name,
                'question' => $question->question,
                'order_no' => (int) $question->order_no,
            ])
            ->values()
            ->all();

        if ($questions === []) {
            return back()->withErrors([
                'module_id' => 'Modul yang dipilih belum memiliki soal aktif. Tambahkan soal terlebih dahulu.',
            ]);
        }

        $durationMinutes = (int) $payload['duration_minutes'];
        $startedAt = CarbonImmutable::now();
        $deadlineAt = $startedAt->addMinutes($durationMinutes);

        $simulation = [
            'id' => (string) Str::uuid(),
            'module_id' => $module->id,
            'module_name' => $module->name,
            'started_at' => $startedAt->toIso8601String(),
            'deadline_at' => $deadlineAt->toIso8601String(),
            'duration_minutes' => $durationMinutes,
            'question_count' => count($questions),
            'questions' => $questions,
            'answers' => [],
            'current_index' => 0,
            'is_submitted' => false,
            'submitted_at' => null,
        ];

        $request->session()->put(self::SESSION_KEY, $simulation);

        return redirect()
            ->route('admin.simulation.run')
            ->with('success', "Simulasi baru dimulai: modul {$module->name}, ".count($questions)." soal, {$durationMinutes} menit.");
    }

    public function run(Request $request): View|RedirectResponse
    {
        $simulation = $this->readSimulation($request);
        if ($simulation === null) {
            return redirect()
                ->route('admin.simulation.index')
                ->withErrors(['simulation' => 'Belum ada simulasi aktif. Pilih modul terlebih dahulu.']);
        }

        if ((bool) ($simulation['is_submitted'] ?? false) === true) {
            return redirect()->route('admin.simulation.result');
        }

        $questions = is_array($simulation['questions'] ?? null) ? $simulation['questions'] : [];
        $maxIndex = max(0, count($questions) - 1);
        $currentIndex = (int) ($simulation['current_index'] ?? 0);
        $currentIndex = max(0, min($currentIndex, $maxIndex));

        return view('admin.simulation.run', [
            'questions' => $questions,
            'answers' => is_array($simulation['answers'] ?? null) ? $simulation['answers'] : [],
            'currentIndex' => $currentIndex,
            'deadlineAt' => (string) ($simulation['deadline_at'] ?? CarbonImmutable::now()->addMinutes(1)->toIso8601String()),
            'durationMinutes' => (int) ($simulation['duration_minutes'] ?? 0),
            'questionCount' => (int) ($simulation['question_count'] ?? count($questions)),
            'startedAt' => (string) ($simulation['started_at'] ?? CarbonImmutable::now()->toIso8601String()),
            'moduleName' => (string) ($simulation['module_name'] ?? 'Modul'),
        ]);
    }

    public function saveAnswer(Request $request): JsonResponse
    {
        $simulation = $this->readSimulation($request);
        if ($simulation === null) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi simulasi tidak ditemukan.',
                'redirect' => route('admin.simulation.index'),
            ], 422);
        }

        if ((bool) ($simulation['is_submitted'] ?? false) === true) {
            return response()->json([
                'success' => false,
                'message' => 'Simulasi sudah disubmit.',
                'redirect' => route('admin.simulation.result'),
            ], 422);
        }

        if ($this->isExpired($simulation)) {
            $simulation['is_submitted'] = true;
            $simulation['submitted_at'] = CarbonImmutable::now()->toIso8601String();
            $request->session()->put(self::SESSION_KEY, $simulation);

            return response()->json([
                'success' => false,
                'message' => 'Waktu simulasi habis. Simulasi dikunci otomatis.',
                'redirect' => route('admin.simulation.result'),
            ], 422);
        }

        $payload = $request->validate([
            'question_id' => ['required', 'integer', 'min:1'],
            'answer' => ['required', 'boolean'],
            'current_index' => ['nullable', 'integer', 'min:0'],
        ]);

        $questionId = (int) $payload['question_id'];
        if (! $this->questionExists($simulation, $questionId)) {
            return response()->json([
                'success' => false,
                'message' => 'Soal simulasi tidak valid.',
            ], 422);
        }

        $answers = is_array($simulation['answers'] ?? null) ? $simulation['answers'] : [];
        $answers[(string) $questionId] = (bool) $payload['answer'];
        $simulation['answers'] = $answers;

        if (array_key_exists('current_index', $payload)) {
            $maxIndex = max(0, ((int) ($simulation['question_count'] ?? 0)) - 1);
            $simulation['current_index'] = max(0, min((int) $payload['current_index'], $maxIndex));
        }

        $request->session()->put(self::SESSION_KEY, $simulation);

        $answeredCount = count($answers);
        $questionCount = (int) ($simulation['question_count'] ?? $answeredCount);

        return response()->json([
            'success' => true,
            'message' => 'Jawaban simulasi tersimpan.',
            'meta' => [
                'answered_count' => $answeredCount,
                'unanswered_count' => max(0, $questionCount - $answeredCount),
            ],
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $simulation = $this->readSimulation($request);
        if ($simulation === null) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi simulasi tidak ditemukan.',
                'redirect' => route('admin.simulation.index'),
            ], 422);
        }

        if ((bool) ($simulation['is_submitted'] ?? false) === true) {
            return response()->json([
                'success' => true,
                'message' => 'Simulasi sudah disubmit sebelumnya.',
                'redirect' => route('admin.simulation.result'),
            ]);
        }

        $simulation['is_submitted'] = true;
        $simulation['submitted_at'] = CarbonImmutable::now()->toIso8601String();
        $request->session()->put(self::SESSION_KEY, $simulation);

        return response()->json([
            'success' => true,
            'message' => 'Simulasi berhasil disubmit.',
            'redirect' => route('admin.simulation.result'),
        ]);
    }

    public function result(Request $request): View|RedirectResponse
    {
        $simulation = $this->readSimulation($request);
        if ($simulation === null) {
            return redirect()
                ->route('admin.simulation.index')
                ->withErrors(['simulation' => 'Belum ada data simulasi untuk ditampilkan.']);
        }

        if ((bool) ($simulation['is_submitted'] ?? false) !== true) {
            return redirect()->route('admin.simulation.run');
        }

        $questionCount = (int) ($simulation['question_count'] ?? 0);
        $answers = is_array($simulation['answers'] ?? null) ? $simulation['answers'] : [];
        $answeredCount = count($answers);
        $unansweredCount = max(0, $questionCount - $answeredCount);
        $scorePercent = $questionCount > 0 ? (int) round(($answeredCount / $questionCount) * 100) : 0;

        $startedAt = CarbonImmutable::parse((string) ($simulation['started_at'] ?? CarbonImmutable::now()->toIso8601String()));
        $submittedAt = CarbonImmutable::parse((string) ($simulation['submitted_at'] ?? CarbonImmutable::now()->toIso8601String()));
        $durationUsedSeconds = max(0, $submittedAt->diffInSeconds($startedAt));

        return view('admin.simulation.result', [
            'simulation' => $simulation,
            'answeredCount' => $answeredCount,
            'unansweredCount' => $unansweredCount,
            'scorePercent' => $scorePercent,
            'durationUsedSeconds' => $durationUsedSeconds,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readSimulation(Request $request): ?array
    {
        $simulation = $request->session()->get(self::SESSION_KEY);
        if (! is_array($simulation)) {
            return null;
        }

        return $simulation;
    }

    /**
     * @param  array<string, mixed>  $simulation
     */
    private function questionExists(array $simulation, int $questionId): bool
    {
        $questions = is_array($simulation['questions'] ?? null) ? $simulation['questions'] : [];

        foreach ($questions as $question) {
            if (is_array($question) && (int) ($question['id'] ?? 0) === $questionId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $simulation
     */
    private function isExpired(array $simulation): bool
    {
        $deadlineAt = (string) ($simulation['deadline_at'] ?? '');
        if ($deadlineAt === '') {
            return true;
        }

        return CarbonImmutable::now()->greaterThanOrEqualTo(CarbonImmutable::parse($deadlineAt));
    }
}
