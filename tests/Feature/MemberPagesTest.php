<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\RechargeMethod;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'member']);
        Role::create(['name' => 'shop']);

        $this->member = User::factory()->create(['status' => 'active']);
        $this->member->assignRole('member');

        Wallet::query()->create([
            'user_id' => $this->member->id,
            'balance' => 500,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        RechargeMethod::query()->create([
            'name' => 'Bank Recharge',
            'type' => 'bank',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        WithdrawalMethod::query()->create([
            'name' => 'Bank Withdrawal',
            'type' => 'bank',
            'status' => 'active',
            'sort_order' => 1,
        ]);
    }

    public function test_member_routes_require_authentication(): void
    {
        $this->get('/home/products')->assertRedirect(route('auth.login'));
    }

    public function test_member_home_page(): void
    {
        $this->actingAs($this->member)
            ->get('/home')
            ->assertOk()
            ->assertSee(__('member.products_for_you'));
    }

    public function test_member_products_and_orders_pages(): void
    {
        $this->actingAs($this->member)
            ->get('/home/products')
            ->assertOk();

        $this->actingAs($this->member)
            ->get('/home/orders')
            ->assertOk()
            ->assertSee(__('member.orders.title'));
    }

    public function test_member_my_and_wallet_pages(): void
    {
        $this->actingAs($this->member)
            ->get('/home/my')
            ->assertOk()
            ->assertSee(__('member.my.recharge'));

        $this->actingAs($this->member)
            ->get('/home/recharge')
            ->assertOk();

        $this->actingAs($this->member)
            ->get('/home/fund-records')
            ->assertOk();
    }

    public function test_member_can_place_order(): void
    {
        $category = Category::query()->create([
            'name' => 'Test',
            'slug' => 'test',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'selling_price' => 10,
            'commission' => 1,
            'stock' => 5,
            'status' => 'active',
        ]);

        $shopOwner = User::factory()->create(['status' => 'active']);
        $shopOwner->assignRole('shop');
        Shop::query()->create([
            'user_id' => $shopOwner->id,
            'name' => 'Catalog Shop',
            'slug' => 'catalog-shop',
            'status' => 'active',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $shopOwner->id,
            'product_id' => $product->id,
            'selling_price' => $product->selling_price,
            'purchase_price' => 0,
            'commission' => 1,
            'commission_type' => 'fixed',
        ]);

        $this->actingAs($this->member)
            ->get(route('member.checkout.show', $product))
            ->assertRedirect(route('member.payment-password.create', ['redirect' => route('member.checkout.show', $product)]));

        $this->member->update(['payment_password' => '123456']);

        $this->actingAs($this->member->fresh())
            ->get(route('member.checkout.show', $product))
            ->assertOk()
            ->assertSee(__('member.checkout.title'));

        \App\Models\ShippingAddress::query()->create([
            'user_id' => $this->member->id,
            'recipient_name' => 'Test User',
            'phone' => '0901234567',
            'country' => 'VN',
            'state' => 'Hanoi',
            'city' => 'Hanoi',
            'address_line' => '123 Test Street',
            'is_default' => true,
        ]);

        $balanceBefore = (float) $this->member->wallet->balance;

        $this->actingAs($this->member->fresh())
            ->post(route('member.checkout.store', $product), [
                'qty' => 1,
                'payment_method' => 'balance',
            ])
            ->assertRedirect(route('member.orders.index', ['status' => 'awaiting_pickup']));

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->member->id,
            'status' => 'awaiting_pickup',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->member->id,
            'type' => 'order_payment',
            'amount' => 10,
        ]);

        $this->assertSame($balanceBefore - 10.0, (float) $this->member->wallet->fresh()->balance);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_member_can_store_payment_password(): void
    {
        $this->assertFalse($this->member->hasPaymentPassword());

        $this->actingAs($this->member)
            ->post(route('member.payment-password.store'), [
                'payment_password' => '123456',
                'payment_password_confirmation' => '123456',
            ])
            ->assertRedirect(route('member.home'))
            ->assertSessionHas('status');

        $this->assertTrue($this->member->fresh()->hasPaymentPassword());
    }

    public function test_member_session_resyncs_after_user_id_changes(): void
    {
        $email = $this->member->email;

        $this->actingAs($this->member)
            ->get(route('member.home'))
            ->assertOk();

        $this->member->delete();

        $replacement = User::factory()->create([
            'email' => $email,
            'status' => 'active',
        ]);
        $replacement->assignRole('member');

        Wallet::query()->create([
            'user_id' => $replacement->id,
            'balance' => 500,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $this->get(route('member.home'))
            ->assertOk();

        $this->assertSame($replacement->id, auth()->id());

        $this->post(route('member.payment-password.store'), [
            'payment_password' => '123456',
            'payment_password_confirmation' => '123456',
        ])->assertRedirect(route('member.home'));

        $this->assertTrue($replacement->fresh()->hasPaymentPassword());
    }

    public function test_member_can_store_shipping_address_with_full_country_name(): void
    {
        $this->actingAs($this->member)
            ->post(route('member.shipping.store'), [
                'recipient_name' => 'Nguyen Van A',
                'phone' => '0901234567',
                'country' => 'Việt Nam',
                'state' => 'Hà Nội',
                'city' => 'Cầu Giấy',
                'address_line' => '123 Phố Test',
                'is_default' => '1',
            ])
            ->assertRedirect(route('member.shipping.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('shipping_addresses', [
            'user_id' => $this->member->id,
            'country' => 'Việt Nam',
            'recipient_name' => 'Nguyen Van A',
        ]);
    }

    public function test_checkout_requires_address_before_placing_order(): void
    {
        $product = Product::query()->create([
            'category_id' => Category::query()->create([
                'name' => 'Test',
                'slug' => 'test-2',
                'status' => 'active',
            ])->id,
            'name' => 'Gated Product',
            'slug' => 'gated-product',
            'selling_price' => 10,
            'commission' => 1,
            'stock' => 5,
            'status' => 'active',
        ]);

        $shopOwner = User::factory()->create(['status' => 'active']);
        $shopOwner->assignRole('shop');
        Shop::query()->create([
            'user_id' => $shopOwner->id,
            'name' => 'Gated Shop',
            'slug' => 'gated-shop',
            'status' => 'active',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $shopOwner->id,
            'product_id' => $product->id,
            'selling_price' => $product->selling_price,
            'purchase_price' => 0,
            'commission' => 1,
            'commission_type' => 'fixed',
        ]);

        $this->member->update(['payment_password' => '123456']);

        $this->actingAs($this->member->fresh())
            ->post(route('member.checkout.store', $product), [
                'qty' => 1,
                'payment_method' => 'balance',
            ])
            ->assertRedirect(route('member.shipping.index', ['redirect' => route('member.checkout.show', $product)]));
    }

    public function test_member_can_submit_recharge_request(): void
    {
        $method = RechargeMethod::query()->first();

        $this->actingAs($this->member)
            ->post('/home/recharge', [
                'recharge_method_id' => $method->id,
                'amount' => 100,
            ])
            ->assertRedirect(route('member.wallet.recharge'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('recharge_requests', [
            'user_id' => $this->member->id,
            'amount' => 100,
        ]);
    }
}
