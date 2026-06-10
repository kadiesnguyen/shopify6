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

        Role::create(['name' => 'member']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'shop']);

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

    public function test_member_can_submit_shop_application(): void
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
            'status' => ShopApplication::STATUS_PENDING,
        ]);
    }

    public function test_admin_can_approve_shop_application(): void
    {
        $application = ShopApplication::query()->create([
            'user_id' => $this->member->id,
            'seller_type' => ShopApplication::TYPE_PERSONAL,
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
        ]);

        $this->assertDatabaseHas('shop_applications', [
            'id' => $application->id,
            'status' => ShopApplication::STATUS_APPROVED,
        ]);

        $this->assertTrue($this->member->fresh()->hasRole('shop'));
    }

    public function test_member_with_shop_is_redirected_from_application_form(): void
    {
        Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Existing Shop',
            'slug' => 'existing-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->member)
            ->get(route('member.shop-application.create'))
            ->assertRedirect(route('member.home'));
    }
}
