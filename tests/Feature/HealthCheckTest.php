<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_health_endpoint_returns_ok_with_expected_structure(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'timestamp',
                'checks' => [
                    'database',
                    'cache',
                ],
            ])
            ->assertJsonPath('status', 'ok');
    }

    public function test_health_endpoint_reports_database_and_cache_ok_when_reachable(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJsonPath('checks.database', 'ok')
            ->assertJsonPath('checks.cache', 'ok');
    }

    public function test_health_endpoint_returns_iso8601_timestamp(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk();

        $timestamp = $response->json('timestamp');
        $this->assertNotNull($timestamp);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $timestamp,
        );
    }
}
