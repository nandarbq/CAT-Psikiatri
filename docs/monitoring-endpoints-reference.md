# Referensi Endpoint Monitoring (Health Check + Version)

Dokumen ini mencatat pola implementasi endpoint monitoring yang sudah dipakai di proyek ini, agar bisa direplikasi ke proyek Laravel lain.

## Tujuan

- Menyediakan endpoint sederhana untuk health probe server.
- Menyediakan endpoint versi/build untuk verifikasi deploy live vs commit GitHub.
- Memudahkan integrasi dengan sistem monitoring eksternal.

## Endpoint yang Direkomendasikan

### 1) Health Check (Machine Readable)

- `GET /health`
- Response JSON contoh:

```json
{
  "status": "ok",
  "app": "WBS RSUD Blambangan",
  "environment": "production",
  "timestamp": "2026-03-25T14:10:00+07:00",
  "checks": {
    "database": {
      "status": "ok",
      "message": "Database connection is healthy.",
      "connection": "mysql"
    }
  }
}
```

Catatan:
- `status` bisa `ok` atau `degraded`.
- HTTP status `200` saat `ok`, `503` saat `degraded`.

### 2) Status UI (Human Readable)

- `GET /status`
- Menampilkan halaman UI monitor status.
- Jika request `Accept: application/json`, endpoint dapat mengembalikan JSON payload health yang sama.

### 3) Version / Build Info (Deployment Verification)

- `GET /version`
- `GET /build-info` (alias dari `/version`)
- Response JSON contoh:

```json
{
  "app": "WBS RSUD Blambangan",
  "environment": "production",
  "version": "release-2026.03.25",
  "commit": "abc123def4567890abc123def4567890abc123de",
  "commit_short": "abc123d",
  "deployed_at": "2026-03-25T12:00:00+07:00",
  "generated_at": "2026-03-25T14:10:00+07:00"
}
```

## Kontrak Payload untuk Integrasi Monitoring Deploy

Agar kompatibel dengan service monitoring yang membandingkan commit live vs GitHub:

- Field commit live minimal salah satu:
  - `commit`
  - `live_commit_sha`
  - `sha`
- Field opsional tambahan:
  - `deployed_at`
  - `env` atau `environment`

Status sinkronisasi umumnya:
- `in_sync`: commit live sama (atau prefix match short sha) dengan commit GitHub terbaru.
- `behind`: commit live berbeda dari commit GitHub terbaru.
- `unknown`: endpoint tidak ada, timeout, payload invalid, atau commit tidak tersedia.

## Strategi Fallback Metadata (Agar Tidak Bergantung Penuh ke .env)

### Commit (`commit`)

Urutan sumber data:
1. `APP_COMMIT_SHA` (env/config)
2. File `REVISION`
3. File `.commit_sha`
4. `.git/HEAD` (termasuk packed refs)

### Version (`version`)

Urutan sumber data:
1. `APP_VERSION` (env/config)
2. File `VERSION`
3. File `.version`
4. Fallback otomatis `git-<short_commit>` jika commit ada

### Deployed At (`deployed_at`)

Urutan sumber data:
1. `APP_DEPLOYED_AT` (env/config)
2. File `DEPLOYED_AT`
3. File `.deployed_at`
4. Fallback `filemtime` dari marker file (`DEPLOYED_AT`, `.deployed_at`, `REVISION`, `.commit_sha`)

## Checklist Implementasi di Proyek Baru

1. Tambah route publik:
   - `/health`
   - `/status`
   - `/version`
   - `/build-info`
2. Buat controller health check:
   - cek koneksi DB
   - return status `ok/degraded`
3. Buat controller version info:
   - expose `commit`, `version`, `deployed_at`
   - aktifkan fallback otomatis (env/file/git)
4. Tambah UI Blade untuk `/status` (opsional tapi direkomendasikan).
5. Tambah test feature untuk endpoint health + version.
6. Metadata deploy tidak wajib lewat env.
   Gunakan salah satu sumber yang tersedia:
   - env (opsional): `APP_COMMIT_SHA`, `APP_VERSION`, `APP_DEPLOYED_AT`
   - atau file marker: `REVISION`/`.commit_sha`, `VERSION`/`.version`, `DEPLOYED_AT`/`.deployed_at`
7. Jika env diubah di server, reload config:

```bash
php artisan config:clear
```

## Referensi Implementasi di Proyek Ini

- Health controller: `app/Http/Controllers/HealthCheckController.php`
- Version controller: `app/Http/Controllers/VersionInfoController.php`
- Status UI: `resources/views/status.blade.php`
- Routes: `routes/web.php`
- Tests:
  - `tests/Feature/HealthCheckTest.php`
  - `tests/Feature/VersionInfoEndpointTest.php`
