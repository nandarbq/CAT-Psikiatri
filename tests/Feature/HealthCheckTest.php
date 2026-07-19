<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_returns_machine_readable_payload(): void
    {
        $response = $this->getJson('/health');
        $status = (string) $response->json('status');

        $this->assertContains($status, ['ok', 'degraded']);
        $response->assertStatus($status === 'ok' ? 200 : 503);
        $response->assertJsonStructure([
            'status',
            'app',
            'environment',
            'timestamp',
            'checks' => [
                'database' => [
                    'status',
                    'message',
                    'connection',
                ],
            ],
        ]);
    }

    public function test_status_endpoint_returns_html_and_can_return_json_payload(): void
    {
        $this->get('/status')
            ->assertOk()
            ->assertSee('Status Sistem');

        $jsonResponse = $this->get('/status', ['Accept' => 'application/json']);
        $status = (string) $jsonResponse->json('status');

        $this->assertContains($status, ['ok', 'degraded']);
        $jsonResponse->assertStatus($status === 'ok' ? 200 : 503);
        $jsonResponse->assertJsonStructure([
            'status',
            'app',
            'environment',
            'timestamp',
            'checks' => [
                'database' => [
                    'status',
                    'message',
                    'connection',
                ],
            ],
        ]);
    }
}
