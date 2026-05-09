<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookmarkController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = Post::published()
            ->whereHas('bookmarks', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('user', 'tags')
            ->withCount('comments')
            ->latest('updated_at')
            ->paginate();

        return PostResource::collection($posts);
    }

    public function toggle(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        $bookmark = $user->bookmarks()->where('post_id', $post->id)->first();

        if ($bookmark) {
            $bookmark->delete();
            $bookmarked = false;
        } else {
            $user->bookmarks()->create(['post_id' => $post->id]);
            $bookmarked = true;
        }

        return response()->json(['bookmarked' => $bookmarked]);
    }
}
