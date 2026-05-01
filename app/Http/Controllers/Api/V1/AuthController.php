<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\IssueTokenAction;
use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\RegisterUserAction;
use App\Actions\Auth\TwoFactorChallengeService;
use App\Data\Auth\TwoFactorRequiredData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\TokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterUserAction $registerAction,
        private readonly LoginUserAction $loginAction,
        private readonly LogoutUserAction $logoutAction,
        private readonly IssueTokenAction $issueTokenAction,
        private readonly TwoFactorChallengeService $challengeService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->registerAction->execute($request->toData());
        $tokenData = $this->issueTokenAction->execute($user, $request->input('device_name', 'api'));

        return (new TokenResource($tokenData))->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->loginAction->execute($request->toData());

        if ($result instanceof TwoFactorRequiredData) {
            $challengeToken = $this->challengeService->create($result->userId);

            return response()->json(['two_factor' => true, 'challenge_token' => $challengeToken]);
        }

        return (new TokenResource($result))->response();
    }

    public function logout(Request $request): Response
    {
        $this->logoutAction->execute($request);

        return response()->noContent();
    }
}
