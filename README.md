# CAT Psikiatri Examination System (Laravel 12)

Aplikasi web ujian psikiatri gaya CAT (Computer Assisted Test) sesuai dokumen `cat_psychology_system_documentation_master (1).md`.

## Stack

- Laravel 12
- PHP 8.2 (locked via Composer platform)
- MySQL / MariaDB (XAMPP local)
- Blade + Alpine.js + Fetch API (autosave)

## Fitur MVP yang sudah dibuat

- Login peserta (`/login`)
- Validasi token sesi ujian (`/exam/access`)
- Create / resume attempt tunggal per peserta
- Halaman ujian one-question focus + timer + progress
- Autosave jawaban YES/NO via AJAX (`POST /exam/save-answer`)
- Submit ujian dan lock attempt (`POST /exam/submit`)
- Halaman selesai ujian
- Login terpadu (`/login`) untuk peserta dan admin/dokter dengan deteksi role otomatis
- CRUD admin: sesi, peserta, soal
- Bulk tambah peserta per sesi dari data akun peserta existing melalui halaman khusus (`/admin/sessions/{session}/participants/bulk`)
- Aksi cepat tutup/buka sesi ujian dari list sesi untuk kebutuhan pasca-tes
- Pengaturan mode akses sesi ujian:
  - `Online` (bisa dari luar RSUD)
  - `Dalam Radius RSUD` (dibatasi geofence berdasarkan titik koordinat + radius meter)
- Halaman standalone `Lokasi RSUD` untuk atur titik pusat + radius geofence global (OpenStreetMap)
- CRUD admin: token ujian (`/admin/tokens`) berlaku per sesi
- Generator token otomatis dari halaman form token
- CRUD master kategori (`/admin/categories`)
- Filter & search cepat pada list sesi, peserta, dan soal
- Bulk action pada list sesi, peserta, dan soal
- Monitoring hasil admin (`/admin/results`)
- Export hasil:
  - Summary CSV (`/admin/results/export/csv`)
  - Summary Excel-compatible (`/admin/results/export/excel`)
  - Detail per-attempt CSV (`/admin/results/{attempt}/export/csv`)

## Lock Composer ke PHP 8.2

`composer.json` sudah dikunci:

- `require.php: ^8.2`
- `config.platform.php: 8.2.0`
- `config.platform-check: php-only`

Ini memastikan dependency resolution mengikuti target XAMPP PHP 8.2 walaupun CLI host lebih baru.

## Setup Lokal (XAMPP)

1. Pastikan XAMPP aktif:
   - Apache
   - MySQL
2. Buat database baru, contoh: `cat_psychology`
3. Masuk folder project:
   ```bash
   cd /home/viasco/Koding/cat-pyschology
   ```
4. Install dependency (sudah terpasang saat scaffold, jalankan lagi bila perlu):
   ```bash
   composer install
   npm install
   ```
5. Salin env (jika belum ada) dan sesuaikan:
   ```bash
   cp .env.example .env
   ```
6. `.env` default sudah diarahkan ke XAMPP MySQL:
   - `DB_CONNECTION=mysql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=3306`
   - `DB_DATABASE=cat_psychology`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=`
7. Generate key + migrate + seed:
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```
8. Jalankan app:
   ```bash
   php artisan serve
   npm run dev
   ```

## Akun Demo Seeder

- Peserta:
  - Username: `peserta01`
  - Password: `password123`
  - Token Ujian: `123456`
- Admin:
  - URL Login: `/login`
  - Username: `admin01`
  - Password: `password123`

## Struktur penting

- Routes: `routes/web.php`
- Middleware: `app/Http/Middleware/ExamGuard.php`
- Services:
  - `app/Services/StartExamService.php`
  - `app/Services/SaveAnswerService.php`
  - `app/Services/SubmitExamService.php`
  - `app/Services/ValidateSessionService.php`
- Exam UI: `resources/views/exam/index.blade.php`

## Catatan

- Semua route admin sudah diproteksi middleware `admin.auth`.
- Khusus route `Lokasi RSUD` diproteksi middleware `role:admin` (dokter tidak dapat mengakses).
- Hardening rule admin:
  - Peserta dengan attempt aktif/belum submit tidak bisa dipindah sesi
  - Sesi yang sudah punya attempt tidak bisa mengubah start/end/duration
- Token ujian dikelola lewat menu `Token Ujian` (`/admin/tokens`) dan berlaku untuk sesi yang dipilih saat peserta mengakses `/exam/access`.
- Untuk kebutuhan testing, bypass token dapat diaktifkan via `.env`:
  - `EXAM_TOKEN_BYPASS=true` (token tidak divalidasi saat akses ujian)
  - **Wajib** dikembalikan ke `false` setelah testing.
- Test database (`AdminAuthTest`, `ParticipantExamFlowTest`) membutuhkan ekstensi `pdo_sqlite` untuk berjalan penuh di local testing.

## Mode Shared Hosting Tanpa `storage:link`

Project ini sudah disiapkan untuk mode **no symlink**:

- `FILESYSTEM_DISK=public`
- Disk `public` diarahkan ke `public/uploads` (bukan `storage/app/public`)
- URL file publik: `APP_URL/uploads/...`

Langkah minimum di hosting:

1. Pastikan folder `public/uploads` ada.
2. Pastikan folder tersebut writable oleh web server.
3. Tidak perlu menjalankan `php artisan storage:link`.

## Panduan UX Writing

Untuk konsistensi teks antarmuka (bahasa Indonesia sederhana untuk dokter/admin dan peserta), gunakan panduan berikut:

- `docs/ux-writing-guide-id.md`
