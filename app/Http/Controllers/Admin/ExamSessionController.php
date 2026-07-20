<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamSessionRequest;
use App\Models\ExamSession;
use App\Models\Participant;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamSessionController extends Controller
{
    public function index(Request $request): View
    {
        $query = ExamSession::query();
        $keyword = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        if ($keyword !== '') {
            $query->where('title', 'like', '%'.$keyword.'%');
        }

        if ($status !== null && $status !== '') {
            $query->where('status', (bool) ((int) $status));
        }

        return view('admin.sessions.index', [
            'sessions' => $query->latest('id')->paginate(15)->withQueryString(),
            'filters' => [
                'q' => $keyword,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.sessions.form', [
            'session' => new ExamSession(),
            'hasAttempts' => false,
            'isEdit' => false,
        ]);
    }

    public function store(ExamSessionRequest $request): RedirectResponse
    {
        ExamSession::query()->create($request->validated());

        return redirect()->route('admin.sessions.index')->with('success', 'Sesi berhasil dibuat.');
    }

    public function edit(ExamSession $session): View
    {
        return view('admin.sessions.form', [
            'session' => $session,
            'hasAttempts' => $session->attempts()->exists(),
            'isEdit' => true,
        ]);
    }

    public function update(ExamSessionRequest $request, ExamSession $session): RedirectResponse
    {
        $payload = $request->validated();
        $hasAttempts = $session->attempts()->exists();
        $timeConfigChanged = ! CarbonImmutable::parse($session->start_time)->equalTo(CarbonImmutable::parse((string) $payload['start_time']))
            || ! CarbonImmutable::parse($session->end_time)->equalTo(CarbonImmutable::parse((string) $payload['end_time']))
            || (int) $session->duration_minutes !== (int) $payload['duration_minutes'];
        $accessConfigChanged = ($session->access_mode ?? ExamSession::ACCESS_MODE_ONLINE) !== (string) $payload['access_mode'];

        if ($hasAttempts && ($timeConfigChanged || $accessConfigChanged)) {
            return back()
                ->withInput()
                ->withErrors(['session' => 'Sesi yang sudah memiliki riwayat pengerjaan tidak boleh mengubah waktu mulai, waktu selesai, durasi, atau konfigurasi akses lokasi.']);
        }

        $session->update($payload);

        return redirect()->route('admin.sessions.index')->with('success', 'Sesi berhasil diperbarui.');
    }

    public function destroy(ExamSession $session): RedirectResponse
    {
        if ($session->attempts()->exists()) {
            return back()->withErrors(['session' => 'Sesi tidak dapat dihapus karena sudah memiliki riwayat pengerjaan tes.']);
        }

        $session->delete();

        return redirect()->route('admin.sessions.index')->with('success', 'Sesi berhasil dihapus.');
    }

    public function updateStatus(Request $request, ExamSession $session): RedirectResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $payload['status'];
        $session->forceFill(['status' => $isActive])->save();

        $label = $isActive ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Sesi {$session->title} berhasil {$label}.");
    }

    public function bulk(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:exam_sessions,id'],
            'action' => ['required', 'in:activate,deactivate,delete'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $payload['ids'])));
        $action = (string) $payload['action'];
        $sessions = ExamSession::query()->whereIn('id', $ids)->get();

        if ($action === 'activate') {
            ExamSession::query()->whereIn('id', $ids)->update(['status' => true]);

            return back()->with('success', 'Aksi massal selesai: '.count($ids).' sesi diaktifkan.');
        }

        if ($action === 'deactivate') {
            ExamSession::query()->whereIn('id', $ids)->update(['status' => false]);

            return back()->with('success', 'Aksi massal selesai: '.count($ids).' sesi dinonaktifkan.');
        }

        $deletedCount = 0;
        $skippedCount = 0;
        foreach ($sessions as $session) {
            if ($session->attempts()->exists()) {
                $skippedCount++;
                continue;
            }

            $session->delete();
            $deletedCount++;
        }

        $message = "Aksi massal selesai: {$deletedCount} sesi dihapus.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} sesi dilewati karena sudah memiliki riwayat pengerjaan tes.";
        }

        return back()->with('success', $message);
    }

    public function participantsBulkForm(Request $request, ExamSession $session): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $sourceSessionId = trim((string) $request->query('source_session_id', ''));

        $query = Participant::query()
            ->with('session:id,title')
            ->orderBy('name');

        if ($keyword !== '') {
            $query->where(function ($inner) use ($keyword): void {
                $inner->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('username', 'like', '%'.$keyword.'%')
                    ->orWhere('nik', 'like', '%'.$keyword.'%')
                    ->orWhere('code', 'like', '%'.$keyword.'%');
            });
        }

        if ($sourceSessionId !== '') {
            $query->where('session_id', (int) $sourceSessionId);
        }

        $participants = $query->paginate(20)->withQueryString();
        $pageParticipantIds = $participants->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $activeAttemptParticipantIds = empty($pageParticipantIds)
            ? []
            : DB::table('exam_attempts')
                ->whereIn('participant_id', $pageParticipantIds)
                ->where('is_submitted', false)
                ->pluck('participant_id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $targetSessionCodes = Participant::query()
            ->where('session_id', $session->id)
            ->pluck('code')
            ->map(fn ($code) => (string) $code)
            ->all();
        $targetSessionCodeLookup = array_fill_keys($targetSessionCodes, true);

        return view('admin.sessions.participants-bulk', [
            'session' => $session,
            'participants' => $participants,
            'sessionOptions' => ExamSession::query()->orderBy('start_time')->get(['id', 'title']),
            'activeAttemptParticipantIds' => $activeAttemptParticipantIds,
            'targetSessionCodeLookup' => $targetSessionCodeLookup,
            'filters' => [
                'q' => $keyword,
                'source_session_id' => $sourceSessionId,
            ],
        ]);
    }

    public function participantsBulkStore(Request $request, ExamSession $session): RedirectResponse
    {
        $payload = $request->validate([
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['integer', 'exists:participants,id'],
        ]);

        $participantIds = array_values(array_unique(array_map('intval', (array) $payload['participant_ids'])));
        $participants = Participant::query()->whereIn('id', $participantIds)->get();

        $movedCount = 0;
        $skippedAlreadyInSession = 0;
        $skippedActiveAttempt = 0;
        $skippedCodeConflict = 0;

        DB::transaction(function () use (
            $participants,
            $session,
            &$movedCount,
            &$skippedAlreadyInSession,
            &$skippedActiveAttempt,
            &$skippedCodeConflict
        ): void {
            foreach ($participants as $participant) {
                if ((int) $participant->session_id === (int) $session->id) {
                    $skippedAlreadyInSession++;
                    continue;
                }

                if ($participant->attempts()->where('is_submitted', false)->exists()) {
                    $skippedActiveAttempt++;
                    continue;
                }

                $codeConflictExists = Participant::query()
                    ->where('session_id', $session->id)
                    ->where('code', $participant->code)
                    ->where('id', '!=', $participant->id)
                    ->exists();

                if ($codeConflictExists) {
                    $skippedCodeConflict++;
                    continue;
                }

                $participant->forceFill(['session_id' => $session->id])->save();
                $movedCount++;
            }
        });

        $message = "Bulk peserta selesai: {$movedCount} peserta dipindahkan ke sesi {$session->title}.";
        if ($skippedAlreadyInSession > 0) {
            $message .= " {$skippedAlreadyInSession} peserta dilewati karena sudah berada di sesi ini.";
        }
        if ($skippedActiveAttempt > 0) {
            $message .= " {$skippedActiveAttempt} peserta dilewati karena sedang mengerjakan tes.";
        }
        if ($skippedCodeConflict > 0) {
            $message .= " {$skippedCodeConflict} peserta dilewati karena kode bentrok pada sesi tujuan.";
        }

        return redirect()
            ->route('admin.sessions.participants.bulk.form', $session)
            ->with('success', $message);
    }

    public function instructions(Request $request): View
    {
        $sessions = ExamSession::query()
            ->orderBy('start_time')
            ->get(['id', 'title', 'status', 'start_time', 'end_time']);

        $selectedSessionId = (int) $request->query('session_id', 0);
        $selectedSession = $selectedSessionId > 0
            ? $sessions->firstWhere('id', $selectedSessionId)
            : $sessions->first();

        return view('admin.sessions.instructions', [
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'selectedSessionId' => $selectedSession?->id,
        ]);
    }

    public function updateInstructions(Request $request, ExamSession $session): RedirectResponse
    {
        $payload = $request->validate([
            'instructions' => ['nullable', 'string', 'max:10000'],
        ]);

        $session->update([
            'instructions' => blank($payload['instructions'] ?? null)
                ? null
                : trim((string) $payload['instructions']),
        ]);

        return redirect()
            ->route('admin.instructions.index', ['session_id' => $session->id])
            ->with('success', 'Petunjuk tes berhasil diperbarui.');
    }

}
