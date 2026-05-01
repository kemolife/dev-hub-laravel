<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($ref = $request->query('ref')) {
            cookie()->queue('referral_code', (string) $ref, 60 * 24 * 30);
        }

        return $next($request);
    }
}
