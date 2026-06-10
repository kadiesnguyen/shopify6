<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminApiTest extends TestCase
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

    public function test_admin_api_requires_admin_role(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        $member->assignRole('member');

        Sanctum::actingAs($member);

        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_admin_can_list_users(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_admin_can_create_product_via_api(): void
    {
        Sanctum::actingAs($this->admin);

        $category = Category::query()->create([
            'name' => 'API Cat',
            'slug' => 'api-cat',
            'status' => 'active',
        ]);

        $this->postJson('/api/admin/products', [
            'category_id' => $category->id,
            'name' => 'API Product',
            'selling_price' => 19.99,
            'purchase_price' => 10,
            'commission' => 2,
            'stock' => 5,
            'status' => 'active',
        ])->assertCreated();

        $this->assertDatabaseHas('products', ['name' => 'API Product']);
    }

    public function test_admin_users_export_returns_csv(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->get('/api/admin/users/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
