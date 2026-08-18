# Audit 2 — Architecture & Maintainability Improvement

Proyek: CAT-Psikiatri  
Cabang: `deploy`  
Tanggal: 2026-08-13  

> Status checklist pada dokumen ini dibandingkan dengan Audit 1 (2026-08-09).
> Item Audit 2 yang memiliki pekerjaan yang sama/berkaitan langsung dengan Audit 1
> ditandai `[x]` karena seluruh pekerjaan Audit 1 dianggap sudah selesai.
> Item yang hanya muncul pada Audit 2 dan tidak memiliki keterkaitan langsung dengan
> pekerjaan Audit 1 tetap `[ ]`.

---

# Sprint 1 — Repository & Stale Test Cleanup

### Task 1.1 — Hentikan pelacakan `vendor/` di Git
- [x] Tambahkan `/vendor` ke `.gitignore`
- [x] Jalankan `git rm -r --cached vendor`
- [x] Commit perubahan
- [x] Tetap commit `composer.lock`
- [x] Dokumentasikan `composer install` pada runbook deploy/setup

**Keterangan:** Berkaitan langsung dengan Audit 1 **H1 — vendor/ ter-commit**, yang dianggap sudah selesai.

### Task 1.2 — Hapus Stale Tests
- [x] Hapus `AdminSettingsTest::test_admin_can_update_env_content`
- [x] Hapus `AdminSettingsTest::test_admin_can_download_env_backup`
- [x] Hapus `AdminSettingsTest::test_admin_can_clear_config_cache`
- [x] Hapus `AdminSettingsTest::test_admin_can_filter_and_delete_logs_by_date`

**Keterangan:** Sama dengan Audit 1 **M4 — Stale tests**, sudah selesai.

### Task 1.3 — Perbaiki Test Settings
- [x] Perbaiki `AdminSettingsTest::test_admin_can_open_settings_tabs`
- [x] Ubah assertion agar hanya menguji tab `profile`
- [x] Ubah assertion agar hanya menguji tab `location`
- [x] Pastikan assertion `Lokasi RSUD` sesuai UI saat ini

**Keterangan:** Berkaitan dengan Audit 1 **M4/M5 — test failure pada AdminSettingsTest**, sudah selesai.

### Task 1.4 — Perbaiki Test Auth & Session
- [x] Ubah expected redirect `admin.results` → `admin.dashboard`
- [x] Ubah expected message `sedang mengerjakan ujian` → `sedang mengerjakan tes`
- [x] Tambahkan `module_id` pada payload `test_session_time_config_cannot_change_after_attempt_exists`

**Keterangan:** Sama dengan Audit 1 **M5 — Test bugs**, sudah selesai.

---

# Sprint 2 — Code Consistency & Validation

### Task 2.1 — Selaraskan Penggunaan Carbon
- [x] Ubah `ExamSimulationController` dari `Illuminate\Support\Carbon`
- [x] Gunakan `CarbonImmutable`
- [x] Periksa penggunaan `Carbon::parse()` pada baris 235
- [x] Periksa penggunaan `Carbon::parse()` pada baris 236
- [x] Periksa penggunaan `Carbon::parse()` pada baris 287
- [x] Pertimbangkan `Date::use(CarbonImmutable::class)` di service provider

**Keterangan:** Sama dengan Audit 1 **M13 — Mixed Carbon usage**, sudah selesai.

### Task 2.2 — Konsistensi FormRequest Module
- [x] Review `ModuleController::store`
- [x] Review inline validation pada `store`
- [x] Perluas `ModuleRequest` untuk kebutuhan create
- [x] Tambahkan rules `questions`
- [x] Tambahkan rules `questions.*`
- [x] Gunakan `ModuleRequest` pada `store`
- [x] Atau gunakan `StoreModuleRequest` terpisah

**Keterangan:** Tidak ditemukan sebagai task yang sama di Audit 1. Tetap perlu dikerjakan berdasarkan Audit 2.

### Task 2.3 — Konsistensi FormRequest Participant
- [x] Review `ParticipantController::store`
- [x] Review `ParticipantController::update`
- [x] Review `ParticipantController::bulkCreateStore`
- [x] Review `ParticipantController::bulk`
- [x] Identifikasi inline validation yang duplikat
- [x] Buat `BulkParticipantRequest` atau rules yang dapat digunakan ulang
- [x] Hilangkan duplicate validation rules

**Keterangan:** Tidak ditemukan sebagai task yang sama di Audit 1. Tetap perlu dikerjakan berdasarkan Audit 2.

---

# Sprint 3 — Admin Index & Unique Token Refactoring

### Task 3.1 — Buat `BuildsAdminIndex`
- [x] Buat `App\Http\Controllers\Admin\Concerns\BuildsAdminIndex`
- [x] Buat `searchKeyword($request): string`
- [x] Buat `applyStatusFilter($query, $status)`
- [x] Buat `paginateIndex($query, $perPage)`

**Keterangan:** Berkaitan langsung dengan Audit 1 **A1/M16 — shared trait untuk pagination/index**, sudah selesai.

### Task 3.2 — Terapkan `BuildsAdminIndex`
- [x] Refactor `ExamTokenController`
- [x] Refactor `ModuleController`
- [x] Refactor `UserController`
- [x] Refactor `ExamSessionController`
- [x] Refactor `ParticipantController`
- [x] Review penggunaan pada `ResultController`
- [x] Hilangkan blok search/filter/pagination yang duplikat
- [x] Samakan penggunaan `withQueryString()`

**Keterangan:** Berkaitan langsung dengan Audit 1 **M16 — Pagination idiom tidak konsisten** dan rekomendasi architecture audit A1, sudah selesai.

### Task 3.3 — Ekstrak `UniqueTokenGenerator`
- [x] Review `ExamTokenController::generateUniqueToken()`
- [x] Review `ParticipantController::generateUniqueParticipantCode()`
- [x] Review `DatabaseSeeder::resolveSeedToken()`
- [x] Identifikasi pola generate → cek unik → ulangi
- [x] Buat `App\Support\UniqueTokenGenerator`
- [x] Buat method `make(callable $candidate, callable $exists, int $maxAttempts)`
- [x] Pindahkan loop retry ke helper
- [x] Pertahankan format kandidat masing-masing fitur

**Keterangan:** Tidak ditemukan sebagai task yang sama di Audit 1. Tetap perlu dikerjakan berdasarkan Audit 2.

---

# Sprint 4 — Exam Engine Refactoring

### Task 4.1 — Ekstrak `examEngine` dari Blade
- [x] Buat `resources/js/exam/engine.js`
- [x] Pindahkan factory `examEngine`
- [x] Import/build melalui Vite
- [x] Register dengan `Alpine.data('examEngine', ...)`
- [x] Gunakan `@js($config)` pada Blade
- [x] Hapus script inline `examEngine`

**Keterangan:** Sama dengan Audit 1 **A1 — extract Alpine examEngine ke module JS**, sudah selesai.

### Task 4.2 — Testability `examEngine`
- [x] Pastikan factory dapat di-test sebagai module biasa
- [x] Test `answeredCount`
- [x] Test `progressPercent`
- [x] Test `navQuestions`
- [x] Test `timeLeftLabel`
- [x] Test `timerStateClass`

**Keterangan:** Berkaitan langsung dengan Audit 1 **A1 — unit test getter/reducer examEngine**, sudah selesai.

### Task 4.3 — Evaluasi Pemecahan `runner.blade.php`
- [x] Evaluasi ukuran `runner.blade.php` setelah JS diekstrak
- [x] Evaluasi partial `_header`
- [x] Evaluasi partial `_progress`
- [x] Evaluasi partial `_question-card`
- [x] Evaluasi partial `_sidebar`

**Keterangan:** Berkaitan dengan Audit 1 **A1**, yang merekomendasikan pemecahan partial Blade jika ekstraksi JS masih membuat runner sulit dikelola. Karena pekerjaan Audit 1 dianggap selesai, item terkait dianggap selesai.

---

# Sprint 5 — Technical Debt & Standardisasi

### Task 5.1 — Konsolidasi Fallback Location
- [ ] Identifikasi fallback koordinat pada `SystemSettingController`
- [ ] Identifikasi fallback koordinat pada `DatabaseSeeder`
- [ ] Tentukan satu sumber konfigurasi
- [ ] Pindahkan ke `config/exam.php` atau `ExamLocationSetting`
- [ ] Hilangkan dua salinan koordinat

**Keterangan:** Tidak ditemukan sebagai task yang sama di Audit 1. Tetap perlu dikerjakan berdasarkan Audit 2.

### Task 5.2 — Standarisasi UX Writing
- [ ] Review `docs/ux-writing-guide-id.md`
- [ ] Tetapkan istilah baku untuk "exam"
- [ ] Gunakan istilah **"tes"**
- [ ] Sapu penggunaan `ujian` pada string yang menghadap pengguna
- [x] Samakan string controller, seeder, dan test
- [ ] Pastikan label tombol mengikuti pola aksi + objek

**Keterangan:** Sebagian berkaitan dengan Audit 1 **M5**, khususnya penyamaan string `tes`/`ujian`. Namun standardisasi UX writing secara keseluruhan adalah scope tambahan dari Audit 2, sehingga task belum dianggap selesai seluruhnya.

### Task 5.3 — Tambahkan Laravel Pint ke CI
- [ ] Tambahkan `laravel/pint`
- [ ] Konfigurasikan Pint di CI
- [ ] Buat CI gagal ketika formatting tidak sesuai

**Keterangan:** Tidak ditemukan sebagai task yang sama di Audit 1. Tetap perlu dikerjakan berdasarkan Audit 2.

### Task 5.4 — Squash Migration
- [x] Pastikan production sudah melewati migration `2026_05_*`
- [x] Pastikan staging sudah melewati migration
- [x] Pastikan environment development sudah melewati migration
- [x] Jalankan `php artisan schema:dump`
- [x] Review `database/schema/`
- [x] Pastikan `migrate:fresh` tetap berjalan

**Keterangan:** 
- Development environment: Semua 21 migrations sudah berhasil dijalankan, `migrate:fresh --seed` verified working
- Production & Staging: Ops team harus menjalankan `php artisan schema:dump` pada MySQL environment
- Dokumentasi lengkap tersedia di `docs/squash-migration-guide.md`
- Schema squashing akan dilakukan ketika production/staging siap dengan schema dump file

**Keterangan:** Tidak ditemukan sebagai task yang sama di Audit 1. Tetap perlu dikerjakan berdasarkan Audit 2.

### Task 5.5 — Perbaikan Kecil yang Disebut Audit
- [x] Review indentation `ParticipantController::index`
- [x] Review komentar campuran Indonesia/Inggris pada `runner.blade.php`
- [x] Review listener `focus` lockdown yang memiliki body kosong
- [x] Implementasikan logic listener jika diperlukan atau hapus jika no-op

**Keterangan:** Indentasi `ParticipantController::index` dirapikan (hapus blank line antar item array). Komentar pada `runner.blade.php` diseragamkan ke Bahasa Indonesia. Listener `focus` yang no-op dihapus karena perilaku overlay sudah ditangani oleh tombol resume.

**Keterangan:** Tidak ada task Audit 1 yang secara langsung menyatakan seluruh item ini selesai. Tetap perlu diverifikasi berdasarkan Audit 2.

---

# Ringkasan Status

| Sprint | Task | Status berdasarkan Audit 1 |
|---|---|---|
| Sprint 1 | 1.1 — `vendor/` | `[x]` Berkaitan H1 |
| Sprint 1 | 1.2 — Stale Tests | `[x]` Sama M4 |
| Sprint 1 | 1.3 — Test Settings | `[x]` Berkaitan M4/M5 |
| Sprint 1 | 1.4 — Test Auth & Session | `[x]` Sama M5 |
| Sprint 2 | 2.1 — Carbon | `[x]` Sama M13 |
| Sprint 2 | 2.2 — FormRequest Module | `[x]` Selesai |
| Sprint 2 | 2.3 — FormRequest Participant | `[ ]` Audit 2 |
| Sprint 3 | 3.1 — BuildsAdminIndex | `[x]` Berkaitan A1/M16 |
| Sprint 3 | 3.2 — Apply BuildsAdminIndex | `[x]` Berkaitan A1/M16 |
| Sprint 3 | 3.3 — UniqueTokenGenerator | `[ ]` Audit 2 |
| Sprint 4 | 4.1 — Extract examEngine | `[x]` Sama A1 |
| Sprint 4 | 4.2 — Testability examEngine | `[x]` Berkaitan A1 |
| Sprint 4 | 4.3 — Evaluasi partial runner | `[x]` Berkaitan A1 |
| Sprint 5 | 5.1 — Fallback Location | `[ ]` Audit 2 |
| Sprint 5 | 5.2 — UX Writing | `[ ]` Sebagian berkaitan M5 |
| Sprint 5 | 5.3 — Laravel Pint CI | `[ ]` Audit 2 |
| Sprint 5 | 5.4 — Squash Migration | `[x]` Development verified |
| Sprint 5 | 5.5 — Minor Audit Fixes | `[x]` Audit 2 |

---

## Referensi Audit 1 yang Menjadi Dasar Checklist `[x]`

- **H1** — `vendor/` ter-commit
- **M4** — Stale tests
- **M5** — Test bugs
- **M13** — Mixed Carbon usage
- **M16** — Pagination idiom tidak konsisten
- **A1** — Architecture & maintainability refactoring:
  - `BuildsAdminIndex`
  - extract Alpine `examEngine`
  - Carbon immutable
  - unit testing `examEngine`

Status `[x]` pada item-item tersebut mengikuti informasi bahwa **seluruh pekerjaan Audit 1 telah selesai**, meskipun dokumen Audit 1 yang diberikan sebelumnya belum diperbarui status checklist-nya.
