<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use App\Support\Member\ShopOrderStatusBadges;
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

        Role::findOrCreate('member');

        $this->member = User::factory()->create(['status' => 'active']);
        $this->member->assignRole('member');
    }

    public function test_member_with_shop_record_but_no_shop_role_does_not_see_shop_data_on_my_page(): void
    {
        Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Demo Shop',
            'slug' => 'demo-shop',
            'status' => 'active',
            'display_total_sales' => 999.99,
            'display_visitors_today' => 42,
            'star_rating' => 4.5,
        ]);

        $this->actingAs($this->member->fresh())
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertSee($this->member->name)
            ->assertDontSee(__('member.shop_dashboard.store_data'))
            ->assertDontSee(__('member.shop_dashboard.sales_chart'))
            ->assertDontSee('$999.99');
    }

    public function test_shop_role_sees_shop_data_on_my_page(): void
    {
        Role::findOrCreate('shop');
        $this->member->assignRole('shop');

        Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Demo Shop',
            'slug' => 'demo-shop-role',
            'status' => 'active',
            'display_total_sales' => 999.99,
            'display_visitors_today' => 42,
            'star_rating' => 4.5,
        ]);

        $this->actingAs($this->member->fresh())
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertSee('Demo Shop')
            ->assertSee(__('member.shop_dashboard.store_data'))
            ->assertSee(__('member.shop_dashboard.sales_chart'))
            ->assertSee('$999.99')
            ->assertSee('42')
            ->assertDontSee(__('member.my.shop_dashboard'));
    }

    public function test_shop_dashboard_route_redirects_to_my_page(): void
    {
        Role::findOrCreate('shop');
        $this->member->assignRole('shop');

        Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Demo Shop',
            'slug' => 'demo-shop',
            'status' => 'active',
        ]);

        $this->actingAs($this->member)
            ->get(route('member.shop-dashboard.index'))
            ->assertRedirect(route('member.my.index'));
    }

    public function test_shop_dashboard_service_uses_display_overrides(): void
    {
        Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Demo Shop',
            'slug' => 'demo-shop-2',
            'status' => 'active',
            'display_total_sales' => 500,
            'display_orders_today' => 7,
        ]);

        $stats = app(\App\Services\Member\ShopDashboardService::class)->statsFor($this->member);

        $this->assertSame(500.0, $stats['total_sales']);
        $this->assertSame(7, $stats['orders_today']);
        $this->assertCount(10, $stats['chart_labels']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $stats['chart_labels'][0]);
    }

    public function test_member_without_shop_does_not_see_shop_data_on_my_page(): void
    {
        $this->actingAs($this->member)
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertDontSee(__('member.shop_dashboard.store_data'));
    }

    public function test_my_page_order_status_badges_use_actual_seller_counts(): void
    {
        Role::findOrCreate('shop');
        $this->member->assignRole('shop');

        $shop = Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Buff Shop',
            'slug' => 'buff-shop',
            'status' => 'active',
            'display_pending_orders' => 99,
        ]);

        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shop->id,
            'seller_id' => $this->member->id,
            'order_no' => 'ORD-BUFF-001',
            'total' => 100,
            'commission' => 10,
            'purchase_cost' => 60,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => 'wallet',
        ]);

        $this->actingAs($this->member)
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertSee('1', false)
            ->assertDontSee('>99<', false)
            ->assertSee(route('member.seller.orders.index', ['status' => 'pending_payment']), false);
    }

    public function test_shop_role_without_shop_row_uses_seller_order_metrics(): void
    {
        Role::findOrCreate('shop');
        $this->member->assignRole('shop');

        \App\Models\Wallet::query()->updateOrCreate(
            ['user_id' => $this->member->id],
            ['balance' => 0, 'balance_pending' => 0, 'balance_frozen' => 0],
        );

        $buyer = User::factory()->create(['status' => 'active']);
        $buyer->assignRole('member');

        Order::query()->create([
            'user_id' => $buyer->id,
            'seller_id' => $this->member->id,
            'order_no' => 'ORD-ROLE-001',
            'total' => 250,
            'commission' => 40,
            'purchase_cost' => 150,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => 'wallet',
        ]);

        $this->actingAs($this->member)
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertSee('$150.00');
    }

    public function test_seller_orders_page_requires_shop(): void
    {
        $this->actingAs($this->member)
            ->get(route('member.seller.orders.index'))
            ->assertForbidden();
    }

    public function test_shop_order_status_badge_clears_after_viewing_seller_orders(): void
    {
        Role::findOrCreate('shop');
        $this->member->assignRole('shop');

        $shop = Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Badge Shop',
            'slug' => 'badge-shop',
            'status' => 'active',
        ]);

        $buyer = User::factory()->create(['status' => 'active']);

        Order::query()->create([
            'user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'seller_id' => $this->member->id,
            'order_no' => 'ORD-BADGE-001',
            'total' => 120,
            'commission' => 20,
            'purchase_cost' => 70,
            'status' => Order::STATUS_COMPLETED,
            'payment_method' => 'wallet',
            'completed_at' => now(),
        ]);

        $this->assertSame(
            1,
            ShopOrderStatusBadges::unseenCounts($shop, $this->member->id)->get('completed'),
        );

        $this->actingAs($this->member)
            ->get(route('member.seller.orders.index', ['status' => Order::STATUS_COMPLETED]))
            ->assertOk();

        $this->assertSame(
            0,
            ShopOrderStatusBadges::unseenCounts($shop->fresh(), $this->member->id)->get('completed'),
        );

        Order::query()->create([
            'user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'seller_id' => $this->member->id,
            'order_no' => 'ORD-BADGE-002',
            'total' => 90,
            'commission' => 15,
            'purchase_cost' => 50,
            'status' => Order::STATUS_COMPLETED,
            'payment_method' => 'wallet',
            'completed_at' => now(),
        ]);

        $this->assertSame(
            1,
            ShopOrderStatusBadges::unseenCounts($shop->fresh(), $this->member->id)->get('completed'),
        );
    }
}
