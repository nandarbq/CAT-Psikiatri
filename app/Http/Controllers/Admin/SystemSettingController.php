<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamLocationSettingRequest;
use App\Models\ExamLocationSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function index(Request $request): View
    {
        $activeTab = $this->resolveTab((string) $request->query('tab', 'profile'));

        return view('admin.settings.index', [
            'activeTab' => $activeTab,
            'locationSetting' => $this->currentLocationSetting(),
            'currentUser' => Auth::user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:60', 'alpha_dash', 'unique:users,username,'.$user->id],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = trim((string) $payload['name']);
        $user->username = strtolower(trim((string) $payload['username']));
        $user->email = strtolower(trim((string) $payload['email']));

        if (! blank($payload['password'] ?? null)) {
            $user->password = Hash::make((string) $payload['password']);
        }

        $user->save();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'profile'])
            ->with('success', 'Profil berhasil diperbarui.');
    }

    public function updateLocation(ExamLocationSettingRequest $request): RedirectResponse
    {
        $setting = $this->currentLocationSetting();
        $setting->update($request->validated());

        return redirect()
            ->route('admin.settings.index', ['tab' => 'location'])
            ->with('success', 'Lokasi RSUD berhasil diperbarui.');
    }

    private function resolveTab(string $tab): string
    {
        return in_array($tab, ['profile', 'location'], true) ? $tab : 'profile';
    }

    private function currentLocationSetting(): ExamLocationSetting
    {
        // NOTE: approximate Banyuwangi town-center fallback used only when no
        // row exists yet. Admin should verify the exact RSUD Blambangan pin
        // on this page and save it for an accurate radius check.
        return ExamLocationSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'center_latitude' => -8.2135000,
                'center_longitude' => 114.3652000,
                'allowed_radius_meters' => 500,
            ],
        );
    }
}
