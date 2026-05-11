<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MessageRole;
use App\Models\AiConversation;
use App\Models\AiMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiMessage>
 */
class AiMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => AiConversation::factory(),
            'role' => MessageRole::User,
            'content' => $this->faker->paragraph(),
        ];
    }

    public function assistant(): static
    {
        return $this->state(['role' => MessageRole::Assistant]);
    }
}
