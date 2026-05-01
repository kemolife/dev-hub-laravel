<?php

declare(strict_types=1);

namespace App\Actions\Tag;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\TagNormalizer;

class SyncPostTagsAction
{
    /**
     * Normalize, deduplicate, and sync up to 5 tags on a post.
     *
     * @param  array<string>  $tagNames
     */
    public function execute(Post $post, array $tagNames, User $addedBy): void
    {
        $slugs = collect($tagNames)
            ->map(fn (string $name): string => TagNormalizer::normalize($name))
            ->filter(fn (string $slug): bool => $slug !== '')
            ->unique()
            ->take(5)
            ->values();

        $tags = $slugs->map(function (string $slug) use ($tagNames): Tag {
            $originalName = collect($tagNames)->first(
                fn (string $name): bool => TagNormalizer::normalize($name) === $slug,
                $slug,
            );

            return Tag::firstOrCreate(
                ['slug' => $slug],
                ['name' => $originalName],
            );
        });

        /** @var array<int, array{weight: float, added_by_user_id: int}> $syncData */
        $syncData = $tags->mapWithKeys(fn (Tag $tag): array => [
            $tag->id => [
                'weight' => 1.0,
                'added_by_user_id' => $addedBy->id,
            ],
        ])->all();

        $post->tags()->sync($syncData);

        $tagIds = $tags->pluck('id')->all();

        Tag::whereIn('id', $tagIds)->each(
            fn (Tag $tag): bool => $tag->update(['posts_count' => $tag->posts()->count()])
        );
    }
}
