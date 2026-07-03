<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberCategoryBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_browse_categories_page(): void
    {
        Role::findOrCreate('member');

        $member = User::factory()->create(['status' => 'active']);
        $member->assignRole('member');

        Category::query()->create([
            'name' => 'Trang phục',
            'slug' => 'trang-phuc',
            'status' => Category::STATUS_ACTIVE,
            'sort_order' => 1,
        ]);

        $this->actingAs($member)
            ->get(route('member.categories.index'))
            ->assertOk()
            ->assertSee(__('member.nav.categories'))
            ->assertSee('Trang phục');
    }
}
