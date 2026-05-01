<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Reaction\ToggleReactionAction;
use App\Enums\ReactionType;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReactionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_toggle_reaction_action_adds_a_reaction(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        $action = new ToggleReactionAction;

        $added = $action->execute($user, $post, ReactionType::Like);

        $this->assertTrue($added);
        $this->assertDatabaseHas('reactions', [
            'user_id' => $user->id,
            'reactable_type' => Post::class,
            'reactable_id' => $post->id,
            'type' => ReactionType::Like->value,
        ]);
    }

    public function test_toggle_reaction_action_removes_existing_reaction(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        $action = new ToggleReactionAction;

        $action->execute($user, $post, ReactionType::Like);
        $removed = $action->execute($user, $post, ReactionType::Like);

        $this->assertFalse($removed);
        $this->assertDatabaseMissing('reactions', [
            'user_id' => $user->id,
            'reactable_type' => Post::class,
            'reactable_id' => $post->id,
            'type' => ReactionType::Like->value,
        ]);
    }

    public function test_reactions_count_increments_and_decrements_correctly(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        $action = new ToggleReactionAction;

        $this->assertEquals(0, $post->reactions_count);

        $action->execute($user, $post, ReactionType::Like);
        $post->refresh();
        $this->assertEquals(1, $post->reactions_count);

        $action->execute($user, $post, ReactionType::Like);
        $post->refresh();
        $this->assertEquals(0, $post->reactions_count);
    }
}
