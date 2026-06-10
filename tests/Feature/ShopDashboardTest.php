<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShopDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'member']);

        $this->member = User::factory()->create(['status' => 'active']);
        $this->member->assignRole('member');
    }

    public function test_member_with_shop_can_view_shop_dashboard(): void
    {
        Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Demo Shop',
            'slug' => 'demo-shop',
            'status' => 'active',
        ]);

        $this->actingAs($this->member)
            ->get(route('member.shop-dashboard.index'))
            ->assertOk()
            ->assertSee(__('member.shop_dashboard.title'))
            ->assertSee(__('member.shop_dashboard.store_data'));
    }

    public function test_member_without_shop_is_redirected_to_apply(): void
    {
        $this->actingAs($this->member)
            ->get(route('member.shop-dashboard.index'))
            ->assertRedirect(route('member.shop-application.create'));
    }

    public function test_seller_orders_page_requires_shop(): void
    {
        $this->actingAs($this->member)
            ->get(route('member.seller.orders.index'))
            ->assertForbidden();
    }
}
