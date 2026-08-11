<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionRequest;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function edit(Question $question): View
    {
        return view('admin.questions.form', [
            'question' => $question,
            'isEdit' => true,
        ]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $payload = $request->validated();
        $targetOrder = (int) $payload['order_no'];
        unset($payload['order_no']);

        DB::transaction(function () use ($question, $payload, $targetOrder): void {
            $moduleId = $question->module_id;

            $orderedIds = Question::query()
                ->where('module_id', $moduleId)
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

        return redirect()
            ->route('admin.modules.edit', $question->module_id)
            ->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        if ($question->answers()->exists()) {
            return back()->withErrors(['question' => 'Soal tidak dapat dihapus karena sudah dipakai jawaban.']);
        }

        $moduleId = $question->module_id;
        $question->delete();

        return redirect()
            ->route('admin.modules.edit', $moduleId)
            ->with('success', 'Soal berhasil dihapus.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:questions,id'],
        ]);

        $moduleId = (int) $payload['module_id'];
        $ids = array_values(array_map('intval', $payload['ids']));
        if (count($ids) !== count(array_unique($ids))) {
            return response()->json([
                'success' => false,
                'message' => 'Payload urutan soal tidak valid.',
            ], 422);
        }


        // Use a transaction with row locking to prevent concurrent reorder races.
        try {
            DB::transaction(function () use ($moduleId, $ids): void {
                $moduleQuestionIds = Question::query()
                    ->where('module_id', $moduleId)
                    ->orderBy('order_no')
                    ->lockForUpdate()
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();

                $requestedIds = array_values(array_map('intval', $ids));

                if (count($requestedIds) !== count($moduleQuestionIds)) {
                    throw new \RuntimeException('Urutan soal harus memuat semua soal pada modul ini.');
                }

                $sortedRequested = $requestedIds;
                $sortedModule = $moduleQuestionIds;
                sort($sortedRequested);
                sort($sortedModule);

                if (array_values($sortedRequested) !== array_values($sortedModule)) {
                    throw new \RuntimeException('Urutan soal harus memuat semua soal pada modul ini.');
                }

                foreach ($requestedIds as $index => $questionId) {
                    Question::query()
                        ->whereKey($questionId)
                        ->update(['order_no' => $index + 1]);
                }
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Gagal memperbarui urutan soal.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Urutan soal berhasil diperbarui.',
        ]);
    }
}
