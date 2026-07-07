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

    public function test_shop_role_sees_shop_data_on_shop_hub(): void
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

        // My page shows shop identity + link to shop hub (reference layout).
        $this->actingAs($this->member->fresh())
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertSee('Demo Shop')
            ->assertSee(__('member.my.shop_manage'))
            ->assertDontSee(__('member.shop_dashboard.store_data'));

        // Shop hub carries the merchant dashboard layout.
        $this->actingAs($this->member->fresh())
            ->get(route('member.shop-hub.index'))
            ->assertOk()
            ->assertSee(__('member.shop_hub.overview'))
            ->assertSee(__('member.shop_hub.order_rate_month'))
            ->assertSee('$999.99')
            ->assertSee('42');

        $this->actingAs($this->member->fresh())
            ->get(route('member.shop-hub.menu'))
            ->assertOk()
            ->assertSee(__('member.shop_hub.section_shop'))
            ->assertSee(__('member.shop_hub.section_account'))
            ->assertSee(__('member.shop_hub.section_goods'));
    }

    public function test_shop_dashboard_route_redirects_to_shop_hub(): void
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
            ->assertRedirect(route('member.shop-hub.index'));
    }

    public function test_shop_dashboard_service_uses_display_overrides(): void
    {
        Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Demo Shop',
            'slug' => 'demo-shop-2',
            'status' => 'active',
            'display_total_sales' => 500,
            'display_total_orders' => 163,
            'display_balance' => 57939.85,
            'display_orders_today' => 7,
        ]);

        $stats = app(\App\Services\Member\ShopDashboardService::class)->statsFor($this->member);

        $this->assertSame(500.0, $stats['total_sales']);
        $this->assertSame(163, $stats['total_orders']);
        $this->assertSame(57939.85, $stats['available_balance']);
        $this->assertSame(7, $stats['orders_today']);
        $this->assertCount(10, $stats['chart_labels']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $stats['chart_labels'][0]);
        $this->assertArrayHasKey('monthly_chart_labels', $stats);
    }

    public function test_shop_hub_uses_admin_order_status_display_overrides(): void
    {
        Role::findOrCreate('shop');
        $this->member->assignRole('shop');

        $shop = Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Minh Store',
            'slug' => 'minh-store',
            'status' => 'active',
            'display_delivering_orders' => 5,
            'display_received_orders' => 88,
            'display_completed_orders' => 120,
        ]);

        $counts = $shop->orderStatusDisplayCounts($this->member->id);
        $this->assertSame(5, $counts['awaiting_pickup']);
        $this->assertSame(88, $counts['shipped']);
        $this->assertSame(120, $counts['completed']);

        $this->actingAs($this->member->fresh())
            ->get(route('member.shop-hub.index'))
            ->assertOk()
            ->assertSee('Minh Store')
            ->assertDontSee(__('member.my.merchant_after_sales'), false)
            ->assertSee('99+', false);
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

        // My page badge counts fall back to seller order queries without a shop row.
        $this->actingAs($this->member)
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertSee(__('member.my.merchant_pending_payment'))
            ->assertSee(route('member.seller.orders.index', ['status' => 'pending_payment']), false);

        // Shop hub still opens and shows order-derived metrics.
        $this->actingAs($this->member)
            ->get(route('member.shop-hub.index'))
            ->assertOk()
            ->assertSee(__('member.shop_hub.overview'));
    }

    public function test_seller_orders_page_requires_shop(): void
    {
        $this->actingAs($this->member)
            ->get(route('member.seller.orders.index'))
            ->assertForbidden();
    }

    public function test_shop_hub_shows_seller_stats_and_subpages(): void
    {
        Role::findOrCreate('shop');
        $this->member->assignRole('shop');

        $shop = Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Demo Shop',
            'slug' => 'demo-shop-stats',
            'status' => 'active',
            'industry_id' => 'general',
            'credit_score' => 88,
            'star_rating' => 4.0,
        ]);

        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shop->id,
            'seller_id' => $this->member->id,
            'order_no' => 'ORD-COMPLETE-001',
            'total' => 100,
            'commission' => 10,
            'purchase_cost' => 60,
            'status' => Order::STATUS_COMPLETED,
            'payment_method' => 'wallet',
            'completed_at' => now(),
        ]);

        $this->actingAs($this->member->fresh())
            ->get(route('member.shop-hub.index'))
            ->assertOk()
            ->assertSee(__('member.shop_hub.overview'))
            ->assertSee(__('member.shop_hub.order_management'))
            ->assertSee('1', false);

        $this->actingAs($this->member)
            ->get(route('member.shop-hub.rank'))
            ->assertOk()
            ->assertSee(__('member.shop_hub.rank_title'))
            ->assertSee('88');

        $this->actingAs($this->member)
            ->get(route('member.shop-hub.info'))
            ->assertOk()
            ->assertSee(__('member.shop_hub.info_title'))
            ->assertSee('Demo Shop');

        $this->actingAs($this->member)
            ->put(route('member.shop-hub.info.update'), [
                'name' => 'Updated Shop',
                'description' => 'New description',
                'keywords' => 'fashion, shoes',
                'address' => '123 Street',
                'contact_name' => 'Seller Name',
                'phone' => '0901234567',
            ])
            ->assertRedirect(route('member.shop-hub.info'));

        $shop->refresh();
        $this->assertSame('Updated Shop', $shop->name);
        $this->assertSame('fashion, shoes', $shop->keywords);

        $this->actingAs($this->member)
            ->get(route('member.shop-hub.reviews'))
            ->assertOk()
            ->assertSee(__('member.shop_hub.reviews_title'));
    }

    public function test_shop_info_requires_shop_row(): void
    {
        Role::findOrCreate('shop');
        $this->member->assignRole('shop');

        $this->actingAs($this->member)
            ->get(route('member.shop-hub.info'))
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
