<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Billing\StartTrialAction;
use App\Data\Auth\RegisterData;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    public function __construct(private readonly StartTrialAction $startTrialAction) {}

    public function execute(RegisterData $data): User
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);

        Event::dispatch(new Registered($user));

        $this->startTrialAction->execute($user);

        return $user;
    }
}
