<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductReviewController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = ProductReview::query()
            ->with(['user:id,email,name', 'product:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function toggleStatus(ProductReview $review): RedirectResponse
    {
        $review->update([
            'status' => $review->status === ProductReview::STATUS_PUBLISHED
                ? ProductReview::STATUS_HIDDEN
                : ProductReview::STATUS_PUBLISHED,
        ]);

        return back()->with('status', __('admin.reviews.status_updated'));
    }

    public function destroy(ProductReview $review): RedirectResponse
    {
        $review->delete();

        return back()->with('status', __('admin.reviews.deleted'));
    }
}
