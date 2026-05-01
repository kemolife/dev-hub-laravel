<?php

declare(strict_types=1);

namespace App\Actions\Webhook;

use App\Data\Webhook\UpdateWebhookEndpointData;
use App\Models\WebhookEndpoint;

class UpdateWebhookEndpointAction
{
    public function execute(WebhookEndpoint $endpoint, UpdateWebhookEndpointData $data): WebhookEndpoint
    {
        $attributes = array_filter([
            'url' => $data->url,
            'events' => $data->events,
            'enabled' => $data->enabled,
        ], fn (mixed $value): bool => $value !== null);

        $endpoint->update($attributes);

        return $endpoint->fresh() ?? $endpoint;
    }
}
