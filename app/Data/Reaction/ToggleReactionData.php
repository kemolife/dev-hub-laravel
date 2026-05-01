<?php

declare(strict_types=1);

namespace App\Data\Reaction;

use App\Enums\ReactionType;

readonly class ToggleReactionData
{
    public function __construct(public ReactionType $type) {}

    /** @param array{type: string} $data */
    public static function from(array $data): self
    {
        return new self(type: ReactionType::from($data['type']));
    }
}
