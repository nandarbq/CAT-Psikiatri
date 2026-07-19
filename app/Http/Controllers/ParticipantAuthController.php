<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ParticipantLoginRequest;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ParticipantAuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        if ((int) $request->session()->get('participant_id', 0) > 0) {
            return redirect()->route('participant.dashboard');
        }

        return view('participant.login');
    }

    public function login(ParticipantLoginRequest $request): RedirectResponse
    {
        $username = trim($request->string('username')->toString());
        $password = $request->string('password')->toString();

        $admin = User::query()
            ->where('username', $username)
            ->first();

        if ($admin !== null && Hash::check($password, $admin->password)) {
            if (Auth::check()) {
                Auth::logout();
            }

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Auth::login($admin);
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        $participant = Participant::query()
            ->where('username', $username)
            ->first();

        if ($participant === null || ! Hash::check($password, $participant->password)) {
            return back()
                ->withInput($request->safe()->except('password'))
                ->withErrors(['username' => 'Username atau password tidak valid.']);
        }

        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('participant_id', $participant->id);
        $request->session()->regenerate();

        return redirect()->route('participant.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('participant.login');
    }
}
