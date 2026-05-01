<?php

declare(strict_types=1);

namespace App\Data\Notification;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;

readonly class UpdatePreferencesData
{
    public function __construct(
        public NotificationType $type,
        public NotificationChannel $channel,
        public bool $enabled,
        public bool $digest = false,
    ) {}

    /**
     * @param  array{type: string, channel: string, enabled: bool, digest?: bool}  $data
     */
    public static function from(array $data): self
    {
        return new self(
            type: NotificationType::from($data['type']),
            channel: NotificationChannel::from($data['channel']),
            enabled: (bool) $data['enabled'],
            digest: (bool) ($data['digest'] ?? false),
        );
    }
}
