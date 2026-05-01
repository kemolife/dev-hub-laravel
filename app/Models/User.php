<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property Role $role
 * @property string|null $plan
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $onboarding_completed_at
 * @property array<string>|null $onboarding_steps
 */
#[Fillable([
    'name',
    'email',
    'password',
    'username',
    'bio',
    'avatar_path',
    'website_url',
    'timezone',
    'role',
    'plan',
    'trial_ends_at',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->public_id ??= (string) Str::uuid();
            $user->role ??= Role::Member;
            $user->plan ??= 'free';
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'onboarding_steps' => 'array',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'role' => Role::class,
        ];
    }

    /** @return HasMany<SocialAccount, $this> */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isModerator(): bool
    {
        return $this->role === Role::Moderator || $this->isAdmin();
    }

    public function isOnProPlan(): bool
    {
        return $this->plan === 'pro' || $this->plan === 'pro_annual';
    }

    public function isOnFreePlan(): bool
    {
        return $this->plan === 'free' || $this->plan === null;
    }
}
