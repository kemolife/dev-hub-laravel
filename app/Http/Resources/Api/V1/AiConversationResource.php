<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\AiConversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property AiConversation $resource */
class AiConversationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'selected_text' => $this->resource->selected_text,
            'selection_start' => $this->resource->selection_start,
            'selection_end' => $this->resource->selection_end,
            'is_private' => $this->resource->is_private,
            'owner_id' => $this->resource->user_id,
            'messages' => AiMessageResource::collection($this->whenLoaded('messages')),
            'created_at' => $this->resource->created_at,
        ];
    }
}
