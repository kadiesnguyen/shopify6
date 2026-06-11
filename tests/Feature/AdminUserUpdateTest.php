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
                'role' => 'shop',
                'shop_name' => 'New Shop Name',
                'followers' => 12,
                'credit_score' => 88,
                'id_number' => '001122334455',
                'address' => '123 Test Street',
                'country' => 'Vietnam',
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
        $this->assertSame('123 Test Street', $shop->address);
        $this->assertSame('Vietnam', $shop->country);
        $this->assertSame(3, $shop->display_pending_orders);
        $this->assertSame(2, $shop->display_delivering_orders);
        $this->assertSame(1, $shop->display_received_orders);
        $this->assertSame(9, $shop->display_completed_orders);
        $this->assertSame('1500.50', $shop->display_total_income);
        $this->assertSame('1000000.00', $shop->display_balance);
        $this->assertStringStartsWith('uploads/shops/', $shop->logo);
        $this->assertStringStartsWith('private/shops/', $shop->id_front);
        $this->assertStringStartsWith('private/shops/', $shop->id_back);
        $this->assertFileExists(public_path($shop->logo));
        Storage::disk(ShopDocumentStorage::DISK)->assertExists($shop->id_front);
        Storage::disk(ShopDocumentStorage::DISK)->assertExists($shop->id_back);
        $this->assertStringContainsString(
            route('admin.users.documents.show', ['user' => $member->id, 'document' => 'id_front']),
            (string) $shop->documentUrl($shop->id_front),
        );

        $this->assertDatabaseHas('shipping_addresses', [
            'user_id' => $member->id,
            'address_line' => '123 Test Street',
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
            ->assertSee(__('admin.users.actions.buff_stats_title'))
            ->assertSee(__('admin.users.actions.choose_image'));
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
                'role' => 'shop',
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
}
