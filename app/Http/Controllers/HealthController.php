<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * Return the application health status.
     *
     * Checks database and cache connectivity. Always returns HTTP 200 so
     * load-balancer health probes don't flip on transient blips — the JSON
     * body carries the granular status of each component.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
            ],
        ]);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (Throwable) {
            return 'fail';
        }
    }

    private function checkCache(): string
    {
        try {
            Cache::put('health:ping', true, 5);
            Cache::forget('health:ping');

            return 'ok';
        } catch (Throwable) {
            return 'fail';
        }
    }
}
