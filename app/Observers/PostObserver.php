<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PostObserver
{
    public function creating(Post $post): void
    {
        $post->slug = $this->generateUniqueSlug(Str::slug($post->title));
    }

    public function updating(Post $post): void
    {
        if ($post->isDirty('title') && $post->status === PostStatus::Draft) {
            $post->slug = $this->generateUniqueSlug(Str::slug($post->title), $post->id);
        }
    }

    public function saved(Post $post): void
    {
        if (Cache::supportsTags()) {
            Cache::tags(['posts'])->flush();
        }
    }

    public function deleted(Post $post): void
    {
        if (Cache::supportsTags()) {
            Cache::tags(['posts'])->flush();
        }
    }

    private function generateUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $original = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = Post::withTrashed()->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
