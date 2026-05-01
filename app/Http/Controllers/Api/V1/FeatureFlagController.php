<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

class FeatureFlagController extends Controller
{
    /** @var array<string> */
    private const array FLAGS = [
        'new-editor',
        'ai-summaries',
        'recommendations',
        'public-roadmap',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $active = array_values(
            array_filter(self::FLAGS, fn (string $flag): bool => Feature::for($user)->active($flag)),
        );

        return response()->json(['features' => $active]);
    }
}
