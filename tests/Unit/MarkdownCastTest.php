<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Casts\MarkdownCast;
use App\Models\Post;
use App\Support\ReadingTime;
use PHPUnit\Framework\TestCase;

class MarkdownCastTest extends TestCase
{
    public function test_markdown_cast_renders_markdown_to_html_on_set(): void
    {
        $cast = new MarkdownCast;
        $model = new Post;

        $result = $cast->set($model, 'body_markdown', '# Hello World', []);

        $this->assertIsArray($result);
        $this->assertEquals('# Hello World', $result['body_markdown']);
        $this->assertStringContainsString('<h1>Hello World</h1>', $result['body_html']);
        $this->assertArrayHasKey('reading_time_seconds', $result);
        $this->assertIsInt($result['reading_time_seconds']);
    }

    public function test_markdown_cast_handles_null_value(): void
    {
        $cast = new MarkdownCast;
        $model = new Post;

        $result = $cast->set($model, 'body_markdown', null, []);

        $this->assertIsArray($result);
        $this->assertNull($result['body_markdown']);
        $this->assertNull($result['body_html']);
        $this->assertNull($result['reading_time_seconds']);
    }

    public function test_markdown_cast_get_returns_raw_value(): void
    {
        $cast = new MarkdownCast;
        $model = new Post;

        $result = $cast->get($model, 'body_markdown', '# Hello', []);

        $this->assertEquals('# Hello', $result);
    }

    public function test_reading_time_calculates_minutes_correctly(): void
    {
        $readingTime = new ReadingTime(60);

        $this->assertEquals(1, $readingTime->minutes());
        $this->assertEquals('1 min read', $readingTime->label());
    }

    public function test_reading_time_rounds_up_to_nearest_minute(): void
    {
        $readingTime = new ReadingTime(90);

        $this->assertEquals(2, $readingTime->minutes());
        $this->assertEquals('2 min read', $readingTime->label());
    }

    public function test_reading_time_zero_seconds_shows_one_minute(): void
    {
        $readingTime = new ReadingTime(0);

        $this->assertEquals(0, $readingTime->minutes());
    }

    public function test_markdown_cast_calculates_reading_time_for_longer_content(): void
    {
        $cast = new MarkdownCast;
        $model = new Post;

        // 200 words should be about 60 seconds (1 min)
        $words = implode(' ', array_fill(0, 200, 'word'));
        $result = $cast->set($model, 'body_markdown', $words, []);

        $this->assertIsInt($result['reading_time_seconds']);
        $this->assertGreaterThan(0, $result['reading_time_seconds']);
    }
}
