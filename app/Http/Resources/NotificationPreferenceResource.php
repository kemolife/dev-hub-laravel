<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property NotificationPreference $resource */
class NotificationPreferenceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->resource->type->value,
            'channel' => $this->resource->channel->value,
            'enabled' => $this->resource->enabled,
            'digest' => $this->resource->digest,
        ];
    }
}
