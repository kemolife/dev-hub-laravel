<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property Comment $resource */
class CommentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $trashed = $this->resource->trashed();

        return [
            'id' => $this->resource->id,
            'body_html' => $trashed ? '<p>[deleted]</p>' : $this->resource->body_html,
            'body_markdown' => $trashed ? null : $this->resource->body_markdown,
            'edited_at' => $this->resource->edited_at,
            'created_at' => $this->resource->created_at,
            'author' => $trashed ? null : new UserResource($this->resource->user),
            'replies' => CommentResource::collection($this->resource->replies),
            'reply_count' => $this->resource->replies->count(),
            'is_deleted' => $trashed,
        ];
    }
}
