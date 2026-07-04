<?php

namespace Tests\Feature;

use App\Models\Category;
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

        $this->seedIndustryCategories();

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
            ->assertSee(__('member.shop_application.title'))
            ->assertSee(__('member.shop_application.industry_placeholder'));
    }

    public function test_member_can_submit_personal_shop_application(): void
    {
        $this->actingAs($this->member)
            ->post(route('member.shop-application.store'), $this->applicationPayload())
            ->assertRedirect(route('member.my.index'))
            ->assertSessionHas('status', __('member.shop_application.submitted'));

        $this->actingAs($this->member)
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertSee(__('member.shop_application.submitted'), false);

        $this->assertDatabaseHas('shop_applications', [
            'user_id' => $this->member->id,
            'shop_name' => 'My Shop',
            'shop_description' => 'Fashion shop description',
            'industry_id' => 'fashion',
            'seller_type' => ShopApplication::TYPE_PERSONAL,
            'application_kind' => ShopApplication::KIND_REGISTRATION,
            'status' => ShopApplication::STATUS_PENDING,
        ]);
    }

    public function test_shop_application_rejects_business_category_outside_industry(): void
    {
        $electronicsCategoryId = Category::query()->where('slug', 'dien-thoai')->value('id');

        $this->actingAs($this->member)
            ->post(route('member.shop-application.store'), $this->applicationPayload([
                'business_category_ids' => [$electronicsCategoryId],
                'shop_name' => 'Bad Shop',
                'shop_description' => 'Invalid categories',
            ]))
            ->assertSessionHasErrors('business_category_ids');
    }

    public function test_member_can_submit_comprehensive_shop_application(): void
    {
        $fashionCategoryId = Category::query()->where('slug', 'thoi-trang')->value('id');

        $this->actingAs($this->member)
            ->post(route('member.shop-application.store'), $this->applicationPayload([
                'industry_id' => 'comprehensive',
                'business_category_ids' => [$fashionCategoryId],
                'shop_name' => 'Comprehensive Shop',
                'shop_description' => 'All categories shop',
            ]))
            ->assertRedirect(route('member.my.index'));
    }

    public function test_member_with_pending_application_stays_on_my_page(): void
    {
        ShopApplication::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => ShopApplication::TYPE_PERSONAL,
            'application_kind' => ShopApplication::KIND_REGISTRATION,
            'shop_name' => 'Pending Shop',
            'address' => '123 Street',
            'country' => 'VN',
            'phone' => '0901234567',
            'real_name' => 'Nguyen Van A',
            'id_number' => '001234567890',
            'status' => ShopApplication::STATUS_PENDING,
        ]);

        $this->actingAs($this->member)
            ->get(route('member.shop-application.create'))
            ->assertRedirect(route('member.my.index'))
            ->assertSessionHas('status', __('member.shop_application.pending_exists'));

        $this->actingAs($this->member)
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertSee(__('member.shop_application.pending_exists'), false);

        $this->actingAs($this->member)
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertSee(__('member.actions.start_selling'))
            ->assertSee(__('member.my.regular_user'))
            ->assertSee(__('member.shop_application.pending_toast'), false);
    }

    public function test_member_my_page_shows_start_selling_for_new_user(): void
    {
        $this->actingAs($this->member)
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertSee(__('member.actions.start_selling'))
            ->assertSee(__('member.my.regular_user'))
            ->assertDontSee(__('member.my.shop_manage'));
    }

    public function test_admin_can_approve_personal_shop_application(): void
    {
        $application = ShopApplication::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => ShopApplication::TYPE_PERSONAL,
            'industry_id' => 'fashion',
            'business_category_ids' => [Category::query()->where('slug', 'thoi-trang')->value('id')],
            'application_kind' => ShopApplication::KIND_REGISTRATION,
            'shop_name' => 'Approved Shop',
            'shop_description' => 'Approved shop description',
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
            'industry_id' => 'fashion',
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

    public function test_member_with_shop_record_but_no_shop_role_sees_registration_form(): void
    {
        Shop::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => Shop::TYPE_PERSONAL,
            'name' => 'Orphan Shop',
            'slug' => 'orphan-shop-apply',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->member->fresh())
            ->get(route('member.shop-application.create'))
            ->assertOk()
            ->assertSee(__('member.shop_application.title'))
            ->assertSee(__('member.shop_application.choose_type'))
            ->assertDontSee(__('member.shop_application.upgrade_title'))
            ->assertDontSee(__('member.shop_application.upgrade_intro'));
    }

    public function test_member_with_business_shop_record_but_no_shop_role_can_apply_as_seller(): void
    {
        Shop::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => Shop::TYPE_BUSINESS,
            'name' => 'Old Business Shop',
            'slug' => 'old-business-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->member->fresh())
            ->get(route('member.shop-application.create'))
            ->assertOk()
            ->assertSee(__('member.shop_application.title'))
            ->assertDontSee(__('member.shop_application.upgrade_title'));
    }

    public function test_member_with_shop_record_but_no_shop_role_can_submit_personal_application(): void
    {
        Shop::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => Shop::TYPE_PERSONAL,
            'name' => 'Orphan Shop',
            'slug' => 'orphan-shop-submit',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->member->fresh())
            ->post(route('member.shop-application.store'), $this->applicationPayload([
                'shop_name' => 'Fresh Shop',
                'phone' => '0356674288',
                'real_name' => 'Test User',
            ]))
            ->assertRedirect(route('member.my.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('shop_applications', [
            'user_id' => $this->member->id,
            'shop_name' => 'Fresh Shop',
            'seller_type' => ShopApplication::TYPE_PERSONAL,
            'application_kind' => ShopApplication::KIND_REGISTRATION,
            'status' => ShopApplication::STATUS_PENDING,
        ]);
    }

    public function test_personal_shop_can_submit_business_upgrade_request(): void
    {
        $this->createPersonalShop();

        $this->actingAs($this->member)
            ->post(route('member.shop-application.store'), $this->applicationPayload([
                'seller_type' => ShopApplication::TYPE_BUSINESS,
                'shop_name' => 'Upgraded Shop',
                'address' => '456 Street',
                'id_number' => 'GP123456',
            ]))
            ->assertRedirect(route('member.my.index'));

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

    public function test_admin_can_approve_registration_when_orphan_shop_exists(): void
    {
        $shop = Shop::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => Shop::TYPE_PERSONAL,
            'name' => 'Orphan Shop',
            'slug' => 'orphan-shop-approve',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $application = ShopApplication::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => ShopApplication::TYPE_PERSONAL,
            'application_kind' => ShopApplication::KIND_REGISTRATION,
            'shop_name' => 'Reactivated Shop',
            'address' => '123 Street',
            'country' => 'VN',
            'phone' => '0356674288',
            'real_name' => 'Test User',
            'id_number' => '001234567890',
            'status' => ShopApplication::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.shop-applications.approve', $application))
            ->assertRedirect()
            ->assertSessionHas('status', __('admin.shop_applications.approved'));

        $this->assertSame(ShopApplication::STATUS_APPROVED, $application->fresh()->status);
        $this->assertTrue($this->member->fresh()->hasRole('shop'));
        $this->assertSame($shop->id, $this->member->fresh()->shop->id);
        $this->assertSame('Reactivated Shop', $this->member->fresh()->shop->name);
    }

    public function test_admin_cannot_approve_registration_when_user_is_already_shop(): void
    {
        $this->createPersonalShop();

        $application = ShopApplication::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => ShopApplication::TYPE_PERSONAL,
            'application_kind' => ShopApplication::KIND_REGISTRATION,
            'shop_name' => 'Duplicate Shop',
            'address' => '123 Street',
            'country' => 'VN',
            'phone' => '0901234567',
            'real_name' => 'Nguyen Van A',
            'id_number' => '001234567890',
            'status' => ShopApplication::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.shop-applications.approve', $application))
            ->assertRedirect()
            ->assertSessionHasErrors('user');

        $this->assertSame(ShopApplication::STATUS_PENDING, $application->fresh()->status);
    }

    public function test_admin_can_delete_shop_application(): void
    {
        $application = ShopApplication::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => ShopApplication::TYPE_PERSONAL,
            'application_kind' => ShopApplication::KIND_REGISTRATION,
            'shop_name' => 'Delete Me Shop',
            'address' => '123 Street',
            'country' => 'VN',
            'phone' => '0356674288',
            'real_name' => 'Test User',
            'id_number' => '001234567890',
            'status' => ShopApplication::STATUS_REJECTED,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.shop-applications.destroy', $application))
            ->assertRedirect()
            ->assertSessionHas('status', __('admin.shop_applications.deleted'));

        $this->assertDatabaseMissing('shop_applications', ['id' => $application->id]);
    }

    public function test_admin_shop_application_index_shows_industry_fields(): void
    {
        $application = ShopApplication::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => ShopApplication::TYPE_PERSONAL,
            'industry_id' => 'fashion',
            'business_category_ids' => [Category::query()->where('slug', 'thoi-trang')->value('id')],
            'application_kind' => ShopApplication::KIND_REGISTRATION,
            'shop_name' => 'Fashion Apply Shop',
            'shop_description' => 'Mô tả cửa hàng thời trang',
            'address' => '123 Street',
            'country' => 'VN',
            'phone' => '0901234567',
            'real_name' => 'Nguyen Van A',
            'id_number' => '001234567890',
            'status' => ShopApplication::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.shop-applications.index'))
            ->assertOk()
            ->assertSee('Ngành thời trang')
            ->assertSee('Mô tả cửa hàng thời trang')
            ->assertSee('Thời trang');
    }

    private function createPersonalShop(): Shop
    {
        $shop = Shop::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => Shop::TYPE_PERSONAL,
            'industry_id' => 'fashion',
            'business_category_ids' => [Category::query()->where('slug', 'thoi-trang')->value('id')],
            'name' => 'Personal Shop',
            'slug' => 'personal-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);
        $this->member->assignRole('shop');

        return $shop;
    }

    /** @param  array<string, mixed>  $overrides */
    private function applicationPayload(array $overrides = []): array
    {
        return array_merge([
            'seller_type' => ShopApplication::TYPE_PERSONAL,
            'industry_id' => 'fashion',
            'business_category_ids' => [Category::query()->where('slug', 'thoi-trang')->value('id')],
            'shop_name' => 'My Shop',
            'shop_description' => 'Fashion shop description',
            'address' => '123 Street',
            'country' => 'VN',
            'phone' => '0901234567',
            'real_name' => 'Nguyen Van A',
            'id_number' => '001234567890',
            'id_front' => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
            'id_back' => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
            'terms' => '1',
        ], $overrides);
    }

    private function seedIndustryCategories(): void
    {
        foreach ([
            ['name' => 'Thời trang', 'slug' => 'thoi-trang'],
            ['name' => 'Điện Thoại', 'slug' => 'dien-thoai'],
            ['name' => 'Mỹ Phẩm', 'slug' => 'my-pham'],
            ['name' => 'Khác', 'slug' => 'khac'],
        ] as $category) {
            Category::query()->firstOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name'], 'status' => 'active'],
            );
        }
    }
}
