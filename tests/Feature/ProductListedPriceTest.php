<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\User;
use App\Services\Member\ProductDetailService;
use App\Services\Member\ProductDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListedPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_the_selling_price_updates_the_price_shown_on_the_product(): void
    {
        $seller = User::factory()->create(['status' => 'active']);
        $category = Category::query()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Listed Product',
            'slug' => 'listed-product',
            'selling_price' => 100,
            'purchase_price' => 80,
            'commission' => 20,
            'commission_type' => 'fixed',
            'stock' => 4,
            'status' => 'active',
        ]);
        ProductDistribution::query()->create([
            'user_id' => $seller->id,
            'product_id' => $product->id,
            'selling_price' => 90,
            'purchase_price' => 80,
            'commission' => 10,
            'commission_type' => 'fixed',
            'status' => ProductDistribution::STATUS_AVAILABLE,
        ]);

        $product->update([
            'selling_price' => 200,
            'purchase_price' => 140,
            'commission' => 60,
            'commission_type' => 'fixed',
        ]);
        app(ProductDistributionService::class)->syncListedPrices($product->fresh());

        $detail = app(ProductDetailService::class)->resolve($product->fresh());

        $this->assertSame(200.0, $detail['selling_price']);
        $this->assertSame(140.0, $detail['purchase_price']);
        $this->assertSame(60.0, $detail['profit']);
    }
}
