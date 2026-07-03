<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\Member\ProductBuyableQuery;
use App\Services\Member\ProductDetailService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductDetailService $productDetails) {}

    public function show(Product $product, Request $request): ProductDetailResource
    {
        abort_unless($this->canViewProduct($product, $request->user()), 404);

        $resource = new ProductDetailResource($this->productDetails->resolve($product, 'https://sieummo.vn', $request->integer('shop_id')));

        return $resource->additional([
            'reviews_count' => ProductReview::query()->published()->where('product_id', $product->id)->count(),
            'reviews' => ProductReview::query()
                ->published()
                ->where('product_id', $product->id)
                ->with('user:id,name')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (ProductReview $review): array => [
                    'id' => $review->id,
                    'user_name' => $review->user?->name,
                    'rating' => $review->rating,
                    'body' => $review->body,
                    'created_at' => $review->created_at?->toIso8601String(),
                ]),
        ]);
    }

    private function canViewProduct(Product $product, User $user): bool
    {
        if ($product->status !== Product::STATUS_ACTIVE) {
            return false;
        }

        if (ProductBuyableQuery::isBuyable($product)) {
            return true;
        }

        return $user->isShop()
            && ProductBuyableQuery::forShop($user->id)->whereKey($product->id)->exists();
    }
}
