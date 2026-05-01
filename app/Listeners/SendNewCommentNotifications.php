<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CommentPosted;
use App\Models\Post;
use App\Models\User;
use App\Notifications\MentionedInComment;
use App\Notifications\NewCommentOnYourPost;
use App\Notifications\ReplyToYourComment;

class SendNewCommentNotifications
{
    public function handle(CommentPosted $event): void
    {
        $comment = $event->comment;
        $post = $comment->commentable;

        // Notify post author — skip if the commenter is the post author
        if ($post instanceof Post && $post->user_id !== $comment->user_id) {
            $post->user->notify(new NewCommentOnYourPost($comment));
        }

        // If this is a reply, notify the parent comment author
        if ($comment->parent_id !== null) {
            $parent = $comment->parent;

            if ($parent !== null && $parent->user_id !== $comment->user_id) {
                $parent->user->notify(new ReplyToYourComment($comment));
            }
        }

        // Notify mentioned users
        foreach ($event->mentionedUsernames as $username) {
            $mentionedUser = User::where('username', $username)->first();

            if ($mentionedUser !== null && $mentionedUser->id !== $comment->user_id) {
                $mentionedUser->notify(new MentionedInComment($comment));
            }
        }
    }
}
