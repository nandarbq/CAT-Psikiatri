<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ExamLocationSetting;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Module;
use App\Models\Participant;
use App\Models\Question;
use App\Models\User;
use App\Support\UniqueTokenGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin CAT',
                'username' => 'admin01',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make('password123'),
            ],
        );

        $locationFallback = (array) config('exam.default_location_fallback', []);

        ExamLocationSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'center_latitude' => (float) ($locationFallback['center_latitude'] ?? -8.2135000),
                'center_longitude' => (float) ($locationFallback['center_longitude'] ?? 114.3652000),
                'allowed_radius_meters' => (int) ($locationFallback['allowed_radius_meters'] ?? 500),
            ],
        );

        $module = Module::query()->updateOrCreate(
            ['name' => 'Modul Umum'],
            [
                'description' => 'Modul soal contoh untuk pengujian sistem CAT Psikiatri.',
                'status' => true,
            ],
        );

        foreach ([
            'Saya merasa mudah fokus dalam pekerjaan harian.',
            'Saya sering menunda tugas sampai mendekati tenggat waktu.',
            'Saya nyaman bekerja sama dalam tim.',
            'Saya mudah merasa cemas saat menghadapi perubahan.',
            'Saya bisa mempertahankan rutinitas kerja dengan konsisten.',
            'Saya cenderung mengambil keputusan dengan cepat.',
            'Saya menikmati tantangan yang membutuhkan analisis mendalam.',
            'Saya sering merasa lelah meskipun pekerjaan belum banyak.',
            'Saya tetap tenang saat menerima kritik.',
            'Saya mampu menyelesaikan tugas sesuai prioritas.',
        ] as $index => $text) {
            Question::query()->updateOrCreate(
                ['module_id' => $module->id, 'order_no' => $index + 1],
                [
                    'question' => $text,
                    'is_active' => true,
                ],
            );
        }

        $session = ExamSession::query()->updateOrCreate(
            ['title' => 'CAT Psikiatri Session A'],
            [
                'module_id' => $module->id,
                'start_time' => now()->subHour(),
                'end_time' => now()->addHours(3),
                'duration_minutes' => 90,
                'status' => true,
                'instructions' => "1. Baca setiap soal dengan teliti.\n2. Jangan menutup browser selama ujian.\n3. Pastikan koneksi internet stabil.\n4. Klik \"Akhiri Ujian\" hanya jika sudah selesai.",
            ],
        );

        $participant = Participant::query()->updateOrCreate(
            ['username' => 'peserta01'],
            [
                'session_id' => $session->id,
                'nik' => '3175010101010001',
                'password' => Hash::make('password123'),
                'code' => 'CAT-SESSION-A',
                'name' => 'Peserta Demo',
                'birth_place' => 'Banyuwangi',
                'birth_date' => '2000-01-01',
                'gender' => 'male',
                'phone' => '08123456789',
                'address' => 'Jakarta',
                'purpose' => 'Demo / Uji coba sistem',
                'is_finished' => false,
            ],
        );

        $sessionToken = ExamToken::query()->firstOrNew([
            'session_id' => $session->id,
        ]);

        $sessionToken->forceFill([
            'token' => $this->resolveSeedToken('123456', $sessionToken->id),
            'is_active' => true,
            'expires_at' => null,
        ])->save();
    }

    private function resolveSeedToken(string $preferredToken, ?int $ignoreId = null): string
    {
        if (! $this->isTokenUsedByOtherRecord($preferredToken, $ignoreId)) {
            return $preferredToken;
        }

        return UniqueTokenGenerator::make(
            candidate: fn (): string => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            exists: fn (string $candidate): bool => $this->isTokenUsedByOtherRecord($candidate, $ignoreId),
            maxAttempts: 1000,
        );
    }

    private function isTokenUsedByOtherRecord(string $token, ?int $ignoreId = null): bool
    {
        return ExamToken::query()
            ->where('token', $token)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
