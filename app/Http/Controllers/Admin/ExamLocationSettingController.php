<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamLocationSettingRequest;
use App\Models\ExamLocationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamLocationSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.location-settings.edit', [
            'setting' => $this->currentSetting(),
        ]);
    }

    public function update(ExamLocationSettingRequest $request): RedirectResponse
    {
        $setting = $this->currentSetting();
        $setting->update($request->validated());

        return redirect()
            ->route('admin.location-settings.edit')
            ->with('success', 'Lokasi RSUD berhasil diperbarui.');
    }

    private function currentSetting(): ExamLocationSetting
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

