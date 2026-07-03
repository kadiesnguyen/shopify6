<?php

namespace Tests\Feature\Api;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberPortalApiTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'member']);

        $this->member = User::factory()->create(['status' => 'active']);
        $this->member->assignRole('member');

        Wallet::query()->create([
            'user_id' => $this->member->id,
            'balance' => 0,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);
    }

    public function test_member_home_api_returns_feed(): void
    {
        Sanctum::actingAs($this->member);

        $this->getJson('/api/member/home')
            ->assertOk()
            ->assertJsonStructure(['products', 'cart_count', 'unread_notifications']);
    }

    public function test_member_my_api_returns_summary(): void
    {
        Sanctum::actingAs($this->member);

        $this->getJson('/api/member/my')
            ->assertOk()
            ->assertJsonStructure(['wallet', 'order_status_counts', 'user']);
    }

    public function test_dashboard_api_is_deprecated(): void
    {
        Sanctum::actingAs($this->member);

        $this->getJson('/api/member/dashboard')->assertStatus(410);
    }

    public function test_member_categories_api(): void
    {
        Sanctum::actingAs($this->member);

        $this->getJson('/api/member/categories')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_member_cart_api_starts_empty(): void
    {
        Sanctum::actingAs($this->member);

        $this->getJson('/api/member/cart')
            ->assertOk()
            ->assertJsonPath('item_count', 0);
    }

    public function test_cart_api_enforces_ownership_and_select_all(): void
    {
        [$product, $distribution] = $this->makeDistributedProduct();

        $other = User::factory()->create(['status' => 'active']);
        $other->assignRole('member');

        $foreignItem = CartItem::query()->create([
            'user_id' => $other->id,
            'product_id' => $product->id,
            'product_distribution_id' => $distribution->id,
            'shop_user_id' => $distribution->user_id,
            'quantity' => 1,
            'selected' => true,
        ]);

        Sanctum::actingAs($this->member);

        $this->deleteJson("/api/member/cart/{$foreignItem->id}")->assertForbidden();
        $this->patchJson("/api/member/cart/{$foreignItem->id}", ['quantity' => 9])->assertForbidden();

        $this->postJson('/api/member/cart', ['product_id' => $product->id, 'qty' => 2])->assertCreated();

        $this->postJson('/api/member/cart/select-all', ['selected' => false])
            ->assertOk()
            ->assertJsonPath('groups.0.items.0.selected', false);
    }

    public function test_cart_checkout_api_requires_payment_password(): void
    {
        [$product] = $this->makeDistributedProduct();

        Sanctum::actingAs($this->member);
        $this->postJson('/api/member/cart', ['product_id' => $product->id])->assertCreated();

        $this->postJson('/api/member/cart/checkout')->assertStatus(422);
    }

    public function test_review_api_requires_purchase_and_blocks_duplicates(): void
    {
        [$product, $distribution] = $this->makeDistributedProduct();

        Sanctum::actingAs($this->member);

        $this->postJson('/api/member/reviews', ['product_id' => $product->id, 'rating' => 5])
            ->assertStatus(422);

        Order::query()->create([
            'user_id' => $this->member->id,
            'seller_id' => $distribution->user_id,
            'product_distribution_id' => $distribution->id,
            'order_no' => 'ORD-API-REV-1',
            'total' => 10,
            'purchase_cost' => 5,
            'commission' => 5,
            'status' => Order::STATUS_COMPLETED,
        ]);

        $this->postJson('/api/member/reviews', ['product_id' => $product->id, 'rating' => 5, 'body' => 'Nice'])
            ->assertCreated();

        $this->postJson('/api/member/reviews', ['product_id' => $product->id, 'rating' => 1])
            ->assertStatus(422);

        // Published review is exposed on the product detail API.
        $this->getJson("/api/member/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('reviews_count', 1)
            ->assertJsonPath('reviews.0.body', 'Nice');
    }

    /** @return array{0: Product, 1: ProductDistribution} */
    private function makeDistributedProduct(): array
    {
        Role::findOrCreate('shop');

        $seller = User::factory()->create(['status' => 'active']);
        $seller->assignRole('shop');
        Shop::query()->create([
            'user_id' => $seller->id,
            'name' => 'Api Shop',
            'slug' => 'api-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $category = Category::query()->create([
            'name' => 'Api Cat',
            'slug' => 'api-cat',
            'status' => Category::STATUS_ACTIVE,
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'name' => 'Api Product',
            'slug' => 'api-product',
            'category_id' => $category->id,
            'selling_price' => 10,
            'purchase_price' => 5,
            'stock' => 20,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $distribution = ProductDistribution::query()->create([
            'user_id' => $seller->id,
            'product_id' => $product->id,
            'selling_price' => 10,
            'purchase_price' => 5,
            'commission' => 5,
            'commission_type' => ProductDistribution::COMMISSION_FIXED,
            'status' => ProductDistribution::STATUS_AVAILABLE,
        ]);

        return [$product, $distribution];
    }
}
