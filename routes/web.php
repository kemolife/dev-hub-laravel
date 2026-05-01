<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['name' => config('app.name'), 'status' => 'ok']));

Route::get('/health', HealthController::class)->name('health');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/robots.txt', function () {
    return response("User-agent: *\nAllow: /\nSitemap: ".url('/sitemap.xml'), 200)
        ->header('Content-Type', 'text/plain');
});
