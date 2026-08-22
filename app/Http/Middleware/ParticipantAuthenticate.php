<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Participant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ParticipantAuthenticate
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $participantId = (int) $request->session()->get('participant_id', 0);
        if ($participantId <= 0) {
            if (Auth::check()) {
                return redirect()->route('admin.results');
            }

            return redirect()->route('participant.login');
        }

        $participant = Participant::query()
            ->with('session')
            ->find($participantId);

        if ($participant === null) {
            $request->session()->forget([
                'participant_id',
                'exam_attempt_id',
                'exam_session_token',
            ]);

            return redirect()
                ->route('participant.login')
                ->withErrors(['session' => 'Sesi peserta tidak ditemukan. Silakan login kembali.']);
        }

        $request->attributes->set('participant', $participant);

        return $next($request);
    }
}
