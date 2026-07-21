<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminShopRoleTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'member', 'shop'] as $role) {
            Role::findOrCreate($role);
        }
    }

    public function test_role_downgrade_attempt_keeps_shop_role_and_pending_orders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $seller = User::factory()->create(['status' => 'active']);
        $seller->assignRole(['member', 'shop']);

        $shop = Shop::query()->create([
            'user_id' => $seller->id,
            'name' => 'Locked Shop',
            'slug' => 'locked-shop',
            'status' => Shop::STATUS_ACTIVE,
            'seller_type' => Shop::TYPE_PERSONAL,
        ]);

        $buyer = User::factory()->create(['status' => 'active']);
        $buyer->assignRole('member');

        $pending = Order::query()->create([
            'user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'seller_id' => $seller->id,
            'order_no' => 'ORD-LOCKED-001',
            'total' => 100,
            'commission' => 15,
            'purchase_cost' => 60,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => 'wallet',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $seller), [
                'username' => $seller->username,
                'user_code' => $seller->user_code,
                'name' => $seller->name,
                'email' => $seller->email,
                'phone' => $seller->phone,
                'status' => User::STATUS_ACTIVE,
                'role' => 'member',
            ])
            ->assertSessionHasErrors(['role']);

        $this->assertDatabaseHas('orders', ['id' => $pending->id]);
        $this->assertTrue($seller->fresh()->isShop());
    }

    public function test_shops_sync_roles_skips_admin_accounts(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(['admin', 'member']);

        Shop::query()->create([
            'user_id' => $admin->id,
            'name' => 'Admin Shop',
            'slug' => 'admin-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->artisan('shops:sync-roles')->assertSuccessful();

        $admin->refresh();
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->hasRole('member'));
        $this->assertFalse($admin->hasRole('shop'));
    }
}
