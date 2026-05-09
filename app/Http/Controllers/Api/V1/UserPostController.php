<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserPostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->enum('status', PostStatus::class);

        $posts = $request->user()
            ->posts()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with('tags')
            ->latest('updated_at')
            ->paginate();

        return PostResource::collection($posts);
    }
}
