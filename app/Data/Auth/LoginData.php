<?php

declare(strict_types=1);

namespace App\Data\Auth;

readonly class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName = 'api',
    ) {}

    /** @param array{email: string, password: string, device_name?: string} $data */
    public static function from(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            deviceName: $data['device_name'] ?? 'api',
        );
    }
}
