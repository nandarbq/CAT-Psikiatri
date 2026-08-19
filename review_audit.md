# Review Audit — PR #12: Konsolidasi Audit Sprint 4 & 5

Total 22 task dari hasil review, dibagi rata ke 3 sprint (bukan dikelompokkan per kategori). Setiap task diberi label prioritas asli dari reviewer:

| Label | Arti | Warna asli |
|---|---|---|
| **H** | High — blocker, wajib sebelum merge | 🔴 |
| **M** | Medium — wajib diperbaiki sebelum merge | 🟠 |
| **L** | Low — saran, tidak wajib | 🟡 |
| **N** | Nit — catatan kecil | ⚪ |

---

## Sprint 1 (8 task)

- [x] **[H1]** Perbaiki loading `examEngine` yang mati total — `asset("js/examEngine.js")` 404 karena tidak dibuild Vite, dan Alpine.js sudah jalan duluan sebelum script module di-load. **Fix:** di `resources/js/app.js` tambahkan `import { examEngine } from './examEngine'; window.examEngine = examEngine;`, lalu hapus blok `<script type="module">` di `runner.blade.php`. *(Alternatif: daftarkan `examEngine.js` ke `vite.config.js` + `@vite(...)`.)*
- [x] **[M1]** Kebijakan geo-check terlalu ketat — `geo_grace_seconds` (120 detik) hilang, sekarang langsung reject di kegagalan GPS pertama. Konfirmasi disengaja atau tidak; kalau tidak, kembalikan masa toleransinya.
- [x] **[M2]** Kode mati akibat perubahan geo-check — `exam_geo_out_of_range_since` di session & `geo_grace_seconds` di `config/exam.php` jadi tidak terpakai. Hapus atau kembalikan sesuai keputusan task sebelumnya. *(Diputuskan M1: kembalikan grace period. Keduanya sudah dipakai lagi — `geo_grace_seconds` dibaca di `ExamGuard.php:233` dan `exam_geo_out_of_range_since` dipakai di `ExamGuard.php:220-258` + dites di `ParticipantExamFlowTest.php:340`.)*
- [x] **[M3]** Nama method tidak sesuai isi — `enforceLocationWithGracePeriod()` di `ExamGuard.php` tidak lagi punya grace period. Ganti nama jadi `enforceLocation()`. *(Tidak perlu diubah: M1 sudah mengembalikan grace period, jadi nama `enforceLocationWithGracePeriod()` di `ExamGuard.php:197` sudah sesuai dengan isi method yang memakai `geo_grace_seconds` & `exam_geo_out_of_range_since`.)*
- [x] **[M4]** `BuildsAdminIndexViews.php` tidak dipakai & berpotensi error — redefine `paginateQuery()`, `resolvePerPage()`, `$paginationPerPageOptions` yang sudah ada di `HandlesPagination` → konflik fatal kalau dipakai bareng. **Hapus file ini.**
- [x] **[M5]** Timer auto-submit spam popup tanpa henti — submit telat ditolak server → popup retry → loop terus. **Server:** tetap terima submit meski telat. **Client:** kalau force-submit gagal, anggap selesai, arahkan ke halaman hasil.
- [x] **[M6]** Auto-advance ke soal berikutnya bisa salah — pengecekan "sudah dijawab" dilakukan saat timer dibuat, bukan saat callback jalan (delay 350ms). Fix: cek ulang nomor soal di dalam callback, batalkan timer kalau peserta pindah manual.
- [ ] **[M7]** Fetch call tanpa timeout — koneksi macet bikin tombol freeze. Tambahkan `AbortSignal.timeout(8000)`, tampilkan pesan error asli dari server.

---

## Sprint 2 (7 task)

- [ ] **[M8]** `ExamAttemptFactory` menghasilkan data inkonsisten — participant & session dibuat terpisah, bisa hasilkan `session_id` yang tidak cocok dengan `participant.session_id` (melanggar aturan M11). Perbaiki default:
    ```php
    'session_id' => fn (array $attributes) =>
        Participant::query()->find($attributes['participant_id'])?->session_id
        ?? ExamSession::factory(),
    ```
- [ ] **[M9]** Test jalur sukses middleware kurang kuat — `ExamGuardTest.php` hanya cek `assertSessionMissing('errors')`. Tambahkan `assertOk()` dan `assertViewIs('exam.index')`.
- [ ] **[L1]** `SubmitExamService`: hemat ~4 query berlebih di jalur submit; sebutkan di deskripsi PR bahwa submit ganda sekarang "sukses diam-diam" (bukan error).
- [ ] **[L2]** `ExamController`: kembalikan 2 komentar penting yang terhapus (alasan seed `Mt19937($attempt->id)` untuk anti-curang, dan perilaku fallback `currentIndex`). Pertimbangkan kembalikan format baris asli agar diff tidak membesar tanpa alasan.
- [ ] **[L3]** `HandlesPagination`: baru dipakai di 2 halaman padahal ada ~6 halaman admin lain dengan pola pagination berbeda — terapkan juga ke halaman lain, atau sesuaikan judul/deskripsi commit.
- [ ] **[L4]** Test `location_check_rejects_missing_coordinates`: tambahkan pembuatan setting lokasi agar benar-benar menguji logika koordinat.
- [ ] **[L5]** Test `StartExamService`: tambahkan test untuk session onsite (gagal tanpa koordinat, sukses dengan koordinat valid, token dari session berbeda).

---

## Sprint 3 (7 task)

- [ ] **[L6]** Test `SaveAnswerService`: tambahkan pengecekan kolom `question_snapshot` / `answered_at`, dan test saat durasi ujian sudah habis.
- [ ] **[L7]** Frontend: deteksi error 401/419 (session/CSRF habis) saat autosave dan arahkan ke halaman login; pertimbangkan selisih jam client vs server untuk `deadlineAt`.
- [ ] **[N1]** Bersihkan trailing whitespace & tambahkan newline di akhir file-file baru.
- [ ] **[N2]** `pluralize()` di `BuildsAdminIndexViews` salah untuk kata seperti "users" → "userses" (pakai `Str::plural()` — atau abaikan karena file ini sudah dihapus di Sprint 1).
- [ ] **[N3]** `jumpToQuestionNumber()`: input kosong + Enter bisa lempar peserta ke soal 1.
- [ ] **[N4]** Namespace test `Tests\Unit\Services` tidak sesuai lokasi folder `tests/Unit/` — pindahkan ke subfolder yang sesuai.
- [ ] **[N5]** `GeoDistanceTest` mewarisi `Tests\TestCase` padahal murni perhitungan matematika — cukup pakai `PHPUnit\Framework\TestCase`.

---

## ✅ Yang sudah bagus (tidak perlu diubah)
- Pemisahan pagination ke `HandlesPagination` tidak mengubah perilaku 2 halaman yang memakainya.
- Test `GeoDistance` memakai titik acuan matematis yang tepat, bisa menangkap regresi.
- Test idempotency submit (submit 2× tidak error) sesuai perilaku baru service.
- Pengecekan konsistensi sesi (M11) di `ExamGuard` sudah benar.
- Tidak ada celah XSS di scope perubahan ini; token CSRF dipakai di kedua request.
