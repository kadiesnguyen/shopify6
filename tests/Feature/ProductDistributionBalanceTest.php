<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Member\ProductDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductDistributionBalanceTest extends TestCase
{
    use RefreshDatabase;

    private User $shopUser;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'shop']);

        $this->shopUser = User::factory()->create(['status' => 'active']);
        $this->shopUser->assignRole('shop');

        Shop::query()->create([
            'user_id' => $this->shopUser->id,
            'name' => 'Balance Shop',
            'slug' => 'balance-shop',
            'status' => 'active',
        ]);

        Wallet::query()->create([
            'user_id' => $this->shopUser->id,
            'balance' => 50,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $category = Category::query()->create([
            'name' => 'Cat',
            'slug' => 'cat-balance',
            'status' => 'active',
        ]);

        $this->product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Costly Product',
            'slug' => 'costly-product',
            'selling_price' => 120,
            'purchase_price' => 60,
            'commission' => 12,
            'stock' => 5,
            'status' => 'active',
        ]);
    }

    public function test_shop_cannot_distribute_without_sufficient_balance(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('insufficient_balance');

        app(ProductDistributionService::class)->distribute($this->shopUser, $this->product);
    }

    public function test_shop_balance_is_deducted_when_distributing(): void
    {
        $this->shopUser->wallet->update(['balance' => 100]);

        $distribution = app(ProductDistributionService::class)->distribute($this->shopUser, $this->product);

        $this->assertSame(40.0, (float) $this->shopUser->wallet->fresh()->balance);
        $this->assertDatabaseHas('product_distributions', ['id' => $distribution->id]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->shopUser->id,
            'type' => Transaction::TYPE_DISTRIBUTION_COST,
            'amount' => 60,
            'reference' => 'distribution-'.$distribution->id,
        ]);
    }

    public function test_portal_rejects_distribution_with_insufficient_balance(): void
    {
        $this->actingAs($this->shopUser)
            ->post(route('member.products.distributions.store'), [
                'product_id' => $this->product->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseCount('product_distributions', 0);
    }

    public function test_portal_accepts_distribution_with_sufficient_balance(): void
    {
        $this->shopUser->wallet->update(['balance' => 100]);

        $this->actingAs($this->shopUser)
            ->post(route('member.products.distributions.store'), [
                'product_id' => $this->product->id,
            ])
            ->assertRedirect(route('member.products.distributions.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('product_distributions', 1);
        $this->assertSame(40.0, (float) $this->shopUser->wallet->fresh()->balance);
    }
}
