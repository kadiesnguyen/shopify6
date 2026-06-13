<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private User $shopUser;

    private User $otherShopUser;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('member');
        Role::findOrCreate('shop');

        $this->member = User::factory()->create(['status' => 'active']);
        $this->member->assignRole('member');

        $this->shopUser = User::factory()->create(['status' => 'active']);
        $this->shopUser->assignRole(['member', 'shop']);

        $this->otherShopUser = User::factory()->create(['status' => 'active']);
        $this->otherShopUser->assignRole(['member', 'shop']);

        foreach ([$this->shopUser, $this->otherShopUser] as $user) {
            Shop::query()->create([
                'user_id' => $user->id,
                'name' => 'Shop '.$user->id,
                'slug' => 'shop-'.$user->id,
                'status' => 'active',
            ]);
        }

        foreach ([$this->member, $this->shopUser, $this->otherShopUser] as $user) {
            Wallet::query()->create([
                'user_id' => $user->id,
                'balance' => 500,
                'balance_pending' => 0,
                'balance_frozen' => 0,
            ]);
        }

        $category = Category::query()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);

        $this->product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Visible Product',
            'slug' => 'visible-product',
            'selling_price' => 25,
            'purchase_price' => 10,
            'commission' => 3,
            'stock' => 10,
            'status' => 'active',
        ]);
    }

    public function test_member_sees_no_products_until_a_shop_distributes(): void
    {
        $this->actingAs($this->member)
            ->get('/home')
            ->assertOk()
            ->assertDontSee('Visible Product');

        $this->actingAs($this->member)
            ->get('/home/products')
            ->assertOk()
            ->assertDontSee('Visible Product');
    }

    public function test_member_sees_products_after_featured_shop_distribution(): void
    {
        $this->distributeAsShop($this->shopUser);

        $this->actingAs($this->member)
            ->get('/home')
            ->assertOk()
            ->assertDontSee('Visible Product');

        $this->markDistributionFeatured($this->shopUser);

        $this->actingAs($this->member)
            ->get('/home')
            ->assertOk()
            ->assertSee('Visible Product');
    }

    public function test_member_does_not_see_non_featured_products_on_default_listing(): void
    {
        $this->distributeAsShop($this->shopUser);

        $this->actingAs($this->member)
            ->get('/home/products')
            ->assertOk()
            ->assertDontSee('Visible Product');
    }

    public function test_member_sees_products_after_shop_distribution(): void
    {
        $this->distributeAsShop($this->shopUser);
        $this->markDistributionFeatured($this->shopUser);

        $this->actingAs($this->member)
            ->get('/home')
            ->assertOk()
            ->assertSee('Visible Product');
    }

    public function test_shop_sees_featured_shop_products_on_portal(): void
    {
        $this->distributeAsShop($this->shopUser);
        $this->markDistributionFeatured($this->shopUser);

        $this->actingAs($this->otherShopUser)
            ->get('/home')
            ->assertOk()
            ->assertSee('Visible Product');
    }

    public function test_search_still_finds_non_featured_shop_products(): void
    {
        $this->distributeAsShop($this->shopUser);

        $this->actingAs($this->member)
            ->get('/home/products?q=Visible')
            ->assertOk()
            ->assertSee('Visible Product');
    }

    public function test_shop_product_routes_do_not_collide_with_product_detail_route(): void
    {
        $this->actingAs($this->shopUser)
            ->get('/home/products/distributions')
            ->assertOk()
            ->assertSee(__('member.products.distribution_center'), false);

        $this->actingAs($this->shopUser)
            ->get('/home/products/manage')
            ->assertOk()
            ->assertSee(__('member.products.management'), false);
    }

    public function test_shop_can_distribute_from_distribution_center(): void
    {
        $this->actingAs($this->shopUser)
            ->get(route('member.products.distributions.index'))
            ->assertOk()
            ->assertSee('Visible Product');

        $this->actingAs($this->shopUser)
            ->post(route('member.products.distributions.store'), [
                'product_id' => $this->product->id,
            ])
            ->assertRedirect(route('member.products.distributions.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('product_distributions', [
            'user_id' => $this->shopUser->id,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_checkout_requires_shop_distribution(): void
    {
        $this->member->update(['payment_password' => '123456']);

        $this->actingAs($this->member->fresh())
            ->get(route('member.checkout.show', $this->product))
            ->assertNotFound();

        $this->distributeAsShop($this->shopUser);

        $this->actingAs($this->member->fresh())
            ->get(route('member.checkout.show', $this->product))
            ->assertRedirect(route('member.payment-password.create', [
                'redirect' => route('member.checkout.show', $this->product),
            ]));
    }

    public function test_member_cannot_access_distribution_center(): void
    {
        $this->actingAs($this->member)
            ->get(route('member.products.distributions.index'))
            ->assertForbidden();
    }

    public function test_member_does_not_see_shop_product_menu_links(): void
    {
        $this->actingAs($this->member)
            ->get(route('member.products.index'))
            ->assertOk()
            ->assertDontSee(__('member.products.distribution_center'), false)
            ->assertDontSee(__('member.products.management'), false);
    }

    public function test_member_with_shop_record_but_no_shop_role_does_not_see_shop_product_menu(): void
    {
        Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Orphan Shop',
            'slug' => 'orphan-shop',
            'status' => 'active',
        ]);

        $this->actingAs($this->member->fresh())
            ->get(route('member.products.index'))
            ->assertOk()
            ->assertDontSee(__('member.products.distribution_center'), false)
            ->assertDontSee(__('member.products.management'), false);

        $this->actingAs($this->member->fresh())
            ->get(route('member.products.distributions.index'))
            ->assertForbidden();
    }

    public function test_shop_user_sees_product_menu_links(): void
    {
        $this->actingAs($this->shopUser)
            ->get(route('member.products.index'))
            ->assertOk()
            ->assertSee(__('member.products.distribution_center'), false)
            ->assertSee(__('member.products.management'), false);
    }

    public function test_products_page_returns_json_for_infinite_scroll(): void
    {
        $this->distributeAsShop($this->shopUser);
        $this->markDistributionFeatured($this->shopUser);

        $categoryId = (int) $this->product->category_id;

        for ($i = 0; $i < 14; $i++) {
            $extra = Product::query()->create([
                'category_id' => $categoryId,
                'name' => 'Visible Product '.$i,
                'slug' => 'visible-product-'.$i,
                'selling_price' => 25 + $i,
                'purchase_price' => 10 + $i,
                'commission' => 3,
                'stock' => 10,
                'status' => 'active',
            ]);

            ProductDistribution::query()->create([
                'user_id' => $this->shopUser->id,
                'product_id' => $extra->id,
                'selling_price' => $extra->selling_price,
                'purchase_price' => $extra->purchase_price,
                'commission' => $extra->commission,
                'commission_type' => 'fixed',
                'status' => ProductDistribution::STATUS_AVAILABLE,
                'is_featured' => true,
                'featured_at' => now(),
            ]);
        }

        $this->actingAs($this->member)
            ->getJson(route('member.products.index', ['page' => 2]))
            ->assertOk()
            ->assertJsonStructure(['html', 'has_more', 'next_page'])
            ->assertJson([
                'has_more' => false,
                'next_page' => 3,
            ]);
    }

    public function test_featured_reserved_distribution_is_hidden_from_default_portal_listing(): void
    {
        $this->distributeAsShop($this->shopUser);
        $this->markDistributionFeatured($this->shopUser);

        ProductDistribution::query()
            ->where('user_id', $this->shopUser->id)
            ->where('product_id', $this->product->id)
            ->update(['status' => ProductDistribution::STATUS_RESERVED]);

        $this->actingAs($this->member)
            ->get('/home')
            ->assertOk()
            ->assertDontSee('Visible Product');

        $this->actingAs($this->member)
            ->get('/home/products')
            ->assertOk()
            ->assertDontSee('Visible Product');
    }

    private function distributeAsShop(User $shopUser): void
    {
        ProductDistribution::query()->create([
            'user_id' => $shopUser->id,
            'product_id' => $this->product->id,
            'selling_price' => $this->product->selling_price,
            'purchase_price' => $this->product->purchase_price,
            'commission' => $this->product->commission,
            'commission_type' => 'fixed',
            'status' => ProductDistribution::STATUS_AVAILABLE,
        ]);
    }

    private function markDistributionFeatured(User $shopUser): void
    {
        ProductDistribution::query()
            ->where('user_id', $shopUser->id)
            ->update([
                'is_featured' => true,
                'featured_at' => now(),
            ]);
    }
}
