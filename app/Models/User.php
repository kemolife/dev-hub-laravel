<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\LogsActivity;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
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
 * @property Carbon|null $suspended_at
 * @property Carbon|null $suspended_until
 * @property string|null $suspension_reason
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
    'suspended_at',
    'suspended_until',
    'suspension_reason',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasApiTokens, HasFactory, LogsActivity, Notifiable, TwoFactorAuthenticatable;

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
            'suspended_at' => 'datetime',
            'suspended_until' => 'datetime',
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

    /** @return HasMany<NotificationPreference, $this> */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /** @return HasMany<WebhookEndpoint, $this> */
    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
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

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null && ($this->suspended_until === null || $this->suspended_until->isFuture());
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() || $this->isModerator();
    }
}
