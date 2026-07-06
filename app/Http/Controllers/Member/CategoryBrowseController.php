<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Services\Member\PortalProductDisplayService;
use App\Services\Member\ProductBuyableQuery;
use App\Support\Cache\CachedModelCollection;
use App\Support\ShopIndustryRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryBrowseController extends Controller
{
    private const BANNER_CACHE_SECONDS = 300;

    public function __construct(
        private readonly PortalProductDisplayService $portalProductDisplay,
        private readonly ShopIndustryRegistry $industries,
    ) {}

    public function index(Request $request): View
    {
        $distributeMode = $request->string('mode')->toString() === 'distribute';

        if ($distributeMode) {
            abort_unless(auth()->user()->isShop(), 403);
            abort_unless(auth()->user()->canSelfDistribute(), 403);
        }

        $shop = auth()->user()->shop;
        $allowedCategoryIds = $shop && $distributeMode
            ? $this->industries->allowedCategoryIds($shop->industry_id, $shop->business_category_ids)
            : null;

        $categoriesQuery = Category::query()
            ->where('status', Category::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($allowedCategoryIds !== null) {
            $categoriesQuery->whereIn('id', $allowedCategoryIds);
        }

        $categories = $categoriesQuery->get();

        $activeCategory = $categories->firstWhere('id', $request->integer('category'))
            ?? $categories->first();

        $products = collect();
        $distributedIds = collect();

        if ($activeCategory) {
            if ($distributeMode) {
                $distributedIds = ProductDistribution::query()
                    ->where('user_id', auth()->id())
                    ->pluck('product_id');

                $products = Product::query()
                    ->with('category')
                    ->where('status', Product::STATUS_ACTIVE)
                    ->where('category_id', $activeCategory->id)
                    ->when($shop, function ($query) use ($shop): void {
                        $query->whereIn('category_id', $this->industries->allowedCategoryIds(
                            $shop->industry_id,
                            $shop->business_category_ids,
                        ));
                    })
                    ->latest()
                    ->limit(48)
                    ->get();
            } else {
                $products = ProductBuyableQuery::forPortal()
                    ->where('category_id', $activeCategory->id)
                    ->limit(24)
                    ->get();

                $this->portalProductDisplay->applyShopLabels($products, featuredOnly: true);
            }
        }

        $banners = CachedModelCollection::remember(
            'member.category.banners',
            self::BANNER_CACHE_SECONDS,
            Banner::class,
            fn () => Banner::query()
                ->where('status', Banner::STATUS_ACTIVE)
                ->orderBy('sort_order')
                ->limit(3)
                ->get(),
        );

        return view('member.categories.index', compact(
            'categories',
            'activeCategory',
            'products',
            'banners',
            'distributeMode',
            'distributedIds',
        ));
    }
}
