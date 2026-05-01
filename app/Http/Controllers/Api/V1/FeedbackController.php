<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Feedback\SubmitFeedbackAction;
use App\Data\Feedback\SubmitFeedbackData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function __construct(private readonly SubmitFeedbackAction $submitFeedbackAction) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:bug,feature,other'],
            'description' => ['required', 'string', 'max:5000'],
            'email' => ['nullable', 'email'],
            'url' => ['nullable', 'url'],
        ]);

        $feedback = $this->submitFeedbackAction->execute(
            SubmitFeedbackData::from($validated),
            $request->user(),
            $request,
        );

        return response()->json(['id' => $feedback->id], 201);
    }
}
