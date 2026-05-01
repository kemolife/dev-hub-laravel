<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Post\IncrementViewCountAction;
use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IncrementPostViewCountJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Post $post,
        public readonly string $viewerKey,
    ) {}

    public function handle(IncrementViewCountAction $action): void
    {
        $action->execute($this->post, $this->viewerKey);
    }
}
