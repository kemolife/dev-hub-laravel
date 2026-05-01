<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WebhookEndpoint> */
class WebhookEndpointFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => fake()->url(),
            'secret' => Str::random(40),
            'events' => ['post.published'],
            'enabled' => true,
            'last_success_at' => null,
            'last_failure_at' => null,
            'failure_count' => 0,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => ['enabled' => false]);
    }
}
