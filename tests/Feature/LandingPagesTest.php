<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_home_renders_sections(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertSee('Lợi Thế Của Chúng Tôi');
        $response->assertSee('/images/landing/hero/TG11.png', false);
        $response->assertSee('/images/landing/features/logistics-bg.jpg', false);
        $response->assertSee('/images/landing/opportunities/case1.jpg', false);
    }

    public function test_default_locale_is_vietnamese(): void
    {
        $this->get('/')->assertOk()->assertSee('Lợi Thế Của Chúng Tôi');
        $this->assertSame('vi', app()->getLocale());
    }

    public function test_news_index_page(): void
    {
        News::query()->create([
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content' => '<p>Body</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get('/tin-tuc')->assertOk()->assertSee('Test Article');
    }

    public function test_news_show_page(): void
    {
        News::query()->create([
            'title' => 'Detail Article',
            'slug' => 'detail-article',
            'content' => '<p>Detail body</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get('/tin-tuc/detail-article')->assertOk()->assertSee('Detail Article');
    }

    public function test_about_page(): void
    {
        $this->get('/gioi-thieu')->assertOk()->assertSee(__('landing.about.title'));
    }

    public function test_contact_form_submission(): void
    {
        $response = $this->post('/lien-he', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '0901234567',
            'message' => 'Hello Shopefy',
        ]);

        $response->assertRedirect(route('landing.contact'));
        $response->assertSessionHas('status');
    }

    public function test_faq_section_on_home_when_seeded(): void
    {
        Faq::query()->create([
            'question' => ['vi' => 'Câu hỏi test', 'en' => 'Test question'],
            'answer' => ['vi' => 'Trả lời test', 'en' => 'Test answer'],
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $this->get('/')->assertOk()->assertSee('Câu hỏi test');
    }
}
