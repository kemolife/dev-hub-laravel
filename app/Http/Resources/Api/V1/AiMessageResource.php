<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\AiMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property AiMessage $resource */
class AiMessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'role' => $this->resource->role->value,
            'content' => $this->resource->content,
            'created_at' => $this->resource->created_at,
        ];
    }
}
