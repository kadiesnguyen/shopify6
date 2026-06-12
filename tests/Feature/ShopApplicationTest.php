<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\ShopApplication;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShopApplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        Role::findOrCreate('member');
        Role::findOrCreate('admin');
        Role::findOrCreate('shop');

        $this->member = User::factory()->create(['status' => 'active']);
        $this->member->assignRole('member');

        Wallet::query()->create([
            'user_id' => $this->member->id,
            'balance' => 0,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $this->admin = User::factory()->create(['status' => 'active', 'email' => 'admin@shopefy.test']);
        $this->admin->assignRole('admin');
    }

    public function test_member_can_view_shop_application_form(): void
    {
        $this->actingAs($this->member)
            ->get(route('member.shop-application.create'))
            ->assertOk()
            ->assertSee(__('member.shop_application.title'));
    }

    public function test_member_can_submit_personal_shop_application(): void
    {
        $this->actingAs($this->member)
            ->post(route('member.shop-application.store'), [
                'seller_type' => ShopApplication::TYPE_PERSONAL,
                'shop_name' => 'My Shop',
                'address' => '123 Street',
                'country' => 'VN',
                'phone' => '0901234567',
                'real_name' => 'Nguyen Van A',
                'id_number' => '001234567890',
                'id_front' => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
                'id_back' => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
                'terms' => '1',
            ])
            ->assertRedirect(route('member.shop-application.create'));

        $this->assertDatabaseHas('shop_applications', [
            'user_id' => $this->member->id,
            'shop_name' => 'My Shop',
            'seller_type' => ShopApplication::TYPE_PERSONAL,
            'application_kind' => ShopApplication::KIND_REGISTRATION,
            'status' => ShopApplication::STATUS_PENDING,
        ]);
    }

    public function test_admin_can_approve_personal_shop_application(): void
    {
        $application = ShopApplication::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => ShopApplication::TYPE_PERSONAL,
            'application_kind' => ShopApplication::KIND_REGISTRATION,
            'shop_name' => 'Approved Shop',
            'address' => '123 Street',
            'country' => 'VN',
            'phone' => '0901234567',
            'real_name' => 'Nguyen Van A',
            'id_number' => '001234567890',
            'status' => ShopApplication::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.shop-applications.approve', $application))
            ->assertRedirect();

        $this->assertDatabaseHas('shops', [
            'user_id' => $this->member->id,
            'name' => 'Approved Shop',
            'seller_type' => Shop::TYPE_PERSONAL,
        ]);

        $this->assertTrue($this->member->fresh()->hasRole('shop'));
    }

    public function test_personal_shop_can_open_upgrade_form(): void
    {
        $this->createPersonalShop();

        $this->actingAs($this->member)
            ->get(route('member.shop-application.create'))
            ->assertOk()
            ->assertSee(__('member.shop_application.upgrade_title'))
            ->assertSee(__('member.shop_application.upgrade_intro'));
    }

    public function test_personal_shop_can_submit_business_upgrade_request(): void
    {
        $this->createPersonalShop();

        $this->actingAs($this->member)
            ->post(route('member.shop-application.store'), [
                'seller_type' => ShopApplication::TYPE_BUSINESS,
                'shop_name' => 'Upgraded Shop',
                'address' => '456 Street',
                'country' => 'VN',
                'phone' => '0901234567',
                'real_name' => 'Nguyen Van A',
                'id_number' => 'GP123456',
                'id_front' => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
                'id_back' => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
                'terms' => '1',
            ])
            ->assertRedirect(route('member.shop-application.create'));

        $this->assertDatabaseHas('shop_applications', [
            'user_id' => $this->member->id,
            'seller_type' => ShopApplication::TYPE_BUSINESS,
            'application_kind' => ShopApplication::KIND_UPGRADE,
            'status' => ShopApplication::STATUS_PENDING,
        ]);
    }

    public function test_admin_can_approve_business_upgrade_for_personal_shop(): void
    {
        $shop = $this->createPersonalShop();

        $application = ShopApplication::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => ShopApplication::TYPE_BUSINESS,
            'application_kind' => ShopApplication::KIND_UPGRADE,
            'shop_name' => 'Business Shop Name',
            'address' => '789 Street',
            'country' => 'VN',
            'phone' => '0901234567',
            'real_name' => 'Nguyen Van A',
            'id_number' => 'GP999888',
            'status' => ShopApplication::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.shop-applications.approve', $application))
            ->assertRedirect();

        $this->assertSame(Shop::TYPE_BUSINESS, $shop->fresh()->seller_type);
        $this->assertSame('Business Shop Name', $shop->fresh()->name);
    }

    public function test_business_shop_is_redirected_from_application_form(): void
    {
        Shop::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => Shop::TYPE_BUSINESS,
            'name' => 'Business Shop',
            'slug' => 'business-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);
        $this->member->assignRole('shop');

        $this->actingAs($this->member)
            ->get(route('member.shop-application.create'))
            ->assertRedirect(route('member.home'));
    }

    private function createPersonalShop(): Shop
    {
        $shop = Shop::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => Shop::TYPE_PERSONAL,
            'name' => 'Personal Shop',
            'slug' => 'personal-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);
        $this->member->assignRole('shop');

        return $shop;
    }
}
