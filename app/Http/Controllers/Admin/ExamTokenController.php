<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamTokenRequest;
use App\Models\ExamSession;
use App\Models\ExamToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamTokenController extends Controller
{
    public function index(Request $request): View
    {
        $query = ExamToken::query()->with('session');
        $keyword = trim((string) $request->query('q', ''));
        $sessionId = $request->query('session_id');
        $isActive = $request->query('is_active');

        if ($keyword !== '') {
            $query->where('token', 'like', '%'.$keyword.'%');
        }

        if ($sessionId !== null && $sessionId !== '') {
            $query->where('session_id', (int) $sessionId);
        }

        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', (bool) ((int) $isActive));
        }

        return view('admin.tokens.index', [
            'tokens' => $query->latest('id')->paginate(20)->withQueryString(),
            'sessionOptions' => ExamSession::query()->orderBy('start_time')->get(['id', 'title']),
            'filters' => [
                'q' => $keyword,
                'session_id' => $sessionId,
                'is_active' => $isActive,
            ],
        ]);
    }

    public function bulkForm(Request $request): View
    {
        $sessionOptions = ExamSession::query()->orderBy('start_time')->get(['id', 'title']);
        $defaultSessionIds = collect((array) $request->query('session_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        return view('admin.tokens.bulk', [
            'sessionOptions' => $sessionOptions,
            'defaultSessionIds' => $defaultSessionIds,
            'tokenSessionIdLookup' => ExamToken::query()
                ->pluck('session_id')
                ->map(fn ($id): int => (int) $id)
                ->flip()
                ->all(),
        ]);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'session_ids' => ['required', 'array', 'min:1'],
            'session_ids.*' => ['integer', 'exists:exam_sessions,id'],
            'is_active' => ['required', 'boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $sessionIds = array_values(array_unique(array_map('intval', (array) $payload['session_ids'])));
        $existingSessionIds = ExamToken::query()
            ->whereIn('session_id', $sessionIds)
            ->pluck('session_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $sessionIdsToCreate = array_values(array_diff($sessionIds, $existingSessionIds));
        $createdCount = 0;

        DB::transaction(function () use ($sessionIdsToCreate, $payload, &$createdCount): void {
            foreach ($sessionIdsToCreate as $sessionId) {
                ExamToken::query()->create([
                    'session_id' => $sessionId,
                    'token' => $this->generateUniqueToken(),
                    'is_active' => (bool) $payload['is_active'],
                    'expires_at' => $payload['expires_at'] ?? null,
                ]);
                $createdCount++;
            }
        });

        $skippedCount = count($existingSessionIds);
        $message = "Bulk token sesi selesai: {$createdCount} token dibuat.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} sesi dilewati karena sudah punya token.";
        }

        return redirect()->route('admin.tokens.index')->with('success', $message);
    }

    public function create(Request $request): View
    {
        return view('admin.tokens.form', [
            'tokenRecord' => new ExamToken(),
            'sessions' => ExamSession::query()->orderBy('start_time')->get(['id', 'title']),
            'defaultSessionId' => (string) $request->query('session_id', ''),
            'isEdit' => false,
            'generatedToken' => $this->generateUniqueToken(),
        ]);
    }

    public function store(ExamTokenRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['token'] = trim((string) $payload['token']);

        ExamToken::query()->create($payload);

        return redirect()->route('admin.tokens.index')->with('success', 'Token sesi berhasil dibuat.');
    }

    public function edit(ExamToken $token): View
    {
        return view('admin.tokens.form', [
            'tokenRecord' => $token,
            'sessions' => ExamSession::query()->orderBy('start_time')->get(['id', 'title']),
            'defaultSessionId' => '',
            'isEdit' => true,
            'generatedToken' => $this->generateUniqueToken(),
        ]);
    }

    public function update(ExamTokenRequest $request, ExamToken $token): RedirectResponse
    {
        $payload = $request->validated();
        $payload['token'] = trim((string) $payload['token']);

        $token->update($payload);

        return redirect()->route('admin.tokens.index')->with('success', 'Token sesi berhasil diperbarui.');
    }

    public function destroy(ExamToken $token): RedirectResponse
    {
        $token->delete();

        return redirect()->route('admin.tokens.index')->with('success', 'Token sesi berhasil dihapus.');
    }

    public function generate(): JsonResponse
    {
        return response()->json([
            'token' => $this->generateUniqueToken(),
        ]);
    }

    private function generateUniqueToken(): string
    {
        $maxAttempts = 1000;
        $attempt = 0;

        do {
            $token = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $attempt++;
        } while (ExamToken::query()->where('token', $token)->exists() && $attempt < $maxAttempts);

        if ($attempt >= $maxAttempts) {
            throw ValidationException::withMessages([
                'token' => 'Gagal membuat token unik 6 digit. Coba lagi.',
            ]);
        }

        return $token;
    }
}

