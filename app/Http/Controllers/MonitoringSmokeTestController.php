<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\MonitoringErrorReporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MonitoringSmokeTestController extends Controller
{
    public function fire(Request $request, MonitoringErrorReporter $monitoringErrorReporter): JsonResponse
    {
        $endpoint = trim((string) config('monitoring.error_log.endpoint', ''));
        $token = trim((string) config('monitoring.error_log.token', ''));
        $deliveryEnabled = $endpoint !== '' && $token !== '';

        $message = trim((string) $request->query('message', 'Smoke test error log dari endpoint lokal.'));
        if ($message === '') {
            $message = 'Smoke test error log dari endpoint lokal.';
        }

        $monitoringErrorReporter->report(new RuntimeException($message));

        return response()->json([
            'status' => 'ok',
            'message' => 'Smoke test telah diproses.',
            'delivery_enabled' => $deliveryEnabled,
            'monitoring_endpoint' => $deliveryEnabled ? $endpoint : null,
            'sent_at' => now()->toIso8601String(),
        ]);
    }
}
