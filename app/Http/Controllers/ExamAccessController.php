<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StartExamRequest;
use App\Models\ExamToken;
use App\Models\Participant;
use App\Services\StartExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamAccessController extends Controller
{
    public function showAccessForm(Request $request): View
    {
        /** @var Participant $participant */
        $participant = $request->attributes->get('participant');
        $participant->loadMissing('session');

        $tokenBypassEnabled = (bool) config('exam.token_bypass_enabled', false);
        $hasSession = $participant->session !== null;
        $hasSessionToken = $hasSession
            ? ExamToken::query()->where('session_id', (int) $participant->session_id)->exists()
            : false;
        $canStartExam = $hasSession && ($tokenBypassEnabled || $hasSessionToken);
        $requiresLocation = $hasSession && $participant->session->isOnsiteRadiusOnly();

        return view('participant.exam-access', [
            'participant' => $participant,
            'session' => $participant->session,
            'tokenBypassEnabled' => $tokenBypassEnabled,
            'hasSession' => $hasSession,
            'hasSessionToken' => $hasSessionToken,
            'canStartExam' => $canStartExam,
            'requiresLocation' => $requiresLocation,
        ]);
    }

    public function start(StartExamRequest $request, StartExamService $startExamService): RedirectResponse
    {
        /** @var Participant $participant */
        $participant = $request->attributes->get('participant');
        $participant->loadMissing('session');

        if ($participant->session === null) {
            return back()->withErrors([
                'session' => 'Anda belum dimasukkan ke sesi tes. Hubungi admin.',
            ])->withInput();
        }

        if (
            ! (bool) config('exam.token_bypass_enabled', false)
            && ! ExamToken::query()->where('session_id', (int) $participant->session_id)->exists()
        ) {
            return back()->withErrors([
                'token' => 'Token sesi belum tersedia. Hubungi admin.',
            ])->withInput();
        }

        try {
            $attempt = $startExamService->startOrResume($participant, $request->string('token')->toString(), $request);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        $request->session()->put('exam_attempt_id', $attempt->id);
        $request->session()->put('exam_session_token', $attempt->session_token);
        $request->session()->put('exam_geo_last_latitude', $this->toNullableFloat($request->input('latitude')));
        $request->session()->put('exam_geo_last_longitude', $this->toNullableFloat($request->input('longitude')));
        $request->session()->put('exam_geo_last_accuracy', $this->toNullableFloat($request->input('accuracy')));
        $request->session()->save();

        return redirect()->route('exam.show');
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }
}
