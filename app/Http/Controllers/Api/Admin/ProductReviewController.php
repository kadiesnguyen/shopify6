<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reviews = ProductReview::query()
            ->with(['user:id,email,name', 'product:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15);

        return response()->json($reviews);
    }

    public function update(Request $request, ProductReview $review): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([ProductReview::STATUS_PUBLISHED, ProductReview::STATUS_HIDDEN])],
        ]);

        $review->update($validated);

        return response()->json(['data' => $review->fresh()]);
    }

    public function destroy(ProductReview $review): JsonResponse
    {
        $review->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
