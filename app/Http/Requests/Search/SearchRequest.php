<?php

declare(strict_types=1);

namespace App\Http\Requests\Search;

use App\Data\Search\SearchData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:200'],
            'author' => ['sometimes', 'nullable', 'string', 'max:50'],
            'sort' => ['sometimes', 'nullable', 'string', 'in:published_at:desc,published_at:asc,view_count:desc'],
        ];
    }

    public function toData(): SearchData
    {
        return SearchData::from($this->validated());
    }
}
