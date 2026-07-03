<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberCartTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('member');
        Role::findOrCreate('shop');

        $this->member = User::factory()->create(['status' => 'active']);
        $this->member->assignRole('member');

        Wallet::query()->create([
            'user_id' => $this->member->id,
            'balance' => 500,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);
    }

    public function test_member_can_add_product_to_cart(): void
    {
        [$product, $distribution] = $this->makeDistributedProduct();

        $this->actingAs($this->member)
            ->post(route('member.cart.store'), [
                'product_id' => $product->id,
                'qty' => 2,
                'shop_user_id' => $distribution->user_id,
            ])
            ->assertRedirect(route('member.cart.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $this->member->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_cart_page_loads_for_member(): void
    {
        $this->actingAs($this->member)
            ->get(route('member.cart.index'))
            ->assertOk()
            ->assertSee(__('member.nav.cart'));
    }

    public function test_member_cannot_touch_another_members_cart_item(): void
    {
        [$product, $distribution] = $this->makeDistributedProduct();

        $owner = User::factory()->create(['status' => 'active']);
        $owner->assignRole('member');

        $item = CartItem::query()->create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'product_distribution_id' => $distribution->id,
            'shop_user_id' => $distribution->user_id,
            'quantity' => 1,
            'selected' => true,
        ]);

        $this->actingAs($this->member)
            ->patch(route('member.cart.update', $item), ['quantity' => 5])
            ->assertForbidden();

        $this->actingAs($this->member)
            ->delete(route('member.cart.destroy', $item))
            ->assertForbidden();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 1]);
    }

    /** @return array{0: Product, 1: ProductDistribution} */
    private function makeDistributedProduct(): array
    {
        $seller = User::factory()->create(['status' => 'active']);
        $seller->assignRole('shop');
        Shop::query()->create([
            'user_id' => $seller->id,
            'name' => 'Test Shop',
            'slug' => 'test-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $category = Category::query()->create([
            'name' => 'Cat',
            'slug' => 'cat',
            'status' => Category::STATUS_ACTIVE,
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'name' => 'Cart Product',
            'slug' => 'cart-product',
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
