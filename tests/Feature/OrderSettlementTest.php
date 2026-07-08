<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Member\OrderSettlementService;
use App\Services\Member\OrderService;
use App\Services\Member\ProductDistributionService;
use App\Support\Member\BellNotificationCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderSettlementTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private User $seller;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('member');
        Role::findOrCreate('shop');

        $this->buyer = User::factory()->create(['status' => 'active']);
        $this->buyer->assignRole('member');
        Wallet::query()->create([
            'user_id' => $this->buyer->id,
            'balance' => 500,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $this->seller = User::factory()->create(['status' => 'active']);
        $this->seller->assignRole(['member', 'shop']);
        Wallet::query()->create([
            'user_id' => $this->seller->id,
            'balance' => 200,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        Shop::query()->create([
            'user_id' => $this->seller->id,
            'name' => 'Seller Shop',
            'slug' => 'seller-shop',
            'status' => 'active',
        ]);

        $category = Category::query()->create([
            'name' => 'Cat',
            'slug' => 'cat',
            'status' => 'active',
        ]);

        $this->product = Product::query()->create([
            'category_id' => $category->id,
            'user_id' => $this->seller->id,
            'shop_id' => $this->seller->shop->id,
            'name' => 'Settlement Product',
            'slug' => 'settlement-product',
            'selling_price' => 100,
            'purchase_price' => 60,
            'commission' => 15,
            'stock' => 10,
            'status' => 'active',
        ]);

        app(ProductDistributionService::class)->distribute($this->seller, $this->product);
    }

    public function test_seller_is_not_charged_product_cost_when_order_is_placed(): void
    {
        $balanceAfterDistribution = (float) $this->seller->wallet->fresh()->balance;

        $order = app(OrderService::class)->placeOrder($this->buyer, $this->product);

        $this->assertSame(60.0, (float) $order->purchase_cost);
        $this->assertSame($balanceAfterDistribution, (float) $this->seller->wallet->fresh()->balance);
        $this->assertDatabaseMissing('transactions', [
            'user_id' => $this->seller->id,
            'type' => Transaction::TYPE_PRODUCT_COST,
            'reference' => $order->order_no.'-seller-cost',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->seller->id,
            'type' => 'order_pending_payment',
        ]);
    }

    public function test_place_order_refreshes_seller_bell_notification_count(): void
    {
        Cache::put('member.bell_unread.'.$this->seller->id, 0, 60);

        app(OrderService::class)->placeOrder($this->buyer, $this->product);

        $this->assertSame(1, BellNotificationCache::unreadCount($this->seller->id));
    }

    public function test_order_routes_to_displayed_shop_not_lowest_id_distribution(): void
    {
        // Platform distribution is created FIRST (lower id) so it would win
        // load-balancing; the buyer was shown the shop created afterwards.
        $product = Product::query()->create([
            'category_id' => $this->product->category_id,
            'user_id' => $this->seller->id,
            'shop_id' => $this->seller->shop->id,
            'name' => 'Routed Product',
            'slug' => 'routed-product',
            'selling_price' => 100,
            'purchase_price' => 60,
            'commission' => 15,
            'stock' => 10,
            'status' => 'active',
        ]);

        $platform = User::factory()->create(['status' => 'active']);
        $platform->assignRole(['member', 'shop']);
        Shop::query()->create([
            'user_id' => $platform->id,
            'name' => 'Platform Shop',
            'slug' => 'platform-shop',
            'status' => 'active',
        ]);
        app(ProductDistributionService::class)->distribute($platform, $product);
        app(ProductDistributionService::class)->distribute($this->seller, $product);

        $shopId = $this->seller->shop->id;

        $order = app(OrderService::class)->placeOrder($this->buyer, $product, 1, $shopId);

        $this->assertSame($this->seller->id, $order->seller_id);
        $this->assertSame($shopId, $order->shop_id);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->seller->id,
            'type' => 'order_pending_payment',
        ]);
    }

    public function test_shop_cannot_place_order_on_own_distribution(): void
    {
        Wallet::query()->where('user_id', $this->seller->id)->update(['balance' => 500]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot_buy_own_shop');

        app(OrderService::class)->placeOrder($this->seller, $this->product);
    }

    public function test_shop_buying_from_own_display_shop_routes_to_other_distributor(): void
    {
        $product = Product::query()->create([
            'category_id' => $this->product->category_id,
            'user_id' => $this->seller->id,
            'shop_id' => $this->seller->shop->id,
            'name' => 'Self Buy Blocked Product',
            'slug' => 'self-buy-blocked-product',
            'selling_price' => 100,
            'purchase_price' => 60,
            'commission' => 15,
            'stock' => 10,
            'status' => 'active',
        ]);

        app(ProductDistributionService::class)->distribute($this->seller, $product);

        $otherSeller = User::factory()->create(['status' => 'active']);
        $otherSeller->assignRole(['member', 'shop']);
        Shop::query()->create([
            'user_id' => $otherSeller->id,
            'name' => 'Other Shop',
            'slug' => 'other-shop',
            'status' => 'active',
        ]);
        app(ProductDistributionService::class)->distribute($otherSeller, $product);

        Wallet::query()->where('user_id', $this->seller->id)->update(['balance' => 500]);

        $order = app(OrderService::class)->placeOrder(
            $this->seller,
            $product,
            1,
            $this->seller->shop->id,
        );

        $this->assertSame($otherSeller->id, $order->seller_id);
        $this->assertNotSame($this->seller->id, $order->seller_id);
    }

    public function test_seller_receives_purchase_return_and_commission_when_order_is_completed(): void
    {
        $order = app(OrderService::class)->placeOrder($this->buyer, $this->product);
        $balanceAfterDistribution = (float) $this->seller->wallet->fresh()->balance;

        app(OrderSettlementService::class)->applyStatusChange(
            $order,
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_SHIPPED,
        );

        $this->assertSame($balanceAfterDistribution, (float) $this->seller->wallet->fresh()->balance);

        app(OrderSettlementService::class)->applyStatusChange(
            $order->fresh(),
            Order::STATUS_SHIPPED,
            Order::STATUS_COMPLETED,
        );

        // selling_price is auto-discounted to 95 on distribute (market 100 - $5),
        // so completion returns purchase 60 + commission 35 = 95.
        $this->assertSame($balanceAfterDistribution + 95.0, (float) $this->seller->wallet->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seller->id,
            'type' => Transaction::TYPE_PURCHASE_RETURN,
            'amount' => 60,
            'reference' => $order->order_no.'-seller-purchase-return',
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seller->id,
            'type' => Transaction::TYPE_COMMISSION,
            'amount' => 35,
            'reference' => $order->order_no.'-seller-commission',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->seller->id,
            'type' => 'order_completed',
        ]);
    }

    public function test_seller_confirm_platform_shipping_deducts_cost_and_moves_to_shipped(): void
    {
        $order = app(OrderService::class)->placeOrder($this->buyer, $this->product);
        $balanceBeforeConfirm = (float) $this->seller->wallet->fresh()->balance;

        app(OrderSettlementService::class)->confirmPlatformShipping($order->fresh());

        $order->refresh();
        $this->assertSame(Order::STATUS_WAITING_SHIPMENT, $order->status);
        $this->assertNull($order->shipped_at);
        $this->assertSame($balanceBeforeConfirm - 60.0, (float) $this->seller->wallet->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seller->id,
            'type' => Transaction::TYPE_PRODUCT_COST,
            'amount' => 60,
            'reference' => $order->order_no.'-seller-cost',
        ]);
    }

    public function test_seller_confirm_platform_shipping_fails_with_insufficient_balance(): void
    {
        $order = app(OrderService::class)->placeOrder($this->buyer, $this->product);
        $this->seller->wallet->update(['balance' => 10]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('insufficient_balance');

        app(OrderSettlementService::class)->confirmPlatformShipping($order->fresh());
    }

    public function test_cancelled_shipped_order_refunds_seller_product_cost(): void
    {
        $order = app(OrderService::class)->placeOrder($this->buyer, $this->product);
        $balanceBeforeConfirm = (float) $this->seller->wallet->fresh()->balance;

        app(OrderSettlementService::class)->confirmPlatformShipping($order->fresh());
        $this->assertSame($balanceBeforeConfirm - 60.0, (float) $this->seller->wallet->fresh()->balance);

        app(OrderSettlementService::class)->applyStatusChange(
            $order->fresh(),
            Order::STATUS_WAITING_SHIPMENT,
            Order::STATUS_CANCELLED,
        );

        $this->assertSame($balanceBeforeConfirm, (float) $this->seller->wallet->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seller->id,
            'reference' => $order->order_no.'-seller-cost-refund',
        ]);
    }

    public function test_seller_confirm_shipping_web_moves_order_between_tabs(): void
    {
        $order = app(OrderService::class)->placeOrder($this->buyer, $this->product);

        $this->actingAs($this->seller)
            ->get(route('member.seller.orders.index', ['status' => Order::STATUS_PENDING_PAYMENT]))
            ->assertOk()
            ->assertSee(__('member.orders.seller_status_pending'), false)
            ->assertSee(__('member.products.cost_price'), false)
            ->assertSee(__('member.products.market_price'), false)
            ->assertSee(__('member.orders.confirm_platform_shipping'), false);

        $this->actingAs($this->seller)
            ->post(route('member.seller.orders.confirm-shipping', $order))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(Order::STATUS_WAITING_SHIPMENT, $order->fresh()->status);

        $counts = \App\Support\Member\ShopOrderStatusBadges::sellerStatusCounts($this->seller->id);
        $this->assertSame(0, $counts['pending_payment']);
        $this->assertSame(1, $counts['awaiting_pickup']);
        $this->assertSame(0, $counts['shipped']);
    }

    public function test_pending_payment_badge_persists_until_confirm(): void
    {
        $order = app(OrderService::class)->placeOrder($this->buyer, $this->product);
        $shop = $this->seller->shop;

        $this->assertSame(
            1,
            \App\Support\Member\ShopOrderStatusBadges::unseenCounts($shop, $this->seller->id)->get('pending_payment'),
        );

        $this->actingAs($this->seller)
            ->get(route('member.seller.orders.index', ['status' => Order::STATUS_PENDING_PAYMENT]))
            ->assertOk();

        $this->assertSame(
            1,
            \App\Support\Member\ShopOrderStatusBadges::unseenCounts($shop->fresh(), $this->seller->id)->get('pending_payment'),
            'Viewing the pending-payment list must not clear the badge.',
        );

        app(OrderSettlementService::class)->confirmPlatformShipping($order->fresh());

        $this->assertSame(
            0,
            \App\Support\Member\ShopOrderStatusBadges::unseenCounts($shop->fresh(), $this->seller->id)->get('pending_payment'),
        );
    }

    public function test_shipped_orders_auto_complete_after_delivery_window(): void
    {
        config(['portal.order_auto_complete_hours' => 1]);

        $order = app(OrderService::class)->placeOrder($this->buyer, $this->product);
        app(OrderSettlementService::class)->confirmPlatformShipping($order->fresh());
        app(OrderSettlementService::class)->applyStatusChange(
            $order->fresh(),
            Order::STATUS_WAITING_SHIPMENT,
            Order::STATUS_SHIPPED,
        );

        $order->update(['shipped_at' => now()->subMinutes(30)]);
        $this->artisan('orders:auto-complete-shipped')->assertSuccessful();
        $this->assertSame(Order::STATUS_SHIPPED, $order->fresh()->status);

        $order->update(['shipped_at' => now()->subHours(2)]);
        $balanceBeforeComplete = (float) $this->seller->wallet->fresh()->balance;

        $this->artisan('orders:auto-complete-shipped')->assertSuccessful();

        $order->refresh();
        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
        $this->assertNotNull($order->completed_at);
        $this->assertSame($balanceBeforeComplete + 95.0, (float) $this->seller->wallet->fresh()->balance);
    }

    public function test_cancelled_order_refunds_buyer_without_returning_distribution_cost_to_seller(): void
    {
        $distribution = \App\Models\ProductDistribution::query()
            ->where('user_id', $this->seller->id)
            ->firstOrFail();

        $this->assertSame(\App\Models\ProductDistribution::STATUS_AVAILABLE, $distribution->status);

        $order = app(OrderService::class)->placeOrder($this->buyer, $this->product);
        $balanceAfterDistribution = (float) $this->seller->wallet->fresh()->balance;

        $this->assertSame(\App\Models\ProductDistribution::STATUS_RESERVED, $distribution->fresh()->status);

        app(OrderSettlementService::class)->applyStatusChange(
            $order,
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_CANCELLED,
        );

        $this->assertSame(\App\Models\ProductDistribution::STATUS_AVAILABLE, $distribution->fresh()->status);
        $this->assertSame($balanceAfterDistribution, (float) $this->seller->wallet->fresh()->balance);
        $this->assertSame(10, $this->product->fresh()->stock);
        $this->assertSame(500.0, (float) $this->buyer->wallet->fresh()->balance);
    }

    public function test_completed_order_restores_distribution_to_shop_manage_list(): void
    {
        $distribution = \App\Models\ProductDistribution::query()
            ->where('user_id', $this->seller->id)
            ->firstOrFail();

        $order = app(OrderService::class)->placeOrder($this->buyer, $this->product);

        $this->assertSame(\App\Models\ProductDistribution::STATUS_RESERVED, $distribution->fresh()->status);

        app(OrderSettlementService::class)->applyStatusChange(
            $order->fresh(),
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_COMPLETED,
        );

        $this->assertSame(\App\Models\ProductDistribution::STATUS_AVAILABLE, $distribution->fresh()->status);
    }

    public function test_admin_can_complete_order_and_settle_commission(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $order = app(OrderService::class)->placeOrder($this->buyer, $this->product);
        $balanceAfterDistribution = (float) $this->seller->wallet->fresh()->balance;

        $this->actingAs($admin)
            ->patch('/admin/orders/'.$order->id, ['status' => Order::STATUS_COMPLETED])
            ->assertRedirect();

        $this->assertSame($balanceAfterDistribution + 95.0, (float) $this->seller->wallet->fresh()->balance);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_COMPLETED,
        ]);
    }

    public function test_pending_payment_status_change_notifies_seller_once(): void
    {
        $order = Order::query()->create([
            'user_id' => $this->buyer->id,
            'shop_id' => $this->seller->shop->id,
            'seller_id' => $this->seller->id,
            'order_no' => 'ORD-PENDING-001',
            'total' => 100,
            'commission' => 40,
            'purchase_cost' => 60,
            'status' => Order::STATUS_AWAITING_PICKUP,
            'payment_method' => 'wallet',
        ]);

        app(OrderSettlementService::class)->applyStatusChange(
            $order,
            Order::STATUS_AWAITING_PICKUP,
            Order::STATUS_PENDING_PAYMENT,
        );

        $this->assertSame(1, Notification::query()
            ->where('user_id', $this->seller->id)
            ->where('type', 'order_pending_payment')
            ->count());
    }

    public function test_admin_delete_order_reverses_wallet_settlements(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $order = app(OrderService::class)->placeOrder($this->buyer, $this->product);
        $balanceAfterDistribution = (float) $this->seller->wallet->fresh()->balance;

        app(OrderSettlementService::class)->applyStatusChange(
            $order,
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_COMPLETED,
        );

        $this->assertSame($balanceAfterDistribution + 95.0, (float) $this->seller->wallet->fresh()->balance);
        $this->assertSame(405.0, (float) $this->buyer->wallet->fresh()->balance);

        $this->actingAs($admin)
            ->delete(route('admin.orders.destroy', $order))
            ->assertRedirect();

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
        $this->assertSame($balanceAfterDistribution, (float) $this->seller->wallet->fresh()->balance);
        $this->assertSame(500.0, (float) $this->buyer->wallet->fresh()->balance);
    }

    public function test_admin_status_progression_syncs_to_seller_orders_page(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $order = Order::query()->create([
            'user_id' => $this->buyer->id,
            'shop_id' => $this->seller->shop->id,
            'seller_id' => $this->seller->id,
            'order_no' => 'ORD-FLOW-001',
            'total' => 100,
            'commission' => 40,
            'purchase_cost' => 60,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => 'wallet',
        ]);

        $flow = [
            Order::STATUS_AWAITING_PICKUP,
            Order::STATUS_WAITING_SHIPMENT,
            Order::STATUS_SHIPPED,
            Order::STATUS_RECEIVED,
            Order::STATUS_COMPLETED,
        ];

        $previous = Order::STATUS_PENDING_PAYMENT;

        foreach ($flow as $next) {
            $this->actingAs($admin)
                ->patch('/admin/orders/'.$order->id, ['status' => $next])
                ->assertRedirect();

            $order->refresh();
            $this->assertSame($next, $order->status);

            $this->actingAs($this->seller)
                ->get(route('member.seller.orders.index', ['status' => $next]))
                ->assertOk()
                ->assertSee($order->order_no, false);

            $previous = $next;
        }

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->seller->id,
            'type' => 'order_completed',
        ]);
    }

    public function test_demo_seeder_does_not_reset_existing_order_status(): void
    {
        $member = User::query()->firstOrCreate(
            ['email' => 'member@shopefy.test'],
            [
                'username' => 'member-demo',
                'user_code' => 'U777777',
                'name' => 'Member Demo',
                'phone' => '+84901234567',
                'password' => bcrypt('password'),
                'status' => 'active',
            ],
        );
        $member->syncRoles(['member', 'shop']);

        $shop = Shop::query()->create([
            'user_id' => $member->id,
            'name' => 'Member Shop',
            'slug' => 'member-shop',
            'status' => 'active',
        ]);

        $category = Category::query()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $shop->id,
            'user_id' => $member->id,
            'name' => 'Wireless Earbuds',
            'slug' => 'wireless-earbuds',
            'selling_price' => 49.99,
            'purchase_price' => 25.00,
            'commission' => 5.00,
            'stock' => 10,
            'status' => 'active',
        ]);

        $order = Order::query()->create([
            'user_id' => $member->id,
            'shop_id' => $shop->id,
            'seller_id' => $member->id,
            'order_no' => 'ORD-DEMO-001',
            'total' => 49.99,
            'commission' => 5,
            'purchase_cost' => 25,
            'status' => Order::STATUS_COMPLETED,
            'payment_method' => 'wallet',
            'completed_at' => now(),
        ]);

        $this->seed(\Database\Seeders\DemoDataSeeder::class);

        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
    }
}
