<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\RechargeMethod;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberPagesTest extends TestCase
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

        RechargeMethod::query()->create([
            'name' => 'Bank Recharge',
            'type' => 'bank',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        WithdrawalMethod::query()->create([
            'name' => 'Bank Withdrawal',
            'type' => 'bank',
            'config' => ['fee_percent' => 0, 'currency' => 'VND'],
            'status' => 'active',
            'sort_order' => 1,
        ]);
    }

    public function test_member_routes_require_authentication(): void
    {
        $this->get('/home/products')->assertRedirect(route('auth.login'));
    }

    public function test_member_home_page(): void
    {
        $this->actingAs($this->member)
            ->get('/home')
            ->assertOk()
            ->assertSee(__('member.guess_you_like'))
            ->assertSee(__('member.pick_quality'))
            ->assertSee(__('member.nav.home'))
            ->assertSee(__('member.nav.categories'))
            ->assertSee(__('member.nav.cart'))
            ->assertSee(__('member.nav.my'));
    }

    public function test_member_products_and_orders_pages(): void
    {
        $this->actingAs($this->member)
            ->get('/home/products')
            ->assertOk();

        $this->actingAs($this->member)
            ->get('/home/orders')
            ->assertOk()
            ->assertSee(__('member.orders.title'))
            ->assertSee(__('member.orders.my_orders'))
            ->assertDontSee(__('member.orders.customer_orders'));
    }

    public function test_member_can_view_product_detail_page(): void
    {
        $category = Category::query()->create([
            'name' => 'Detail Category',
            'slug' => 'detail-category',
            'status' => 'active',
        ]);

        $shopOwner = User::factory()->create(['status' => 'active']);
        $shopOwner->assignRole('shop');
        $shop = Shop::query()->create([
            'user_id' => $shopOwner->id,
            'name' => 'Detail Shop',
            'slug' => 'detail-shop',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $shop->id,
            'user_id' => $shopOwner->id,
            'name' => 'Detail Product Alpha',
            'slug' => 'detail-product-alpha',
            'description' => 'Imported product description from sieummo.',
            'selling_price' => 362.10,
            'purchase_price' => 298.30,
            'commission' => 63.80,
            'stock' => 632,
            'status' => 'active',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $shopOwner->id,
            'product_id' => $product->id,
            'selling_price' => 362.10,
            'purchase_price' => 298.30,
            'commission' => 63.80,
            'commission_type' => 'fixed',
            'status' => ProductDistribution::STATUS_AVAILABLE,
        ]);

        $this->actingAs($this->member)
            ->get(route('member.products.show', $product))
            ->assertOk()
            ->assertSee(__('member.products.goods'))
            ->assertSee(__('member.products.buy_now'))
            ->assertSee(__('member.products.add_to_cart'))
            ->assertSee(__('member.nav.cart'))
            ->assertSee(__('member.products.shop_short'))
            ->assertSee(__('member.products.free_shipping'))
            ->assertSee('Detail Product Alpha')
            ->assertSee('Imported product description from sieummo.')
            ->assertSee('$362.10')
            ->assertSee('632')
            ->assertSee('shop_id='.$shop->id, false);

        $this->actingAs($shopOwner)
            ->get(route('member.products.show', $product))
            ->assertOk()
            ->assertSee(__('member.products.goods'))
            ->assertSee(__('member.products.buy_now'));

        $this->actingAs($shopOwner)
            ->get(route('member.products.show', ['product' => $product, 'from' => 'manage']))
            ->assertOk()
            ->assertSee(__('member.products.purchase_price'))
            ->assertSee('$298.30')
            ->assertSee(__('member.products.expected_profit'))
            ->assertSee('$63.80')
            ->assertSee(__('member.products.recommended'))
            ->assertSee(__('member.products.description'))
            ->assertSee('Imported product description from sieummo.');
    }

    public function test_member_product_detail_shop_link_uses_distributor_shop_products(): void
    {
        $category = Category::query()->create([
            'name' => 'Detail Distributor Category',
            'slug' => 'detail-distributor-category',
            'status' => 'active',
        ]);

        $distributor = User::factory()->create(['status' => 'active']);
        $distributor->assignRole('shop');
        $distributorShop = Shop::query()->create([
            'user_id' => $distributor->id,
            'name' => 'Detail Distributor Shop',
            'slug' => 'detail-distributor-shop',
            'status' => 'active',
        ]);

        $owner = User::factory()->create(['status' => 'active']);
        $owner->assignRole('shop');
        $ownerShop = Shop::query()->create([
            'user_id' => $owner->id,
            'name' => 'Detail Owner Shop',
            'slug' => 'detail-owner-shop',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $ownerShop->id,
            'user_id' => $owner->id,
            'name' => 'Detail Distributor Product',
            'slug' => 'detail-distributor-product',
            'selling_price' => 30,
            'purchase_price' => 20,
            'commission' => 10,
            'stock' => 12,
            'status' => 'active',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $distributor->id,
            'product_id' => $product->id,
            'selling_price' => 30,
            'purchase_price' => 20,
            'commission' => 10,
            'commission_type' => 'fixed',
            'status' => ProductDistribution::STATUS_AVAILABLE,
        ]);

        $this->actingAs($this->member)
            ->get(route('member.products.show', $product))
            ->assertOk()
            ->assertSee('Detail Distributor Shop')
            ->assertDontSee('Detail Owner Shop')
            ->assertSee('shop_id='.$distributorShop->id, false);

        $this->actingAs($this->member)
            ->get(route('member.products.index', [
                'shop_id' => $distributorShop->id,
                'shop' => $distributorShop->name,
            ]))
            ->assertOk()
            ->assertSee('Detail Distributor Product');
    }

    public function test_member_product_detail_api_returns_full_payload(): void
    {
        $category = Category::query()->create([
            'name' => 'API Detail Category',
            'slug' => 'api-detail-category',
            'status' => 'active',
        ]);

        $shopOwner = User::factory()->create(['status' => 'active']);
        $shopOwner->assignRole('shop');
        $shop = Shop::query()->create([
            'user_id' => $shopOwner->id,
            'name' => 'API Detail Shop',
            'slug' => 'api-detail-shop-24',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $shop->id,
            'user_id' => $shopOwner->id,
            'name' => 'API Detail Product',
            'slug' => 'sm-9001',
            'description' => 'API detail description body.',
            'selling_price' => 100,
            'purchase_price' => 60,
            'commission' => 40,
            'stock' => 50,
            'status' => 'active',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $shopOwner->id,
            'product_id' => $product->id,
            'selling_price' => 100,
            'purchase_price' => 60,
            'commission' => 40,
            'commission_type' => 'fixed',
            'status' => ProductDistribution::STATUS_AVAILABLE,
        ]);

        $this->actingAs($this->member, 'sanctum')
            ->getJson('/api/member/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'API Detail Product')
            ->assertJsonPath('data.purchase_price', 60)
            ->assertJsonPath('data.selling_price', 100)
            ->assertJsonPath('data.profit', 40)
            ->assertJsonPath('data.stock', 50)
            ->assertJsonPath('data.is_recommended', true)
            ->assertJsonPath('data.description', 'API detail description body.')
            ->assertJsonPath('data.shop.name', 'API Detail Shop')
            ->assertJsonPath('data.shop.products_url', route('member.products.index', [
                'shop_id' => $shop->id,
                'shop' => $shop->name,
            ]));
    }

    public function test_member_product_search_filters_by_product_and_shop_name(): void
    {
        $category = Category::query()->create([
            'name' => 'Search Category',
            'slug' => 'search-category',
            'status' => 'active',
        ]);

        $shopOwner = User::factory()->create(['status' => 'active']);
        $shopOwner->assignRole('shop');
        $shop = Shop::query()->create([
            'user_id' => $shopOwner->id,
            'name' => 'Needle Shop',
            'slug' => 'needle-shop',
            'status' => 'active',
        ]);

        $otherOwner = User::factory()->create(['status' => 'active']);
        $otherOwner->assignRole('shop');
        $otherShop = Shop::query()->create([
            'user_id' => $otherOwner->id,
            'name' => 'Other Shop',
            'slug' => 'other-shop',
            'status' => 'active',
        ]);

        $matchProduct = Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $shop->id,
            'user_id' => $shopOwner->id,
            'name' => 'Needle Product One',
            'slug' => 'needle-product-one',
            'selling_price' => 12,
            'commission' => 1,
            'stock' => 10,
            'status' => 'active',
        ]);

        $otherProduct = Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $otherShop->id,
            'user_id' => $otherOwner->id,
            'name' => 'Other Product Two',
            'slug' => 'other-product-two',
            'selling_price' => 15,
            'commission' => 1,
            'stock' => 10,
            'status' => 'active',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $shopOwner->id,
            'product_id' => $matchProduct->id,
            'selling_price' => 12,
            'purchase_price' => 8,
            'commission' => 1,
            'commission_type' => 'fixed',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $otherOwner->id,
            'product_id' => $otherProduct->id,
            'selling_price' => 15,
            'purchase_price' => 10,
            'commission' => 1,
            'commission_type' => 'fixed',
        ]);

        $this->actingAs($this->member)
            ->get('/home?q=Needle')
            ->assertOk()
            ->assertSee('Needle Product One')
            ->assertDontSee('Other Product Two');

        $this->actingAs($this->member)
            ->get('/home?q=Needle Shop')
            ->assertOk()
            ->assertSee('Needle Product One')
            ->assertDontSee('Other Product Two');

        $this->actingAs($this->member)
            ->get('/home/products?shop=Needle')
            ->assertOk()
            ->assertSee('Needle Product One')
            ->assertDontSee('Other Product Two');
    }

    public function test_member_shop_filter_by_selected_shop_id_returns_only_that_shop_products(): void
    {
        $category = Category::query()->create([
            'name' => 'Shop Exact Category',
            'slug' => 'shop-exact-category',
            'status' => 'active',
        ]);

        $firstOwner = User::factory()->create(['status' => 'active']);
        $firstOwner->assignRole('shop');
        $selectedShop = Shop::query()->create([
            'user_id' => $firstOwner->id,
            'name' => 'Needle Shop',
            'slug' => 'needle-shop-exact',
            'status' => 'active',
        ]);

        $secondOwner = User::factory()->create(['status' => 'active']);
        $secondOwner->assignRole('shop');
        $similarShop = Shop::query()->create([
            'user_id' => $secondOwner->id,
            'name' => 'Needle Shop Plus',
            'slug' => 'needle-shop-plus',
            'status' => 'active',
        ]);

        $selectedProduct = Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $selectedShop->id,
            'user_id' => $firstOwner->id,
            'name' => 'Selected Shop Product',
            'slug' => 'selected-shop-product',
            'selling_price' => 20,
            'commission' => 1,
            'stock' => 10,
            'status' => 'active',
        ]);

        $similarProduct = Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $similarShop->id,
            'user_id' => $secondOwner->id,
            'name' => 'Similar Shop Product',
            'slug' => 'similar-shop-product',
            'selling_price' => 21,
            'commission' => 1,
            'stock' => 10,
            'status' => 'active',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $firstOwner->id,
            'product_id' => $selectedProduct->id,
            'selling_price' => 20,
            'purchase_price' => 10,
            'commission' => 1,
            'commission_type' => 'fixed',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $secondOwner->id,
            'product_id' => $similarProduct->id,
            'selling_price' => 21,
            'purchase_price' => 11,
            'commission' => 1,
            'commission_type' => 'fixed',
        ]);

        $this->actingAs($this->member)
            ->get('/home/products?shop=Needle&shop_id='.$selectedShop->id)
            ->assertOk()
            ->assertSee('Selected Shop Product')
            ->assertDontSee('Similar Shop Product');
    }

    public function test_member_selected_shop_id_uses_distributor_shop_products(): void
    {
        $category = Category::query()->create([
            'name' => 'Distributor Search Category',
            'slug' => 'distributor-search-category',
            'status' => 'active',
        ]);

        $distributor = User::factory()->create([
            'status' => 'active',
            'user_code' => 'U0356674288',
        ]);
        $distributor->assignRole('shop');
        $distributorShop = Shop::query()->create([
            'user_id' => $distributor->id,
            'name' => 'Tesst Distributor Shop',
            'slug' => 'tesst-distributor-shop',
            'status' => 'active',
        ]);

        $owner = User::factory()->create(['status' => 'active']);
        $owner->assignRole('shop');
        $ownerShop = Shop::query()->create([
            'user_id' => $owner->id,
            'name' => 'Origin Owner Shop',
            'slug' => 'origin-owner-shop',
            'status' => 'active',
        ]);

        $distributedProduct = Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $ownerShop->id,
            'user_id' => $owner->id,
            'name' => 'Distributed By Tesst',
            'slug' => 'distributed-by-tesst',
            'selling_price' => 20,
            'commission' => 1,
            'stock' => 10,
            'status' => 'active',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $distributor->id,
            'product_id' => $distributedProduct->id,
            'selling_price' => 20,
            'purchase_price' => 11,
            'commission' => 1,
            'commission_type' => 'fixed',
            'status' => 'available',
            'is_featured' => true,
            'featured_at' => now(),
        ]);

        $this->actingAs($this->member)
            ->get('/home/products?shop=Tesst&shop_id='.$distributorShop->id)
            ->assertOk()
            ->assertSee('Distributed By Tesst')
            ->assertSee('Tesst Distributor Shop')
            ->assertDontSee('Origin Owner Shop');
    }

    public function test_portal_listing_shows_latest_distributor_shop_on_product_card(): void
    {
        $category = Category::query()->create([
            'name' => 'Latest Distributor Label Category',
            'slug' => 'latest-distributor-label-category',
            'status' => 'active',
        ]);

        $owner = User::factory()->create(['status' => 'active']);
        $owner->assignRole('shop');
        $ownerShop = Shop::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner Platform Shop',
            'slug' => 'owner-platform-shop',
            'status' => 'active',
        ]);

        $distributor = User::factory()->create(['status' => 'active']);
        $distributor->assignRole('shop');
        $distributorShop = Shop::query()->create([
            'user_id' => $distributor->id,
            'name' => 'Tesst Distributor Shop',
            'slug' => 'tesst-distributor-shop',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $ownerShop->id,
            'user_id' => $owner->id,
            'name' => 'Latest Distributor Label Product',
            'slug' => 'latest-distributor-label-product',
            'selling_price' => 25,
            'purchase_price' => 15,
            'commission' => 10,
            'stock' => 10,
            'status' => 'active',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'selling_price' => 25,
            'purchase_price' => 15,
            'commission' => 10,
            'commission_type' => 'fixed',
            'status' => ProductDistribution::STATUS_AVAILABLE,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        ProductDistribution::query()->create([
            'user_id' => $distributor->id,
            'product_id' => $product->id,
            'selling_price' => 25,
            'purchase_price' => 15,
            'commission' => 10,
            'commission_type' => 'fixed',
            'status' => ProductDistribution::STATUS_AVAILABLE,
            'is_featured' => true,
            'featured_at' => now(),
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        // Reference-style home cards show only image, name, and price.
        $this->actingAs($this->member)
            ->get(route('member.home'))
            ->assertOk()
            ->assertSee('Latest Distributor Label Product');

        $this->actingAs($this->member)
            ->get(route('member.products.index'))
            ->assertOk()
            ->assertSee('Tesst Distributor Shop')
            ->assertDontSee('Owner Platform Shop');
    }

    public function test_portal_products_are_ordered_by_latest_distribution(): void
    {
        $category = Category::query()->create([
            'name' => 'Distribution Sort Category',
            'slug' => 'distribution-sort-category',
            'status' => 'active',
        ]);

        $distributor = User::factory()->create(['status' => 'active']);
        $distributor->assignRole('shop');
        $shop = Shop::query()->create([
            'user_id' => $distributor->id,
            'name' => 'Sort Distributor Shop',
            'slug' => 'sort-distributor-shop',
            'status' => 'active',
        ]);

        $olderDistributionProduct = Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $shop->id,
            'user_id' => $distributor->id,
            'name' => 'Older Listed Second Product',
            'slug' => 'older-listed-second-product',
            'selling_price' => 10,
            'purchase_price' => 5,
            'commission' => 5,
            'stock' => 10,
            'status' => 'active',
        ]);

        $latestDistributionProduct = Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $shop->id,
            'user_id' => $distributor->id,
            'name' => 'Latest Distributed First Product',
            'slug' => 'latest-distributed-first-product',
            'selling_price' => 12,
            'purchase_price' => 6,
            'commission' => 6,
            'stock' => 10,
            'status' => 'active',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $distributor->id,
            'product_id' => $olderDistributionProduct->id,
            'selling_price' => 10,
            'purchase_price' => 5,
            'commission' => 5,
            'commission_type' => 'fixed',
            'status' => ProductDistribution::STATUS_AVAILABLE,
            'is_featured' => true,
            'featured_at' => now()->subDay(),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        ProductDistribution::query()->create([
            'user_id' => $distributor->id,
            'product_id' => $latestDistributionProduct->id,
            'selling_price' => 12,
            'purchase_price' => 6,
            'commission' => 6,
            'commission_type' => 'fixed',
            'status' => ProductDistribution::STATUS_AVAILABLE,
            'is_featured' => true,
            'featured_at' => now(),
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $this->actingAs($this->member)
            ->get(route('member.products.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Latest Distributed First Product',
                'Older Listed Second Product',
            ]);

        $this->actingAs($this->member)
            ->get(route('member.home'))
            ->assertOk()
            ->assertSeeInOrder([
                'Latest Distributed First Product',
                'Older Listed Second Product',
            ]);
    }

    public function test_member_search_suggestions_return_results_from_expected_context(): void
    {
        $category = Category::query()->create([
            'name' => 'Suggest Category',
            'slug' => 'suggest-category',
            'status' => 'active',
        ]);

        $shopUser = User::factory()->create(['status' => 'active']);
        $shopUser->assignRole(['member', 'shop']);
        Shop::query()->create([
            'user_id' => $shopUser->id,
            'name' => 'Needle Manage Shop',
            'slug' => 'needle-manage-shop',
            'status' => 'active',
        ]);

        $shopWithoutProductsOwner = User::factory()->create([
            'status' => 'active',
            'user_code' => 'SMTEST001',
        ]);
        $shopWithoutProductsOwner->assignRole('shop');
        Shop::query()->create([
            'user_id' => $shopWithoutProductsOwner->id,
            'name' => 'Tesst Empty Shop',
            'slug' => 'tesst-empty-shop',
            'status' => 'active',
        ]);

        $portalOwner = User::factory()->create(['status' => 'active']);
        $portalOwner->assignRole('shop');
        $portalShop = Shop::query()->create([
            'user_id' => $portalOwner->id,
            'name' => 'Needle Portal Shop',
            'slug' => 'needle-portal-shop',
            'status' => 'active',
        ]);

        $manageProduct = Product::query()->create([
            'category_id' => $category->id,
            'shop_id' => $portalShop->id,
            'user_id' => $portalOwner->id,
            'name' => 'Needle Portal Product',
            'slug' => 'needle-portal-product',
            'selling_price' => 18,
            'commission' => 1,
            'stock' => 8,
            'status' => 'active',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $shopUser->id,
            'product_id' => $manageProduct->id,
            'selling_price' => 18,
            'purchase_price' => 12,
            'commission' => 1,
            'commission_type' => 'fixed',
        ]);

        $portalResponse = $this->actingAs($this->member)
            ->getJson('/home/search/suggestions?q=N&target=product&context=portal')
            ->assertOk();

        $this->assertTrue(
            collect($portalResponse->json('items'))->pluck('value')->contains('Needle Portal Product'),
        );

        $shopResponse = $this->actingAs($this->member)
            ->getJson('/home/search/suggestions?q=N&target=shop&context=portal')
            ->assertOk();

        $this->assertTrue(
            collect($shopResponse->json('items'))->pluck('value')->contains('Needle Portal Shop'),
        );
        $shopItem = collect($shopResponse->json('items'))->firstWhere('value', 'Needle Portal Shop');
        $this->assertNotNull($shopItem['id'] ?? null);

        $combinedResponse = $this->actingAs($this->member)
            ->getJson('/home/search/suggestions?q=N&target=combined&context=portal')
            ->assertOk();

        $combinedValues = collect($combinedResponse->json('items'))->pluck('value');
        $this->assertTrue($combinedValues->contains('Needle Portal Shop'));
        $this->assertTrue($combinedValues->contains('Needle Portal Product'));

        $this->actingAs($this->member)
            ->getJson('/home/search/suggestions?q=Tesst&target=shop&context=portal')
            ->assertOk()
            ->assertJsonFragment(['value' => 'Tesst Empty Shop']);

        $manageResponse = $this->actingAs($shopUser)
            ->getJson('/home/search/suggestions?q=N&target=product&context=manage')
            ->assertOk();

        $this->assertTrue(
            collect($manageResponse->json('items'))->pluck('value')->contains('Needle Portal Product'),
        );
    }

    public function test_member_my_and_wallet_pages(): void
    {
        $this->actingAs($this->member)
            ->get('/home/my')
            ->assertOk()
            ->assertSee(__('member.my.my_orders'))
            ->assertSee(__('member.my.my_wallet'));

        $this->actingAs($this->member)
            ->get('/home/recharge')
            ->assertOk()
            ->assertSee(__('member.wallet.recharge_title'))
            ->assertSee(__('member.wallet.recharge_method'));

        $this->member->update(['payment_password' => '123456']);

        $this->actingAs($this->member->fresh())
            ->get('/home/withdrawal')
            ->assertOk()
            ->assertSee(__('member.wallet.withdraw_title'))
            ->assertSee(__('member.wallet.withdraw_method'))
            ->assertSee(__('member.wallet.withdraw_password'));

        $this->actingAs($this->member)
            ->get('/home/fund-records')
            ->assertOk()
            ->assertSee(__('member.wallet.fund_records'))
            ->assertSee(__('member.wallet.tab_recharge'))
            ->assertSee(__('member.wallet.tab_withdrawal'));

        $this->actingAs($this->member)
            ->get('/home/withdrawal-records')
            ->assertRedirect(route('member.wallet.fund-records', ['type' => 'withdrawal']));
    }

    public function test_shop_user_sees_buyer_and_seller_order_tabs(): void
    {
        $shopUser = User::factory()->create(['status' => 'active']);
        $shopUser->assignRole(['member', 'shop']);

        Shop::query()->create([
            'user_id' => $shopUser->id,
            'name' => 'Tab Shop',
            'slug' => 'tab-shop',
            'status' => 'active',
        ]);

        Wallet::query()->create([
            'user_id' => $shopUser->id,
            'balance' => 500,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $this->actingAs($shopUser)
            ->get('/home/orders')
            ->assertOk()
            ->assertSee(__('member.orders.my_orders'))
            ->assertSee(__('member.orders.customer_orders'))
            ->assertSee(route('member.orders.index', ['scope' => 'all']), false);

        $this->actingAs($shopUser)
            ->get('/home/orders?scope=all')
            ->assertOk()
            ->assertSee(__('member.orders.all'));

        $this->actingAs($shopUser)
            ->get('/home/my')
            ->assertOk()
            ->assertSee(__('member.my.my_orders'))
            ->assertSee(__('member.my.merchant_pending_payment'))
            ->assertSee(__('member.my.shop_manage'));
    }

    public function test_member_can_submit_bank_withdrawal_request(): void
    {
        $method = WithdrawalMethod::query()->first();
        $this->member->update(['payment_password' => '123456']);

        $this->actingAs($this->member)
            ->post('/home/withdrawal', [
                'withdrawal_method_id' => $method->id,
                'amount' => 100,
                'bank_account_name' => 'Nguyen Van A',
                'bank_name' => 'Vietcombank',
                'bank_account_number' => '0123456789',
                'payment_password' => '123456',
            ])
            ->assertRedirect(route('member.wallet.fund-records', ['type' => 'withdrawal']))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('withdrawal_requests', [
            'user_id' => $this->member->id,
            'withdrawal_method_id' => $method->id,
            'amount' => 100,
            'status' => 'pending',
        ]);
    }

    public function test_member_can_submit_crypto_withdrawal_request(): void
    {
        $method = WithdrawalMethod::query()->create([
            'name' => 'Blockchain',
            'type' => 'crypto',
            'config' => [
                'fee_percent' => 0,
                'currencies' => ['USDT'],
                'networks' => [
                    ['label' => 'USDT (TRC20)', 'fee' => 0],
                ],
            ],
            'status' => 'active',
            'sort_order' => 2,
        ]);
        $this->member->update(['payment_password' => '123456']);

        $this->actingAs($this->member)
            ->post('/home/withdrawal', [
                'withdrawal_method_id' => $method->id,
                'amount' => 50,
                'currency' => 'USDT',
                'network' => 'USDT (TRC20)',
                'crypto_address' => 'TXyz123456789',
                'payment_password' => '123456',
            ])
            ->assertRedirect(route('member.wallet.fund-records', ['type' => 'withdrawal']))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('withdrawal_requests', [
            'user_id' => $this->member->id,
            'amount' => 50,
        ]);
    }

    public function test_member_withdrawal_rejects_insufficient_balance(): void
    {
        $method = WithdrawalMethod::query()->first();
        $this->member->update(['payment_password' => '123456']);

        $this->actingAs($this->member)
            ->post('/home/withdrawal', [
                'withdrawal_method_id' => $method->id,
                'amount' => 9999,
                'bank_account_name' => 'Nguyen Van A',
                'bank_name' => 'Vietcombank',
                'bank_account_number' => '0123456789',
                'payment_password' => '123456',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('amount');
    }

    public function test_member_withdrawal_rejects_invalid_password(): void
    {
        $method = WithdrawalMethod::query()->first();
        $this->member->update(['payment_password' => '123456']);

        $this->actingAs($this->member)
            ->post('/home/withdrawal', [
                'withdrawal_method_id' => $method->id,
                'amount' => 100,
                'bank_account_name' => 'Nguyen Van A',
                'bank_name' => 'Vietcombank',
                'bank_account_number' => '0123456789',
                'payment_password' => '000000',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('payment_password');
    }

    public function test_member_without_withdraw_password_redirected_from_withdrawal_page(): void
    {
        $this->actingAs($this->member)
            ->get('/home/withdrawal')
            ->assertRedirect(route('member.payment-password.create', [
                'redirect' => route('member.wallet.withdrawal'),
            ]));
    }

    public function test_member_can_place_order(): void
    {
        $category = Category::query()->create([
            'name' => 'Test',
            'slug' => 'test',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'selling_price' => 10,
            'commission' => 1,
            'stock' => 5,
            'status' => 'active',
        ]);

        $shopOwner = User::factory()->create(['status' => 'active']);
        $shopOwner->assignRole('shop');
        Shop::query()->create([
            'user_id' => $shopOwner->id,
            'name' => 'Catalog Shop',
            'slug' => 'catalog-shop',
            'status' => 'active',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $shopOwner->id,
            'product_id' => $product->id,
            'selling_price' => $product->selling_price,
            'purchase_price' => 0,
            'commission' => 1,
            'commission_type' => 'fixed',
        ]);

        $this->actingAs($this->member)
            ->get(route('member.checkout.show', $product))
            ->assertRedirect(route('member.payment-password.create', ['redirect' => route('member.checkout.show', $product)]));

        $this->member->update(['payment_password' => '123456']);

        $this->actingAs($this->member->fresh())
            ->get(route('member.checkout.show', $product))
            ->assertOk()
            ->assertSee(__('member.checkout.title'));

        \App\Models\ShippingAddress::query()->create([
            'user_id' => $this->member->id,
            'recipient_name' => 'Test User',
            'phone' => '0901234567',
            'country' => 'VN',
            'state' => 'Hanoi',
            'city' => 'Hanoi',
            'address_line' => '123 Test Street',
            'is_default' => true,
        ]);

        $balanceBefore = (float) $this->member->wallet->balance;

        $this->actingAs($this->member->fresh())
            ->post(route('member.checkout.store', $product), [
                'qty' => 1,
                'payment_method' => 'balance',
            ])
            ->assertRedirect(route('member.orders.index', ['status' => 'awaiting_pickup']));

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->member->id,
            'status' => 'pending_payment',
        ]);

        $placedOrder = Order::query()
            ->where('user_id', $this->member->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($placedOrder);

        $this->actingAs($this->member)
            ->get(route('member.orders.index', ['status' => 'awaiting_pickup']))
            ->assertOk()
            ->assertSee($placedOrder->order_no)
            ->assertSee(__('member.orders.awaiting_pickup'))
            ->assertDontSee(__('member.orders.pending_payment'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->member->id,
            'type' => 'order_payment',
            'amount' => 10,
        ]);

        $this->assertSame($balanceBefore - 10.0, (float) $this->member->wallet->fresh()->balance);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_buyer_orders_hide_pending_payment_status_and_map_to_awaiting_pickup(): void
    {
        Order::query()->create([
            'user_id' => $this->member->id,
            'order_no' => 'ORD-BUYER-PENDING-001',
            'total' => 50,
            'commission' => 5,
            'purchase_cost' => 30,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => 'wallet',
            'paid_at' => now(),
        ]);

        $this->actingAs($this->member)
            ->get(route('member.orders.index'))
            ->assertOk()
            ->assertDontSee(route('member.orders.index', ['status' => 'pending_payment']), false)
            ->assertSee(__('member.orders.awaiting_pickup'))
            ->assertDontSee(__('member.orders.pending_payment'));
    }

    public function test_member_can_store_payment_password(): void
    {
        $this->assertFalse($this->member->hasPaymentPassword());

        $this->actingAs($this->member)
            ->post(route('member.payment-password.store'), [
                'payment_password' => '123456',
                'payment_password_confirmation' => '123456',
            ])
            ->assertRedirect(route('member.home'))
            ->assertSessionHas('status');

        $this->assertTrue($this->member->fresh()->hasPaymentPassword());
    }

    public function test_member_can_change_login_password_from_profile(): void
    {
        $this->actingAs($this->member)
            ->put(route('member.profile.password.update'), [
                'current_password' => 'password',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertRedirect(route('member.settings.index'))
            ->assertSessionHas('status');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $this->member->fresh()->password));
    }

    public function test_member_can_change_payment_password_from_profile(): void
    {
        $this->member->update(['payment_password' => '123456']);

        $this->actingAs($this->member)
            ->put(route('member.payment-password.update'), [
                'current_payment_password' => '123456',
                'payment_password' => '654321',
                'payment_password_confirmation' => '654321',
            ])
            ->assertRedirect(route('member.settings.index'))
            ->assertSessionHas('status');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('654321', $this->member->fresh()->getRawOriginal('payment_password')));
    }

    public function test_member_can_update_combined_profile(): void
    {
        $this->actingAs($this->member)
            ->put(route('member.profile.update'), [
                'name' => 'Demo User',
                'gender' => 'female',
                'birthday' => '1990-05-15',
            ])
            ->assertRedirect(route('member.profile.show'))
            ->assertSessionHas('status');

        $this->member->refresh();
        $this->assertSame('Demo User', $this->member->name);
        $this->assertSame('female', $this->member->gender);
        $this->assertSame('1990-05-15', $this->member->birthday?->format('Y-m-d'));
    }

    public function test_member_personal_page_matches_demo_user_info(): void
    {
        $this->member->update([
            'name' => 'Nguyen Van A',
            'phone' => '0901234567',
            'gender' => 'male',
            'birthday' => '1995-03-20',
        ]);

        $this->actingAs($this->member)
            ->get(route('member.profile.show'))
            ->assertOk()
            ->assertSee(__('member.profile.info_title'))
            ->assertSee(__('member.profile.avatar'))
            ->assertSee(__('member.profile.username'))
            ->assertSee(__('member.profile.gender'))
            ->assertSee(__('member.profile.birthday'))
            ->assertSee(__('member.profile.confirm_changes'));
    }

    public function test_member_settings_page_matches_demo_sections(): void
    {
        $this->member->update([
            'name' => 'Nguyen Van A',
            'phone' => '0901234567',
        ]);

        $this->actingAs($this->member)
            ->get(route('member.settings.index'))
            ->assertOk()
            ->assertSee(__('member.settings.title'))
            ->assertSee('Nguyen Van A')
            ->assertSee('0901234567')
            ->assertSee(__('member.settings.shipping'))
            ->assertSee(__('member.settings.bind_login'))
            ->assertSee(__('member.settings.change_account'))
            ->assertSee(__('member.settings.login_password'))
            ->assertSee(__('member.settings.language'))
            ->assertSee(__('member.settings.about'))
            ->assertSee(__('member.settings.logout'));
    }

    public function test_member_can_update_profile_name(): void
    {
        $this->actingAs($this->member)
            ->put(route('member.profile.name.update'), [
                'name' => 'Dao Minh Huy',
            ])
            ->assertRedirect(route('member.profile.show'))
            ->assertSessionHas('status');

        $this->assertSame('Dao Minh Huy', $this->member->fresh()->name);
    }

    public function test_member_can_upload_profile_avatar(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('avatar.png', 100, 'image/png');

        $this->actingAs($this->member)
            ->post(route('member.profile.avatar.update'), [
                'avatar' => $file,
            ])
            ->assertRedirect(route('member.profile.show'))
            ->assertSessionHas('status');

        $path = $this->member->fresh()->avatar;
        $this->assertNotNull($path);
        $this->assertStringStartsWith('avatars/', $path);
        $this->assertTrue(Storage::disk('public')->exists($path));
    }

    public function test_member_avatar_upload_syncs_shop_logo_when_user_has_shop(): void
    {
        $this->member->assignRole('shop');
        $shop = Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Avatar Shop',
            'slug' => 'avatar-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('avatar.png', 100, 'image/png');

        $this->actingAs($this->member)
            ->post(route('member.profile.avatar.update'), [
                'avatar' => $file,
            ])
            ->assertRedirect(route('member.profile.show'));

        $this->member->refresh();
        $shop->refresh();

        $this->assertSame($this->member->avatar, $shop->logo);
        $this->assertSame($this->member->avatarUrl(), $shop->displayLogoUrl());
    }

    public function test_email_registered_member_can_update_phone(): void
    {
        $this->actingAs($this->member)
            ->put(route('member.profile.phone.update'), [
                'phone' => '0908765432',
            ])
            ->assertRedirect(route('member.settings.change-account'))
            ->assertSessionHas('status');

        $this->assertSame('0908765432', $this->member->fresh()->phone);
    }

    public function test_phone_registered_member_can_update_email(): void
    {
        $phone = '0356'.fake()->unique()->numerify('######');
        $phoneUser = User::factory()->create([
            'email' => $phone.'@member.shopefy.local',
            'phone' => $phone,
            'status' => 'active',
        ]);
        $phoneUser->assignRole('member');

        $this->actingAs($phoneUser)
            ->put(route('member.profile.email.update'), [
                'email' => 'seller@example.com',
                'current_password' => 'password',
            ])
            ->assertRedirect(route('member.settings.change-account'))
            ->assertSessionHas('status');

        $fresh = $phoneUser->fresh();
        $this->assertSame('seller@example.com', $fresh->email);
        $this->assertFalse($fresh->canEditEmail());
        $this->assertFalse($fresh->isEmailVerified());
        $this->assertNull($fresh->email_verified_at);
    }

    public function test_phone_registered_member_cannot_update_email_without_current_password(): void
    {
        $phoneUser = User::factory()->create([
            'email' => '0356674289@member.shopefy.local',
            'phone' => '0356674289',
            'status' => 'active',
        ]);
        $phoneUser->assignRole('member');

        $this->actingAs($phoneUser)
            ->put(route('member.profile.email.update'), [
                'email' => 'seller@example.com',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame('0356674289@member.shopefy.local', $phoneUser->fresh()->email);
    }

    public function test_phone_registered_member_cannot_update_phone_via_profile(): void
    {
        $phoneUser = User::factory()->create([
            'email' => '0356674290@member.shopefy.local',
            'phone' => '0356674290',
            'status' => 'active',
        ]);
        $phoneUser->assignRole('member');

        $this->actingAs($phoneUser)
            ->put(route('member.profile.phone.update'), [
                'phone' => '0999999999',
            ])
            ->assertForbidden();
    }

    public function test_email_registered_member_cannot_update_email_via_profile(): void
    {
        $this->actingAs($this->member)
            ->put(route('member.profile.email.update'), [
                'email' => 'other@example.com',
            ])
            ->assertForbidden();
    }

    public function test_member_session_resyncs_after_user_id_changes(): void
    {
        $email = $this->member->email;

        $this->actingAs($this->member)
            ->get(route('member.home'))
            ->assertOk();

        $this->member->delete();

        $replacement = User::factory()->create([
            'email' => $email,
            'status' => 'active',
        ]);
        $replacement->assignRole('member');

        Wallet::query()->create([
            'user_id' => $replacement->id,
            'balance' => 500,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $this->get(route('member.home'))
            ->assertOk();

        $this->assertSame($replacement->id, auth()->id());

        $this->post(route('member.payment-password.store'), [
            'payment_password' => '123456',
            'payment_password_confirmation' => '123456',
        ])->assertRedirect(route('member.home'));

        $this->assertTrue($replacement->fresh()->hasPaymentPassword());
    }

    public function test_member_can_store_shipping_address_with_full_country_name(): void
    {
        $this->actingAs($this->member)
            ->post(route('member.shipping.store'), [
                'recipient_name' => 'Nguyen Van A',
                'phone' => '0901234567',
                'country' => 'Việt Nam',
                'state' => 'Hà Nội',
                'city' => 'Cầu Giấy',
                'address_line' => '123 Phố Test',
                'is_default' => '1',
            ])
            ->assertRedirect(route('member.shipping.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('shipping_addresses', [
            'user_id' => $this->member->id,
            'country' => 'Việt Nam',
            'recipient_name' => 'Nguyen Van A',
        ]);
    }

    public function test_checkout_requires_address_before_placing_order(): void
    {
        $product = Product::query()->create([
            'category_id' => Category::query()->create([
                'name' => 'Test',
                'slug' => 'test-2',
                'status' => 'active',
            ])->id,
            'name' => 'Gated Product',
            'slug' => 'gated-product',
            'selling_price' => 10,
            'commission' => 1,
            'stock' => 5,
            'status' => 'active',
        ]);

        $shopOwner = User::factory()->create(['status' => 'active']);
        $shopOwner->assignRole('shop');
        Shop::query()->create([
            'user_id' => $shopOwner->id,
            'name' => 'Gated Shop',
            'slug' => 'gated-shop',
            'status' => 'active',
        ]);

        ProductDistribution::query()->create([
            'user_id' => $shopOwner->id,
            'product_id' => $product->id,
            'selling_price' => $product->selling_price,
            'purchase_price' => 0,
            'commission' => 1,
            'commission_type' => 'fixed',
        ]);

        $this->member->update(['payment_password' => '123456']);

        $this->actingAs($this->member->fresh())
            ->post(route('member.checkout.store', $product), [
                'qty' => 1,
                'payment_method' => 'balance',
            ])
            ->assertRedirect(route('member.shipping.index', ['redirect' => route('member.checkout.show', $product)]));
    }

    public function test_member_can_submit_recharge_request(): void
    {
        $method = RechargeMethod::query()->where('type', RechargeMethod::TYPE_BANK)->firstOrFail();

        $this->actingAs($this->member)
            ->post('/home/recharge', [
                'recharge_method_id' => $method->id,
                'amount' => 100,
            ])
            ->assertRedirect(route('member.wallet.fund-records', ['type' => 'recharge']))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('recharge_requests', [
            'user_id' => $this->member->id,
            'amount' => 100,
        ]);
    }

    public function test_member_cannot_submit_recharge_with_disabled_method(): void
    {
        $method = RechargeMethod::query()->first();
        $method->update(['status' => RechargeMethod::STATUS_INACTIVE]);

        $this->actingAs($this->member)
            ->post('/home/recharge', [
                'recharge_method_id' => $method->id,
                'amount' => 100,
            ])
            ->assertSessionHasErrors('recharge_method_id');

        $this->assertDatabaseMissing('recharge_requests', [
            'user_id' => $this->member->id,
            'amount' => 100,
        ]);
    }

    public function test_member_recharge_redirects_to_support_when_no_active_methods(): void
    {
        RechargeMethod::query()->update(['status' => RechargeMethod::STATUS_INACTIVE]);

        $prefill = RechargeMethod::supportChatPrefill($this->member);

        $this->actingAs($this->member)
            ->get(route('member.wallet.recharge'))
            ->assertRedirect(route('member.chat.index', ['prefill' => $prefill]));

        $response = $this->actingAs($this->member)
            ->get(route('member.chat.index', ['prefill' => $prefill]));

        $response->assertOk();
        $this->assertStringContainsString((string) $this->member->user_code, $response->getContent());
        $this->assertStringContainsString('prefill', $response->getContent());
    }

    public function test_member_recharge_page_includes_disabled_methods(): void
    {
        $disabled = RechargeMethod::query()->create([
            'name' => 'Disabled Recharge',
            'type' => 'bank',
            'status' => RechargeMethod::STATUS_INACTIVE,
            'sort_order' => 99,
        ]);

        $response = $this->actingAs($this->member)
            ->get('/home/recharge');

        $response->assertOk()
            ->assertSee($disabled->name);

        $this->assertStringContainsString(
            str_replace('/', '\\/', route('member.chat.index')),
            $response->getContent(),
        );
    }

    public function test_member_cannot_view_financial_report_page(): void
    {
        $this->actingAs($this->member)
            ->get(route('member.financial-report.index'))
            ->assertForbidden();

        $this->actingAs($this->member)
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertDontSee(__('member.my.financial_report'));
    }

    public function test_shop_can_view_financial_report_page(): void
    {
        $this->member->assignRole('shop');

        Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Finance Shop',
            'slug' => 'finance-shop-view',
            'status' => 'active',
        ]);

        $this->actingAs($this->member->fresh())
            ->get(route('member.financial-report.index'))
            ->assertOk()
            ->assertSee(__('member.financial_report.title'))
            ->assertSee(__('member.financial_report.stock_import'))
            ->assertSee(__('member.financial_report.period_day'));

        $this->actingAs($this->member->fresh())
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertDontSee(__('member.my.financial_report'));
    }

    public function test_financial_report_shows_seller_profit_totals(): void
    {
        $this->member->assignRole('shop');

        Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'Finance Shop',
            'slug' => 'finance-shop',
            'status' => 'active',
        ]);

        $buyer = User::factory()->create(['status' => 'active']);

        Order::query()->create([
            'user_id' => $buyer->id,
            'seller_id' => $this->member->id,
            'order_no' => 'ORD-FIN-1',
            'total' => 100,
            'purchase_cost' => 60,
            'commission' => 15,
            'status' => Order::STATUS_COMPLETED,
            'created_at' => now(),
        ]);

        Order::query()->create([
            'user_id' => $buyer->id,
            'seller_id' => $this->member->id,
            'order_no' => 'ORD-FIN-2',
            'total' => 120,
            'purchase_cost' => 70,
            'commission' => 20,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'created_at' => now(),
        ]);

        $this->actingAs($this->member->fresh())
            ->get(route('member.financial-report.index'))
            ->assertOk()
            ->assertSee('$35.00')
            ->assertSee(__('member.my.total_income'));
    }

    public function test_member_my_page_shows_all_eight_menu_items(): void
    {
        // Regular member sees "Bắt đầu bán" (not "Quản lý cửa hàng").
        $this->actingAs($this->member)
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertSee(__('member.actions.start_selling'))
            ->assertSee(__('member.my.my_wallet'))
            ->assertSee(__('member.my.shipping_address'))
            ->assertSee(__('member.my.my_reviews'))
            ->assertSee(__('member.actions.support'))
            ->assertSee(__('member.my.complaints'))
            ->assertSee(__('member.my.about'))
            ->assertSee(__('member.settings.title'));
    }

    public function test_member_my_page_matches_reference_merchant_layout(): void
    {
        $this->member->assignRole('shop');

        Shop::query()->create([
            'user_id' => $this->member->id,
            'name' => 'My Layout Shop',
            'slug' => 'my-layout-shop',
            'seller_type' => Shop::TYPE_BUSINESS,
            'status' => 'active',
        ]);

        // Reference merchant my page: orders card, merchant badge, menu grid, feed — no wallet card.
        $this->actingAs($this->member->fresh())
            ->get(route('member.my.index'))
            ->assertOk()
            ->assertSee(__('member.my.merchant_badge'))
            ->assertSee(__('member.my.merchant_pending_payment'))
            ->assertSee(__('member.my.shop_manage'))
            ->assertDontSee(__('member.my.pending_payment_amount'))
            ->assertDontSee(__('member.my.total_income'));
    }
}
