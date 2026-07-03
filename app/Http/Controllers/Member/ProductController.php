<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\ProductReview;
use App\Models\Shop;
use App\Models\User;
use App\Services\Member\PortalProductDisplayService;
use App\Services\Member\ProductBuyableQuery;
use App\Services\Member\ProductDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductDetailService $productDetails,
        private readonly PortalProductDisplayService $portalProductDisplay,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $keyword = trim($request->string('q')->toString());
        $shopKeyword = trim($request->string('shop')->toString());
        $shopId = $request->integer('shop_id');
        $shopFilter = $this->resolveShopFilter($shopId, $shopKeyword);
        $shopUserIds = $shopFilter['user_ids'];
        $selectedShop = $shopFilter['selected_shop'];
        $hasSearchFilters = $keyword !== '' || $shopKeyword !== '' || $shopId > 0;

        if (! $hasSearchFilters) {
            $products = ProductBuyableQuery::paginateFeaturedPortalProducts(12)->withQueryString();
            $this->portalProductDisplay->applyShopLabels($products->getCollection(), featuredOnly: true);
        } else {
            $productQuery = ProductBuyableQuery::forPortal()
                ->when($keyword !== '', fn ($query) => $query->where('name', 'like', "%{$keyword}%"))
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

            $products = ProductBuyableQuery::orderByLatestDistribution($productQuery, $shopUserIds)
                ->paginate(12)
                ->withQueryString();

            $this->portalProductDisplay->applyShopLabels($products->getCollection(), $shopUserIds, $selectedShop);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('member.products.partials.portal-product-items', [
                    'products' => $products,
                ])->render(),
                'has_more' => $products->hasMorePages(),
                'next_page' => $products->currentPage() + 1,
            ]);
        }

        return view('member.products.index', compact('products'));
    }

    public function show(Product $product, Request $request): View
    {
        abort_unless($this->canViewProduct($product, $request->user()), 404);

        $detail = $this->productDetails->resolve($product, 'https://sieummo.vn', $request->integer('shop_id'));
        $product = $detail['product'];

        $from = $request->string('from')->toString();
        $backUrl = match ($from) {
            'manage' => route('member.products.manage.index'),
            'distribution' => route('member.products.distributions.index'),
            'home' => route('member.home'),
            default => route('member.products.index'),
        };

        $user = $request->user();
        $isShopView = in_array($from, ['manage', 'distribution'], true);
        $isDistributed = $isShopView && ProductDistribution::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->exists();

        $reviews = ProductReview::query()
            ->published()
            ->where('product_id', $product->id)
            ->with('user:id,name')
            ->latest()
            ->limit(10)
            ->get();
        $reviewsCount = ProductReview::query()->published()->where('product_id', $product->id)->count();
        $canReview = ! $isShopView
            && ProductReview::qualifyingOrderId($user->id, $product->id) !== null
            && ! ProductReview::query()->where('user_id', $user->id)->where('product_id', $product->id)->exists();

        return view('member.products.show', compact(
            'product',
            'detail',
            'backUrl',
            'isShopView',
            'isDistributed',
            'reviews',
            'reviewsCount',
            'canReview',
        ));
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
