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

    public function test_reprice_sets_tiered_cost_for_products_distributions_and_open_orders(): void
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

        // 6300 / 1.30 = 4846.15, profit = 1453.85 (30% of cost).
        $this->assertSame('4846.15', (string) $product->fresh()->purchase_price);
        $this->assertSame('4846.15', (string) $distribution->fresh()->purchase_price);

        $order->refresh();
        $this->assertSame('4846.15', (string) $order->purchase_cost);
        $this->assertSame('1453.85', (string) $order->commission);
        $this->assertSame('6300.00', (string) $order->total);
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
