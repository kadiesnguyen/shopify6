<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\ProductReview;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Product $product;

    private ProductDistribution $distribution;

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

        $seller = User::factory()->create(['status' => 'active']);
        $seller->assignRole('shop');
        Shop::query()->create([
            'user_id' => $seller->id,
            'name' => 'Review Shop',
            'slug' => 'review-shop',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $category = Category::query()->create([
            'name' => 'Review Cat',
            'slug' => 'review-cat',
            'status' => Category::STATUS_ACTIVE,
            'sort_order' => 1,
        ]);

        $this->product = Product::query()->create([
            'name' => 'Reviewable Product',
            'slug' => 'reviewable-product',
            'category_id' => $category->id,
            'selling_price' => 10,
            'purchase_price' => 5,
            'stock' => 20,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->distribution = ProductDistribution::query()->create([
            'user_id' => $seller->id,
            'product_id' => $this->product->id,
            'selling_price' => 10,
            'purchase_price' => 5,
            'commission' => 5,
            'commission_type' => ProductDistribution::COMMISSION_FIXED,
            'status' => ProductDistribution::STATUS_AVAILABLE,
        ]);
    }

    private function makeCompletedOrder(): Order
    {
        return Order::query()->create([
            'user_id' => $this->member->id,
            'seller_id' => $this->distribution->user_id,
            'product_distribution_id' => $this->distribution->id,
            'order_no' => 'ORD-REV-'.uniqid(),
            'total' => 10,
            'purchase_cost' => 5,
            'commission' => 5,
            'status' => Order::STATUS_COMPLETED,
        ]);
    }

    public function test_review_requires_delivered_purchase(): void
    {
        $this->actingAs($this->member)
            ->post(route('member.reviews.store'), [
                'product_id' => $this->product->id,
                'rating' => 5,
                'body' => 'Great',
            ])
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_member_can_review_purchased_product_once(): void
    {
        $order = $this->makeCompletedOrder();

        $this->actingAs($this->member)
            ->post(route('member.reviews.store'), [
                'product_id' => $this->product->id,
                'rating' => 4,
                'body' => 'Good product',
            ])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('product_reviews', [
            'user_id' => $this->member->id,
            'product_id' => $this->product->id,
            'order_id' => $order->id,
            'rating' => 4,
            'status' => ProductReview::STATUS_PUBLISHED,
        ]);

        // Duplicate blocked.
        $this->actingAs($this->member)
            ->post(route('member.reviews.store'), [
                'product_id' => $this->product->id,
                'rating' => 1,
            ])
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseCount('product_reviews', 1);
    }

    public function test_product_detail_shows_published_reviews_and_hides_hidden(): void
    {
        $this->makeCompletedOrder();

        ProductReview::query()->create([
            'user_id' => $this->member->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'body' => 'Visible review body',
            'status' => ProductReview::STATUS_PUBLISHED,
        ]);

        $other = User::factory()->create(['status' => 'active']);
        $other->assignRole('member');
        ProductReview::query()->create([
            'user_id' => $other->id,
            'product_id' => $this->product->id,
            'rating' => 1,
            'body' => 'Hidden review body',
            'status' => ProductReview::STATUS_HIDDEN,
        ]);

        $this->actingAs($this->member)
            ->get(route('member.products.show', $this->product))
            ->assertOk()
            ->assertSee('Visible review body')
            ->assertDontSee('Hidden review body');
    }

    public function test_review_form_only_visible_to_eligible_buyer(): void
    {
        // No purchase → no form.
        $this->actingAs($this->member)
            ->get(route('member.products.show', $this->product))
            ->assertOk()
            ->assertDontSee(__('member.reviews.submit'));

        $this->makeCompletedOrder();

        $this->actingAs($this->member)
            ->get(route('member.products.show', $this->product))
            ->assertOk()
            ->assertSee(__('member.reviews.submit'));
    }
}
