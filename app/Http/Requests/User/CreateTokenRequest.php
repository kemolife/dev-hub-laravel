<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Data\User\CreateTokenData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['sometimes', 'array'],
            'abilities.*' => ['string'],
        ];
    }

    public function toData(): CreateTokenData
    {
        return CreateTokenData::from($this->validated());
    }
}
