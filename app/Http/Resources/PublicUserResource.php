<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $authUser = $request->user();

        return [
            'id' => $user->public_id,
            'name' => $user->name,
            'username' => $user->username,
            'bio' => $user->bio,
            'avatar_path' => $user->avatar_path,
            'website_url' => $user->website_url,
            'followers_count' => $user->followers_count,
            'following_count' => $user->following_count,
            'is_following' => $authUser ? $authUser->isFollowing($user) : false,
            'created_at' => $user->created_at,
        ];
    }
}
