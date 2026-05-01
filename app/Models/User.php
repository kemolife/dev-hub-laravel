<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property Carbon|null $last_seen_at
 * @property int $followers_count
 * @property int $following_count
 * @property string|null $referral_code
 * @property int|null $referred_by_user_id
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
    'followers_count',
    'following_count',
    'referral_code',
    'referred_by_user_id',
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
            $user->referral_code ??= Str::random(8);
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'role' => Role::class,
            'followers_count' => 'integer',
            'following_count' => 'integer',
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

    /** @return BelongsToMany<User, $this> */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followee_id')
            ->withTimestamps('created_at', false);
    }

    /** @return BelongsToMany<User, $this> */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'followee_id', 'follower_id')
            ->withTimestamps('created_at', false);
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()->where('followee_id', $user->id)->exists();
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
