<?php

declare(strict_types=1);

namespace App\Http\Requests\Webhook;

use App\Data\Webhook\StoreWebhookEndpointData;
use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'in:post.published,comment.posted'],
        ];
    }

    public function toData(): StoreWebhookEndpointData
    {
        return StoreWebhookEndpointData::from($this->validated());
    }
}
