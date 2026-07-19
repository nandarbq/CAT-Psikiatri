<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionRequest;
use App\Models\MasterCategory;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Question::query()->with('masterCategory');
        $keyword = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category_id');
        $isActive = $request->query('is_active');
        $perPageParam = (string) $request->query('per_page', '20');

        if ($keyword !== '') {
            $query->where('question', 'like', '%'.$keyword.'%');
        }

        if ($categoryId !== null && $categoryId !== '') {
            $query->where('category_id', (int) $categoryId);
        }

        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', (bool) ((int) $isActive));
        }

        $hasActiveFilters = $keyword !== ''
            || ($categoryId !== null && $categoryId !== '')
            || ($isActive !== null && $isActive !== '');

        $isShowingAll = $perPageParam === 'all';
        $perPage = in_array($perPageParam, ['20', '50', '100'], true)
            ? (int) $perPageParam
            : 20;
        $orderedQuery = $query->orderBy('order_no');
        $questions = $isShowingAll
            ? $orderedQuery->get()
            : $orderedQuery->paginate($perPage)->withQueryString();

        $canReorder = ! $hasActiveFilters && $isShowingAll;
        $reorderNotice = null;
        if ($hasActiveFilters) {
            $reorderNotice = 'Mode filter aktif. Drag urutan dimatikan agar urutan global tidak bentrok.';
        } elseif (! $isShowingAll) {
            $reorderNotice = 'Mode pagination aktif. Ubah ke "Semua Soal" untuk drag urutan global.';
        }

        return view('admin.questions.index', [
            'questions' => $questions,
            'categoryOptions' => MasterCategory::query()->orderBy('name')->get(['id', 'name']),
            'canReorder' => $canReorder,
            'reorderNotice' => $reorderNotice,
            'filters' => [
                'q' => $keyword,
                'category_id' => $categoryId,
                'is_active' => $isActive,
                'per_page' => $isShowingAll ? 'all' : (string) $perPage,
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.questions.form', [
            'question' => new Question(),
            'categories' => MasterCategory::query()->orderBy('name')->get(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->has('questions')) {
            $payload = $request->validate([
                'questions' => ['required', 'array', 'min:1'],
                'questions.*.category_id' => ['required', 'integer', 'exists:master_categories,id'],
                'questions.*.question' => ['required', 'string', 'max:10000'],
                'questions.*.is_active' => ['required', 'boolean'],
            ]);

            /** @var array<int, array{category_id:int,question:string,is_active:bool|int|string}> $rows */
            $rows = array_values($payload['questions']);
            $categoryIds = array_values(array_unique(array_map(
                static fn (array $row): int => (int) $row['category_id'],
                $rows
            )));
            $categoryNameLookup = MasterCategory::query()
                ->whereIn('id', $categoryIds)
                ->pluck('name', 'id');

            DB::transaction(function () use ($rows, $categoryNameLookup): void {
                $nextOrderNo = ((int) Question::query()->max('order_no')) + 1;

                foreach ($rows as $row) {
                    $categoryId = (int) $row['category_id'];
                    Question::query()->create([
                        'category_id' => $categoryId,
                        'category' => (string) ($categoryNameLookup[$categoryId] ?? ''),
                        'question' => trim((string) $row['question']),
                        'order_no' => $nextOrderNo++,
                        'is_active' => (bool) $row['is_active'],
                    ]);
                }
            });

            return redirect()
                ->route('admin.questions.index')
                ->with('success', count($rows).' soal berhasil dibuat.');
        }

        $payload = $request->validate([
            'category_id' => ['required', 'integer', 'exists:master_categories,id'],
            'question' => ['required', 'string', 'max:10000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $categoryId = (int) $payload['category_id'];
        $category = MasterCategory::query()->findOrFail($categoryId);

        Question::query()->create([
            'category_id' => $categoryId,
            'category' => $category->name,
            'question' => trim((string) $payload['question']),
            'order_no' => ((int) Question::query()->max('order_no')) + 1,
            'is_active' => (bool) $payload['is_active'],
        ]);

        return redirect()->route('admin.questions.index')->with('success', 'Soal berhasil dibuat.');
    }

    public function edit(Question $question): View
    {
        return view('admin.questions.form', [
            'question' => $question,
            'categories' => MasterCategory::query()->orderBy('name')->get(),
            'isEdit' => true,
        ]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $payload = $request->validated();
        $category = MasterCategory::query()->findOrFail((int) $payload['category_id']);
        $payload['category'] = $category->name;
        $targetOrder = (int) $payload['order_no'];
        unset($payload['order_no']);

        DB::transaction(function () use ($question, $payload, $targetOrder): void {
            $orderedIds = Question::query()
                ->orderBy('order_no')
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $question->update($payload);

            if ($orderedIds === []) {
                return;
            }

            $clampedTargetOrder = min(max($targetOrder, 1), count($orderedIds));
            $remainingIds = array_values(array_filter(
                $orderedIds,
                static fn (int $id): bool => $id !== (int) $question->id
            ));
            array_splice($remainingIds, $clampedTargetOrder - 1, 0, [(int) $question->id]);

            foreach ($remainingIds as $index => $questionId) {
                Question::query()
                    ->whereKey($questionId)
                    ->update(['order_no' => $index + 1]);
            }
        });

        return redirect()->route('admin.questions.index')->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        if ($question->answers()->exists()) {
            return back()->withErrors(['question' => 'Soal tidak dapat dihapus karena sudah dipakai jawaban.']);
        }

        $question->delete();

        return redirect()->route('admin.questions.index')->with('success', 'Soal berhasil dihapus.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:questions,id'],
            'action' => ['required', 'in:activate,deactivate,delete'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $payload['ids'])));
        $action = (string) $payload['action'];

        if ($action === 'activate') {
            Question::query()->whereIn('id', $ids)->update(['is_active' => true]);

            return back()->with('success', 'Aksi massal selesai: '.count($ids).' soal diaktifkan.');
        }

        if ($action === 'deactivate') {
            Question::query()->whereIn('id', $ids)->update(['is_active' => false]);

            return back()->with('success', 'Aksi massal selesai: '.count($ids).' soal dinonaktifkan.');
        }

        $questions = Question::query()->whereIn('id', $ids)->get();
        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($questions as $question) {
            if ($question->answers()->exists()) {
                $skippedCount++;
                continue;
            }

            $question->delete();
            $deletedCount++;
        }

        $message = "Aksi massal selesai: {$deletedCount} soal dihapus.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} soal dilewati karena sudah dipakai jawaban.";
        }

        return back()->with('success', $message);
    }

    public function reorder(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:questions,id'],
        ]);

        $ids = array_values(array_map('intval', $payload['ids']));
        if (count($ids) !== count(array_unique($ids))) {
            return response()->json([
                'success' => false,
                'message' => 'Payload urutan soal tidak valid.',
            ], 422);
        }

        $allQuestionIds = Question::query()->pluck('id')->map(static fn ($id): int => (int) $id);
        $requestedIds = collect($ids);
        if (
            $requestedIds->count() !== $allQuestionIds->count()
            || $requestedIds->sort()->values()->all() !== $allQuestionIds->sort()->values()->all()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Urutan soal harus memuat semua soal yang ada.',
            ], 422);
        }

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $index => $questionId) {
                Question::query()
                    ->whereKey($questionId)
                    ->update(['order_no' => $index + 1]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Urutan soal berhasil diperbarui.',
        ]);
    }
}
