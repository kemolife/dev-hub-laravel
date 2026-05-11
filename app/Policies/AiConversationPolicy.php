<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AiConversation;
use App\Models\User;

class AiConversationPolicy
{
    public function view(User $user, AiConversation $conversation): bool
    {
        return $conversation->user_id === $user->id || ! $conversation->is_private;
    }

    public function update(User $user, AiConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }

    public function addMessage(User $user, AiConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }
}
