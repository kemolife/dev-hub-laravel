<?php

declare(strict_types=1);

namespace App\Http\Requests\Post;

use App\Data\Post\CreatePostData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body_markdown' => ['nullable', 'string'],
            'tags' => ['nullable', 'array', 'max:5'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    public function toData(): CreatePostData
    {
        return CreatePostData::from($this->validated());
    }
}
