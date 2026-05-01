<?php

declare(strict_types=1);

namespace App\Data\Auth;

readonly class TwoFactorRequiredData
{
    public function __construct(
        public int $userId,
    ) {}
}
