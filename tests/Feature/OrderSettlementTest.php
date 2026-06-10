<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Member\OrderSettlementService;
use App\Services\Member\OrderService;
use App\Services\Member\ProductDistributionService;
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
    }

    public function test_seller_receives_purchase_return_and_commission_when_order_is_completed(): void
    {
        $order = app(OrderService::class)->placeOrder($this->buyer, $this->product);
        $balanceAfterDistribution = (float) $this->seller->wallet->fresh()->balance;

        app(OrderSettlementService::class)->applyStatusChange(
            $order,
            Order::STATUS_AWAITING_PICKUP,
            Order::STATUS_SHIPPED,
        );

        $this->assertSame($balanceAfterDistribution, (float) $this->seller->wallet->fresh()->balance);

        app(OrderSettlementService::class)->applyStatusChange(
            $order->fresh(),
            Order::STATUS_SHIPPED,
            Order::STATUS_COMPLETED,
        );

        $this->assertSame($balanceAfterDistribution + 100.0, (float) $this->seller->wallet->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seller->id,
            'type' => Transaction::TYPE_PURCHASE_RETURN,
            'amount' => 60,
            'reference' => $order->order_no.'-seller-purchase-return',
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seller->id,
            'type' => Transaction::TYPE_COMMISSION,
            'amount' => 40,
            'reference' => $order->order_no.'-seller-commission',
        ]);
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
            Order::STATUS_AWAITING_PICKUP,
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
            Order::STATUS_AWAITING_PICKUP,
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

        $this->assertSame($balanceAfterDistribution + 100.0, (float) $this->seller->wallet->fresh()->balance);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_COMPLETED,
        ]);
    }
}
