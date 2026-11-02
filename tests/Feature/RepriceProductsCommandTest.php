<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepriceProductsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reprice_sets_thirty_percent_commission_for_products_distributions_and_open_orders(): void
    {
        $category = Category::query()->create([
            'name' => 'Cat',
            'slug' => 'cat',
            'status' => 'active',
        ]);

        $seller = User::factory()->create(['status' => 'active']);
        $buyer = User::factory()->create(['status' => 'active']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'user_id' => $seller->id,
            'name' => 'Expensive Bag',
            'slug' => 'expensive-bag',
            'selling_price' => 6300,
            'purchase_price' => 3748.5,
            'commission' => 630,
            'stock' => 5,
            'status' => 'active',
        ]);

        $distribution = ProductDistribution::query()->create([
            'user_id' => $seller->id,
            'product_id' => $product->id,
            'selling_price' => 6300,
            'purchase_price' => 3748.5,
            'commission' => 2551.5,
            'commission_type' => ProductDistribution::COMMISSION_FIXED,
            'status' => ProductDistribution::STATUS_AVAILABLE,
        ]);

        $order = Order::query()->create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'product_distribution_id' => $distribution->id,
            'order_no' => 'ORD-REPRICE-1',
            'total' => 6300,
            'commission' => 2551.5,
            'purchase_cost' => 3748.5,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => 'wallet',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'qty' => 1,
            'unit_price' => 6300,
            'purchase_price' => 3748.5,
            'commission' => 2551.5,
            'subtotal' => 6300,
        ]);

        $this->artisan('products:reprice')->assertSuccessful();

        // 30% of 6300 = 1890 commission, leaving 4410 cost.
        $this->assertSame('4410.00', (string) $product->fresh()->purchase_price);
        $this->assertSame('1890.00', (string) $product->fresh()->commission);
        $this->assertSame('4410.00', (string) $distribution->fresh()->purchase_price);
        $this->assertSame('1890.00', (string) $distribution->fresh()->commission);

        $order->refresh();
        $this->assertSame('4410.00', (string) $order->purchase_cost);
        $this->assertSame('1890.00', (string) $order->commission);
        $this->assertSame('6300.00', (string) $order->total);
    }

    public function test_reprice_can_leave_open_orders_at_their_placed_cost(): void
    {
        $category = Category::query()->create(['name' => 'C', 'slug' => 'c', 'status' => 'active']);
        $seller = User::factory()->create(['status' => 'active']);
        $buyer = User::factory()->create(['status' => 'active']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'user_id' => $seller->id,
            'name' => 'Knife Set',
            'slug' => 'knife-set',
            'selling_price' => 66,
            'purchase_price' => 60,
            'commission' => 6,
            'stock' => 5,
            'status' => 'active',
        ]);

        $order = Order::query()->create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'order_no' => 'ORD-OPEN-1',
            'total' => 66,
            'commission' => 6,
            'purchase_cost' => 60,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => 'wallet',
        ]);

        $this->artisan('products:reprice', ['--skip-orders' => true])->assertSuccessful();

        $this->assertSame('46.20', (string) $product->fresh()->purchase_price);
        $this->assertSame('19.80', (string) $product->fresh()->commission);
        $this->assertSame('60.00', (string) $order->fresh()->purchase_cost);
    }

    public function test_reprice_leaves_settled_orders_untouched(): void
    {
        $category = Category::query()->create(['name' => 'C', 'slug' => 'c', 'status' => 'active']);
        $seller = User::factory()->create(['status' => 'active']);
        $buyer = User::factory()->create(['status' => 'active']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'user_id' => $seller->id,
            'name' => 'P',
            'slug' => 'p',
            'selling_price' => 6300,
            'purchase_price' => 3748.5,
            'commission' => 630,
            'stock' => 5,
            'status' => 'active',
        ]);

        $order = Order::query()->create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'order_no' => 'ORD-SETTLED-1',
            'total' => 6300,
            'commission' => 2551.5,
            'purchase_cost' => 3748.5,
            'status' => Order::STATUS_COMPLETED,
            'payment_method' => 'wallet',
            'completed_at' => now(),
        ]);

        $this->artisan('products:reprice')->assertSuccessful();

        $this->assertSame('3748.50', (string) $order->fresh()->purchase_cost);
    }
}
