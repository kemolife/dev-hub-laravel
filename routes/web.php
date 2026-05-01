<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['name' => config('app.name'), 'status' => 'ok']));

Route::get('/health', HealthController::class)->name('health');
