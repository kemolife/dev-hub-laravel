<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Data\Auth\TwoFactorChallengeData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TwoFactorChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'challenge_token' => ['required', 'string'],
            'code' => ['nullable', 'string', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'required_without:code'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function toData(): TwoFactorChallengeData
    {
        return TwoFactorChallengeData::from($this->validated());
    }
}
