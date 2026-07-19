<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\MonitoringErrorReporter;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MonitoringErrorReporterTest extends TestCase
{
    public function test_report_sends_payload_to_monitoring_endpoint(): void
    {
        config()->set('monitoring.error_log.endpoint', 'https://monitoring.test/api/websites/10/error-logs');
        config()->set('monitoring.error_log.token', 'token-abc');
        config()->set('monitoring.error_log.include_trace', false);

        Http::fake();

        app(MonitoringErrorReporter::class)->report(new RuntimeException('Database timeout saat checkout'));

        Http::assertSent(function (HttpRequest $request): bool {
            $payload = $request->data();

            return $request->url() === 'https://monitoring.test/api/websites/10/error-logs'
                && $request->hasHeader('X-Website-Token', 'token-abc')
                && ($payload['message'] ?? null) === 'Database timeout saat checkout'
                && ($payload['framework'] ?? null) === 'laravel'
                && ($payload['level'] ?? null) === 'error'
                && isset($payload['context']);
        });
    }

    public function test_report_does_nothing_when_endpoint_or_token_is_empty(): void
    {
        config()->set('monitoring.error_log.endpoint', '');
        config()->set('monitoring.error_log.token', '');

        Http::fake();

        app(MonitoringErrorReporter::class)->report(new RuntimeException('No-op test'));

        Http::assertNothingSent();
    }

    public function test_report_swallows_http_failures(): void
    {
        config()->set('monitoring.error_log.endpoint', 'https://monitoring.test/api/websites/10/error-logs');
        config()->set('monitoring.error_log.token', 'token-abc');

        Http::fake(static function (): void {
            throw new RuntimeException('Monitoring API unreachable');
        });

        $this->expectNotToPerformAssertions();
        app(MonitoringErrorReporter::class)->report(new RuntimeException('Network failure test'));
    }
}
