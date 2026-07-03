<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_index_filters_by_shop_name(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('shop');
        Role::findOrCreate('member');

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $seller = User::factory()->create([
            'status' => 'active',
            'name' => 'Shop Owner',
            'email' => 'owner@example.com',
        ]);
        $seller->assignRole('shop');
        Shop::query()->create([
            'user_id' => $seller->id,
            'name' => 'chuech',
            'slug' => 'chuech-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $other = User::factory()->create([
            'status' => 'active',
            'name' => 'Other Member',
            'email' => 'other@example.com',
        ]);
        $other->assignRole('member');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'chuech']))
            ->assertOk()
            ->assertSee('Shop Owner')
            ->assertSee('chuech')
            ->assertDontSee('Other Member');
    }

    public function test_admin_user_search_suggestions_return_matching_users(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('shop');
        Role::findOrCreate('member');

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $shopName = 'chuech-uniqueshop-'.uniqid();

        $seller = User::factory()->create([
            'status' => 'active',
            'name' => 'Shop Owner Unique',
            'phone' => '0356674288',
        ]);
        $seller->assignRole('shop');
        Shop::query()->create([
            'user_id' => $seller->id,
            'name' => $shopName,
            'slug' => 'chuech-suggest-'.$seller->id,
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.users.search-suggestions', ['q' => $shopName]))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $seller->id,
                'value' => 'Shop Owner Unique',
                'meta' => collect([$seller->loginIdentifier(), $shopName])->filter()->unique()->join(' · '),
            ]);
    }

    public function test_admin_users_index_filters_by_user_id_from_suggest(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('member');

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $target = User::factory()->create([
            'status' => 'active',
            'name' => 'Target User',
            'email' => 'target@example.com',
        ]);
        $target->assignRole('member');

        $other = User::factory()->create([
            'status' => 'active',
            'name' => 'Other User',
            'email' => 'other@example.com',
        ]);
        $other->assignRole('member');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['user_id' => $target->id, 'q' => 'Target User']))
            ->assertOk()
            ->assertSee('Target User')
            ->assertDontSee('Other User');
    }
}
