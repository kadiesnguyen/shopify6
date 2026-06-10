<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RechargeMethod;
use App\Models\RechargeRequest;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalMethod;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');
    }

    public function test_admin_routes_require_admin_role(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        $member->assignRole('member');

        $this->actingAs($member)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_dashboard_and_users(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee(__('admin.dashboard.total_users'));

        $this->actingAs($this->admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee(__('admin.users.title'));
    }

    public function test_admin_can_create_product(): void
    {
        $category = Category::query()->create([
            'name' => 'Cat',
            'slug' => 'cat',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->post('/admin/products', [
                'category_id' => $category->id,
                'name' => 'Admin Product',
                'selling_price' => 10,
                'purchase_price' => 5,
                'commission' => 1,
                'commission_type' => 'fixed',
                'stock' => 20,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['name' => 'Admin Product']);
    }

    public function test_admin_can_approve_recharge_request(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        $member->assignRole('member');

        $wallet = Wallet::query()->create([
            'user_id' => $member->id,
            'balance' => 0,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $method = RechargeMethod::query()->create([
            'name' => 'Bank',
            'type' => 'bank',
            'status' => 'active',
        ]);

        $request = RechargeRequest::query()->create([
            'user_id' => $member->id,
            'recharge_method_id' => $method->id,
            'amount' => 100,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.recharge-requests.approve', $request))
            ->assertRedirect();

        $this->assertEquals(100, $wallet->fresh()->balance);
        $this->assertEquals('approved', $request->fresh()->status);
    }

    public function test_admin_can_approve_withdrawal_request(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        $member->assignRole('member');

        $wallet = Wallet::query()->create([
            'user_id' => $member->id,
            'balance' => 200,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $method = WithdrawalMethod::query()->create([
            'name' => 'Bank',
            'type' => 'bank',
            'status' => 'active',
        ]);

        $request = WithdrawalRequest::query()->create([
            'user_id' => $member->id,
            'withdrawal_method_id' => $method->id,
            'amount' => 50,
            'status' => 'pending',
            'payout_details' => ['details' => '123456789'],
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.withdrawal-requests.index'))
            ->assertOk()
            ->assertSee(__('admin.requests.withdrawal_title'));

        $this->actingAs($this->admin)
            ->post(route('admin.withdrawal-requests.approve', $request))
            ->assertRedirect();

        $this->assertEquals(150, $wallet->fresh()->balance);
        $this->assertEquals('approved', $request->fresh()->status);
    }

    public function test_admin_can_view_and_manage_shop_product_distributions(): void
    {
        Role::create(['name' => 'shop']);

        $shopOwner = User::factory()->create(['status' => 'active', 'phone' => '0901234567']);
        $shopOwner->assignRole(['member', 'shop']);

        Shop::query()->create([
            'user_id' => $shopOwner->id,
            'name' => 'Distribution Shop',
            'slug' => 'distribution-shop',
            'status' => 'active',
        ]);

        $category = Category::query()->create([
            'name' => 'Cat',
            'slug' => 'cat-dist',
            'status' => 'active',
        ]);

        $product = \App\Models\Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Distribution Product',
            'slug' => 'distribution-product',
            'selling_price' => 99,
            'purchase_price' => 50,
            'commission' => 10,
            'stock' => 5,
            'status' => 'active',
        ]);

        $distribution = \App\Models\ProductDistribution::query()->create([
            'user_id' => $shopOwner->id,
            'product_id' => $product->id,
            'selling_price' => $product->selling_price,
            'purchase_price' => $product->purchase_price,
            'commission' => $product->commission,
            'commission_type' => 'fixed',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.users.distributions.store', $shopOwner), [
                'product_id' => $product->id,
            ])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['show_distributions' => $shopOwner->id]))
            ->assertOk()
            ->assertSee(__('admin.users.distributions.title'))
            ->assertSee('Distribution Product');

        $this->actingAs($this->admin)
            ->patch(route('admin.users.distributions.update', [$shopOwner, $distribution]), [
                'selling_price' => 120,
                'purchase_price' => 55,
                'commission' => 15,
                'commission_type' => 'percent',
            ])
            ->assertRedirect();

        $this->assertEquals('percent', $distribution->fresh()->commission_type);
        $this->assertEquals('120.00', $distribution->fresh()->selling_price);

        $this->actingAs($this->admin)
            ->delete(route('admin.users.distributions.destroy', [$shopOwner, $distribution]))
            ->assertRedirect();

        $this->assertDatabaseMissing('product_distributions', ['id' => $distribution->id]);
    }

    public function test_admin_user_actions_menu_and_modals(): void
    {
        $member = User::factory()->create([
            'status' => 'active',
            'phone' => '0901111222',
            'user_code' => 'U000099',
        ]);
        $member->assignRole('member');

        Wallet::query()->create([
            'user_id' => $member->id,
            'balance' => 100,
            'balance_pending' => 10,
            'balance_frozen' => 5,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(__('admin.users.actions.view_info'), false);

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['show_info' => $member->id]))
            ->assertOk()
            ->assertSee(__('admin.users.actions.info_title'))
            ->assertSee('0901111222');

        $this->actingAs($this->admin)
            ->patch(route('admin.users.balance.update', $member), [
                'balance_pending' => 20,
                'balance' => 150,
                'balance_frozen' => 0,
            ])
            ->assertRedirect(route('admin.users.index'));

        $wallet = $member->wallet->fresh();
        $this->assertEquals('150.00', $wallet->balance);
        $this->assertEquals('20.00', $wallet->balance_pending);

        $this->actingAs($this->admin)
            ->post(route('admin.users.deposit', $member), ['amount' => 50, 'note' => 'Test'])
            ->assertRedirect(route('admin.users.index'));

        $this->assertEquals('200.00', $member->wallet->fresh()->balance);

        $this->actingAs($this->admin)
            ->patch(route('admin.users.password.update', $member), [
                'password' => 'newpass123',
                'password_confirmation' => 'newpass123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->actingAs($this->admin)
            ->patch(route('admin.users.payment-password.update', $member), [
                'payment_password' => '654321',
                'payment_password_confirmation' => '654321',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($member->fresh()->hasPaymentPassword());

        $this->actingAs($this->admin)
            ->post(route('admin.users.toggle-lock', $member))
            ->assertRedirect(route('admin.users.index'));

        $this->assertEquals(User::STATUS_BANNED, $member->fresh()->status);

        $this->actingAs($this->admin)
            ->post(route('admin.users.toggle-distribution-lock', $member))
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($member->fresh()->distribution_locked);
    }
}
