<?php

declare(strict_types=1);

namespace App\Actions\Post;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class IncrementViewCountAction
{
    public function execute(Post $post, string $viewerKey): void
    {
        $cacheKey = "post_view:{$post->id}:{$viewerKey}";

        if (Cache::add($cacheKey, true, 3600)) {
            Post::withoutTimestamps(function () use ($post): void {
                Post::where('id', $post->id)->increment('view_count');
            });
        }
    }
}
