<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Models\User;

readonly class AuthTokenData
{
    public function __construct(
        public string $token,
        public string $tokenType,
        public User $user,
    ) {}
}
