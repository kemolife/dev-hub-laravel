<?php

declare(strict_types=1);

namespace App\Data\Auth;

readonly class RegisterData
{
    public function __construct(
        public string $name,
        public string $username,
        public string $email,
        public string $password,
    ) {}

    /** @param array{name: string, username: string, email: string, password: string} $data */
    public static function from(array $data): self
    {
        return new self(
            name: $data['name'],
            username: $data['username'],
            email: $data['email'],
            password: $data['password'],
        );
    }
}
