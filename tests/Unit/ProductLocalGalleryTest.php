<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Services\Import\DemoProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLocalGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_gallery_backfill_adds_english_description_and_images(): void
    {
        $category = Category::query()->create([
            'name' => 'Massage Chairs',
            'slug' => 'ghe-massage',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Ghế mát xa MEITFITH',
            'slug' => 'sm-massage-chair-1',
            'image' => 'products/sm/chair.jpg',
            'description' => 'Ghế mát xa MEITFITH',
            'selling_price' => 1200,
            'purchase_price' => 700,
            'commission' => 100,
            'stock' => 5,
            'status' => 'active',
        ]);

        $importer = app(DemoProductImporter::class);

        $this->assertTrue($importer->syncLocalGalleryForProduct($product->fresh()));

        $product->refresh()->loadCount('images');

        $this->assertSame(5, $product->images_count);
        $this->assertGreaterThanOrEqual(5, $importer->countDescriptionImages((string) $product->description));
        $this->assertStringContainsString('MEITFITH', $product->description);
        $this->assertStringContainsString('<ul>', $product->description);
        $this->assertStringContainsString('product-desc-gallery', $product->description);
    }

    public function test_english_description_is_deterministic_per_product(): void
    {
        $importer = app(DemoProductImporter::class);

        $category = Category::query()->create([
            'name' => 'Chairs',
            'slug' => 'chairs',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Sample Chair',
            'slug' => 'sample-chair',
            'selling_price' => 50,
            'purchase_price' => 30,
            'stock' => 1,
            'status' => 'active',
        ]);

        $first = $importer->englishDescriptionHtml($product);
        $second = $importer->englishDescriptionHtml($product->fresh());

        $this->assertSame($first, $second);
        $this->assertStringContainsString('Sample Chair', $first);
    }
}
