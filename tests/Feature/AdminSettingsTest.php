<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('member');

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_settings_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee(__('admin.menu.settings'))
            ->assertSee(__('admin.settings.profile_marquee'))
            ->assertSee(__('admin.settings.tabs.about'));
    }

    public function test_admin_can_update_text_settings(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/settings', [
                'profile_marquee_text' => 'Cảnh báo profile test',
                'website_title' => 'Shopefy Test',
                'seo_description' => 'Mô tả SEO test',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'general']));

        $this->assertSame('Cảnh báo profile test', SiteSettings::profileMarqueeText());
        $this->assertSame('Shopefy Test', SiteSettings::websiteTitle());
        $this->assertSame('Mô tả SEO test', SiteSettings::seoDescription());
    }

    public function test_profile_page_shows_custom_marquee(): void
    {
        SiteSettings::set(SiteSettings::KEY_PROFILE_MARQUEE, 'Profile marquee unique text');

        $member = User::factory()->create(['status' => 'active']);
        $member->assignRole('member');

        $this->actingAs($member)
            ->get('/home/my')
            ->assertOk()
            ->assertSee('Profile marquee unique text');
    }

    public function test_landing_home_uses_custom_seo_settings(): void
    {
        SiteSettings::set(SiteSettings::KEY_SEO_DESCRIPTION, 'Custom SEO description for sharing');
        SiteSettings::set(SiteSettings::KEY_WEBSITE_TITLE, 'Custom Brand Title');

        $this->get('/')
            ->assertOk()
            ->assertSee('Custom SEO description for sharing', false)
            ->assertSee('Custom Brand Title', false);
    }

    public function test_admin_can_upload_branding_files(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->create('logo.png', 10, 'image/png');
        $favicon = UploadedFile::fake()->create('favicon.png', 10, 'image/png');
        $og = UploadedFile::fake()->create('og.jpg', 10, 'image/jpeg');

        $this->actingAs($this->admin)
            ->put('/admin/settings', [
                'website_title' => 'Upload Test',
                'logo' => $logo,
                'favicon' => $favicon,
                'seo_og_image' => $og,
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'general']));

        Storage::disk('public')->assertExists(SiteSettings::get(SiteSettings::KEY_LOGO));
        Storage::disk('public')->assertExists(SiteSettings::get(SiteSettings::KEY_FAVICON));
        Storage::disk('public')->assertExists(SiteSettings::get(SiteSettings::KEY_SEO_OG_IMAGE));
    }

    public function test_member_cannot_access_settings(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        $member->assignRole('member');

        $this->actingAs($member)
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_admin_can_update_about_content(): void
    {
        $this->seed(\Database\Seeders\CmsSeeder::class);

        $this->actingAs($this->admin)
            ->put('/admin/settings', [
                'about_content_vi' => '<h2>Giá trị test</h2><p>Nội dung <strong>giới thiệu</strong>.</p>',
                'active_tab' => 'about',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'about']));

        $member = User::factory()->create(['status' => 'active']);
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('member.contract.show'))
            ->assertOk()
            ->assertSee('Giá trị test')
            ->assertSee('Nội dung <strong>giới thiệu</strong>', false);
    }

    public function test_admin_can_upload_cms_image(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('cms.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->admin)
            ->post('/admin/settings/cms-images', ['image' => $file])
            ->assertOk()
            ->assertJsonStructure(['url']);

        $path = ltrim(str_replace('/storage/', '', parse_url($response->json('url'), PHP_URL_PATH)), '/');
        Storage::disk('public')->assertExists($path);
    }
}
