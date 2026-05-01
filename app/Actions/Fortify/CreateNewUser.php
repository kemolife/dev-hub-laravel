<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Actions\Auth\RegisterUserAction;
use App\Data\Auth\RegisterData;
use App\Models\User;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function __construct(private readonly RegisterUserAction $registerUserAction) {}

    /** @param array<string, string> $input */
    public function create(array $input): User
    {
        return $this->registerUserAction->execute(RegisterData::from($input));
    }
}
