<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\User\CompleteOnboardingStepAction;
use App\Enums\OnboardingStep;
use App\Events\CommentPosted;
use App\Events\PostPublished;

class TrackOnboardingProgress
{
    public function __construct(private readonly CompleteOnboardingStepAction $action) {}

    public function handlePostPublished(PostPublished $event): void
    {
        $this->action->execute($event->post->user, OnboardingStep::FirstPostPublished);
    }

    public function handleCommentPosted(CommentPosted $event): void
    {
        $this->action->execute($event->comment->user, OnboardingStep::FirstCommentLeft);
    }
}
