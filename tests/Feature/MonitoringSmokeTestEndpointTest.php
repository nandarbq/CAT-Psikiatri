<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonitoringSmokeTestEndpointTest extends TestCase
{
    public function test_smoke_endpoint_triggers_monitoring_reporter_when_config_enabled(): void
    {
        config()->set('monitoring.error_log.endpoint', 'https://monitoring.test/api/websites/10/error-logs');
        config()->set('monitoring.error_log.token', 'token-abc');

        Http::fake();

        $response = $this->getJson('/monitoring/smoke-error-log?message=Smoke+check+manual');

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('delivery_enabled', true);

        Http::assertSent(function (HttpRequest $request): bool {
            $payload = $request->data();

            return $request->url() === 'https://monitoring.test/api/websites/10/error-logs'
                && $request->hasHeader('X-Website-Token', 'token-abc')
                && ($payload['message'] ?? null) === 'Smoke check manual';
        });
    }

    public function test_smoke_endpoint_still_returns_ok_when_config_not_set(): void
    {
        config()->set('monitoring.error_log.endpoint', '');
        config()->set('monitoring.error_log.token', '');

        Http::fake();

        $response = $this->getJson('/monitoring/smoke-error-log');

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('delivery_enabled', false);
        Http::assertNothingSent();
    }
}
