<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParticipantBulkCreateRequest;
use App\Http\Requests\ParticipantRequest;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\Participant;
use App\Support\Concerns\HandlesPagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ParticipantController extends Controller
{
    use HandlesPagination;

    public function index(Request $request): View
    {
        $query = Participant::query()
            ->with('session');

        $keyword = trim((string) $request->query('q', ''));
        $sessionId = $request->query('session_id');

        if ($keyword !== '') {
            $query->where(function ($inner) use ($keyword): void {
                $inner->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('username', 'like', '%'.$keyword.'%')
                    ->orWhere('nik', 'like', '%'.$keyword.'%');
            });
        }

        if ($sessionId !== null && $sessionId !== '') {
            $query->where('session_id', (int) $sessionId);
        }

        return view('admin.participants.index', [
            'participants' => $this->paginateQuery(
                $query->latest('id'),
                $request
            ),
            'sessionOptions' => ExamSession::query()
                ->orderBy('start_time')
                ->get(['id', 'title']),
            'filters' => [
                'q' => $keyword,
                'session_id' => $sessionId,
            ],
        ]);
    }

    public function history(Request $request, Participant $participant): View
    {
        $attempts = $this->paginateQuery(
            ExamAttempt::query()
                ->with('session')
                ->where('participant_id', $participant->id)
                ->withCount('answers')
                ->latest('id'),
            $request
        );

        return view('admin.participants.history', [
            'participant' => $participant->load('session'),
            'attempts' => $attempts,
        ]);
    }

    public function create(): View
    {
        return view('admin.participants.form', [
            'participant' => new Participant,
            'sessions' => ExamSession::query()->orderBy('start_time')->get(['id', 'title']),
            'hasAttempts' => false,
            'isEdit' => false,
        ]);
    }

    public function bulkCreateForm(): View
    {
        return view('admin.participants.bulk-create', [
            'sessionOptions' => ExamSession::query()->orderBy('start_time')->get(['id', 'title']),
        ]);
    }

    public function bulkCreateStore(ParticipantBulkCreateRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        $sessionId = (int) $payload['session_id'];
        $participants = (array) ($payload['participants'] ?? []);
        $createdCount = 0;

        DB::transaction(function () use ($participants, $sessionId, &$createdCount): void {
            foreach ($participants as $row) {
                Participant::query()->create([
                    'session_id' => $sessionId,
                    'nik' => trim((string) $row['nik']),
                    'username' => trim((string) $row['username']),
                    'password' => (string) $row['password'],
                    'code' => $this->generateUniqueParticipantCode($sessionId),
                    'name' => trim((string) $row['name']),
                    'birth_place' => blank($row['birth_place'] ?? null) ? null : trim((string) $row['birth_place']),
                    'birth_date' => $row['birth_date'],
                    'gender' => (string) $row['gender'],
                    'phone' => blank($row['phone'] ?? null) ? null : trim((string) $row['phone']),
                    'address' => blank($row['address'] ?? null) ? null : trim((string) $row['address']),
                    'purpose' => blank($row['purpose'] ?? null) ? null : trim((string) $row['purpose']),
                    'is_finished' => false,
                ]);

                $createdCount++;
            }
        });

        $session = ExamSession::query()->findOrFail($sessionId);

        return redirect()
            ->route('admin.participants.bulk-create.form')
            ->with('success', "Penambahan peserta sekaligus selesai: {$createdCount} peserta baru ditambahkan ke sesi {$session->title}.");
    }

    public function store(ParticipantRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['code'] = (string) ($payload['code'] ?? $this->generateUniqueParticipantCode((int) $payload['session_id']));
        $payload['is_finished'] = false;

        Participant::query()->create($payload);

        return redirect()->route('admin.participants.index')->with('success', 'Peserta berhasil dibuat.');
    }

    public function edit(Participant $participant): View
    {
        return view('admin.participants.form', [
            'participant' => $participant,
            'sessions' => ExamSession::query()->orderBy('start_time')->get(),
            'hasAttempts' => $participant->attempts()->exists(),
            'isEdit' => true,
        ]);
    }

    public function update(ParticipantRequest $request, Participant $participant): RedirectResponse
    {
        $payload = $request->validated();
        if (blank($payload['code'] ?? null)) {
            $payload['code'] = $participant->code;
        }

        $targetSessionId = (int) $payload['session_id'];
        $sessionChanged = $targetSessionId !== (int) $participant->session_id;

        if ($sessionChanged && $participant->attempts()->where('is_submitted', false)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['participant' => 'Peserta sedang mengerjakan tes sehingga tidak bisa dipindah sesi.']);
        }

        if ($sessionChanged) {
            $codeConflict = Participant::query()
                ->where('session_id', $targetSessionId)
                ->where('code', (string) $payload['code'])
                ->where('id', '!=', $participant->id)
                ->exists();

            if ($codeConflict) {
                return back()
                    ->withInput()
                    ->withErrors(['participant' => 'Peserta tidak bisa dipindah ke sesi tujuan karena kode bentrok.']);
            }

            // Status selesai harus merepresentasikan sesi aktif saat ini.
            $payload['is_finished'] = false;
        }

        if (blank($payload['password'] ?? null)) {
            unset($payload['password']);
        }

        $participant->update($payload);

        return redirect()->route('admin.participants.index')->with('success', 'Peserta berhasil diperbarui.');
    }

    public function destroy(Participant $participant): RedirectResponse
    {
        if ($participant->attempts()->exists()) {
            return back()->withErrors(['participant' => 'Peserta tidak dapat dihapus karena sudah memiliki riwayat pengerjaan tes.']);
        }

        $participant->delete();

        return redirect()->route('admin.participants.index')->with('success', 'Peserta berhasil dihapus.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:participants,id'],
            'action' => ['required', 'in:mark_finished,mark_unfinished,delete'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $payload['ids'])));
        $action = (string) $payload['action'];

        if ($action === 'mark_finished') {
            Participant::query()->whereIn('id', $ids)->update(['is_finished' => true]);

            return back()->with('success', 'Aksi massal selesai: '.count($ids).' peserta ditandai selesai.');
        }

        if ($action === 'mark_unfinished') {
            Participant::query()->whereIn('id', $ids)->update(['is_finished' => false]);

            return back()->with('success', 'Aksi massal selesai: '.count($ids).' peserta ditandai belum selesai.');
        }

        $participants = Participant::query()->whereIn('id', $ids)->withCount('attempts')->get();
        $withHistoryCount = $participants->filter(fn ($participant) => $participant->attempts_count > 0)->count();

        Participant::query()->whereIn('id', $ids)->delete();

        $message = "Aksi massal selesai: {$participants->count()} peserta dihapus.";
        if ($withHistoryCount > 0) {
            $message .= " {$withHistoryCount} di antaranya memiliki riwayat pengerjaan tes yang ikut terhapus.";
        }

        return back()->with('success', $message);
    }

    private function generateUniqueParticipantCode(int $sessionId): string
    {
        do {
            $code = 'AUTO-'.strtoupper(Str::random(8));
        } while (Participant::query()->where('session_id', $sessionId)->where('code', $code)->exists());

        return $code;
    }
}
