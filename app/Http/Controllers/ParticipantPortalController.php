<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ParticipantProfileUpdateRequest;
use App\Models\ExamToken;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParticipantPortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        /** @var Participant $participant */
        $participant = $request->attributes->get('participant');
        $participant->loadMissing('session');

        $tokenBypassEnabled = (bool) config('exam.token_bypass_enabled', false);
        $hasSession = $participant->session !== null;
        $hasSessionToken = $hasSession
            ? ExamToken::query()->where('session_id', (int) $participant->session_id)->exists()
            : false;
        $hasExamAccess = $hasSession && ($hasSessionToken || $tokenBypassEnabled);

        $accessTone = 'danger';
        $accessText = 'Anda belum dimasukkan ke sesi ujian. Hubungi admin untuk penjadwalan sesi.';

        if ($hasSession && ! $hasExamAccess) {
            $accessTone = 'warning';
            $accessText = "Anda sudah terdaftar pada sesi {$participant->session?->title}, tetapi token sesi belum tersedia.";
        }

        if ($hasExamAccess) {
            $accessTone = 'success';
            $accessText = "Anda sudah dapat mengakses ujian pada sesi {$participant->session?->title}.";
            if ($tokenBypassEnabled) {
                $accessText .= ' Mode bypass token sedang aktif untuk testing.';
            }
        }

        return view('participant.dashboard', [
            'participant' => $participant,
            'hasIncompleteProfile' => $this->hasIncompleteProfile($participant),
            'hasSession' => $hasSession,
            'hasSessionToken' => $hasSessionToken,
            'hasExamAccess' => $hasExamAccess,
            'accessTone' => $accessTone,
            'accessText' => $accessText,
        ]);
    }

    public function editProfile(Request $request): View
    {
        /** @var Participant $participant */
        $participant = $request->attributes->get('participant');

        return view('participant.profile-edit', [
            'participant' => $participant,
        ]);
    }

    public function updateProfile(ParticipantProfileUpdateRequest $request): RedirectResponse
    {
        /** @var Participant $participant */
        $participant = $request->attributes->get('participant');

        $participant->update($request->validated());

        return redirect()
            ->route('participant.dashboard')
            ->with('success', 'Data diri berhasil diperbarui.');
    }

    private function hasIncompleteProfile(Participant $participant): bool
    {
        return blank($participant->nik)
            || blank($participant->name)
            || blank($participant->birth_place)
            || blank($participant->birth_date)
            || blank($participant->address)
            || blank($participant->purpose);
    }
}
