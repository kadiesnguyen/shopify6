<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(): JsonResponse
    {
        $reviews = ProductReview::query()
            ->where('user_id', auth()->id())
            ->with('product:id,name')
            ->latest()
            ->paginate(15);

        return response()->json($reviews);
    }

    public function store(Request $request): JsonResponse
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
            return response()->json(['message' => __('member.reviews.already_reviewed')], 422);
        }

        $orderId = ProductReview::qualifyingOrderId(auth()->id(), $productId);

        if ($orderId === null) {
            return response()->json(['message' => __('member.reviews.purchase_required')], 422);
        }

        $review = ProductReview::query()->create([
            'user_id' => auth()->id(),
            'product_id' => $productId,
            'order_id' => $orderId,
            'rating' => $validated['rating'],
            'body' => $validated['body'] ?? null,
            'status' => ProductReview::STATUS_PUBLISHED,
        ]);

        return response()->json(['data' => $review], 201);
    }
}
