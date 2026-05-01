<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Owner or admin may attempt to update a comment.
     * The edit time window is enforced separately in EditCommentAction.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $user->isAdmin();
    }

    /**
     * Owner, moderator, or admin may delete a comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id
            || $user->isModerator();
    }
}
