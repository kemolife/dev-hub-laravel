<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\User\ToggleFollowAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class FollowController extends Controller
{
    public function __construct(private readonly ToggleFollowAction $toggleFollowAction) {}

    public function toggle(Request $request, User $user): JsonResponse
    {
        try {
            $following = $this->toggleFollowAction->execute($request->user(), $user);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['user' => $e->getMessage()]);
        }

        $user->refresh();

        return response()->json([
            'following' => $following,
            'followers_count' => $user->followers_count,
        ]);
    }

    public function followers(Request $request, User $user): AnonymousResourceCollection
    {
        $followers = $user->followers()->paginate(20);

        return UserResource::collection($followers);
    }

    public function following(Request $request, User $user): AnonymousResourceCollection
    {
        $following = $user->following()->paginate(20);

        return UserResource::collection($following);
    }
}
