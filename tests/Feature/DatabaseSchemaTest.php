<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Language;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_tables_exist(): void
    {
        $tables = [
            'users', 'shops', 'wallets', 'transactions', 'categories', 'products',
            'orders', 'order_items', 'promotions', 'news', 'languages',
            'notifications', 'shipping_addresses', 'banners', 'pages', 'faqs', 'site_settings',
            'invite_codes', 'payment_methods', 'recharge_methods', 'withdrawal_methods',
            'recharge_requests', 'withdrawal_requests', 'password_change_requests',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_user_wallet_relationship(): void
    {
        $user = User::factory()->create();

        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 100,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $this->assertTrue($user->fresh()->wallet->is($wallet));
    }

    public function test_product_belongs_to_category(): void
    {
        $category = Category::query()->create([
            'name' => 'Test',
            'slug' => 'test',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Sample',
            'slug' => 'sample',
            'status' => 'active',
        ]);

        $this->assertTrue($product->category->is($category));
    }

    public function test_order_status_constants(): void
    {
        $this->assertContains('pending_payment', Order::STATUSES);
        $this->assertContains('completed', Order::STATUSES);
    }

    public function test_language_seeder_data_structure(): void
    {
        Language::query()->create([
            'code' => 'vi',
            'name' => 'Tiếng Việt',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('languages', ['code' => 'vi', 'is_default' => true]);
    }
}
