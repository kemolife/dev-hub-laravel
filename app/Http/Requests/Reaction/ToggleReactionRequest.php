<?php

declare(strict_types=1);

namespace App\Http\Requests\Reaction;

use App\Data\Reaction\ToggleReactionData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ToggleReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:like,insightful,fire,heart,mind_blown'],
        ];
    }

    public function toData(): ToggleReactionData
    {
        return ToggleReactionData::from($this->validated());
    }
}
