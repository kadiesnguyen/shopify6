<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMemberAboutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('member');
        Role::findOrCreate('shop');

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');

        $this->member = User::factory()->create(['status' => 'active']);
        $this->member->assignRole('member');
    }

    public function test_admin_can_view_about_tab_in_settings(): void
    {
        $this->seed(CmsSeeder::class);

        $this->actingAs($this->admin)
            ->get(route('admin.settings.edit', ['tab' => 'about']))
            ->assertOk()
            ->assertSee(__('admin.settings.tabs.about'))
            ->assertSee(__('admin.settings.about_hint'))
            ->assertSee('data-rich-editor', false);
    }

    public function test_admin_can_update_about_content_with_html(): void
    {
        $this->seed(CmsSeeder::class);

        $html = '<h2>Tiêu đề test</h2><p>Nội dung <span style="color:#e11d48">màu đỏ</span>.</p>';

        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'about_content_vi' => $html,
                'active_tab' => 'about',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'about']))
            ->assertSessionHas('status', __('admin.settings.saved'));

        $this->actingAs($this->member)
            ->get(route('member.contract.show'))
            ->assertOk()
            ->assertSee('Tiêu đề test')
            ->assertSee('color:#e11d48', false);
    }

    public function test_shop_can_view_about_page(): void
    {
        $this->seed(CmsSeeder::class);

        $shop = User::factory()->create(['status' => 'active']);
        $shop->assignRole('shop');

        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'about_content_vi' => '<p>Nội dung cho cửa hàng</p>',
                'active_tab' => 'about',
            ]);

        $this->actingAs($shop)
            ->get(route('member.contract.show'))
            ->assertOk()
            ->assertSee('Nội dung cho cửa hàng');
    }
}
