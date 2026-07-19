<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class MonitoringErrorReporter
{
    public function report(Throwable $exception): void
    {
        $endpoint = trim((string) config('monitoring.error_log.endpoint', ''));
        $token = trim((string) config('monitoring.error_log.token', ''));

        if ($endpoint === '' || $token === '') {
            return;
        }

        $payload = $this->buildPayload($exception);

        try {
            Http::asJson()
                ->acceptJson()
                ->withHeaders([
                    'X-Website-Token' => $token,
                ])
                ->connectTimeout((float) config('monitoring.error_log.connect_timeout', 1.5))
                ->timeout((float) config('monitoring.error_log.timeout', 2.5))
                ->post($endpoint, $payload);
        } catch (Throwable) {
            // Prevent recursive exceptions when the monitoring API is unreachable.
        }
    }

    /**
     * @return array{
     *     message:string,
     *     framework:string,
     *     level:string,
     *     severity:string,
     *     file:string,
     *     line:int,
     *     context:array<string, mixed>
     * }
     */
    private function buildPayload(Throwable $exception): array
    {
        $level = $this->resolveLevel($exception);
        $requestContext = $this->buildRequestContext(request());
        $context = [
            'exception_class' => $exception::class,
            'app_name' => (string) config('app.name', 'Laravel'),
            'environment' => app()->environment(),
            ...$requestContext,
        ];

        if ((bool) config('monitoring.error_log.include_trace', false)) {
            $context['trace'] = collect($exception->getTrace())
                ->take((int) config('monitoring.error_log.trace_limit', 20))
                ->values()
                ->all();
        }

        return [
            'message' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Unhandled exception',
            'framework' => 'laravel',
            'level' => $level,
            'severity' => $this->mapSeverityFromLevel($level),
            'file' => $this->relativePath($exception->getFile()),
            'line' => $exception->getLine(),
            'context' => $context,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestContext(?Request $request): array
    {
        if ($request === null) {
            return [
                'request_id' => null,
                'method' => null,
                'url' => null,
                'ip' => null,
                'user_agent' => null,
            ];
        }

        return [
            'request_id' => $request->headers->get('X-Request-Id')
                ?? $request->headers->get('X-Correlation-Id')
                ?? $request->headers->get('X-Amzn-Trace-Id'),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    private function resolveLevel(Throwable $exception): string
    {
        if ($exception instanceof \Error) {
            return 'critical';
        }

        return 'error';
    }

    private function mapSeverityFromLevel(string $level): string
    {
        return in_array($level, ['emergency', 'alert', 'critical'], true)
            ? 'fatal'
            : 'error';
    }

    private function relativePath(string $absolutePath): string
    {
        $base = base_path().DIRECTORY_SEPARATOR;
        if (str_starts_with($absolutePath, $base)) {
            return substr($absolutePath, strlen($base));
        }

        return $absolutePath;
    }
}
