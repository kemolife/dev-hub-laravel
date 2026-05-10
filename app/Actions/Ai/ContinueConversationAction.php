<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Data\Ai\ContinueConversationData;
use App\Enums\MessageRole;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Services\OllamaClient;
use Generator;

class ContinueConversationAction
{
    public function __construct(private readonly OllamaClient $ollamaClient) {}

    /** @return Generator<int, string> */
    public function execute(AiConversation $conversation, ContinueConversationData $data): Generator
    {
        $conversation->messages()->create([
            'role' => MessageRole::User,
            'content' => $data->content,
        ]);

        $history = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn (AiMessage $message): array => [
                'role' => $message->role->value,
                'content' => $message->content,
            ])
            ->toArray();

        return $this->ollamaClient->chat($data->content, $history);
    }
}
