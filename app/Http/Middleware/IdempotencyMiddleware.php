<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! $key || ! in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $next($request);
        }

        $cacheKey = 'idempotency:'.hash('sha256', $request->user()?->id.':'.$key);
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return response()->json($cached['body'], $cached['status'])
                ->header('X-Idempotent-Replayed', 'true');
        }

        /** @var Response $response */
        $response = $next($request);

        if ($response->getStatusCode() < 500) {
            Cache::put($cacheKey, [
                'body' => json_decode($response->getContent(), true),
                'status' => $response->getStatusCode(),
            ], now()->addHours(24));
        }

        return $response;
    }
}
