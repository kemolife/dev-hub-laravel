<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Events\UserActivityRecorded;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeenAt
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user()) {
            UserActivityRecorded::dispatch($request->user());
        }

        return $response;
    }
}
