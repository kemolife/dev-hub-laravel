<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property Post $resource */
class PostResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'title' => $this->resource->title,
            'slug' => $this->resource->slug,
            'excerpt' => $this->resource->excerpt,
            'body_markdown' => $this->resource->body_markdown,
            'body_html' => $this->resource->body_html,
            'reading_time' => $this->resource->readingTime()->label(),
            'status' => $this->resource->status,
            'published_at' => $this->resource->published_at,
            'view_count' => $this->resource->view_count,
            'reactions_count' => $this->resource->reactions_count ?? 0,
            'comments_count' => (int) ($this->resource->comments_count ?? 0),
            'is_bookmarked' => $this->whenNotNull($this->resource->is_bookmarked ?? null),
            'tags' => $this->resource->tags->map(fn (Tag $tag): array => [
                'name' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
            ]),
            'author' => new UserResource($this->resource->user),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
