<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckNotSuspended
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isSuspended()) {
            return response()->json([
                'message' => 'Your account has been suspended.',
                'suspended_until' => $request->user()->suspended_until,
                'reason' => $request->user()->suspension_reason,
            ], 403);
        }

        return $next($request);
    }
}
