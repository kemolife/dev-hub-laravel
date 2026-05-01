<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Data\Billing\CheckoutData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'plan' => ['required', 'string', 'in:pro,pro_annual'],
        ];
    }

    public function toData(): CheckoutData
    {
        return CheckoutData::from($this->validated());
    }
}
