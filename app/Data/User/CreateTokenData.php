<?php

declare(strict_types=1);

namespace App\Data\User;

readonly class CreateTokenData
{
    public function __construct(
        public string $name,
        /** @var string[] */
        public array $abilities = ['*'],
    ) {}

    /** @param array{name: string, abilities?: string[]} $data */
    public static function from(array $data): self
    {
        return new self(
            name: $data['name'],
            abilities: $data['abilities'] ?? ['*'],
        );
    }
}
