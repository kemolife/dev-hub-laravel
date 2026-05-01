<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\TwoFactorChallengeData;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorChallengeService
{
    private const CACHE_PREFIX = '2fa_challenge:';

    private const TTL_MINUTES = 5;

    public function __construct(private readonly TwoFactorAuthenticationProvider $provider) {}

    public function create(int $userId): string
    {
        $token = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX.$token, $userId, now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    public function consume(string $token): ?int
    {
        return Cache::pull(self::CACHE_PREFIX.$token);
    }

    public function verify(User $user, TwoFactorChallengeData $data): bool
    {
        if ($data->code !== null) {
            return $this->provider->verify(decrypt($user->two_factor_secret), $data->code);
        }

        return $this->verifyRecoveryCode($user, (string) $data->recoveryCode);
    }

    private function verifyRecoveryCode(User $user, string $recoveryCode): bool
    {
        $codes = $user->recoveryCodes();

        if (! in_array($recoveryCode, $codes, true)) {
            return false;
        }

        $user->replaceRecoveryCode($recoveryCode);

        return true;
    }
}
