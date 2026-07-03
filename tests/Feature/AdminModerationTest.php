<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MemberComplaint;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('member');

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');

        $this->member = User::factory()->create(['status' => 'active']);
        $this->member->assignRole('member');
    }

    private function makeComplaint(): MemberComplaint
    {
        return MemberComplaint::query()->create([
            'user_id' => $this->member->id,
            'subject' => 'Complaint subject here',
            'body' => 'Complaint body content',
            'status' => MemberComplaint::STATUS_PENDING,
        ]);
    }

    private function makeReview(): ProductReview
    {
        $category = Category::query()->create([
            'name' => 'Mod Cat',
            'slug' => 'mod-cat',
            'status' => Category::STATUS_ACTIVE,
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'name' => 'Moderated Product',
            'slug' => 'moderated-product',
            'category_id' => $category->id,
            'selling_price' => 10,
            'purchase_price' => 5,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        return ProductReview::query()->create([
            'user_id' => $this->member->id,
            'product_id' => $product->id,
            'rating' => 2,
            'body' => 'Moderated review body',
            'status' => ProductReview::STATUS_PUBLISHED,
        ]);
    }

    public function test_admin_can_view_and_resolve_complaints(): void
    {
        $complaint = $this->makeComplaint();

        $this->actingAs($this->admin)
            ->get(route('admin.complaints.index'))
            ->assertOk()
            ->assertSee('Complaint subject here');

        $this->actingAs($this->admin)
            ->post(route('admin.complaints.resolve', $complaint))
            ->assertRedirect();

        $this->assertDatabaseHas('member_complaints', [
            'id' => $complaint->id,
            'status' => MemberComplaint::STATUS_RESOLVED,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.complaints.destroy', $complaint))
            ->assertRedirect();

        $this->assertDatabaseMissing('member_complaints', ['id' => $complaint->id]);
    }

    public function test_member_cannot_access_admin_complaints(): void
    {
        $this->actingAs($this->member)
            ->get(route('admin.complaints.index'))
            ->assertForbidden();
    }

    public function test_admin_can_moderate_reviews(): void
    {
        $review = $this->makeReview();

        $this->actingAs($this->admin)
            ->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertSee('Moderated review body');

        $this->actingAs($this->admin)
            ->patch(route('admin.reviews.toggle-status', $review))
            ->assertRedirect();

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'status' => ProductReview::STATUS_HIDDEN,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.reviews.destroy', $review))
            ->assertRedirect();

        $this->assertDatabaseMissing('product_reviews', ['id' => $review->id]);
    }

    public function test_admin_api_complaints_and_reviews(): void
    {
        $complaint = $this->makeComplaint();
        $review = $this->makeReview();

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/admin/complaints')
            ->assertOk()
            ->assertJsonPath('data.0.subject', 'Complaint subject here');

        $this->patchJson("/api/admin/complaints/{$complaint->id}", ['status' => 'resolved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->getJson('/api/admin/reviews?status=published')
            ->assertOk()
            ->assertJsonPath('data.0.body', 'Moderated review body');

        $this->patchJson("/api/admin/reviews/{$review->id}", ['status' => 'hidden'])
            ->assertOk()
            ->assertJsonPath('data.status', 'hidden');

        $this->deleteJson("/api/admin/reviews/{$review->id}")->assertOk();
        $this->deleteJson("/api/admin/complaints/{$complaint->id}")->assertOk();

        $this->assertDatabaseMissing('product_reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('member_complaints', ['id' => $complaint->id]);
    }

    public function test_member_cannot_access_admin_moderation_api(): void
    {
        Sanctum::actingAs($this->member);

        $this->getJson('/api/admin/complaints')->assertForbidden();
        $this->getJson('/api/admin/reviews')->assertForbidden();
    }
}
