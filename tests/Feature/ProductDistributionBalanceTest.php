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

        Role::findOrCreate('shop');

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

    public function test_distributing_does_not_deduct_shop_wallet(): void
    {
        $distribution = app(ProductDistributionService::class)->distribute($this->shopUser, $this->product);

        $this->assertSame(50.0, (float) $this->shopUser->wallet->fresh()->balance);
        $this->assertDatabaseHas('product_distributions', ['id' => $distribution->id]);
        $this->assertDatabaseMissing('transactions', [
            'user_id' => $this->shopUser->id,
            'type' => Transaction::TYPE_DISTRIBUTION_COST,
        ]);
    }

    public function test_portal_accepts_distribution_without_balance_requirement(): void
    {
        $this->actingAs($this->shopUser)
            ->post(route('member.products.distributions.store'), [
                'product_id' => $this->product->id,
            ])
            ->assertRedirect(route('member.products.manage.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('product_distributions', 1);
        $this->assertSame(50.0, (float) $this->shopUser->wallet->fresh()->balance);
    }

    public function test_distribution_store_returns_json_for_ajax_requests(): void
    {
        $this->actingAs($this->shopUser)
            ->postJson(route('member.products.distributions.store'), [
                'product_id' => $this->product->id,
            ])
            ->assertOk()
            ->assertJson([
                'count' => 1,
                'redirect' => route('member.products.manage.index'),
            ]);

        $this->assertDatabaseCount('product_distributions', 1);
    }

    public function test_distribute_mode_categories_batch_redirects_to_goods_page(): void
    {
        $secondProduct = Product::query()->create([
            'category_id' => $this->product->category_id,
            'name' => 'Second Product',
            'slug' => 'second-product',
            'selling_price' => 90,
            'purchase_price' => 45,
            'commission' => 9,
            'stock' => 5,
            'status' => 'active',
        ]);

        $this->actingAs($this->shopUser)
            ->get(route('member.categories.index', ['mode' => 'distribute']))
            ->assertOk()
            ->assertSee(__('member.products.confirm_distribution'))
            ->assertSee($this->product->name)
            ->assertSee($secondProduct->name);

        $this->actingAs($this->shopUser)
            ->post(route('member.products.distributions.store'), [
                'product_ids' => [$this->product->id, $secondProduct->id],
            ])
            ->assertRedirect(route('member.products.manage.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('product_distributions', 2);

        $this->actingAs($this->shopUser)
            ->get(route('member.products.manage.index'))
            ->assertOk()
            ->assertSee($this->product->name)
            ->assertSee($secondProduct->name)
            ->assertSee(__('member.products.edit'));
    }

    public function test_distribution_center_shows_purchase_selling_and_profit(): void
    {
        $this->actingAs($this->shopUser)
            ->get(route('member.products.distributions.index'))
            ->assertOk()
            ->assertSee(__('member.products.purchase_price'))
            ->assertSee(__('member.products.selling_price'))
            ->assertSee(__('member.products.profit'))
            ->assertSee('$60.00')
            ->assertSee('$120.00')
            ->assertSee('$60.00');
    }

    public function test_distribution_center_returns_json_for_infinite_scroll(): void
    {
        $categoryId = (int) $this->product->category_id;

        for ($i = 0; $i < 14; $i++) {
            Product::query()->create([
                'category_id' => $categoryId,
                'name' => 'Extra Product '.$i,
                'slug' => 'extra-product-'.$i,
                'selling_price' => 120 + $i,
                'purchase_price' => 60 + $i,
                'commission' => 12,
                'stock' => 5,
                'status' => 'active',
            ]);
        }

        $this->actingAs($this->shopUser)
            ->getJson(route('member.products.distributions.index', ['page' => 2]))
            ->assertOk()
            ->assertJsonStructure([
                'html',
                'has_more',
                'next_page',
            ])
            ->assertJson([
                'has_more' => false,
                'next_page' => 3,
            ]);
    }
}
