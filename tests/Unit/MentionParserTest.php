<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\MentionParser;
use PHPUnit\Framework\TestCase;

class MentionParserTest extends TestCase
{
    public function test_extracts_at_usernames_from_markdown(): void
    {
        $text = 'Great post @alice! Have you seen what @bob_dev wrote about this?';

        $mentions = MentionParser::extract($text);

        $this->assertContains('alice', $mentions);
        $this->assertContains('bob_dev', $mentions);
        $this->assertCount(2, $mentions);
    }

    public function test_does_not_extract_email_addresses_as_mentions(): void
    {
        $text = 'Contact me at user@example.com for details.';

        $mentions = MentionParser::extract($text);

        $this->assertEmpty($mentions);
    }

    public function test_returns_unique_mentions(): void
    {
        $text = 'Thanks @alice and @alice for the help!';

        $mentions = MentionParser::extract($text);

        $this->assertCount(1, $mentions);
        $this->assertContains('alice', $mentions);
    }

    public function test_does_not_extract_mentions_preceded_by_word_characters(): void
    {
        $text = 'Email test@domain.com but @charlie is fine.';

        $mentions = MentionParser::extract($text);

        $this->assertCount(1, $mentions);
        $this->assertContains('charlie', $mentions);
    }

    public function test_returns_empty_array_for_text_with_no_mentions(): void
    {
        $text = 'This text has no usernames.';

        $mentions = MentionParser::extract($text);

        $this->assertEmpty($mentions);
    }
}
