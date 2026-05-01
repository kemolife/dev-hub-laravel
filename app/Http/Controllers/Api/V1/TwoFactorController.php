<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\IssueTokenAction;
use App\Actions\Auth\TwoFactorChallengeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Http\Resources\TokenResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorChallengeService $challengeService,
        private readonly IssueTokenAction $issueTokenAction,
    ) {}

    public function challenge(TwoFactorChallengeRequest $request): JsonResponse
    {
        $data = $request->toData();
        $userId = $this->challengeService->consume($data->challengeToken);

        if ($userId === null) {
            throw ValidationException::withMessages([
                'challenge_token' => [__('auth.failed')],
            ]);
        }

        $user = User::findOrFail($userId);

        if (! $this->challengeService->verify($user, $data)) {
            throw ValidationException::withMessages([
                'code' => [__('auth.failed')],
            ]);
        }

        $tokenData = $this->issueTokenAction->execute($user, $data->deviceName);

        return (new TokenResource($tokenData))->response();
    }
}
