<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\ReactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReactionToggled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Model $reactable,
        public readonly User $user,
        public readonly ReactionType $type,
    ) {}
}
