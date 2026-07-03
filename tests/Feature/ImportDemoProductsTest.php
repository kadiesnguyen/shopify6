<?php

namespace Tests\Feature;

use Database\Seeders\RoleAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportDemoProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_import_command_creates_categorized_product_with_images_and_shop(): void
    {
        $this->seed(RoleAndAdminSeeder::class);

        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, 'api/Category/index')) {
                return Http::response([
                    'status' => 200,
                    'mess' => 'ok',
                    'data' => [
                        'cateres' => [
                            ['id' => 2, 'cate_name' => 'Trang phục'],
                        ],
                    ],
                ]);
            }

            if (str_contains($url, 'api/Goods/getCategoryGoodsList')) {
                return Http::response([
                    'status' => 200,
                    'mess' => 'ok',
                    'data' => [
                        'goodres' => [
                            [
                                'id' => 123,
                                'goods_name' => 'Demo Dress',
                                'thumb_url' => 'https://example.com/dress.jpg',
                                'min_price' => '22.67',
                                'shop_id' => 999,
                            ],
                        ],
                    ],
                ]);
            }

            if (str_contains($url, 'api/Goods/goodsInfo')) {
                return Http::response([
                    'status' => 200,
                    'mess' => 'ok',
                    'data' => [
                        'goodsinfo' => [
                            'goods_desc' => '<ul><li>Imported</li><li>Polyester</li></ul>',
                            'zs_shop_price' => '22.67',
                        ],
                        'gpres' => [
                            ['img_url' => 'https://example.com/dress.jpg', 'sort' => 0],
                            ['img_url' => 'https://example.com/dress2.jpg', 'sort' => 1],
                        ],
                    ],
                ]);
            }

            if (str_contains($url, 'api/Shops/getShopInfo')) {
                return Http::response([
                    'status' => 200,
                    'mess' => 'ok',
                    'data' => [
                        'shops' => [
                            'id' => 999,
                            'shop_name' => 'Demo Shop',
                            'logo' => 'https://example.com/logo.jpg',
                            'shop_desc' => 'Demo shop description',
                        ],
                    ],
                ]);
            }

            // Image downloads.
            return Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']);
        });

        $this->artisan('demo:import-products', ['--limit' => 1, '--sleep' => 0])
            ->assertSuccessful();

        $this->assertDatabaseHas('categories', ['slug' => 'demo-cate-2', 'name' => 'Trang phục']);
        $this->assertDatabaseHas('shops', ['slug' => 'shop-999', 'name' => 'Demo Shop']);
        $this->assertDatabaseHas('products', [
            'slug' => 'demo-123',
            'name' => 'Demo Dress',
            'selling_price' => 22.67,
        ]);
        $this->assertDatabaseHas('product_images', ['image' => 'products/demo/dress.jpg', 'sort_order' => 0]);
        $this->assertDatabaseHas('product_images', ['image' => 'products/demo/dress2.jpg', 'sort_order' => 1]);

        $this->assertDatabaseHas('model_has_roles', ['model_type' => 'App\\Models\\User']);
    }
}
