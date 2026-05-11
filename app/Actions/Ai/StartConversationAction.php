<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Data\Ai\StartConversationData;
use App\Enums\MessageRole;
use App\Models\AiConversation;
use App\Models\Post;
use App\Models\User;
use App\Services\OllamaClient;
use Generator;

class StartConversationAction
{
    public function __construct(private readonly OllamaClient $ollamaClient) {}

    /** @return array{0: AiConversation, 1: Generator} */
    public function execute(User $user, Post $post, StartConversationData $data): array
    {
        $conversation = AiConversation::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'selected_text' => $data->selectedText,
            'selection_start' => $data->selectionStart,
            'selection_end' => $data->selectionEnd,
            'is_private' => false,
        ]);

        $conversation->messages()->create([
            'role' => MessageRole::User,
            'content' => $data->selectedText,
        ]);

        $stream = $this->ollamaClient->chat(
            "Please explain the following text in simple terms:\n\n\"{$data->selectedText}\""
        );

        return [$conversation, $stream];
    }
}
