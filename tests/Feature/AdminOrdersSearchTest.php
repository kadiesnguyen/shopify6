<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOrdersSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_orders_search_filters_by_shop_name(): void
    {
        foreach (['admin', 'shop'] as $role) {
            Role::findOrCreate($role);
        }

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $buyer = User::factory()->create([
            'status' => 'active',
            'name' => '0356674288 Buyer',
        ]);
        Wallet::query()->create(['user_id' => $buyer->id, 'balance' => 99992685.61]);

        $sellerA = User::factory()->create(['status' => 'active']);
        $sellerA->assignRole('shop');
        Wallet::query()->create(['user_id' => $sellerA->id, 'balance' => 888.25]);
        $shopA = Shop::query()->create([
            'user_id' => $sellerA->id,
            'name' => 'tesst',
            'slug' => 'tesst-search-a',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $sellerB = User::factory()->create(['status' => 'active']);
        $sellerB->assignRole('shop');
        $shopB = Shop::query()->create([
            'user_id' => $sellerB->id,
            'name' => 'Other Shop',
            'slug' => 'other-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        Order::query()->create([
            'user_id' => $buyer->id,
            'shop_id' => $shopA->id,
            'seller_id' => $sellerA->id,
            'order_no' => 'ORD-TESST-001',
            'total' => 100,
            'commission' => 10,
            'purchase_cost' => 60,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => 'wallet',
        ]);

        Order::query()->create([
            'user_id' => $buyer->id,
            'shop_id' => $shopB->id,
            'seller_id' => $sellerB->id,
            'order_no' => 'ORD-OTHER-001',
            'total' => 50,
            'commission' => 5,
            'purchase_cost' => 30,
            'status' => Order::STATUS_COMPLETED,
            'payment_method' => 'wallet',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['q' => 'tesst']))
            ->assertOk()
            ->assertSee('tesst')
            ->assertSee('0356674288 Buyer')
            ->assertSee('$888.25')
            ->assertSee('$90.00')
            ->assertDontSee('$99,992,685.61')
            ->assertDontSee('wallet')
            ->assertDontSee('Other Shop');

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['shop_id' => $shopA->id, 'q' => 'tesst']))
            ->assertOk()
            ->assertSee('tesst')
            ->assertSee('0356674288 Buyer')
            ->assertSee('$888.25')
            ->assertSee('$90.00')
            ->assertDontSee('wallet')
            ->assertDontSee('Other Shop');
    }

    public function test_admin_shop_search_suggestions_return_matching_shops(): void
    {
        Role::findOrCreate('admin');

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $seller = User::factory()->create(['status' => 'active', 'user_code' => 'U000777']);
        $seller->assignRole('shop');
        Shop::query()->create([
            'user_id' => $seller->id,
            'name' => 'tesst',
            'slug' => 'tesst-suggest',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.shops.search-suggestions', ['q' => 'tes']))
            ->assertOk()
            ->assertJsonPath('items.0.value', 'tesst')
            ->assertJsonPath('items.0.id', fn ($id) => is_int($id));
    }
}
