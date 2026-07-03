<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\ProductImage;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_renders_swipeable_image_gallery(): void
    {
        Role::findOrCreate('member');
        Role::findOrCreate('shop');

        $user = User::factory()->create();
        $user->syncRoles(['member', 'shop']);

        $shop = Shop::query()->create([
            'user_id' => $user->id,
            'name' => 'Test Shop',
            'slug' => 'test-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $category = Category::query()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'status' => Category::STATUS_ACTIVE,
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'name' => 'Gallery Product',
            'slug' => 'gallery-product',
            'image' => 'products/demo/test.jpg',
            'description' => 'A product with gallery images.',
            'selling_price' => 10.00,
            'purchase_price' => 5.00,
            'commission' => 1.00,
            'commission_type' => 'fixed',
            'stock' => 10,
            'status' => Product::STATUS_ACTIVE,
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'image' => 'products/demo/test.jpg',
            'sort_order' => 0,
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'image' => 'products/demo/test2.jpg',
            'sort_order' => 1,
        ]);

        ProductDistribution::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'selling_price' => 10.00,
            'purchase_price' => 5.00,
            'commission' => 1.00,
            'commission_type' => ProductDistribution::COMMISSION_FIXED,
            'status' => ProductDistribution::STATUS_AVAILABLE,
        ]);

        $response = $this->actingAs($user)->get(route('member.products.show', $product));

        $response->assertOk();
        $response->assertSee('x-data="', false);
        $response->assertSee('imgs:', false);
        $response->assertSee('products/demo/test.jpg');
        $response->assertSee('products/demo/test2.jpg');
    }
}
