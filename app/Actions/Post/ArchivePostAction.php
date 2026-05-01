<?php

declare(strict_types=1);

namespace App\Actions\Post;

use App\Enums\PostStatus;
use App\Models\Post;

class ArchivePostAction
{
    public function execute(Post $post): Post
    {
        $post->status = PostStatus::Archived;
        $post->save();

        return $post;
    }
}
