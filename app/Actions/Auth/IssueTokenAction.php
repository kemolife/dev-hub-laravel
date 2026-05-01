<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\AuthTokenData;
use App\Models\User;

class IssueTokenAction
{
    /**
     * @param  string[]  $abilities
     */
    public function execute(User $user, string $deviceName, array $abilities = ['*']): AuthTokenData
    {
        $token = $user->createToken($deviceName, $abilities);

        return new AuthTokenData(
            token: $token->plainTextToken,
            tokenType: 'Bearer',
            user: $user,
        );
    }
}
