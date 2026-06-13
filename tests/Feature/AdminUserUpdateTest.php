<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Support\Storage\ShopDocumentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserUpdateTest extends TestCase
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

    public function test_admin_can_update_user_with_shop_profile_and_display_stats(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create([
            'name' => 'Member Old',
            'phone' => '0900000000',
        ]);
        $member->assignRole('shop');

        $shop = Shop::query()->create([
            'user_id' => $member->id,
            'name' => 'Old Shop',
            'slug' => 'old-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.users.update', $member), [
                'username' => $member->username,
                'user_code' => $member->user_code,
                'name' => 'Member New',
                'email' => $member->email,
                'phone' => '0356674298',
                'status' => User::STATUS_ACTIVE,
                'role' => 'shop_personal',
                'shop_name' => 'New Shop Name',
                'followers' => 12,
                'credit_score' => 88,
                'id_number' => '001122334455',
                'address' => '456 Shop Street',
                'country' => 'Vietnam',
                'shipping_recipient_name' => 'Member New',
                'shipping_phone' => '0356674298',
                'shipping_address' => '123 Test Street',
                'shipping_city' => 'Ho Chi Minh',
                'shipping_state' => 'Ho Chi Minh',
                'shipping_postal_code' => '700000',
                'shipping_country' => 'Vietnam',
                'display_pending_orders' => 3,
                'display_delivering_orders' => 2,
                'display_received_orders' => 1,
                'display_completed_orders' => 9,
                'display_total_income' => 1500.50,
                'display_balance' => 1000000,
                'logo' => UploadedFile::fake()->create('logo.jpg', 100, 'image/jpeg'),
                'id_front' => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
                'id_back' => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
            ]);

        $response->assertRedirect(route('admin.users.index', ['show_edit' => $member->id]));

        $shop->refresh();
        $member->refresh();

        $this->assertSame('Member New', $member->name);
        $this->assertSame('0356674298', $member->phone);
        $this->assertSame('New Shop Name', $shop->name);
        $this->assertSame(12, $shop->followers);
        $this->assertSame(88, $shop->credit_score);
        $this->assertSame('001122334455', $shop->id_number);
        $this->assertSame('456 Shop Street', $shop->address);
        $this->assertSame('Vietnam', $shop->country);
        $this->assertSame(3, $shop->display_pending_orders);
        $this->assertSame(2, $shop->display_delivering_orders);
        $this->assertSame(1, $shop->display_received_orders);
        $this->assertSame(9, $shop->display_completed_orders);
        $this->assertSame('1500.50', $shop->display_total_income);
        $this->assertSame('1000000.00', $shop->display_balance);
        $this->assertStringStartsWith('shops/', $shop->logo);
        $this->assertStringStartsWith('private/shops/', $shop->id_front);
        $this->assertStringStartsWith('private/shops/', $shop->id_back);
        Storage::disk('public')->assertExists($shop->logo);
        Storage::disk(ShopDocumentStorage::DISK)->assertExists($shop->id_front);
        Storage::disk(ShopDocumentStorage::DISK)->assertExists($shop->id_back);
        $this->assertStringContainsString(
            route('admin.users.documents.show', ['user' => $member->id, 'document' => 'id_front']),
            (string) $shop->documentUrl($shop->id_front),
        );

        $this->assertDatabaseHas('shipping_addresses', [
            'user_id' => $member->id,
            'recipient_name' => 'Member New',
            'phone' => '0356674298',
            'address_line' => '123 Test Street',
            'city' => 'Ho Chi Minh',
            'state' => 'Ho Chi Minh',
            'postal_code' => '700000',
            'country' => 'Vietnam',
        ]);
    }

    public function test_edit_user_opens_modal_with_full_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['show_edit' => $member->id]))
            ->assertOk()
            ->assertSee(__('admin.users.actions.edit_title'))
            ->assertSee(__('admin.users.actions.shipping_title'))
            ->assertSee(__('admin.users.actions.shipping_recipient'))
            ->assertSee(__('admin.users.actions.role_change_hint'))
            ->assertSee('wasShop', false);
    }

    public function test_admin_can_update_member_shipping_address(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create([
            'name' => 'Regular Member',
            'phone' => '0900111222',
        ]);
        $member->assignRole('member');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $member), [
                'username' => $member->username,
                'user_code' => $member->user_code,
                'name' => 'Regular Member',
                'email' => $member->email,
                'phone' => '0900111222',
                'status' => User::STATUS_ACTIVE,
                'role' => 'member',
                'shipping_recipient_name' => 'Nguyen Van A',
                'shipping_phone' => '0900111222',
                'shipping_country' => 'Việt Nam',
                'shipping_state' => 'Hồ Chí Minh',
                'shipping_city' => 'Quận 1',
                'shipping_address' => '88 Nguyen Hue',
                'shipping_postal_code' => '700000',
            ])
            ->assertRedirect(route('admin.users.index', ['show_edit' => $member->id]));

        $this->assertDatabaseHas('shipping_addresses', [
            'user_id' => $member->id,
            'recipient_name' => 'Nguyen Van A',
            'phone' => '0900111222',
            'address_line' => '88 Nguyen Hue',
            'city' => 'Quận 1',
            'state' => 'Hồ Chí Minh',
            'postal_code' => '700000',
            'country' => 'Việt Nam',
            'is_default' => true,
        ]);
    }

    public function test_admin_can_update_payment_password_from_edit_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $member), [
                'username' => $member->username,
                'user_code' => $member->user_code,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'status' => User::STATUS_ACTIVE,
                'role' => 'member',
                'payment_password' => '654321',
                'payment_password_confirmation' => '654321',
            ])
            ->assertRedirect(route('admin.users.index', ['show_edit' => $member->id]));

        $this->assertTrue($member->fresh()->hasPaymentPassword());
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('654321', $member->fresh()->getRawOriginal('payment_password')));
    }

    public function test_edit_modal_shows_shop_role_when_user_has_both_member_and_shop_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create();
        $member->assignRole('member');
        $member->assignRole('shop');

        Shop::query()->create([
            'user_id' => $member->id,
            'name' => 'Personal Shop',
            'slug' => 'personal-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->assertSame('shop_personal', $member->fresh()->adminFormRole());

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index', ['show_edit' => $member->id]));

        $response->assertOk();
        $response->assertSee('role: \'shop_personal\'', false);
        $response->assertSee('wasShop: true', false);
        $response->assertSee(__('admin.users.actions.role_downgrade_pending_orders_warning'), false);
        $response->assertSee(__('admin.users.actions.buff_stats_title'));
        $response->assertSee(__('admin.users.actions.shop_name'));
    }

    public function test_admin_can_downgrade_shop_to_member(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create();
        $member->assignRole('member');
        $member->assignRole('shop');

        Shop::query()->create([
            'user_id' => $member->id,
            'name' => 'Downgrade Shop',
            'slug' => 'downgrade-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $member), [
                'username' => $member->username,
                'user_code' => $member->user_code,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'status' => User::STATUS_ACTIVE,
                'role' => 'member',
            ])
            ->assertRedirect(route('admin.users.index', ['show_edit' => $member->id]));

        $member->refresh();

        $this->assertTrue($member->hasRole('member'));
        $this->assertFalse($member->hasRole('shop'));
        $this->assertSame('member', $member->adminFormRole());
    }

    public function test_users_list_shows_member_role_when_shop_record_exists_without_shop_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create(['name' => 'Member With Shop Record']);
        $member->assignRole('member');

        Shop::query()->create([
            'user_id' => $member->id,
            'name' => 'Old Shop',
            'slug' => 'member-with-shop-record-'.$member->id,
            'status' => Shop::STATUS_ACTIVE,
            'seller_type' => Shop::TYPE_PERSONAL,
        ]);

        $this->assertSame('member', $member->fresh()->adminFormRole());

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => $member->email]))
            ->assertOk()
            ->assertSee(__('admin.roles.member'));
    }

    public function test_admin_can_set_business_shop_role_from_edit_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $member), [
                'username' => $member->username,
                'user_code' => $member->user_code,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'status' => User::STATUS_ACTIVE,
                'role' => 'shop_business',
                'shop_name' => 'Business Shop',
            ])
            ->assertRedirect(route('admin.users.index', ['show_edit' => $member->id]));

        $shop = Shop::query()->where('user_id', $member->id)->first();

        $this->assertNotNull($shop);
        $this->assertSame(Shop::TYPE_BUSINESS, $shop->seller_type);
        $this->assertSame('shop_business', $member->fresh()->adminFormRole());
    }

    public function test_admin_can_update_shop_user_with_blank_shop_name(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create(['name' => 'Shop Owner']);
        $member->assignRole('member');
        $member->assignRole('shop');

        $shop = Shop::query()->create([
            'user_id' => $member->id,
            'name' => 'Existing Shop Name',
            'slug' => 'existing-shop-name',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $member), [
                'username' => $member->username,
                'user_code' => $member->user_code,
                'name' => 'Shop Owner Updated',
                'email' => $member->email,
                'phone' => $member->phone,
                'status' => User::STATUS_ACTIVE,
                'role' => 'shop_personal',
                'shop_name' => '',
                'followers' => 0,
                'credit_score' => 0,
                'star_rating' => 0,
            ])
            ->assertRedirect(route('admin.users.index', ['show_edit' => $member->id]));

        $shop->refresh();

        $this->assertSame('Existing Shop Name', $shop->name);
        $this->assertSame('Shop Owner Updated', $member->fresh()->name);
    }

    public function test_admin_can_save_display_stats_for_shop_role_without_existing_shop(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create([
            'name' => 'Shop Owner',
            'user_code' => 'U000099',
        ]);
        $member->assignRole('shop');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $member), [
                'username' => $member->username,
                'user_code' => $member->user_code,
                'name' => 'Shop Owner',
                'email' => $member->email,
                'phone' => $member->phone,
                'status' => User::STATUS_ACTIVE,
                'role' => 'shop_personal',
                'display_pending_orders' => 5,
                'display_completed_orders' => 11,
            ])
            ->assertRedirect(route('admin.users.index', ['show_edit' => $member->id]));

        $shop = Shop::query()->where('user_id', $member->id)->first();

        $this->assertNotNull($shop);
        $this->assertSame('Shop Owner', $shop->name);
        $this->assertSame(5, $shop->display_pending_orders);
        $this->assertSame(11, $shop->display_completed_orders);
    }

    public function test_edit_user_validation_errors_reopen_modal_on_users_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($admin)
            ->from(route('admin.users.index', ['show_edit' => $member->id]))
            ->put(route('admin.users.update', $member).'?'.http_build_query(['show_edit' => $member->id]), [
                'username' => $member->username,
                'user_code' => $member->user_code,
                'name' => '',
                'email' => 'not-an-email',
                'status' => User::STATUS_ACTIVE,
                'role' => 'member',
            ])
            ->assertRedirect(route('admin.users.index', ['show_edit' => $member->id]))
            ->assertSessionHasErrors(['name', 'email']);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $member).'?'.http_build_query(['show_edit' => $member->id]))
            ->assertRedirect(route('admin.users.index', ['show_edit' => $member->id]));
    }

    public function test_shop_id_documents_require_admin_auth(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create();
        $member->assignRole('shop');

        $shop = Shop::query()->create([
            'user_id' => $member->id,
            'name' => 'Doc Shop',
            'slug' => 'doc-shop',
            'status' => Shop::STATUS_ACTIVE,
            'id_front' => 'private/shops/'.$member->id.'/id_front/test-front.jpg',
        ]);

        Storage::disk(ShopDocumentStorage::DISK)->put($shop->id_front, 'front-image');

        $this->get(route('admin.users.documents.show', ['user' => $member, 'document' => 'id_front']))
            ->assertRedirect();

        $this->actingAs($member)
            ->get(route('admin.users.documents.show', ['user' => $member, 'document' => 'id_front']))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.users.documents.show', ['user' => $member, 'document' => 'id_front']))
            ->assertOk();
    }

    public function test_admin_can_toggle_distribution_featured_from_modal(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $member = User::factory()->create();
        $member->assignRole('shop');

        Shop::query()->create([
            'user_id' => $member->id,
            'name' => 'Featured Product Shop',
            'slug' => 'featured-product-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $category = \App\Models\Category::query()->create([
            'name' => 'Featured Toggle Category',
            'slug' => 'featured-toggle-category',
            'status' => 'active',
        ]);

        $product = \App\Models\Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Featured Toggle Product',
            'slug' => 'featured-toggle-product',
            'selling_price' => 20,
            'purchase_price' => 10,
            'commission' => 5,
            'stock' => 10,
            'status' => 'active',
        ]);

        $distribution = \App\Models\ProductDistribution::query()->create([
            'user_id' => $member->id,
            'product_id' => $product->id,
            'selling_price' => 20,
            'purchase_price' => 10,
            'commission' => 5,
            'commission_type' => 'fixed',
            'status' => \App\Models\ProductDistribution::STATUS_AVAILABLE,
            'is_featured' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['show_distributions' => $member->id]))
            ->assertOk()
            ->assertSee(__('admin.users.distributions.featured_hint'), false);

        $this->actingAs($admin)
            ->patch(route('admin.users.distributions.toggle-featured', [$member, $distribution]).'?'.http_build_query(['show_distributions' => $member->id]))
            ->assertRedirect(route('admin.users.index', ['show_distributions' => $member->id]))
            ->assertSessionHas('status');

        $this->assertTrue($distribution->fresh()->is_featured);
    }
}
