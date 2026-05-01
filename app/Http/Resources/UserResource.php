<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property User $resource */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'name' => $this->resource->name,
            'username' => $this->resource->username,
            'email' => $this->resource->email,
            'bio' => $this->resource->bio,
            'avatar_path' => $this->resource->avatar_path,
            'website_url' => $this->resource->website_url,
            'role' => $this->resource->role,
            'email_verified_at' => $this->resource->email_verified_at,
            'created_at' => $this->resource->created_at,
        ];
    }
}
