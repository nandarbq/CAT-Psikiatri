# Render MarkdownAudit Konsolidasi — CAT-Psikiatri

Tanggal: 2026-08-09  
Cabang: deploy  
HEAD: 9c5846d  
Tim: Nandarbq (28 commit), Devi Aulya (14), Bimo Al-Farrabi (9)  
Karakteristik: Proyek Laravel 12 PHP 8.2; kontributor mayoritas magang; vendor/ ter-commit di repo.

Dokumen ini menggabungkan hasil dari empat audit paralel:
- Security audit (Oracle)
- Logic & correctness audit (Oracle)
- Architecture & maintainability audit (Oracle) — lihat juga docs/architecture-audit-2026-08-09.md
- Test quality & coverage audit (Oracle)

Tujuan: memberi peta jalan perbaikan yang terprioritas untuk tim yang sebagian besar magang.

## TL;DR — Yang paling penting

| # | Isu | Severity | Effort |
|---|-----|----------|--------|
| 1 | POST /exam/save-answer tidak membatasi question_id ke modul percobaan | Critical | S |
| 2 | Tidak ada rate limit pada /login dan /exam/access | Critical | S |
| 3 | vendor/ ter-commit di repo | High | S |
| 4 | cascadeOnDelete pada exam_attempts.session_id & participants.session_id | High | S–M |
| 5 | 8 test gagal — 5 stale, 3 salah asersi/test | Medium | S |
| 6 | CSV formula injection di semua kolom teks | Medium | S |
| 7 | Geofence kurangi jarak dengan accuracy | Medium | S |
| 8 | Tidak ada unit test untuk 4 service inti | Medium | M |
| 9 | current_question bisa rollback ke soal lebih awal karena autosave last-writer-wins | High | S |
| 10 | Race condition pada SubmitExamService::submit (double-submit) | High | S |

Kalau cuma sempat satu hal: fix #1. Itu eksploit aktif.

---

# Bagian 1 — Critical findings

## C1. Cross-module answer injection

Lokasi:
- app/Http/Controllers/ExamController.php:100-106
- app/Http/Requests/SaveAnswerRequest.php:22-25
- app/Services/SaveAnswerService.php:17-44

Skenario:
Peserta mengirim POST save-answer dengan question_id dari modul lain (atau soal non-aktif). Server hanya validasi integer, lalu upsert ke participant_answers.

Efek:
- peserta bisa enumerasi seluruh bank soal
- record jawaban bisa berisi snapshot soal dari modul yang bukan haknya
- current_question di-forceFill dari order_no soal asing — cursor melompat

Fix:
```php
// ExamController::saveAnswer
$question = Question::query()
    ->select(['id','question','order_no'])
    ->where('module_id', $attempt->session->module_id)
    ->where('is_active', true)
    ->findOrFail((int) $validated['question_id']);
```

Tambah guard yang sama di SaveAnswerService::save (defense-in-depth).

## C2. Token 6-digit tanpa rate limit

Lokasi:
- routes/web.php:37,45
- app/Http/Controllers/ParticipantAuthController.php:31-74
- app/Services/StartExamService.php:27-51
- app/Http/Controllers/Admin/ExamTokenController.php:165-182

Skenario:
6 digit = 1.000.000 kombinasi. Tidak ada throttle di mana pun. /login mencoba tabel users dulu lalu participants — jadi endpoint yang sama juga jadi vektor brute-force admin.

Fix:
- Tambah throttle:5,1 di POST /login dan POST /exam/access
- Tambah throttle:10,1 di POST /admin/tokens/generate
- Pertimbangkan key throttle berdasarkan participant_id supaya IP rotation tidak bantu attacker
- Satukan pesan error token jadi generik ("Token tidak valid atau tidak dapat digunakan") untuk menghilangkan oracle

---

# Bagian 2 — High findings

## H1. vendor/ ter-commit (9.843 file)

Lokasi:
- repo root
- git ls-files vendor | wc -l → 9843
- .gitignore tidak berisi /vendor

Dampak:
- composer audit terhadap lockfile tidak berlaku untuk file yang di-deploy
- backdoor di vendor/ lolos dari review
- ukuran repo membengkak & konflik merge hosting

Fix:
- git rm -r --cached vendor
- tambahkan /vendor ke .gitignore
- deploy dengan composer install --no-dev --optimize-autoloader dari composer.lock
- tambah composer audit ke CI

## H2. Cascade-delete destroys history

Lokasi:
- database/migrations/2026_05_08_034238_create_exam_attempts_table.php:16
- database/migrations/2026_05_08_034236_create_participants_table.php:15

Skenario:
ExamSessionController::destroy dan ::bulk bebas memanggil $session->delete(). Karena cascadeOnDelete, satu klik admin = seluruh attempt + jawaban + peserta hilang.

Fix:
- Ubah FK ke restrictOnDelete()
- Tambah SoftDeletes ke ExamSession, Participant, ExamAttempt
- Tolak delete saat attempts()->exists() di level aplikasi (cermin guard yang sudah ada di QuestionController::destroy dan ExamSessionController::update)

## H3. Race condition pada SubmitExamService::submit

Lokasi:
- app/Services/SubmitExamService.php:22-44

Skenario:
Double-klik submit / retry jaringan → dua request bersamaan. Keduanya lewat ensureAttemptAccessible, dua-duanya forceFill + save. Token last_used_at dan participants.is_finished di-update dua kali; observer fires dua kali.

Fix:
- Di dalam transaction, ExamAttempt::where('id', $attempt->id)->lockForUpdate()->first()
- lalu re-check is_submitted
- atau pakai conditional UPDATE: WHERE id=? AND is_submitted=0 dan periksa affected rows

## H4. current_question rollback karena last-writer-wins

Lokasi:
- app/Services/SaveAnswerService.php:39-43

Skenario:
Peserta jawab Q5 lalu buka Q3 untuk review, autosave Q3 → current_question jadi 3. Besok resume, cursor landing di Q3, padahal sudah pernah di Q5.

Fix:
- Pakai GREATEST(current_question, :order_no) di raw UPDATE
- atau hanya update jika :order_no > current_question

## H5. session_token rotation tidak kick out device lama

Lokasi:
- app/Services/StartExamService.php:91
- app/Http/Middleware/ExamGuard.php:64-72

Skenario:
Device A mulai ujian, Device B mulai ujian dengan akun sama. startOrResume regenerate session_token di Device B. Tapi ExamGuard self-heal: ketika Device A kirim autosave, middleware deteksi mismatch tapi langsung timpa session token dengan nilai DB → Device A tetap bisa lanjut.

Fix:
- Di ExamGuard, ketika hash_equals gagal DAN $sessionToken !== '' (bukan first request setelah login), perlakukan sebagai takeover dan deny()
- Auto-heal hanya untuk first request

## H6. ExamGuard merevalidasi lokasi setiap request → lock-out mid-ujian

Lokasi:
- app/Http/Middleware/ExamGuard.php:74-82

Skenario:
Sesi onsite_radius, peserta mulai ujian di dalam radius, lalu keluar gedung (masih WiFi). Autosave berikutnya kirim koordinat baru → ensureSessionLocationAccess reject. Peserta locked out dari ujiannya sendiri.

Fix:
Pilihan bisnis:
- cek lokasi hanya di startOrResume
- atau tetap cek di autosave tapi bypass di submit agar jawaban tersimpan

## H7. Geofence bypass lewat header X-Exam-Accuracy

Lokasi:
- app/Services/ValidateSessionService.php:58-74
- app/Http/Middleware/ExamGuard.php:111-134

Skenario:
Klien kirim X-Exam-Accuracy: 50000 (5 km). Kode mengurangi jarak dengan accuracy sehingga effectiveDistance = 0. Radius check lolos dari mana saja.

Fix:
- Jangan kurangi jarak dengan accuracy
- Pakai accuracy sebagai quality gate: reject jika accuracy > config('exam.max_accuracy_meters', 100)
- Bandingkan distanceMeters mentah
- Turunkan cap di StartExamRequest dari max:50000 ke max:500
- Untuk ujian berisiko tinggi, tambah kode check-in proktor

## H8. Admin hanya dilindungi admin.auth, bukan role:admin

Lokasi:
- app/Http/Middleware/AdminAuthenticate.php
- routes/web.php:60-114

Skenario:
AdminAuthenticate cuma cek User logged in. Middleware role:admin hanya di sub-group (users, simulation, settings). Sesi/peserta/token/modul/soal/results — semua di bawah admin.auth saja.

Fix:
- Terapkan role:admin ke seluruh grup admin.auth, bukan hanya sub-group

## H9. Geofence fallback pakai GPS cached dari session

Lokasi:
- app/Http/Middleware/ExamGuard.php:111-134

Skenario:
Peserta start ujian di dalam radius, GPS disimpan di session. Lalu matikan GPS / deny permission. Autosave berikutnya tidak kirim header, middleware kembalikan koordinat cached. Check lolos walaupun peserta sudah 50 km.

Fix:
- Simpan exam_geo_last_at timestamp
- Jika lebih tua dari N detik, perlakukan sebagai missing dan deny untuk onsite_radius

---

# Bagian 3 — Medium findings

## M1. CSV formula injection di semua kolom teks

Lokasi:
- app/Http/Controllers/Admin/ResultController.php:139-152,193-204,233-240

Skenario:
Admin atau peserta dengan nama yang dikontrol admin bernama =cmd|'/c calc'!A1. CSV menulis cell apa adanya. Saat dibuka di Excel, formula berjalan. Hanya NIK yang dibungkus dengan ="...".

Fix:
```php
private function csvSafe(string $value): string
{
    $value = trim($value);
    if ($value === '' || str_starts_with($value, ['=', '+', '-', '@', "\t", "\r"])) {
        return ' ' . $value;
    }

    return $value;
}
```

## M2. EXAM_TOKEN_BYPASS tanpa production guard

Lokasi:
- config/exam.php:6

Skenario:
Set EXAM_TOKEN_BYPASS=true di production .env untuk demo dan lupa kembalikan. Semua token check dilewati.

Fix:
- Hard-guard di service provider: jika environment('production') && config('exam.token_bypass_enabled') maka throw RuntimeException
- Atau emit warning log di setiap request yang kena bypass

## M3. Tidak ada unit test untuk 4 service inti

Lokasi:
- app/Services/StartExamService.php
- app/Services/SaveAnswerService.php
- app/Services/SubmitExamService.php
- app/Services/ValidateSessionService.php

Fix:
- Tambah unit test untuk keempat service + GeoDistance (5–6 koordinat dengan jarak yang sudah diketahui)

## M4. Stale tests (5 dari 8 failure)

Lokasi:
- tests/Feature/AdminSettingsTest.php

Skenario:
Test mengacu route admin.settings.env.update, admin.settings.env.backup, admin.settings.cache.config-clear, admin.settings.logs.delete — yang sudah dihapus.

Fix:
- Hapus test
- Hapus setUp/tearDown yang menulis ke .env dan storage/logs/laravel.log asli

## M5. Test bugs (3 dari 8 failure)

Lokasi:
- tests/Feature/AdminAuthTest.php:73,354,528-537

Detail:
- assertRedirect(route('admin.results')) tapi login redirect ke admin.dashboard
- assertion string "sedang mengerjakan ujian" tapi message asli "sedang mengerjakan tes"
- PUT payload tidak kirim module_id sehingga validasi reject lebih awal

Fix:
- Ganti route ke admin.dashboard
- Ganti string ke "sedang mengerjakan tes" atau drop assertion pada flash message
- Tambahkan 'module_id' => $session->module_id ke PUT payload

## M6. Pre-check-then-update race pada (session_id, code) unique di bulk move

Lokasi:
- app/Http/Controllers/Admin/ExamSessionController.php:221-254

Skenario:
Dua admin bulk-move bersamaan ke sesi yang sama. Keduanya lewat pre-check codeConflictExists. Keduanya commit. Constraint unique fire di commit kedua, seluruh batch rollback.

Fix:
- Wrap setiap $participant->save() dalam try/catch QueryException kode 23000
- increment skippedCodeConflict, continue

## M7. Module destroy — manual cascade tanpa lock

Lokasi:
- app/Http/Controllers/Admin/ModuleController.php:121-124

Skenario:
Pre-check pass. Di dalam transaction, $module->questions()->delete() berjalan, tapi questions.module_id adalah restrictOnDelete. Jika ada insert soal oleh admin lain di antara pre-check dan delete, transaction rollback dengan error SQLSTATE generik.

Fix:
- lockForUpdate() di awal transaction pada module row
- re-check di dalam lock
- convert QueryException menjadi pesan user-friendly

## M8. Question reorder tanpa lock

Lokasi:
- app/Http/Controllers/Admin/QuestionController.php:115-121

Skenario:
Dua admin drag-drop reorder bersamaan. Tanpa row lock, UPDATE interleaving menghasilkan urutan campur.

Fix:
- SELECT id FROM questions WHERE module_id=? ORDER BY order_no FOR UPDATE sebelum UPDATE di dalam transaction

## M9. ExamController::show — fallback currentIndex ke 0 ketika soal is_active=false

Lokasi:
- app/Http/Controllers/ExamController.php:70-71

Skenario:
Peserta sampai Q10. Admin set Q10 is_active=false. Resume → search miss → landing di Q1 shuffled. Disorienting.

Fix:
- Cari order_no tertinggi di $answers yang masih ada di $questions, pakai itu sebagai index

## M10. sessionAttemptNumber selalu 1 (dead code)

Lokasi:
- app/Http/Controllers/ExamController.php:74-82

Skenario:
Query count attempt dengan id <= $attempt->id per (participant_id, session_id). Karena unique(participant_id, session_id), hasilnya selalu 1.

Fix:
- Hapus query dan field view
- atau jika multi-attempt fitur masa depan, drop unique constraint dan tambah attempt_number column

## M11. ExamGuard tidak verifikasi attempt.session_id === participant.session_id

Lokasi:
- app/Http/Middleware/ExamGuard.php:28-53

Skenario:
Peserta dipindah sesi via participantsBulkStore. Sesi lama bisa masih punya attempt in-progress. Middleware ambil attempt latest non-submitted tanpa cek apakah attempt-nya milik sesi saat ini.

Fix:
- Setelah load attempt, assert (int) $attempt->session_id === (int) $attempt->participant->session_id. Deny jika beda.

## M12. EnsureUserRole redirect ke admin.dashboard (confusing)

Lokasi:
- app/Http/Middleware/EnsureUserRole.php:27-29

Fix:
- Bounce ke halaman utama non-admin (login/dashboard peserta), atau tampilkan 403

## M13. Mixed Carbon usage (mutable vs immutable)

Lokasi:
- app/Http/Controllers/Admin/ExamSimulationController.php:13
- app/Http/Controllers/Admin/ExamSessionController.php:12
- app/Services/ValidateSessionService.php

Fix:
- Standarkan ke CarbonImmutable di semua tempat

## M14. Tidak ada factory untuk model bisnis

Lokasi:
- database/factories/ — hanya UserFactory.php

Fix:
- Tambah factory untuk 6 model bisnis

## M15. AdminSettingsTest menulis ke .env dan log host asli

Lokasi:
- tests/Feature/AdminSettingsTest.php:21-49

Fix:
- Hapus seluruh test class setelah M4

## M16. Pagination idiom tidak konsisten

Lokasi:
- ParticipantController:44
- ResultController:28

Fix:
- Standarkan via shared trait

## M17. Tidak ada E2E test

Fix:
- Plan satu path E2E (Dusk/Playwright) untuk login → start exam → autosave → submit, plus GPS out-of-radius denial

---

# Bagian 4 — Low / FYI

- Password policy lemah (min:8 tanpa kompleksitas). Pakai Password::min(10)->mixedCase()->numbers() untuk admin.
- Session cookie encryption off (SESSION_ENCRYPT=false). Set true di production.
- Monitoring reporter mengirim fullUrl() termasuk query string → PII bocor ke log external. Ganti dengan request()->url() (path only).
- Monitoring smoke-test route echo back monitoring_endpoint — jangan expose configured URL.
- Reporter swallow exception di empty catch. Log ke local channel.
- SubmitExamService update SEMUA active token, bukan token yang dipakai. Saat ini unique('session_id') jadi max 1 token per session, jadi bukan masalah.
- Hard-delete session tanpa confirm gate — tambah SoftDeletes atau confirm typing-judul.

---

# Bagian 5 — Pola yang sudah benar (positive observations)

- Session fixation handled correctly. ParticipantAuthController::login memanggil invalidate() + regenerateToken() sebelum Auth::login() dan regenerate() setelahnya.
- session_token tidak pernah bocor ke client. Verified di semua Blade. Hanya dibanding via hash_equals di ExamGuard.
- Tidak ada SQL injection surface.
- Mass assignment clean — semua model punya $fillable eksplisit.
- XSS terkontrol. Question text via @js() + Alpine x-text, tidak pernah x-html.
- Excel export pakai e() di semua sel.
- composer audit clean — terhadap lockfile.
- Service-layer separation bagus.
- Snapshot pattern di ParticipantAnswer.question_snapshot handle kasus soal diedit setelah dijawab dengan benar.
- Defensive checks di bulk mutations menunjukkan pola pikir defensif.
- Pre-checks sebelum destructive ops di beberapa tempat.

---

# Bagian 6 — Peta jalan (diurutkan)

## Sprint 1 (mencegah eksploit aktif + restore test suite)
- [x] C1 fix saveAnswer module-scope
- [x] C2 tambah throttle di /login & /exam/access
- [x] M4 + M5 hapus 5 stale test, perbaiki 3 test bug
- [ ] H1 git rm -r --cached vendor + .gitignore
- [x] H2 ubah FK cascade jadi restrict + tambah SoftDeletes

Note: C1, C2, M4 dan M5 sudah diimplementasikan dan diverifikasi di repo (AdminSettingsTest dihapus; AdminAuthTest dipatch).

## Sprint 2 (race conditions & concurrency)
- [ ] H3 lockForUpdate() di SubmitExamService::submit
- [ ] H4 current_question GREATEST()
- [ ] H5 ExamGuard enforce takeover, bukan self-heal
- [ ] M6 try/catch QueryException di bulk move
- [ ] M7 + M8 tambah lockForUpdate() di module delete & question reorder
- [ ] M9 ExamController::show — fallback currentIndex ke jawaban terakhir yang ada (hindari fallback ke 0)

## Sprint 3 (security hardening)
- [ ] H6 + H9 putuskan kebijakan geo-fence mid-exam, enforce TTL cache
- [ ] H7 jangan kurangi jarak dengan accuracy; treat sebagai quality gate
- [ ] H8 terapkan role:admin ke seluruh admin group
- [ ] M1 CSV formula injection helper
- [ ] M2 hard-guard EXAM_TOKEN_BYPASS di production

## Sprint 4 (test infrastructure)
- [ ] M3 + M14 factory untuk 6 model + unit test untuk 4 service + GeoDistance
- [ ] M16 standarkan pagination idiom via shared trait
- [ ] M17 setup satu path E2E

## Sprint 5 (maintainability, tidak urgent)
- [ ] A1 (lihat docs/architecture-audit-2026-08-09.md) refactor: trait BuildsAdminIndex, extract Alpine examEngine ke module JS, reconcile Carbon ke immutable
- [ ] M13 Carbon Immutable di semua tempat
- [ ] M10 hapus sessionAttemptNumber (dead code)
- [ ] M11 + M12 ExamGuard session-id sanity + EnsureUserRole UX fix

Low/FYI items (session encryption, password policy, monitoring URL exposure)

---

# Lampiran A — Detail Test Failure

Tests: 8 failed, 33 passed (163 assertions)  
Duration: 0.65s

| Test | Root cause | Tindakan |
|------|------------|----------|
| AdminAuthTest > admin can login and access admin pages | Assertion assertRedirect(route('admin.results')), app redirect ke admin.dashboard | Fix test (ganti route) |
| AdminAuthTest > bulk move participants skips active attempt | String check "sedang mengerjakan ujian", message asli "tes" | Fix test (ganti string) atau drop assertion |
| AdminAuthTest > session time config cannot change after attempt exists | PUT payload tidak kirim module_id | Fix test (tambah field) |
| AdminSettingsTest > admin can open settings tabs | Stale: cari "Editor .env" / "Log Harian" yang sudah dihapus | Hapus test |
| AdminSettingsTest > admin can update env content | Stale: route admin.settings.env.update hilang | Hapus test |
| AdminSettingsTest > admin can download env backup | Stale: route admin.settings.env.backup hilang | Hapus test |
| AdminSettingsTest > admin can clear config cache | Stale: route admin.settings.cache.config-clear hilang | Hapus test |
| AdminSettingsTest > admin can filter and delete logs by date | Stale: feature log viewer hilang | Hapus test |

---

# Lampiran B — Laporan audit individual

- docs/architecture-audit-2026-08-09.md — arsitektur & maintainability (21 KB, 5 refactor utama + findings detail)
- Laporan security, logic, dan test ada di transcript audit ini (tidak ditulis ke file karena user minta report langsung di sini)

# Catatan metodologi

Audit dilakukan paralel via 4 specialist background task (Oracle); semua telah reconcile.
Security & logic audit memverifikasi klaim lewat baca kode + DB schema; tidak menjalankan exploit.
Architecture audit memverifikasi terhadap state repo (git ls-files, .gitignore, routes/web.php).
Test audit menjalankan php artisan test setelah setup .env (APP_NAME perlu di-quote karena "CAT Psikiatri" mengandung spasi — .env.example masih mentah). Hasil: 33 passed, 8 failed, 163 assertions, 0.65s.

Tidak ada exploit aktif yang dijalankan terhadap aplikasi.
