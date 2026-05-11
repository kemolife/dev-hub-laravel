<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AiConversation;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiConversation>
 */
class AiConversationFactory extends Factory
{
    public function definition(): array
    {
        $start = $this->faker->numberBetween(0, 200);

        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'selected_text' => $this->faker->sentence(),
            'selection_start' => $start,
            'selection_end' => $start + $this->faker->numberBetween(10, 100),
            'is_private' => false,
        ];
    }

    public function private(): static
    {
        return $this->state(['is_private' => true]);
    }
}
