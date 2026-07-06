<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Shop;
use App\Services\Member\PortalProductDisplayService;
use App\Services\Member\ProductBuyableQuery;
use App\Support\Cache\CachedModelCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    private const HOME_PRODUCT_LIMIT = 12;

    private const BANNER_CACHE_SECONDS = 300;

    public function __construct(private readonly PortalProductDisplayService $portalProductDisplay) {}

    public function index(Request $request): View|JsonResponse
    {
        $keyword = trim($request->string('q')->toString());
        $shopKeyword = trim($request->string('shop')->toString());
        $shopId = $request->integer('shop_id');
        $shopFilter = $this->resolveShopFilter($shopId, $shopKeyword);
        $shopUserIds = $shopFilter['user_ids'];
        $selectedShop = $shopFilter['selected_shop'];
        $hasSearchFilters = $keyword !== '' || $shopKeyword !== '' || $shopId > 0;
        $hasExplicitShopFilter = $shopId > 0 || ($shopKeyword !== '' && $shopUserIds !== []);

        $shopUserIdsFromKeyword = [];
        if ($keyword !== '' && ! $hasExplicitShopFilter) {
            $keywordShopFilter = $this->resolveShopFilter(0, $keyword);
            $shopUserIdsFromKeyword = $keywordShopFilter['user_ids'];
            if ($selectedShop === null) {
                $selectedShop = $keywordShopFilter['selected_shop'];
            }
        }

        $isCombinedHomeSearch = $keyword !== '' && ! $hasExplicitShopFilter && $shopUserIdsFromKeyword !== [];

        if (! $hasSearchFilters) {
            $products = ProductBuyableQuery::paginatePortalProducts(self::HOME_PRODUCT_LIMIT)->withQueryString();
            $this->portalProductDisplay->applyShopLabels($products->getCollection());
        } else {
            $productQuery = ProductBuyableQuery::forPortal();

            if ($isCombinedHomeSearch) {
                $productQuery->where(function ($query) use ($keyword, $shopUserIdsFromKeyword): void {
                    $query
                        ->where('name', 'like', "%{$keyword}%")
                        ->orWhereHas('distributions', function ($distributionQuery) use ($shopUserIdsFromKeyword): void {
                            $distributionQuery
                                ->available()
                                ->whereIn('user_id', $shopUserIdsFromKeyword);
                        });
                });
            } else {
                $productQuery
                    ->when($keyword !== '' && ! $hasExplicitShopFilter, fn ($query) => $query->where('name', 'like', "%{$keyword}%"))
                    ->when($shopUserIds !== [], function ($query) use ($shopUserIds): void {
                        $query->whereHas('distributions', function ($distributionQuery) use ($shopUserIds): void {
                            $distributionQuery
                                ->available()
                                ->whereIn('user_id', $shopUserIds);
                        });
                    })
                    ->when($shopKeyword !== '' && $shopUserIds === [], function ($query): void {
                        $query->whereRaw('1 = 0');
                    });
            }

            $orderShopUserIds = $shopUserIds !== [] ? $shopUserIds : $shopUserIdsFromKeyword;
            $labelShopUserIds = $shopUserIds !== [] ? $shopUserIds : $shopUserIdsFromKeyword;

            $products = ProductBuyableQuery::orderByLatestDistribution($productQuery, $orderShopUserIds)
                ->paginate(self::HOME_PRODUCT_LIMIT)
                ->withQueryString();

            $this->portalProductDisplay->applyShopLabels($products->getCollection(), $labelShopUserIds, $selectedShop);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('member.home.partials.product-cards', [
                    'products' => $products,
                    'imageOffset' => ($products->currentPage() - 1) * $products->perPage(),
                ])->render(),
                'has_more' => $products->hasMorePages(),
                'next_page' => $products->currentPage() + 1,
            ]);
        }

        $banners = CachedModelCollection::remember(
            'member.home.banners',
            self::BANNER_CACHE_SECONDS,
            Banner::class,
            fn () => Banner::query()
                ->where('status', Banner::STATUS_ACTIVE)
                ->orderBy('sort_order')
                ->limit(4)
                ->get(),
        );

        return view('member.home', compact('products', 'banners'));
    }

    private function resolveShopFilter(int $shopId, string $shopKeyword): array
    {
        if ($shopId > 0) {
            $shop = Shop::query()
                ->with('user:id,avatar')
                ->whereKey($shopId)
                ->where('status', Shop::STATUS_ACTIVE)
                ->first(['id', 'user_id', 'name', 'logo']);

            return [
                'user_ids' => $shop ? [(int) $shop->user_id] : [],
                'selected_shop' => $shop,
            ];
        }

        if ($shopKeyword === '') {
            return ['user_ids' => [], 'selected_shop' => null];
        }

        $numericKeyword = preg_replace('/\D+/', '', $shopKeyword);
        $shops = Shop::query()
            ->with('user:id,user_code,avatar')
            ->where('status', Shop::STATUS_ACTIVE)
            ->where(function ($query) use ($shopKeyword, $numericKeyword): void {
                $query
                    ->where('name', 'like', "%{$shopKeyword}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('user_code', 'like', "%{$shopKeyword}%"));

                if ($numericKeyword !== '') {
                    $query->orWhere('id', (int) $numericKeyword);
                }
            })
            ->limit(20)
            ->get(['id', 'user_id', 'name', 'logo']);

        return [
            'user_ids' => $shops->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all(),
            'selected_shop' => $shops->count() === 1 ? $shops->first() : null,
        ];
    }
}
