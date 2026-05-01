<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\AuthTokenData;
use App\Data\Auth\LoginData;
use App\Data\Auth\TwoFactorRequiredData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    public function __construct(private readonly IssueTokenAction $issueTokenAction) {}

    /** @throws ValidationException */
    public function execute(LoginData $data): AuthTokenData|TwoFactorRequiredData
    {
        $user = User::where('email', $data->email)->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return new TwoFactorRequiredData(userId: $user->id);
        }

        return $this->issueTokenAction->execute($user, $data->deviceName);
    }
}
