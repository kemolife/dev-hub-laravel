<?php

declare(strict_types=1);

namespace App\Actions\Feedback;

use App\Data\Feedback\SubmitFeedbackData;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Http\Request;

readonly class SubmitFeedbackAction
{
    public function execute(SubmitFeedbackData $data, ?User $user, Request $request): Feedback
    {
        return Feedback::create([
            'user_id' => $user?->id,
            'type' => $data->type,
            'description' => $data->description,
            'email' => $data->email ?? $user?->email,
            'url' => $data->url ?? $request->header('Referer'),
            'browser' => $request->userAgent(),
            'metadata' => [
                'ip' => $request->ip(),
                'app_version' => config('app.version', '1.0.0'),
            ],
        ]);
    }
}
