<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sitemap_returns_xml_with_published_posts(): void
    {
        Post::factory()->published()->count(3)->create();

        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml');

        $this->assertStringContainsString('urlset', $response->getContent());
    }

    public function test_draft_posts_are_excluded_from_sitemap(): void
    {
        $published = Post::factory()->published()->create();
        Post::factory()->draft()->count(2)->create();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString($published->slug, $content);
        $this->assertEquals(1, substr_count($content, '<url>') - 1); // -1 for root URL
    }

    public function test_robots_txt_is_accessible(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $this->assertStringContainsString('User-agent: *', $response->getContent());
        $this->assertStringContainsString('Sitemap:', $response->getContent());
    }
}
