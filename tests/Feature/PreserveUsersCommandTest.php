<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreserveUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_db_upgrade_runs_without_wiping_users(): void
    {
        $user = User::factory()->create(['email' => 'keep-me@test.com']);

        $this->artisan('db:upgrade')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'keep-me@test.com', 'id' => $user->id]);
    }

    public function test_db_seed_is_blocked_when_database_has_users(): void
    {
        User::factory()->create();

        $this->artisan('db:seed')
            ->assertFailed();
    }

    public function test_db_seed_allows_baseline_seeder_when_database_has_users(): void
    {
        User::factory()->create();

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\BaselineDatabaseSeeder'])
            ->assertSuccessful();
    }

    public function test_demo_seeder_skips_when_products_exist(): void
    {
        User::factory()->create();
        $this->createProduct();

        $before = Product::query()->count();

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\DemoDataSeeder'])
            ->assertSuccessful();

        $this->assertSame($before, Product::query()->count());
    }

    public function test_sieummo_deactivate_missing_is_blocked_without_flag(): void
    {
        $this->artisan('sieummo:import-products', [
            '--dry-run' => true,
            '--deactivate-missing' => true,
        ])->assertFailed();
    }

    public function test_db_guard_reports_counts(): void
    {
        User::factory()->create();

        $this->artisan('db:guard')
            ->assertSuccessful()
            ->expectsOutputToContain('users=1');
    }

    public function test_db_backup_creates_sql_file(): void
    {
        if (config('database.connections.'.config('database.default').'.driver') !== 'mysql') {
            $this->markTestSkipped('db:backup requires mysql.');
        }

        User::factory()->create();

        $path = storage_path('framework/testing/backups');

        $this->artisan('db:backup', ['--path' => $path])
            ->assertSuccessful();

        $files = glob($path.'/shopefy_*.sql');
        $this->assertNotEmpty($files);
        $this->assertGreaterThan(0, filesize($files[0]));
    }

    private function createProduct(): Product
    {
        $category = Category::query()->create([
            'name' => 'Test',
            'slug' => 'test-cat',
            'status' => 'active',
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'selling_price' => 10,
            'purchase_price' => 5,
            'commission' => 1,
            'stock' => 1,
            'status' => 'active',
        ]);
    }
}
