<?php

namespace Tests\Unit;

use App\Jobs\FetchProductDescriptionJob;
use App\Models\Category;
use App\Models\Product;
use App\Services\Member\ProductDetailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductDetailRemoteFetchTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_does_not_call_remote_http_on_the_sync_queue(): void
    {
        Http::fake();

        $product = $this->productWithDescription(null);

        $detail = app(ProductDetailService::class)->resolve($product);

        Http::assertNothingSent();
        $this->assertSame($product->name, $detail['description']);
    }

    public function test_resolve_queues_one_fetch_when_the_description_is_poor(): void
    {
        Http::fake();
        Bus::fake();
        config(['queue.default' => 'database']);

        $product = $this->productWithDescription('short');
        $service = app(ProductDetailService::class);

        $first = $service->resolve($product);
        $service->resolve($product->fresh());

        Http::assertNothingSent();
        $this->assertSame('short', $first['description']);
        Bus::assertDispatchedTimes(FetchProductDescriptionJob::class, 1);
    }

    public function test_resolve_skips_the_queue_when_the_description_is_already_rich(): void
    {
        Http::fake();
        Bus::fake();
        config(['queue.default' => 'database']);

        $html = '<p>'.str_repeat('Full product specification for the buyer. ', 4).'</p>';
        $product = $this->productWithDescription($html);

        $detail = app(ProductDetailService::class)->resolve($product);

        Bus::assertNotDispatched(FetchProductDescriptionJob::class);
        $this->assertStringContainsString('Full product specification', $detail['description']);
    }

    private function productWithDescription(?string $description): Product
    {
        $category = Category::query()->create([
            'name' => 'Test',
            'slug' => 'test-'.uniqid(),
            'status' => 'active',
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Remote Fetch Product',
            'slug' => 'remote-fetch-'.uniqid(),
            'description' => $description,
            'selling_price' => 10,
            'purchase_price' => 5,
            'stock' => 1,
            'status' => 'active',
        ]);
    }
}
