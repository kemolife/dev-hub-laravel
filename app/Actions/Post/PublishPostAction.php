<?php

declare(strict_types=1);

namespace App\Actions\Post;

use App\Enums\PostStatus;
use App\Events\PostPublished;
use App\Models\Post;

class PublishPostAction
{
    public function execute(Post $post): Post
    {
        $post->published_at = now();
        $post->status = PostStatus::Published;
        $post->save();

        PostPublished::dispatch($post);

        return $post;
    }
}
