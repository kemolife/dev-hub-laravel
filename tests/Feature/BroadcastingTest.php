<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BroadcastingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_comment_posted_event_broadcasts_on_correct_channel(): void
    {
        Event::fake([CommentPosted::class]);

        $post = Post::factory()->published()->create();
        $user = User::factory()->create();
        $comment = Comment::factory()->forPost($post)->for($user)->create();

        $event = new CommentPosted($comment, []);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertSame('posts.'.$post->id, $channels[0]->name);
    }

    public function test_comment_posted_event_broadcasts_correct_payload(): void
    {
        $post = Post::factory()->published()->create();
        $user = User::factory()->create();
        $comment = Comment::factory()->forPost($post)->for($user)->create();

        $event = new CommentPosted($comment, []);

        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('comment_id', $payload);
        $this->assertArrayHasKey('author', $payload);
        $this->assertArrayHasKey('created_at', $payload);
        $this->assertSame($comment->id, $payload['comment_id']);
        $this->assertSame($user->username, $payload['author']);
    }

    public function test_comment_posted_event_broadcasts_as_correct_event_name(): void
    {
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->forPost($post)->create();

        $event = new CommentPosted($comment, []);

        $this->assertSame('comment.posted', $event->broadcastAs());
    }

    public function test_private_notification_channel_rejects_unauthorized_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        // Retrieve the channel callback registered in routes/channels.php
        $channels = Broadcast::driver()->getChannels();
        $callback = $channels['users.{userId}.notifications'];

        // Unauthorized user (other) attempting to access owner's notification channel
        $result = $callback($other, $owner->id);

        $this->assertFalse($result);
    }

    public function test_private_notification_channel_accepts_correct_user(): void
    {
        $user = User::factory()->create();

        // Retrieve the channel callback registered in routes/channels.php
        $channels = Broadcast::driver()->getChannels();
        $callback = $channels['users.{userId}.notifications'];

        // Correct user accessing their own notification channel
        $result = $callback($user, $user->id);

        $this->assertTrue($result);
    }
}
