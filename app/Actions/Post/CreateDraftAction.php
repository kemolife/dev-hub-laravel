<?php

declare(strict_types=1);

namespace App\Actions\Post;

use App\Data\Post\CreatePostData;
use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;

class CreateDraftAction
{
    public function execute(User $author, CreatePostData $data): Post
    {
        return $author->posts()->create([
            'title' => $data->title,
            'excerpt' => $data->excerpt,
            'body_markdown' => $data->bodyMarkdown,
            'status' => PostStatus::Draft,
        ]);
    }
}
