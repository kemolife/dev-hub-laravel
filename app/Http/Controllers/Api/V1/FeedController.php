<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $followingIds = $user->following()->pluck('users.id');

        $posts = Post::published()
            ->with(['user', 'tags'])
            ->whereIn('user_id', $followingIds)
            ->latest('published_at')
            ->paginate(15);

        return PostResource::collection($posts);
    }
}
