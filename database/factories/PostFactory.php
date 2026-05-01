<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'user_id' => User::factory(),
            'public_id' => (string) Str::uuid(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'excerpt' => fake()->optional()->paragraph(),
            'body_markdown' => fake()->optional()->paragraphs(3, true),
            'status' => PostStatus::Draft,
            'published_at' => null,
            'view_count' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Published,
            'published_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Archived,
            'published_at' => now()->subDays(fake()->numberBetween(30, 90)),
        ]);
    }
}
