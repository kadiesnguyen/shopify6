<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shop;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\Storage\ShopDocumentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminShopRoleTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake(ShopDocumentStorage::DISK);

        foreach (['admin', 'member', 'shop'] as $role) {
            Role::findOrCreate($role);
        }
    }

    public function test_downgrading_shop_to_member_removes_pending_payment_orders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $seller = User::factory()->create(['status' => 'active']);
        $seller->assignRole(['member', 'shop']);

        $shop = Shop::query()->create([
            'user_id' => $seller->id,
            'name' => 'Downgrade Shop',
            'slug' => 'downgrade-shop',
            'status' => Shop::STATUS_ACTIVE,
            'seller_type' => Shop::TYPE_PERSONAL,
        ]);

        $buyer = User::factory()->create(['status' => 'active']);
        $buyer->assignRole('member');
        Wallet::query()->create([
            'user_id' => $buyer->id,
            'balance' => 400,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $pending = Order::query()->create([
            'user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'seller_id' => $seller->id,
            'order_no' => 'ORD-PENDING-001',
            'total' => 100,
            'commission' => 15,
            'purchase_cost' => 60,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => 'wallet',
            'paid_at' => now(),
        ]);

        Transaction::query()->create([
            'user_id' => $buyer->id,
            'wallet_id' => $buyer->wallet->id,
            'amount' => 100,
            'type' => Transaction::TYPE_ORDER_PAYMENT,
            'status' => Transaction::STATUS_COMPLETED,
            'reference' => 'ORD-PENDING-001',
            'description' => 'Order payment ORD-PENDING-001',
            'processed_at' => now(),
        ]);

        $completed = Order::query()->create([
            'user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'seller_id' => $seller->id,
            'order_no' => 'ORD-DONE-001',
            'total' => 80,
            'commission' => 10,
            'purchase_cost' => 50,
            'status' => Order::STATUS_COMPLETED,
            'payment_method' => 'wallet',
            'completed_at' => now(),
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
            ->assertRedirect();

        $this->assertDatabaseMissing('orders', ['id' => $pending->id]);
        $this->assertDatabaseHas('orders', ['id' => $completed->id]);
        $this->assertFalse($seller->fresh()->isShop());
        $this->assertSame(400.0, (float) $buyer->wallet->fresh()->balance);
        $this->assertDatabaseMissing('transactions', ['reference' => 'ORD-PENDING-001-buyer-refund']);
    }

    public function test_downgrading_shop_to_member_does_not_refund_buyer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $seller = User::factory()->create(['status' => 'active']);
        $seller->assignRole(['member', 'shop']);

        Shop::query()->create([
            'user_id' => $seller->id,
            'name' => 'No Refund Shop',
            'slug' => 'no-refund-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $buyer = User::factory()->create(['status' => 'active']);
        Wallet::query()->create([
            'user_id' => $buyer->id,
            'balance' => 250,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        Order::query()->create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'order_no' => 'ORD-NOREFUND-001',
            'total' => 100,
            'commission' => 15,
            'purchase_cost' => 60,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => 'wallet',
            'paid_at' => now(),
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
            ->assertRedirect();

        $this->assertSame(250.0, (float) $buyer->wallet->fresh()->balance);
        $this->assertDatabaseMissing('orders', ['order_no' => 'ORD-NOREFUND-001']);
    }

    public function test_downgrading_business_shop_to_personal_keeps_pending_payment_orders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $seller = User::factory()->create(['status' => 'active']);
        $seller->assignRole(['member', 'shop']);

        $shop = Shop::query()->create([
            'user_id' => $seller->id,
            'name' => 'Business Shop',
            'slug' => 'business-shop',
            'status' => Shop::STATUS_ACTIVE,
            'seller_type' => Shop::TYPE_BUSINESS,
        ]);

        $buyer = User::factory()->create(['status' => 'active']);
        $buyer->assignRole('member');

        $pending = Order::query()->create([
            'user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'seller_id' => $seller->id,
            'order_no' => 'ORD-KEEP-001',
            'total' => 120,
            'commission' => 20,
            'purchase_cost' => 70,
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
                'role' => 'shop_personal',
                'shop_name' => $shop->name,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => $pending->id]);
        $this->assertTrue($seller->fresh()->isShop());
        $this->assertSame(Shop::TYPE_PERSONAL, $shop->fresh()->seller_type);
    }

    public function test_downgrading_business_shop_to_member_also_removes_pending_payment_orders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $seller = User::factory()->create(['status' => 'active']);
        $seller->assignRole(['member', 'shop']);

        $shop = Shop::query()->create([
            'user_id' => $seller->id,
            'name' => 'Biz Downgrade',
            'slug' => 'biz-downgrade',
            'status' => Shop::STATUS_ACTIVE,
            'seller_type' => Shop::TYPE_BUSINESS,
        ]);

        $buyer = User::factory()->create(['status' => 'active']);

        $pending = Order::query()->create([
            'user_id' => $buyer->id,
            'shop_id' => $shop->id,
            'seller_id' => $seller->id,
            'order_no' => 'ORD-BIZ-PENDING',
            'total' => 90,
            'commission' => 12,
            'purchase_cost' => 55,
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
            ->assertRedirect();

        $this->assertDatabaseMissing('orders', ['id' => $pending->id]);
    }
}
