<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentPosted
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string>  $mentionedUsernames
     */
    public function __construct(
        public readonly Comment $comment,
        public readonly array $mentionedUsernames,
    ) {}
}
