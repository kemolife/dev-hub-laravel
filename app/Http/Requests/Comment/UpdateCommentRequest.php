<?php

declare(strict_types=1);

namespace App\Http\Requests\Comment;

use App\Data\Comment\EditCommentData;
use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Comment $comment */
        $comment = $this->route('comment');

        return $this->user()->can('update', $comment);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'body_markdown' => ['required', 'string', 'max:10000'],
        ];
    }

    public function toData(): EditCommentData
    {
        return EditCommentData::from($this->validated());
    }
}
