<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Data\Comment\PostCommentData;
use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\User;
use App\Support\MentionParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use League\CommonMark\GithubFlavoredMarkdownConverter;

readonly class PostCommentAction
{
    /**
     * Maximum allowed nesting depth for threaded comments.
     */
    private const int MAX_DEPTH = 4;

    public function execute(User $user, Model $commentable, PostCommentData $data): Comment
    {
        $this->enforceRateLimit($user);

        if ($data->parentId !== null) {
            $this->enforceNestingDepth($data->parentId);
        }

        $bodyHtml = $this->renderMarkdown($data->bodyMarkdown);

        $comment = new Comment([
            'user_id' => $user->id,
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
            'parent_id' => $data->parentId,
            'body_markdown' => $data->bodyMarkdown,
            'body_html' => $bodyHtml,
        ]);

        $comment->save();

        $mentionedUsernames = MentionParser::extract($data->bodyMarkdown);

        CommentPosted::dispatch($comment, $mentionedUsernames);

        return $comment;
    }

    private function enforceRateLimit(User $user): void
    {
        $minuteKey = "comments-minute:{$user->id}";
        $hourKey = "comments-hour:{$user->id}";

        $minuteAllowed = RateLimiter::attempt($minuteKey, 10, fn () => null, 60);
        $hourAllowed = RateLimiter::attempt($hourKey, 50, fn () => null, 3600);

        if (! $minuteAllowed || ! $hourAllowed) {
            throw ValidationException::withMessages([
                'body_markdown' => ['You are posting comments too quickly. Please slow down.'],
            ]);
        }
    }

    private function enforceNestingDepth(int $parentId): void
    {
        $depth = 1;
        $currentId = $parentId;

        while ($currentId !== null) {
            $parent = Comment::find($currentId);

            if ($parent === null) {
                break;
            }

            $depth++;

            if ($depth >= self::MAX_DEPTH) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Maximum comment nesting depth reached.'],
                ]);
            }

            $currentId = $parent->parent_id;
        }
    }

    private function renderMarkdown(string $markdown): string
    {
        $converter = new GithubFlavoredMarkdownConverter;

        return (string) $converter->convert($markdown);
    }
}
