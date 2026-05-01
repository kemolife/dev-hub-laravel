<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/** @property PersonalAccessToken $resource */
class PersonalAccessTokenResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'abilities' => $this->resource->abilities,
            'last_used_at' => $this->resource->last_used_at,
            'expires_at' => $this->resource->expires_at,
            'created_at' => $this->resource->created_at,
        ];
    }
}
