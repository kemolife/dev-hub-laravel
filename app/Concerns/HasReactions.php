<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\ReactionType;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait HasReactions
{
    /** @return MorphMany<Reaction, $this> */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    /**
     * Returns reaction counts keyed by type value.
     *
     * @return array<string, int>
     */
    public function reactionCounts(): array
    {
        $key = "reaction_counts:{$this->getMorphClass()}:{$this->getKey()}";

        return Cache::remember($key, 300, function (): array {
            return $this->reactions()
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();
        });
    }

    public function reactedBy(User $user, ?ReactionType $type = null): bool
    {
        $query = $this->reactions()->where('user_id', $user->id);

        if ($type !== null) {
            $query->where('type', $type->value);
        }

        return $query->exists();
    }
}
