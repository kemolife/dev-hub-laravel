<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Events\CommentPosted;
use App\Listeners\SendNewCommentNotifications;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\MentionedInComment;
use App\Notifications\NewCommentOnYourPost;
use App\Notifications\ReplyToYourComment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendNewCommentNotificationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_post_author_receives_notification_when_someone_comments(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->for($author)->published()->create();
        $comment = Comment::factory()->for($commenter)->forPost($post)->create();

        $listener = new SendNewCommentNotifications;
        $listener->handle(new CommentPosted($comment, []));

        Notification::assertSentTo($author, NewCommentOnYourPost::class);
    }

    public function test_author_is_not_notified_when_they_comment_on_their_own_post(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $post = Post::factory()->for($author)->published()->create();
        $comment = Comment::factory()->for($author)->forPost($post)->create();

        $listener = new SendNewCommentNotifications;
        $listener->handle(new CommentPosted($comment, []));

        Notification::assertNothingSentTo($author);
    }

    public function test_parent_comment_author_receives_reply_notification(): void
    {
        Notification::fake();

        $parentAuthor = User::factory()->create();
        $replier = User::factory()->create();
        $post = Post::factory()->published()->create();
        $parent = Comment::factory()->for($parentAuthor)->forPost($post)->create();
        $reply = Comment::factory()->for($replier)->forPost($post)->create(['parent_id' => $parent->id]);

        $listener = new SendNewCommentNotifications;
        $listener->handle(new CommentPosted($reply, []));

        Notification::assertSentTo($parentAuthor, ReplyToYourComment::class);
    }

    public function test_mentioned_users_receive_mention_notification(): void
    {
        Notification::fake();

        $mentionedUser = User::factory()->create(['username' => 'jane']);
        $commenter = User::factory()->create();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->for($commenter)->forPost($post)->create();

        $listener = new SendNewCommentNotifications;
        $listener->handle(new CommentPosted($comment, ['jane']));

        Notification::assertSentTo($mentionedUser, MentionedInComment::class);
    }

    public function test_commenter_is_not_notified_when_they_mention_themselves(): void
    {
        Notification::fake();

        $commenter = User::factory()->create(['username' => 'self_mentioner']);
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->for($commenter)->forPost($post)->create();

        $listener = new SendNewCommentNotifications;
        $listener->handle(new CommentPosted($comment, ['self_mentioner']));

        Notification::assertNothingSentTo($commenter);
    }
}
