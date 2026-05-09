<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicUserResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function show(Request $request, User $user): PublicUserResource
    {
        Auth::shouldUse('sanctum');

        return new PublicUserResource($user);
    }
}
