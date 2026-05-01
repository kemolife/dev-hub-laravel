<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Reaction\ToggleReactionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reaction\ToggleReactionRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class ReactionController extends Controller
{
    public function __construct(private readonly ToggleReactionAction $toggleReactionAction) {}

    public function toggle(ToggleReactionRequest $request, Post $post): JsonResponse
    {
        $data = $request->toData();

        $added = $this->toggleReactionAction->execute($request->user(), $post, $data->type);

        $post->refresh();

        return response()->json([
            'added' => $added,
            'reactions_count' => $post->reactions_count,
        ]);
    }
}
