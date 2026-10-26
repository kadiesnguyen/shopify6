<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Shop;
use App\Models\ShopSubAccount;
use App\Models\User;
use App\Models\UserPayoutAccount;
use App\Models\Wallet;
use App\Models\WithdrawalMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShopHubApiTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('shop');
        Role::findOrCreate('member');

        $this->seller = User::factory()->create(['status' => 'active', 'phone' => '0900111222']);
        $this->seller->assignRole('shop');

        Wallet::query()->create([
            'user_id' => $this->seller->id,
            'balance' => 500,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $this->shop = Shop::query()->create([
            'user_id' => $this->seller->id,
            'name' => 'Api Seller Shop',
            'slug' => 'api-seller-shop',
            'status' => 'active',
            'credit_score' => 72,
            'star_rating' => 4.2,
        ]);
    }

    public function test_shop_hub_api_returns_dashboard_payload(): void
    {
        Sanctum::actingAs($this->seller);

        $this->getJson('/api/member/shop-hub')
            ->assertOk()
            ->assertJsonStructure([
                'shop' => ['id', 'name', 'merchant_level', 'loyalty_points'],
                'stats',
                'order_status_counts',
                'quick_links',
                'seller_stats',
            ])
            ->assertJsonPath('shop.merchant_level', 'L2');
    }

    public function test_shop_hub_menu_and_info_api(): void
    {
        Sanctum::actingAs($this->seller);

        $this->getJson('/api/member/shop-hub/menu')
            ->assertOk()
            ->assertJsonStructure(['shop', 'account', 'goods']);

        $this->getJson('/api/member/shop-hub/info')
            ->assertOk()
            ->assertJsonPath('name', 'Api Seller Shop');

        $this->putJson('/api/member/shop-hub/info', [
            'name' => 'Updated Api Shop',
            'contact_name' => 'Seller Api',
            'phone' => '0900999888',
            'keywords' => 'shoes',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Api Shop');
    }

    public function test_sub_accounts_and_payout_accounts_api(): void
    {
        Sanctum::actingAs($this->seller);

        $this->postJson('/api/member/shop-hub/sub-accounts', [
            'name' => 'Staff A',
            'username' => 'staff_a',
            'password' => 'secret12',
        ])->assertCreated();

        $account = ShopSubAccount::query()->first();
        $this->assertNotNull($account);

        $this->getJson('/api/member/shop-hub/sub-accounts')
            ->assertOk()
            ->assertJsonPath('data.0.username', 'staff_a');

        $this->deleteJson('/api/member/shop-hub/sub-accounts/'.$account->id)->assertOk();

        $this->postJson('/api/member/payout-accounts', [
            'type' => UserPayoutAccount::TYPE_BANK,
            'bank_name' => 'VCB',
            'account_name' => 'Seller Api',
            'account_number' => '123456',
            'is_default' => true,
        ])->assertCreated();

        $payout = UserPayoutAccount::query()->first();
        $this->getJson('/api/member/payout-accounts')->assertOk()->assertJsonPath('data.0.bank_name', 'VCB');
        $this->deleteJson('/api/member/payout-accounts/'.$payout->id)->assertOk();
    }

    public function test_seller_refund_api(): void
    {
        $buyer = User::factory()->create(['status' => 'active']);
        $buyer->assignRole('member');

        $order = Order::query()->create([
            'user_id' => $buyer->id,
            'shop_id' => $this->shop->id,
            'seller_id' => $this->seller->id,
            'order_no' => 'ORD-REF-API-1',
            'total' => 80,
            'commission' => 10,
            'purchase_cost' => 50,
            'status' => Order::STATUS_COMPLETED,
            'payment_method' => 'wallet',
            'completed_at' => now(),
        ]);

        Sanctum::actingAs($this->seller);

        $this->postJson('/api/member/seller/refunds', [
            'order_id' => $order->id,
            'reason' => 'Damaged item',
        ])->assertCreated();

        $this->getJson('/api/member/seller/refunds')
            ->assertOk()
            ->assertJsonPath('data.0.order_no', 'ORD-REF-API-1');
    }

    public function test_wallet_withdrawal_api(): void
    {
        $method = WithdrawalMethod::query()->create([
            'name' => 'Bank',
            'type' => WithdrawalMethod::TYPE_BANK,
            'config' => ['currency' => 'USD'],
            'status' => WithdrawalMethod::STATUS_ACTIVE,
            'sort_order' => 1,
        ]);

        Sanctum::actingAs($this->seller);
        $this->seller->update(['payment_password' => '123456']);

        $this->postJson('/api/member/wallet/withdrawal', [
            'withdrawal_method_id' => $method->id,
            'amount' => 50,
            'bank_account_name' => 'Seller',
            'bank_name' => 'VCB',
            'bank_account_number' => '999',
            'payment_password' => '123456',
        ])->assertCreated();
    }
}
