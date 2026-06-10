<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_xml_with_static_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee(route('landing.home'), false);
        $response->assertSee(route('landing.news.index'), false);
        $response->assertSee(route('landing.about'), false);
        $response->assertSee(route('landing.contact'), false);
    }

    public function test_sitemap_includes_published_news(): void
    {
        $article = News::query()->create([
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content' => 'Body content',
            'status' => News::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('landing.news.show', $article->slug), false);
    }

    public function test_landing_home_has_seo_meta_tags(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('property="og:title"', false);
        $response->assertSee('name="twitter:card"', false);
        $response->assertSee('application/ld+json', false);
    }

    public function test_robots_txt_disallows_private_areas(): void
    {
        $contents = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Disallow: /admin', $contents);
        $this->assertStringContainsString('Disallow: /home', $contents);
        $this->assertStringContainsString('Sitemap:', $contents);
    }
}
