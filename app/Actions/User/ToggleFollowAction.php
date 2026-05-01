<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use App\Notifications\NewFollowerNotification;

readonly class ToggleFollowAction
{
    public function execute(User $follower, User $followee): bool
    {
        if ($follower->id === $followee->id) {
            throw new \InvalidArgumentException('Cannot follow yourself.');
        }

        if ($follower->isFollowing($followee)) {
            $follower->following()->detach($followee->id);
            $follower->decrement('following_count');
            $followee->decrement('followers_count');

            return false;
        }

        $follower->following()->attach($followee->id);
        $follower->increment('following_count');
        $followee->increment('followers_count');

        $followee->notify(new NewFollowerNotification($follower));

        return true;
    }
}
