<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Data\Comment\EditCommentData;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use League\CommonMark\GithubFlavoredMarkdownConverter;

readonly class EditCommentAction
{
    /**
     * Minutes after posting that non-admin users may still edit.
     */
    private const int EDIT_WINDOW_MINUTES = 15;

    public function execute(User $user, Comment $comment, EditCommentData $data): Comment
    {
        if (! $user->isAdmin()) {
            $createdAt = $comment->created_at;

            if ($createdAt !== null && $createdAt->diffInMinutes(now()) > self::EDIT_WINDOW_MINUTES) {
                throw ValidationException::withMessages([
                    'body_markdown' => ['Edit window has closed.'],
                ]);
            }
        }

        $converter = new GithubFlavoredMarkdownConverter;
        $bodyHtml = (string) $converter->convert($data->bodyMarkdown);

        $comment->body_markdown = $data->bodyMarkdown;
        $comment->body_html = $bodyHtml;
        $comment->edited_at = now()->toDateTimeString();
        $comment->save();

        return $comment;
    }
}
