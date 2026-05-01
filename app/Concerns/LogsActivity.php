<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\AuditLog;
use App\Models\User;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        // Not auto-logging here — we log explicitly in actions
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function logActivity(string $action, array $before = [], array $after = [], ?User $actor = null): void
    {
        AuditLog::create([
            'user_id' => $actor !== null ? $actor->id : auth()->id(),
            'action' => $action,
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'before' => $before ?: null,
            'after' => $after ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
