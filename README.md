# CAT Psikiatri Examination System (Laravel 12)

Aplikasi web ujian psikiatri berbasis CAT (Computer Assisted Test) untuk mendukung alur ujian peserta, administrasi sesi, monitoring hasil, dan pengaturan lokasi RSUD.

## Stack yang digunakan

- Laravel 12
- PHP 8.2 (dikunci lewat Composer platform)
- MySQL / MariaDB (kondisi lokal XAMPP)
- Blade + Alpine.js + Axios/Fetch untuk autosave
- Vite untuk asset frontend

## Fitur utama saat ini

- Login terintegrasi di `/login` untuk peserta dan admin/dokter dengan deteksi role otomatis
- Akses ujian di `/exam/access` dengan validasi sesi dan token ujian
- Alur ujian satu soal fokus, timer, progress, dan resume attempt
- Autosave jawaban secara berkala melalui endpoint `POST /exam/save-answer`
- Submit ujian dan lock attempt melalui `POST /exam/submit`
- Halaman selesai ujian dan status attempt
- Admin dapat mengelola sesi, peserta, modul, soal, token, serta pengguna
- Bulk tambah peserta ke sesi dari akun peserta yang sudah ada
- Fitur cepat tutup/buka sesi ujian dari daftar sesi
- Pengaturan mode akses sesi:
  - `Online` untuk akses dari luar RSUD
  - `Dalam Radius RSUD` dengan geofence berbasis koordinat dan radius meter
- Halaman pengaturan lokasi RSUD untuk titik pusat dan radius global (OpenStreetMap)
- Monitoring hasil admin di `/admin/results` dengan export summary/detail CSV serta Excel-compatible
- Endpoint monitoring: `/health`, `/status`, `/version`, `/build-info`
- Fitur simulasi ujian untuk kebutuhan testing dan presentasi admin

## Lock Composer ke PHP 8.2

File `composer.json` sudah dikunci dengan:

- `require.php: ^8.2`
- `config.platform.php: 8.2.0`
- `config.platform-check: php-only`

Hal ini membantu memastikan dependency resolution tetap sesuai target environment XAMPP PHP 8.2.

## Setup lokal (XAMPP)

1. Pastikan XAMPP aktif untuk Apache dan MySQL.
2. Buat database baru, misalnya `cat_psychology`.
3. Masuk ke folder project:
   ```bash
   cd /path/to/CAT-Psikiatri
   ```
4. Instal dependency:
   ```bash
   composer install
   npm install
   ```
5. Salin file environment jika belum ada:
   ```bash
   cp .env.example .env
   ```
6. Sesuaikan konfigurasi database di `.env`:
   - `DB_CONNECTION=mysql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=3306`
   - `DB_DATABASE=cat_psychology`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=`
7. Jalankan migrasi dan seeder:
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```
8. Jalankan aplikasi:
   ```bash
   php artisan serve
   npm run dev
   ```

## Akun demo seed

- Peserta:
  - Username: `peserta01`
  - Password: `password123`
  - Token ujian: `123456`
- Admin:
  - URL login: `/login`
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
- UI ujian: `resources/views/exam/index.blade.php`

## Catatan penting

- Semua route admin diproteksi middleware `admin.auth`.
- Route `Lokasi RSUD` hanya dapat diakses oleh role admin, bukan dokter.
- Hardening rule admin:
  - peserta dengan attempt aktif belum submit tidak dapat dipindah sesi
  - sesi yang sudah memiliki attempt tidak dapat mengubah jam mulai/selesai/durasi
- Token ujian dikelola melalui menu `Token Ujian` di `/admin/tokens` dan berlaku untuk sesi yang dipilih saat peserta mengakses `/exam/access`.
- Untuk kebutuhan testing, bypass token dapat diaktifkan lewat `.env`:
  - `EXAM_TOKEN_BYPASS=true`
  - Wajib dikembalikan ke `false` setelah testing selesai.
- Test database untuk skenario admin dan peserta membutuhkan ekstensi `pdo_sqlite`.

## Mode shared hosting tanpa `storage:link`

Project ini sudah disiapkan untuk mode no symlink:

- `FILESYSTEM_DISK=public`
- Disk `public` diarahkan ke `public/uploads` (bukan `storage/app/public`)
- URL file publik memakai pola `APP_URL/uploads/...`

Langkah minimum di hosting:

1. Pastikan folder `public/uploads` ada.
2. Pastikan folder tersebut writable oleh web server.
3. Tidak perlu menjalankan `php artisan storage:link`.

## Panduan UX writing

Untuk menjaga konsistensi teks antarmuka dalam bahasa Indonesia yang sederhana, gunakan panduan berikut:

- `docs/ux-writing-guide-id.md`
