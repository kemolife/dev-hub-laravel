<?php

declare(strict_types=1);

namespace App\Http\Requests\Post;

use App\Data\Post\UpdatePostData;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Post $post */
        $post = $this->route('post');

        return $this->user()?->can('update', $post) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:500'],
            'body_markdown' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'string', 'in:draft,published,archived'],
        ];
    }

    public function toData(): UpdatePostData
    {
        return UpdatePostData::from($this->validated());
    }
}
