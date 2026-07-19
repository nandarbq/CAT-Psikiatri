# Integrasi Error Log untuk Laravel & CI4

Dokumen ini fokus ke snippet siap tempel untuk website yang terdaftar di Monitoring App.

## API Contract

- Endpoint: `POST /api/websites/{website_id}/error-logs`
- Header: `X-Website-Token: <error_log_token>`
- Body minimal: `message`

Contoh payload:

```json
{
  "message": "Database timeout saat checkout",
  "framework": "laravel",
  "level": "error",
  "file": "app/Http/Controllers/CheckoutController.php",
  "line": 87,
  "context": {
    "request_id": "req-001"
  }
}
```

Catatan:
- Boleh kirim `severity` (`error|critical|fatal`) atau `level` native framework.
- Mapping `level` otomatis:
  - `emergency|alert|critical -> fatal`
  - `error -> error`
  - lainnya -> `error`

## Laravel (11/12) - Plug and Play

Snippet source di repo ini:
- `integration-snippets/laravel/MonitoringErrorReporter.php`
- `integration-snippets/laravel/bootstrap-app-snippet.php`
- `integration-snippets/laravel/MonitoringErrorReporterNoTrace.php` (production ringan)
- `integration-snippets/laravel/bootstrap-app-snippet-no-trace.php` (production ringan)

Langkah:
1. Copy `integration-snippets/laravel/MonitoringErrorReporter.php` ke project website Laravel, target file: `app/Support/MonitoringErrorReporter.php`.
2. Di `bootstrap/app.php`, tambahkan hook sesuai contoh `integration-snippets/laravel/bootstrap-app-snippet.php`.
3. Tambahkan env berikut di website Laravel:

```env
MONITORING_ERROR_LOG_ENDPOINT=https://monitoring.example.com/api/websites/12/error-logs
MONITORING_ERROR_LOG_TOKEN=isi_token_dari_monitoring_app
```

## CI4 - Plug and Play

Snippet source di repo ini:
- `integration-snippets/ci4/monitoring_helper.php`
- `integration-snippets/ci4/usage-example.php`
- `integration-snippets/ci4/monitoring_helper_no_trace.php` (production ringan)
- `integration-snippets/ci4/usage-example-no-trace.php` (production ringan)

Langkah:
1. Copy `integration-snippets/ci4/monitoring_helper.php` ke project CI4, target file: `app/Helpers/monitoring_helper.php`.
2. Di flow yang punya `try/catch`, load helper dan panggil `sendErrorToMonitoring($exception)` sesuai `integration-snippets/ci4/usage-example.php`.
3. Tambahkan env berikut di website CI4:

```env
monitoring.errorLogEndpoint="https://monitoring.example.com/api/websites/34/error-logs"
monitoring.errorLogToken="isi_token_dari_monitoring_app"
```

## Smoke Test Cepat

```bash
curl -X POST https://monitoring.example.com/api/websites/12/error-logs \
  -H 'Content-Type: application/json' \
  -H 'X-Website-Token: isi_token_dari_monitoring_app' \
  -d '{"framework":"laravel","level":"error","message":"Test error ingest"}'
```

## Rekomendasi Production (Tanpa Trace)

- Laravel:
  - pakai `MonitoringErrorReporterNoTrace.php`
  - pakai hook dari `bootstrap-app-snippet-no-trace.php`
- CI4:
  - pakai `monitoring_helper_no_trace.php`
  - panggil `sendErrorToMonitoringNoTrace($exception)`

Payload tetap berisi info penting (`message`, `file`, `line`, metadata request), tapi lebih ringan karena tanpa `trace`.
