# Panduan UX Writing (Bahasa Indonesia)

Panduan ini dipakai agar teks antarmuka konsisten, mudah dipahami orang awam, dan nyaman dipakai dokter/admin maupun peserta ujian.

## Tujuan

- Mengurangi istilah teknis yang membingungkan.
- Menjaga konsistensi kata di seluruh halaman.
- Membuat instruksi lebih jelas dan bisa langsung dipraktikkan.

## Prinsip Utama

- Pakai kalimat singkat dan langsung ke inti.
- Utamakan kata kerja yang jelas: `Tambah`, `Ubah`, `Hapus`, `Simpan`, `Kirim`.
- Hindari campuran bahasa Indonesia-Inggris jika ada padanan yang jelas.
- Gunakan istilah teknis hanya bila wajib, lalu jelaskan dengan bahasa sederhana.
- Untuk pesan error, jelaskan sebab + apa yang harus dilakukan pengguna.

## Istilah Baku

- `Bulk action` -> `Aksi massal`
- `Bulk create` -> `Tambah banyak ...` atau `Tambah ... sekaligus`
- `Generate token` -> `Buat token`
- `Edit` -> `Ubah`
- `Reset filter` -> `Atur Ulang`
- `Submitted` -> `Sudah Dikirim`
- `Attempt` -> `Pengerjaan` atau `Riwayat pengerjaan`
- `Online` -> `Daring`
- `Male` -> `Laki-laki`
- `Female` -> `Perempuan`
- `Phone` -> `No. HP`
- `Address` -> `Alamat`

## Gaya Penulisan Tombol

- Gunakan format aksi + objek bila perlu.
- Contoh:
  - `Tambah Peserta`
  - `Tambah Banyak Peserta`
  - `Simpan Perubahan`
  - `Buat Token Sekaligus`
  - `Kembali ke Daftar`

## Gaya Penulisan Alert/Error

- Gunakan pola:
  - **Masalahnya apa**
  - **Langkah pengguna berikutnya**

Contoh:

- `Peserta sedang mengerjakan ujian sehingga tidak bisa dipindah sesi.`
- `Pilih minimal satu item untuk aksi massal.`
- `Tidak ada peserta yang memenuhi syarat untuk dibuatkan token pada sesi ini.`

## Istilah yang Sebaiknya Dihindari di UI

- `attempt aktif/belum submit` -> ganti jadi `sedang mengerjakan ujian`
- `eligible` -> ganti jadi `memenuhi syarat` atau `bisa dipilih`
- `code bentrok` -> ganti jadi `kode bentrok`

## Checklist Sebelum Merge

- Semua label dan tombol sudah bahasa Indonesia.
- Tidak ada istilah campur bahasa tanpa alasan.
- Pesan sukses/error bisa dipahami tanpa pengetahuan teknis.
- Kata yang sama dipakai konsisten di halaman lain.
