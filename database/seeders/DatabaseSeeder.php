<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ExamSession;
use App\Models\ExamLocationSetting;
use App\Models\ExamToken;
use App\Models\MasterCategory;
use App\Models\Participant;
use App\Models\Question;
use App\Models\User;
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

        ExamLocationSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'center_latitude' => -6.2015000,
                'center_longitude' => 106.8166667,
                'allowed_radius_meters' => 500,
            ],
        );

        $session = ExamSession::query()->updateOrCreate(
            ['title' => 'CAT Psikiatri Session A'],
            [
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

        $generalCategory = MasterCategory::query()->updateOrCreate(
            ['slug' => 'general'],
            [
                'name' => 'General',
                'is_active' => true,
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
                ['order_no' => $index + 1],
                [
                    'category_id' => $generalCategory->id,
                    'category' => 'General',
                    'question' => $text,
                    'is_active' => true,
                ],
            );
        }
    }

    private function resolveSeedToken(string $preferredToken, ?int $ignoreId = null): string
    {
        if (! $this->isTokenUsedByOtherRecord($preferredToken, $ignoreId)) {
            return $preferredToken;
        }

        $maxAttempts = 1000;
        $attempt = 0;
        do {
            $candidate = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $attempt++;
        } while ($this->isTokenUsedByOtherRecord($candidate, $ignoreId) && $attempt < $maxAttempts);

        if ($attempt >= $maxAttempts) {
            throw new \RuntimeException('Gagal membuat token seeder yang unik setelah 1000 percobaan.');
        }

        return $candidate;
    }

    private function isTokenUsedByOtherRecord(string $token, ?int $ignoreId = null): bool
    {
        return ExamToken::query()
            ->where('token', $token)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
