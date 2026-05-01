<?php

declare(strict_types=1);

namespace App\Http\Requests\Webhook;

use App\Data\Webhook\UpdateWebhookEndpointData;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWebhookEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var WebhookEndpoint $endpoint */
        $endpoint = $this->route('webhookEndpoint');

        return $this->user()?->id === $endpoint->user_id;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'url' => ['sometimes', 'url', 'max:2048'],
            'events' => ['sometimes', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'in:post.published,comment.posted'],
            'enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function toData(): UpdateWebhookEndpointData
    {
        return UpdateWebhookEndpointData::from($this->validated());
    }
}
