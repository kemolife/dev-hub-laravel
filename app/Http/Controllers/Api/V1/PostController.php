<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Jobs\IncrementPostViewCountJob;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = Post::published()
            ->with('user', 'tags')
            ->latest()
            ->paginate();

        return PostResource::collection($posts);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        // Route is public; resolve Bearer token so owners can view their own drafts.
        Auth::shouldUse('sanctum');

        $this->authorize('view', $post);

        $post->loadMissing('user', 'tags');

        $viewerKey = $request->user()?->id
            ? (string) $request->user()->id
            : $request->ip() ?? 'unknown';

        IncrementPostViewCountJob::dispatch($post, $viewerKey);

        return (new PostResource($post))->response();
    }
}
