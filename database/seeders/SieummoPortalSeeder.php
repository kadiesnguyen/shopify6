<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SieummoPortalSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBanners();
        $this->seedCatalog();
    }

    private function seedBanners(): void
    {
        $banners = [
            ['id' => 1, 'image' => 'images/portal/banners/banner1.jpg', 'sort_order' => 1],
            ['id' => 2, 'image' => 'images/portal/banners/banner2.jpg', 'sort_order' => 2],
            ['id' => 3, 'image' => 'images/portal/banners/banner3.jpg', 'sort_order' => 3],
            ['id' => 4, 'image' => 'images/portal/banners/banner4.jpg', 'sort_order' => 4],
        ];

        foreach ($banners as $banner) {
            Banner::query()->updateOrCreate(
                ['id' => $banner['id']],
                [
                    'image' => $banner['image'],
                    'link_url' => null,
                    'sort_order' => $banner['sort_order'],
                    'status' => 'active',
                    'title' => ['vi' => 'Banner '.$banner['sort_order'], 'en' => 'Banner '.$banner['sort_order']],
                    'subtitle' => null,
                ],
            );
        }
    }

    private function seedCatalog(): void
    {
        $admin = User::role('admin')->first();
        $memberRole = Role::findOrCreate('member');

        if (! $admin) {
            return;
        }

        $categories = [
            ['name' => 'Trang sức', 'slug' => 'trang-suc', 'sort_order' => 1],
            ['name' => 'Đồ Gia Dụng', 'slug' => 'do-gia-dung', 'sort_order' => 2],
            ['name' => 'Túi Sách Hàng Hiệu', 'slug' => 'tui-sach-hang-hieu', 'sort_order' => 3],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                array_merge($category, ['status' => 'active']),
            );
        }

        $shops = [
            [
                'name' => 'Boy Shop',
                'slug' => 'boy-shop',
                'logo' => 'images/portal/shops/boy-shop.jpeg',
                'email' => 'boy-shop@shopefy.test',
            ],
            [
                'name' => 'Tuấn Shop 76',
                'slug' => 'tuan-shop-76',
                'logo' => 'images/portal/shops/tuan-shop-76.jpg',
                'email' => 'tuan-shop-76@shopefy.test',
            ],
        ];

        $shopIds = [];

        foreach ($shops as $shopData) {
            $owner = User::query()->firstOrCreate(
                ['email' => $shopData['email']],
                [
                    'username' => Str::slug($shopData['name']),
                    'user_code' => 'U'.str_pad((string) (900 + count($shopIds)), 6, '0', STR_PAD_LEFT),
                    'name' => $shopData['name'],
                    'phone' => null,
                    'password' => 'password',
                    'status' => 'active',
                ],
            );
            $owner->syncRoles([$memberRole]);

            $shop = Shop::query()->updateOrCreate(
                ['slug' => $shopData['slug']],
                [
                    'user_id' => $owner->id,
                    'name' => $shopData['name'],
                    'description' => 'Shop imported from sieummo.vn',
                    'logo' => $shopData['logo'],
                    'status' => 'active',
                ],
            );

            $shopIds[$shopData['slug']] = $shop->id;
        }

        $products = [
            [
                'name' => 'Earring',
                'slug' => 'earring',
                'category' => 'trang-suc',
                'shop' => 'boy-shop',
                'image' => 'images/portal/products/earring.jpeg',
                'selling_price' => 142.80,
                'purchase_price' => 85.00,
                'commission' => 14.28,
                'stock' => 1972,
            ],
            [
                'name' => 'Mopping Robot',
                'slug' => 'mopping-robot',
                'category' => 'do-gia-dung',
                'shop' => 'boy-shop',
                'image' => 'images/portal/products/mopping-robot.jpeg',
                'selling_price' => 400.99,
                'purchase_price' => 280.00,
                'commission' => 40.10,
                'stock' => 1649,
            ],
            [
                'name' => '14k Gold Polished Sun Charm',
                'slug' => '14k-gold-polished-sun-charm',
                'category' => 'trang-suc',
                'shop' => 'boy-shop',
                'image' => 'images/portal/products/gold-sun-charm.jpeg',
                'selling_price' => 276.20,
                'purchase_price' => 180.00,
                'commission' => 27.62,
                'stock' => 788,
            ],
            [
                'name' => 'Hand bag',
                'slug' => 'hand-bag',
                'category' => 'tui-sach-hang-hieu',
                'shop' => 'tuan-shop-76',
                'image' => 'images/portal/products/hand-bag.jpeg',
                'selling_price' => 155.00,
                'purchase_price' => 95.00,
                'commission' => 15.50,
                'stock' => 1548,
            ],
        ];

        $sieummoSlugs = [];

        foreach ($products as $item) {
            $category = Category::query()->where('slug', $item['category'])->first();
            $shopId = $shopIds[$item['shop']] ?? null;

            if (! $category || ! $shopId) {
                continue;
            }

            $sieummoSlugs[] = $item['slug'];

            Product::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'category_id' => $category->id,
                    'shop_id' => $shopId,
                    'user_id' => $admin->id,
                    'name' => $item['name'],
                    'image' => $item['image'],
                    'description' => $item['name'],
                    'selling_price' => $item['selling_price'],
                    'purchase_price' => $item['purchase_price'],
                    'commission' => $item['commission'],
                    'stock' => $item['stock'],
                    'status' => Product::STATUS_ACTIVE,
                ],
            );
        }

        Product::query()
            ->whereNotIn('slug', $sieummoSlugs)
            ->update(['status' => Product::STATUS_INACTIVE]);
    }
}
