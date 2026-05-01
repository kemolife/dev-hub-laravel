<?php

declare(strict_types=1);

namespace App\Http\Requests\Comment;

use App\Data\Comment\PostCommentData;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'body_markdown' => ['required', 'string', 'max:10000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }

    public function toData(): PostCommentData
    {
        return PostCommentData::from($this->validated());
    }
}
