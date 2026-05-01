<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\IssueTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateTokenRequest;
use App\Http\Resources\PersonalAccessTokenResource;
use App\Http\Resources\TokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TokenController extends Controller
{
    public function __construct(private readonly IssueTokenAction $issueTokenAction) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return PersonalAccessTokenResource::collection($request->user()->tokens);
    }

    public function store(CreateTokenRequest $request): JsonResponse
    {
        $data = $request->toData();
        $tokenData = $this->issueTokenAction->execute($request->user(), $data->name, $data->abilities);

        return (new TokenResource($tokenData))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, string $tokenId): Response
    {
        $request->user()->tokens()->where('id', $tokenId)->firstOrFail()->delete();

        return response()->noContent();
    }
}
