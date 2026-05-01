<?php

declare(strict_types=1);

namespace App\Actions\Webhook;

use App\Data\Webhook\StoreWebhookEndpointData;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Str;

class CreateWebhookEndpointAction
{
    public function execute(User $user, StoreWebhookEndpointData $data): WebhookEndpoint
    {
        return $user->webhookEndpoints()->create([
            'url' => $data->url,
            'secret' => Str::random(40),
            'events' => $data->events,
            'enabled' => true,
        ]);
    }
}
