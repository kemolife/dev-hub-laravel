<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\OnboardingStep;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'completed' => $user->onboarding_completed_at !== null,
            'steps' => $user->onboarding_steps ?? [],
            'all_steps' => OnboardingStep::all(),
        ]);
    }
}
