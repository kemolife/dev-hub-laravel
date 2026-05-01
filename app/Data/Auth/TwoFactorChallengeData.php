<?php

declare(strict_types=1);

namespace App\Data\Auth;

readonly class TwoFactorChallengeData
{
    public function __construct(
        public string $challengeToken,
        public ?string $code,
        public ?string $recoveryCode,
        public string $deviceName = 'api',
    ) {}

    /** @param array{challenge_token: string, code?: string|null, recovery_code?: string|null, device_name?: string} $data */
    public static function from(array $data): self
    {
        return new self(
            challengeToken: $data['challenge_token'],
            code: $data['code'] ?? null,
            recoveryCode: $data['recovery_code'] ?? null,
            deviceName: $data['device_name'] ?? 'api',
        );
    }
}
