<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Member\ProductDetailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailGalleryUrlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_product_images_without_dropping_duplicate_paths(): void
    {
        $category = Category::query()->create([
            'name' => 'Test',
            'slug' => 'test',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Padded Gallery',
            'slug' => 'padded-gallery',
            'image' => 'products/sieummo/same.jpg',
            'selling_price' => 10,
            'purchase_price' => 5,
            'stock' => 1,
            'status' => 'active',
        ]);

        foreach ([0, 1, 2] as $sort) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'image' => 'products/sieummo/same.jpg',
                'sort_order' => $sort,
            ]);
        }

        $detail = app(ProductDetailService::class)->resolve($product->fresh(['images']));

        $this->assertCount(3, $detail['images']);
    }

    public function test_falls_back_to_main_image_when_gallery_rows_missing(): void
    {
        $category = Category::query()->create([
            'name' => 'Test',
            'slug' => 'test-fallback',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Single Image',
            'slug' => 'single-image',
            'image' => 'products/sieummo/only.jpg',
            'selling_price' => 10,
            'purchase_price' => 5,
            'stock' => 1,
            'status' => 'active',
        ]);

        $detail = app(ProductDetailService::class)->resolve($product);

        $this->assertCount(1, $detail['images']);
        $this->assertStringContainsString('products/sieummo/only.jpg', $detail['images'][0]);
    }
}
