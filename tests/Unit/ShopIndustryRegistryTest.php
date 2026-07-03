<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Support\ShopIndustryRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopIndustryRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_fashion_shop_cannot_distribute_outside_industry(): void
    {
        $fashion = Category::query()->firstOrCreate(['slug' => 'thoi-trang'], ['name' => 'Thời trang', 'status' => 'active']);
        $phone = Category::query()->firstOrCreate(['slug' => 'dien-thoai'], ['name' => 'Điện Thoại', 'status' => 'active']);

        $shop = Shop::query()->create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Fashion Shop',
            'slug' => 'fashion-shop',
            'industry_id' => 'fashion',
            'business_category_ids' => [$fashion->id],
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $allowedProduct = Product::query()->create([
            'category_id' => $fashion->id,
            'name' => 'Dress',
            'slug' => 'dress',
            'selling_price' => 10,
            'purchase_price' => 5,
            'commission' => 5,
            'stock' => 1,
            'status' => 'active',
        ]);

        $blockedProduct = Product::query()->create([
            'category_id' => $phone->id,
            'name' => 'Phone',
            'slug' => 'phone',
            'selling_price' => 10,
            'purchase_price' => 5,
            'commission' => 5,
            'stock' => 1,
            'status' => 'active',
        ]);

        $registry = app(ShopIndustryRegistry::class);

        $this->assertTrue($registry->shopAllowsProduct($shop, $allowedProduct));
        $this->assertFalse($registry->shopAllowsProduct($shop, $blockedProduct));
    }
}
