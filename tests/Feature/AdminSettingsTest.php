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

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_settings_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee(__('admin.menu.settings'))
            ->assertSee(__('admin.settings.portal_home_marquee'));
    }

    public function test_admin_can_update_text_settings(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/settings', [
                'portal_home_marquee_text' => 'Thông báo trang chủ test',
                'profile_marquee_text' => 'Cảnh báo profile test',
                'website_title' => 'Shopefy Test',
                'seo_description' => 'Mô tả SEO test',
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('Thông báo trang chủ test', SiteSettings::portalHomeMarqueeText());
        $this->assertSame('Cảnh báo profile test', SiteSettings::profileMarqueeText());
        $this->assertSame('Shopefy Test', SiteSettings::websiteTitle());
        $this->assertSame('Mô tả SEO test', SiteSettings::seoDescription());
    }

    public function test_portal_home_shows_custom_marquee(): void
    {
        SiteSettings::set(SiteSettings::KEY_PORTAL_HOME_MARQUEE, 'Marquee portal unique text');

        $member = User::factory()->create(['status' => 'active']);
        $member->assignRole('member');

        $this->actingAs($member)
            ->get('/home')
            ->assertOk()
            ->assertSee('Marquee portal unique text');
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
            ->assertRedirect(route('admin.settings.edit'));

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
}
