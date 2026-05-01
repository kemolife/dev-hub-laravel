<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Billing\StartTrialAction;
use App\Data\Auth\RegisterData;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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

        $this->applyReferral($user);

        Mail::to($user)->later(now()->addMinutes(5), new WelcomeMail($user));

        return $user;
    }

    private function applyReferral(User $user): void
    {
        $referralCode = request()->cookie('referral_code');

        if (! $referralCode) {
            return;
        }

        $referrer = User::where('referral_code', $referralCode)->first();

        if ($referrer && $referrer->id !== $user->id) {
            $user->update(['referred_by_user_id' => $referrer->id]);
        }
    }
}
