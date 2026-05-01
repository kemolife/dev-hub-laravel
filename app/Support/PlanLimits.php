<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Reads plan configuration for a user and exposes limit-checking helpers.
 * Plans are defined in config/plans.php — see ADR-0016.
 */
readonly class PlanLimits
{
    /** @var array<string, mixed> */
    private array $limits;

    /** @param array<string, mixed> $planConfig */
    private function __construct(array $planConfig)
    {
        $this->limits = $planConfig['limits'] ?? [];
    }

    public static function for(User $user): self
    {
        $planKey = $user->plan ?? 'free';

        /** @var array<string, mixed> $planConfig */
        $planConfig = config("plans.{$planKey}", config('plans.free'));

        return new self($planConfig);
    }

    public function canCreatePost(int $currentMonthCount): bool
    {
        $limit = $this->postsPerMonth();

        if ($limit === null) {
            return true;
        }

        return $currentMonthCount < $limit;
    }

    public function postsPerMonth(): ?int
    {
        $value = $this->limits['posts_per_month'] ?? null;

        return is_int($value) ? $value : null;
    }

    public function hasApiAccess(): bool
    {
        return (bool) ($this->limits['api_access'] ?? false);
    }

    /**
     * Returns true when the user has consumed 80% or more of the given feature limit.
     * Returns false for unlimited features (null limit).
     */
    public function atSoftLimit(int $current, string $feature): bool
    {
        $limit = match ($feature) {
            'posts_per_month' => $this->postsPerMonth(),
            default => null,
        };

        if ($limit === null) {
            return false;
        }

        return $current >= (int) ceil($limit * 0.8);
    }
}
