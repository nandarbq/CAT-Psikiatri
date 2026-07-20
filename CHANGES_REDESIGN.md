# Ringkasan Perubahan (Redesign)

## 1. Role dokter dihapus
Sistem sekarang hanya punya 2 role: **admin** dan **peserta**.
- `App\Models\User`: konstanta `ROLE_DOKTER` dan method `isDokter()` dihapus.
- `Admin/UserController`, `AdminUserRequest`, seeder, dan semua view terkait
  (`admin/users/*`, `admin/partials/nav.blade.php`, halaman login) disesuaikan
  — form buat/ubah akun sekarang tidak punya dropdown role lagi (otomatis admin).
- Semua teks "admin/dokter" di UI diganti jadi "admin".
- Test yang sebelumnya menguji pembatasan akses dokter sudah disesuaikan/dihapus.
- Seluruh test suite (40 test) lulus: `php vendor/bin/phpunit`.

## 2. Tampilan & layout baru
- **Palet warna**: dari cyan/teal berubah ke **indigo/violet + aksen amber**
  (tombol utama, badge, highlight). Warna status (sukses/hijau, gagal/merah,
  peringatan/kuning) sengaja dipertahankan supaya makna warnanya tetap jelas.
- **Font**: dari Jost/Bodoni Moda ke **Inter** (teks) + **Manrope** (judul),
  Fira Code dipertahankan untuk elemen angka/monospace (timer, kode).
- **Layout admin**: sebelumnya sidebar kiri yang bisa collapse, sekarang jadi
  **topbar horizontal** dengan menu grup (Operasional, Bank Soal, Manajemen)
  yang otomatis jadi menu hamburger di layar kecil (mobile).
- Semua fitur admin & peserta tetap sama persis — hanya kelas CSS dan struktur
  HTML layout yang diubah, seluruh route/controller/logic backend tidak berubah.

## Menjalankan proyek
```bash
composer install
npm install && npm run build   # sudah pre-built di public/build, opsional
php artisan migrate --seed
php artisan serve
```
Login admin demo: `admin01` / `password123`
Login peserta demo: `peserta01` / `password123`
