<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});

// Private notification channel — only the user themselves
Broadcast::channel('users.{userId}.notifications', function (User $user, int $userId): bool {
    return $user->id === $userId;
});

// Presence channel for post viewers
Broadcast::channel('posts.{postId}.viewers', function (User $user, int $postId): array|bool {
    return [
        'id' => $user->id,
        'username' => $user->username ?? $user->name,
    ];
});
