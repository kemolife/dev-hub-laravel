<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Comment\DeleteCommentAction;
use App\Actions\Comment\EditCommentAction;
use App\Actions\Comment\PostCommentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    public function __construct(
        private readonly PostCommentAction $postCommentAction,
        private readonly EditCommentAction $editCommentAction,
        private readonly DeleteCommentAction $deleteCommentAction,
    ) {}

    public function index(Request $request, Post $post): AnonymousResourceCollection
    {
        $comments = $post->comments()
            ->topLevel()
            ->with(['user', 'replies.user', 'replies.replies.user'])
            ->withTrashed()
            ->latest()
            ->paginate();

        return CommentResource::collection($comments);
    }

    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $comment = $this->postCommentAction->execute(
            $request->user(),
            $post,
            $request->toData(),
        );

        $comment->load('user');

        return (new CommentResource($comment))->response()->setStatusCode(201);
    }

    public function update(UpdateCommentRequest $request, Post $post, Comment $comment): JsonResponse
    {
        $comment = $this->editCommentAction->execute(
            $request->user(),
            $comment,
            $request->toData(),
        );

        $comment->load('user');

        return (new CommentResource($comment))->response();
    }

    public function destroy(Request $request, Post $post, Comment $comment): Response
    {
        $this->authorize('delete', $comment);

        $this->deleteCommentAction->execute($request->user(), $comment);

        return response()->noContent();
    }
}
