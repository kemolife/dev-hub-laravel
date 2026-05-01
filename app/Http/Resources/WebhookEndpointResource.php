<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\WebhookEndpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property WebhookEndpoint $resource */
class WebhookEndpointResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'url' => $this->resource->url,
            'events' => $this->resource->events,
            'enabled' => $this->resource->enabled,
            'last_success_at' => $this->resource->last_success_at,
            'last_failure_at' => $this->resource->last_failure_at,
            'failure_count' => $this->resource->failure_count,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
