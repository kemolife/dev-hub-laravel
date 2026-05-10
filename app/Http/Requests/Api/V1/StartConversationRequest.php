<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Data\Ai\StartConversationData;
use Illuminate\Foundation\Http\FormRequest;

class StartConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'selected_text' => ['required', 'string', 'min:1', 'max:5000'],
            'selection_start' => ['required', 'integer', 'min:0'],
            'selection_end' => ['required', 'integer', 'min:0', 'gt:selection_start'],
        ];
    }

    public function toData(): StartConversationData
    {
        return StartConversationData::from($this->validated());
    }
}
