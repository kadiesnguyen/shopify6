<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(): View
    {
        $reviews = ProductReview::query()
            ->where('user_id', auth()->id())
            ->with('product')
            ->latest()
            ->paginate(15);

        return view('member.reviews.index', compact('reviews'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $productId = (int) $validated['product_id'];

        $alreadyReviewed = ProductReview::query()
            ->where('user_id', auth()->id())
            ->where('product_id', $productId)
            ->exists();

        if ($alreadyReviewed) {
            return back()->withErrors(['product_id' => __('member.reviews.already_reviewed')]);
        }

        $orderId = ProductReview::qualifyingOrderId(auth()->id(), $productId);

        if ($orderId === null) {
            return back()->withErrors(['product_id' => __('member.reviews.purchase_required')]);
        }

        ProductReview::query()->create([
            'user_id' => auth()->id(),
            'product_id' => $productId,
            'order_id' => $orderId,
            'rating' => $validated['rating'],
            'body' => $validated['body'] ?? null,
            'status' => ProductReview::STATUS_PUBLISHED,
        ]);

        return back()->with('status', __('member.reviews.submitted'));
    }
}
