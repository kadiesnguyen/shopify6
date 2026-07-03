<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Services\Member\PortalProductDisplayService;
use App\Services\Member\ProductBuyableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private readonly PortalProductDisplayService $portalProductDisplay) {}

    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->where('status', Category::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json(['data' => $categories]);
    }

    public function products(Category $category, Request $request): JsonResponse
    {
        $products = ProductBuyableQuery::forPortal()
            ->where('category_id', $category->id)
            ->limit($request->integer('limit', 24))
            ->get();

        $this->portalProductDisplay->applyShopLabels($products, featuredOnly: true);

        return response()->json(['data' => ProductResource::collection($products)]);
    }
}
