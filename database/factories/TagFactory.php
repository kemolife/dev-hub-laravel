<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /** @var list<string> */
    private static array $techTags = [
        'php', 'laravel', 'javascript', 'typescript', 'react', 'vue', 'svelte',
        'nodejs', 'python', 'rust', 'go', 'docker', 'kubernetes', 'devops',
        'postgresql', 'mysql', 'redis', 'elasticsearch', 'graphql', 'rest-api',
        'testing', 'tdd', 'ci-cd', 'open-source', 'linux', 'cloud', 'aws',
        'security', 'performance', 'architecture', 'microservices', 'ddd',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(self::$techTags);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->optional()->hexColor(),
            'posts_count' => 0,
        ];
    }

    public function withColor(string $color): static
    {
        return $this->state(fn (array $attributes): array => [
            'color' => $color,
        ]);
    }
}
