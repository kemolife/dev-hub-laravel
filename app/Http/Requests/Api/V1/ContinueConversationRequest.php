<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Data\Ai\ContinueConversationData;
use Illuminate\Foundation\Http\FormRequest;

class ContinueConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }

    public function toData(): ContinueConversationData
    {
        return ContinueConversationData::from($this->validated());
    }
}
