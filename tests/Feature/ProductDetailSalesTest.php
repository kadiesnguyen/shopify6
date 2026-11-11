<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\Shop;
use App\Models\User;
use App\Services\Member\ProductDetailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailSalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_count_is_only_the_quantity_this_shop_sold(): void
    {
        $category = Category::query()->create([
            'name' => 'Safety',
            'slug' => 'safety',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => '8210 N95',
            'slug' => '8210-n95',
            'selling_price' => 36.10,
            'purchase_price' => 25,
            'commission' => 11.10,
            'stock' => 10,
            'status' => 'active',
        ]);

        $unsold = $this->shopListing($product, 'Hai Van Shop', 35);
        $sold = $this->shopListing($product, 'Other Shop', 34);
        $buyer = User::factory()->create(['status' => 'active']);

        $this->order($buyer, $sold['user'], $sold['distribution'], $product, 3, Order::STATUS_COMPLETED);
        $this->order($buyer, $unsold['user'], $unsold['distribution'], $product, 4, Order::STATUS_CANCELLED);

        $detail = app(ProductDetailService::class)->resolve($product, displayShopId: $unsold['shop']->id);

        $this->assertSame(0, $detail['sales_count']);
        $this->assertSame($unsold['shop']->id, $detail['shop']['id']);

        $this->order($buyer, $unsold['user'], $unsold['distribution'], $product, 2, Order::STATUS_PENDING_PAYMENT);

        $detail = app(ProductDetailService::class)->resolve($product->fresh(), displayShopId: $unsold['shop']->id);

        $this->assertSame(2, $detail['sales_count']);
        $this->assertSame(3, app(ProductDetailService::class)->resolve($product->fresh(), displayShopId: $sold['shop']->id)['sales_count']);
    }

    /** @return array{user: User, shop: Shop, distribution: ProductDistribution} */
    private function shopListing(Product $product, string $name, float $sellingPrice): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $shop = Shop::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => str($name)->slug().'-'.$user->id,
            'status' => 'active',
        ]);
        $distribution = ProductDistribution::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'selling_price' => $sellingPrice,
            'purchase_price' => 25,
            'commission' => $sellingPrice - 25,
            'commission_type' => ProductDistribution::COMMISSION_FIXED,
            'status' => ProductDistribution::STATUS_AVAILABLE,
        ]);

        return compact('user', 'shop', 'distribution');
    }

    private function order(
        User $buyer,
        User $seller,
        ProductDistribution $distribution,
        Product $product,
        int $qty,
        string $status,
    ): void {
        $order = Order::query()->create([
            'user_id' => $buyer->id,
            'shop_id' => $seller->shop->id,
            'seller_id' => $seller->id,
            'product_distribution_id' => $distribution->id,
            'order_no' => 'ORD-SALES-'.$seller->id.'-'.$status,
            'total' => 35 * $qty,
            'commission' => 10 * $qty,
            'purchase_cost' => 25 * $qty,
            'status' => $status,
            'payment_method' => 'wallet',
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'qty' => $qty,
            'unit_price' => 35,
            'purchase_price' => 25,
            'commission' => 10,
            'subtotal' => 35 * $qty,
        ]);
    }
}
