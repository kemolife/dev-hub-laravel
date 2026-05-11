<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Ai\ContinueConversationAction;
use App\Actions\Ai\StartConversationAction;
use App\Enums\MessageRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ContinueConversationRequest;
use App\Http\Requests\Api\V1\StartConversationRequest;
use App\Http\Resources\Api\V1\AiConversationResource;
use App\Models\AiConversation;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiConversationController extends Controller
{
    public function __construct(
        private readonly StartConversationAction $startConversationAction,
        private readonly ContinueConversationAction $continueConversationAction,
    ) {}

    public function index(Request $request, Post $post): AnonymousResourceCollection
    {
        $conversations = AiConversation::where('post_id', $post->id)
            ->where(function ($query) use ($request): void {
                $query->where('is_private', false)
                    ->orWhere('user_id', $request->user()?->id);
            })
            ->with('messages')
            ->latest()
            ->get();

        return AiConversationResource::collection($conversations);
    }

    public function store(StartConversationRequest $request, Post $post): StreamedResponse
    {
        [$conversation, $stream] = $this->startConversationAction->execute(
            $request->user(),
            $post,
            $request->toData(),
        );

        return response()->stream(function () use ($conversation, $stream): void {
            $fullContent = '';

            foreach ($stream as $chunk) {
                $fullContent .= $chunk;
                echo 'data: '.json_encode(['content' => $chunk])."\n\n";
                ob_flush();
                flush();
            }

            $conversation->messages()->create([
                'role' => MessageRole::Assistant,
                'content' => $fullContent,
            ]);

            echo 'data: '.json_encode(['done' => true, 'conversation_id' => $conversation->public_id])."\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    public function show(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->load('messages');

        return (new AiConversationResource($conversation))->response();
    }

    public function addMessage(ContinueConversationRequest $request, AiConversation $conversation): StreamedResponse
    {
        $this->authorize('addMessage', $conversation);

        $stream = $this->continueConversationAction->execute(
            $conversation,
            $request->toData(),
        );

        return response()->stream(function () use ($conversation, $stream): void {
            $fullContent = '';

            foreach ($stream as $chunk) {
                $fullContent .= $chunk;
                echo 'data: '.json_encode(['content' => $chunk])."\n\n";
                ob_flush();
                flush();
            }

            $conversation->messages()->create([
                'role' => MessageRole::Assistant,
                'content' => $fullContent,
            ]);

            echo 'data: '.json_encode(['done' => true, 'conversation_id' => $conversation->public_id])."\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    public function togglePrivacy(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->update(['is_private' => ! $conversation->is_private]);

        return (new AiConversationResource($conversation))->response();
    }
}
