<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReactionType;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reaction>
 */
class ReactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reactable_type' => Post::class,
            'reactable_id' => Post::factory(),
            'type' => fake()->randomElement(ReactionType::cases())->value,
        ];
    }

    public function forPost(Post $post): static
    {
        return $this->state(fn (array $attributes): array => [
            'reactable_type' => Post::class,
            'reactable_id' => $post->id,
        ]);
    }

    public function ofType(ReactionType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type->value,
        ]);
    }
}
