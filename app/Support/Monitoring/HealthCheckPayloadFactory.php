<?php

declare(strict_types=1);

namespace App\Support\Monitoring;

use Illuminate\Support\Facades\DB;

class HealthCheckPayloadFactory
{
    /**
     * @return array{
     *     status:string,
     *     app:string,
     *     environment:string,
     *     timestamp:string,
     *     checks:array{
     *         database:array{
     *             status:string,
     *             message:string,
     *             connection:string
     *         }
     *     }
     * }
     */
    public function make(): array
    {
        $connection = (string) config('database.default', 'mysql');
        $databaseStatus = 'ok';
        $databaseMessage = 'Database connection is healthy.';

        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $databaseStatus = 'degraded';
            $databaseMessage = 'Database connection is not reachable.';
        }

        $status = $databaseStatus === 'ok' ? 'ok' : 'degraded';

        return [
            'status' => $status,
            'app' => (string) config('monitoring.app_name', config('app.name')),
            'environment' => app()->environment(),
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'database' => [
                    'status' => $databaseStatus,
                    'message' => $databaseMessage,
                    'connection' => $connection,
                ],
            ],
        ];
    }

    public function resolveHttpStatus(array $payload): int
    {
        return (($payload['status'] ?? 'degraded') === 'ok') ? 200 : 503;
    }
}
