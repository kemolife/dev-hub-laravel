<?php

declare(strict_types=1);

namespace App\Data\Billing;

readonly class CheckoutData
{
    public function __construct(
        public string $plan,
    ) {}

    /** @param array{plan: string} $data */
    public static function from(array $data): self
    {
        return new self(plan: $data['plan']);
    }
}
