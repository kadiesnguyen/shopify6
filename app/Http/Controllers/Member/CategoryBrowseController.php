<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Services\Member\PortalProductDisplayService;
use App\Services\Member\ProductBuyableQuery;
use App\Support\Cache\CachedModelCollection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryBrowseController extends Controller
{
    private const BANNER_CACHE_SECONDS = 300;

    public function __construct(private readonly PortalProductDisplayService $portalProductDisplay) {}

    public function index(Request $request): View
    {
        $categories = Category::query()
            ->where('status', Category::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $activeCategory = $categories->firstWhere('id', $request->integer('category'))
            ?? $categories->first();

        $products = collect();

        if ($activeCategory) {
            $products = ProductBuyableQuery::forPortal()
                ->where('category_id', $activeCategory->id)
                ->limit(24)
                ->get();

            $this->portalProductDisplay->applyShopLabels($products, featuredOnly: true);
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

        return view('member.categories.index', compact('categories', 'activeCategory', 'products', 'banners'));
    }
}
