<?php

declare(strict_types=1);

namespace App\Actions\Post;

use App\Actions\Tag\SyncPostTagsAction;
use App\Data\Post\CreatePostData;
use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;

class CreateDraftAction
{
    public function __construct(private readonly SyncPostTagsAction $syncPostTagsAction) {}

    public function execute(User $author, CreatePostData $data): Post
    {
        $post = $author->posts()->create([
            'title' => $data->title,
            'excerpt' => $data->excerpt,
            'body_markdown' => $data->bodyMarkdown,
            'status' => PostStatus::Draft,
        ]);

        if ($data->tags !== null) {
            $this->syncPostTagsAction->execute($post, $data->tags, $author);
        }

        return $post;
    }
}
