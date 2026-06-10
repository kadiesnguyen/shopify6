<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\InviteCode;
use App\Models\News;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\RechargeMethod;
use App\Models\Shop;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalMethod;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPaymentMethods();
        $this->seedCategoriesAndProducts();
        $this->seedMemberCommerce();
        $this->seedNews();
        $this->seedInviteCodes();
    }

    private function seedPaymentMethods(): void
    {
        PaymentMethod::query()->upsert([
            ['name' => 'Wallet Balance', 'code' => 'wallet', 'config' => null, 'status' => 'active', 'sort_order' => 1],
            ['name' => 'Bank Transfer', 'code' => 'bank_transfer', 'config' => null, 'status' => 'active', 'sort_order' => 2],
        ], ['code'], ['name', 'config', 'status', 'sort_order']);

        RechargeMethod::query()->upsert([
            ['name' => 'Chain Recharge', 'type' => 'crypto', 'config' => json_encode(['network' => 'USDT-TRC20']), 'status' => 'active', 'sort_order' => 1],
            ['name' => 'Bank Recharge', 'type' => 'bank', 'config' => json_encode(['bank' => 'Demo Bank']), 'status' => 'active', 'sort_order' => 2],
        ], ['name'], ['type', 'config', 'status', 'sort_order']);

        WithdrawalMethod::query()->upsert([
            ['name' => 'Bank Withdrawal', 'type' => 'bank', 'config' => json_encode(['bank' => 'Demo Bank']), 'status' => 'active', 'sort_order' => 1],
            ['name' => 'Crypto Withdrawal', 'type' => 'crypto', 'config' => json_encode(['network' => 'USDT-TRC20']), 'status' => 'active', 'sort_order' => 2],
        ], ['name'], ['type', 'config', 'status', 'sort_order']);
    }

    private function seedCategoriesAndProducts(): void
    {
        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics', 'sort_order' => 1],
            ['name' => 'Fashion', 'slug' => 'fashion', 'sort_order' => 2],
            ['name' => 'Home & Living', 'slug' => 'home-living', 'sort_order' => 3],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                array_merge($category, ['status' => 'active']),
            );
        }

        $member = User::query()->where('email', 'member@shopefy.test')->first();

        if (! $member) {
            return;
        }

        $shop = Shop::query()->updateOrCreate(
            ['user_id' => $member->id],
            [
                'name' => 'Member Shop',
                'slug' => 'member-shop',
                'description' => 'Demo shop for member portal',
                'status' => 'active',
            ],
        );

        \Spatie\Permission\Models\Role::findOrCreate('shop');
        if (! $member->hasRole('shop')) {
            $member->assignRole('shop');
        }

        $products = [
            ['name' => 'Wireless Earbuds', 'slug' => 'wireless-earbuds', 'category' => 'electronics', 'image' => 'images/portal/products/earring.jpeg', 'selling_price' => 49.99, 'purchase_price' => 25.00, 'commission' => 5.00, 'stock' => 120],
            ['name' => 'Smart Watch', 'slug' => 'smart-watch', 'category' => 'electronics', 'image' => 'images/portal/products/mopping-robot.jpeg', 'selling_price' => 129.99, 'purchase_price' => 80.00, 'commission' => 12.00, 'stock' => 45],
            ['name' => 'Casual T-Shirt', 'slug' => 'casual-t-shirt', 'category' => 'fashion', 'image' => 'images/portal/products/hand-bag.jpeg', 'selling_price' => 19.99, 'purchase_price' => 8.00, 'commission' => 2.00, 'stock' => 200],
            ['name' => 'Desk Lamp', 'slug' => 'desk-lamp', 'category' => 'home-living', 'image' => 'images/portal/products/gold-sun-charm.jpeg', 'selling_price' => 34.50, 'purchase_price' => 15.00, 'commission' => 3.50, 'stock' => 80],
            ['name' => 'Running Shoes', 'slug' => 'running-shoes', 'category' => 'fashion', 'image' => 'images/portal/products/hand-bag.jpeg', 'selling_price' => 89.00, 'purchase_price' => 45.00, 'commission' => 8.00, 'stock' => 60],
        ];

        foreach ($products as $item) {
            $category = Category::query()->where('slug', $item['category'])->first();

            Product::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'category_id' => $category->id,
                    'shop_id' => $shop->id,
                    'user_id' => $member->id,
                    'name' => $item['name'],
                    'image' => $item['image'],
                    'description' => 'Demo product: '.$item['name'],
                    'selling_price' => $item['selling_price'],
                    'purchase_price' => $item['purchase_price'],
                    'commission' => $item['commission'],
                    'stock' => $item['stock'],
                    'status' => 'active',
                ],
            );
        }
    }

    private function seedMemberCommerce(): void
    {
        $member = User::query()->where('email', 'member@shopefy.test')->first();

        if (! $member) {
            return;
        }

        $wallet = Wallet::query()->updateOrCreate(
            ['user_id' => $member->id],
            [
                'balance' => 1250.00,
                'balance_pending' => 50.00,
                'balance_frozen' => 0,
            ],
        );

        Transaction::query()->updateOrCreate(
            ['reference' => 'TXN-DEMO-001'],
            [
                'user_id' => $member->id,
                'wallet_id' => $wallet->id,
                'amount' => 500.00,
                'type' => Transaction::TYPE_DEPOSIT,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Initial demo deposit',
                'processed_at' => now()->subDays(3),
            ],
        );

        Transaction::query()->updateOrCreate(
            ['reference' => 'TXN-DEMO-002'],
            [
                'user_id' => $member->id,
                'wallet_id' => $wallet->id,
                'amount' => 12.00,
                'type' => Transaction::TYPE_COMMISSION,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => 'Order commission',
                'processed_at' => now()->subDay(),
            ],
        );

        $product = Product::query()->where('slug', 'wireless-earbuds')->first();
        $shop = Shop::query()->where('user_id', $member->id)->first();

        if ($product && $shop) {
            $order = Order::query()->updateOrCreate(
                ['order_no' => 'ORD-DEMO-001'],
                [
                    'user_id' => $member->id,
                    'shop_id' => $shop->id,
                    'seller_id' => $member->id,
                    'total' => $product->selling_price,
                    'commission' => $product->commission,
                    'status' => Order::STATUS_PENDING_PAYMENT,
                    'payment_method' => 'wallet',
                ],
            );

            OrderItem::query()->updateOrCreate(
                ['order_id' => $order->id, 'product_id' => $product->id],
                [
                    'product_name' => $product->name,
                    'product_image' => $product->image,
                    'qty' => 1,
                    'unit_price' => $product->selling_price,
                    'commission' => $product->commission,
                    'subtotal' => $product->selling_price,
                ],
            );

            Promotion::query()->updateOrCreate(
                ['title' => 'Summer Sale 2026'],
                [
                    'user_id' => $member->id,
                    'shop_id' => $shop->id,
                    'description' => 'Up to 30% off selected items',
                    'image' => 'promotions/summer-sale.jpg',
                    'start_date' => now()->subDays(7)->toDateString(),
                    'end_date' => now()->addDays(23)->toDateString(),
                    'status' => 'active',
                ],
            );
        }

        Notification::query()->updateOrCreate(
            ['user_id' => $member->id, 'title' => 'Welcome to Shopefy'],
            [
                'body' => 'Your account is ready. Start exploring products!',
                'type' => 'info',
                'read_at' => null,
            ],
        );
    }

    private function seedNews(): void
    {
        News::query()->updateOrCreate(
            ['slug' => 'shopefy-launch'],
            [
                'title' => 'Shopefy chính thức ra mắt',
                'content' => '<p>Nền tảng Shopefy đã sẵn sàng phục vụ thành viên.</p>',
                'image' => 'news/launch.jpg',
                'excerpt' => 'Shopefy officially launches',
                'status' => 'published',
                'published_at' => now()->subDays(5),
            ],
        );

        News::query()->updateOrCreate(
            ['slug' => 'seller-tips'],
            [
                'title' => '5 mẹo bán hàng hiệu quả',
                'content' => '<p>Chia sẻ kinh nghiệm bán hàng trực tuyến.</p>',
                'image' => 'news/seller-tips.jpg',
                'excerpt' => 'Tips for online sellers',
                'status' => 'published',
                'published_at' => now()->subDays(2),
            ],
        );
    }

    private function seedInviteCodes(): void
    {
        $admin = User::role('admin')->first();

        InviteCode::query()->updateOrCreate(
            ['code' => 'SHOPEFY2026'],
            [
                'created_by' => $admin?->id,
                'status' => 'active',
            ],
        );
    }
}
