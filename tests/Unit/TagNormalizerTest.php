<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\TagNormalizer;
use PHPUnit\Framework\TestCase;

class TagNormalizerTest extends TestCase
{
    public function test_it_lowercases_and_strips_special_characters(): void
    {
        $this->assertSame('reactjs', TagNormalizer::normalize('React.js'));
    }

    public function test_it_converts_spaces_to_dashes(): void
    {
        $this->assertSame('node-js', TagNormalizer::normalize('Node JS'));
    }

    public function test_it_collapses_multiple_spaces_to_single_dash(): void
    {
        $this->assertSame('hello-world', TagNormalizer::normalize('hello   world'));
    }

    public function test_it_collapses_multiple_dashes(): void
    {
        $this->assertSame('hello-world', TagNormalizer::normalize('hello---world'));
    }

    public function test_it_truncates_to_50_characters(): void
    {
        $long = str_repeat('a', 60);
        $result = TagNormalizer::normalize($long);

        $this->assertSame(50, strlen($result));
    }

    public function test_it_strips_special_chars_leaving_alphanumeric(): void
    {
        // Special chars are removed entirely (no concatenation)
        $this->assertSame('c', TagNormalizer::normalize('C#'));
        $this->assertSame('c', TagNormalizer::normalize('C++'));
    }

    public function test_it_handles_leading_and_trailing_whitespace(): void
    {
        $this->assertSame('php', TagNormalizer::normalize('  php  '));
    }

    public function test_it_handles_empty_string(): void
    {
        $this->assertSame('', TagNormalizer::normalize(''));
    }

    public function test_it_preserves_existing_dashes(): void
    {
        $this->assertSame('ci-cd', TagNormalizer::normalize('ci-cd'));
    }
}
