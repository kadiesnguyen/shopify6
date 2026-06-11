<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Models\Product;
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

        return new ProductDetailResource($this->productDetails->resolve($product, 'https://sieummo.vn', $request->integer('shop_id')));
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
