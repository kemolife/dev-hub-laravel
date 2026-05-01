<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\Auth\AuthTokenData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property AuthTokenData $resource */
class TokenResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource->token,
            'token_type' => $this->resource->tokenType,
            'user' => new UserResource($this->resource->user),
        ];
    }
}
