<?php

declare(strict_types=1);

namespace App\Actions\Post;

use App\Data\Post\UpdatePostData;
use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Validation\ValidationException;

class UpdatePostAction
{
    /** @throws ValidationException */
    public function execute(Post $post, UpdatePostData $data): Post
    {
        if ($data->status === PostStatus::Published) {
            $titleToCheck = $data->title ?? $post->title;
            $bodyToCheck = $data->bodyMarkdown ?? $post->body_markdown;

            if (empty($titleToCheck) || empty($bodyToCheck)) {
                throw ValidationException::withMessages([
                    'status' => ['A post must have a title and body before it can be published.'],
                ]);
            }
        }

        if ($data->title !== null) {
            $post->title = $data->title;
        }

        if ($data->excerpt !== null) {
            $post->excerpt = $data->excerpt;
        }

        if ($data->bodyMarkdown !== null) {
            $post->body_markdown = $data->bodyMarkdown;
        }

        if ($data->status !== null) {
            $post->status = $data->status;
        }

        $post->save();

        return $post;
    }
}
