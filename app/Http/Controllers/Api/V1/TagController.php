<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tags = Tag::popular()->paginate();

        return TagResource::collection($tags);
    }

    public function show(Request $request, Tag $tag): TagResource
    {
        $tag->loadCount('posts');
        $tag->load(['posts' => fn ($q) => $q->published()->with('user', 'tags')->withCount('comments')->latest('published_at')]);

        return new TagResource($tag);
    }
}
