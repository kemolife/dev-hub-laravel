<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class CommentFactory extends Factory
{
    public function definition(): array
    {
        $bodyMarkdown = fake()->paragraph();
        $converter = new GithubFlavoredMarkdownConverter;
        $bodyHtml = (string) $converter->convert($bodyMarkdown);

        return [
            'user_id' => User::factory(),
            'commentable_type' => Post::class,
            'commentable_id' => Post::factory()->published(),
            'parent_id' => null,
            'body_markdown' => $bodyMarkdown,
            'body_html' => $bodyHtml,
            'edited_at' => null,
        ];
    }

    public function forPost(Post $post): static
    {
        return $this->state(fn (array $attributes) => [
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);
    }

    public function withParent(Comment $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'commentable_type' => $parent->commentable_type,
            'commentable_id' => $parent->commentable_id,
            'parent_id' => $parent->id,
        ]);
    }
}
