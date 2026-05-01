<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Post\ArchivePostAction;
use App\Actions\Post\CreateDraftAction;
use App\Actions\Post\PublishPostAction;
use App\Actions\Post\UpdatePostAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PostManagementController extends Controller
{
    public function __construct(
        private readonly CreateDraftAction $createDraftAction,
        private readonly UpdatePostAction $updatePostAction,
        private readonly PublishPostAction $publishPostAction,
        private readonly ArchivePostAction $archivePostAction,
    ) {}

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->createDraftAction->execute($request->user(), $request->toData());

        return (new PostResource($post->load('user', 'tags')))->response()->setStatusCode(201);
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $post = $this->updatePostAction->execute($post, $request->toData(), $request->user());

        return (new PostResource($post->load('user', 'tags')))->response();
    }

    public function destroy(Request $request, Post $post): Response
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->noContent();
    }

    public function publish(Request $request, Post $post): JsonResponse
    {
        $this->authorize('publish', $post);

        $post = $this->publishPostAction->execute($post);

        return (new PostResource($post->load('user')))->response();
    }

    public function archive(Request $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $post = $this->archivePostAction->execute($post);

        return (new PostResource($post->load('user')))->response();
    }
}
