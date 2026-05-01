<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Models\Comment;
use App\Models\User;

readonly class DeleteCommentAction
{
    public function execute(User $user, Comment $comment): void
    {
        // Soft-delete regardless; API resource shows tombstone when trashed.
        $comment->delete();
    }
}
