<?php

declare(strict_types=1);

namespace App\Actions\Reaction;

use App\Enums\ReactionType;
use App\Events\ReactionToggled;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ToggleReactionAction
{
    /**
     * Toggle a reaction on a reactable model.
     *
     * Returns true if the reaction was added, false if it was removed.
     */
    public function execute(User $user, Model $reactable, ReactionType $type): bool
    {
        $existing = Reaction::query()
            ->where('user_id', $user->id)
            ->where('reactable_type', $reactable->getMorphClass())
            ->where('reactable_id', $reactable->getKey())
            ->where('type', $type->value)
            ->first();

        $cacheKey = "reaction_counts:{$reactable->getMorphClass()}:{$reactable->getKey()}";

        if ($existing !== null) {
            $existing->delete();
            $reactable->decrement('reactions_count');
            Cache::forget($cacheKey);

            return false;
        }

        Reaction::create([
            'user_id' => $user->id,
            'reactable_type' => $reactable->getMorphClass(),
            'reactable_id' => $reactable->getKey(),
            'type' => $type->value,
        ]);

        $reactable->increment('reactions_count');
        Cache::forget($cacheKey);

        event(new ReactionToggled($reactable, $user, $type));

        return true;
    }
}
