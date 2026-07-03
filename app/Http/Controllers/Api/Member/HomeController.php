<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Banner;
use App\Services\Member\CartService;
use App\Services\Member\PortalProductDisplayService;
use App\Services\Member\ProductBuyableQuery;
use App\Support\Cache\CachedModelCollection;
use App\Support\Member\BellNotificationCache;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    private const BANNER_CACHE_SECONDS = 300;

    public function __construct(
        private readonly PortalProductDisplayService $portalProductDisplay,
        private readonly CartService $cart,
    ) {}

    public function index(): JsonResponse
    {
        $user = auth()->user();
        $products = ProductBuyableQuery::portalFeaturedProducts(12);
        $this->portalProductDisplay->applyShopLabels($products, featuredOnly: true);

        $banners = CachedModelCollection::remember(
            'api.member.home.banners',
            self::BANNER_CACHE_SECONDS,
            Banner::class,
            fn () => Banner::query()
                ->where('status', Banner::STATUS_ACTIVE)
                ->orderBy('sort_order')
                ->limit(4)
                ->get(),
        );

        return response()->json([
            'banners' => $banners->map(fn (Banner $banner) => [
                'image' => $banner->image,
                'link_url' => $banner->link_url,
            ]),
            'quick_actions' => config('portal.quick_actions', []),
            'products' => ProductResource::collection($products),
            'cart_count' => $this->cart->countFor($user),
            'unread_notifications' => BellNotificationCache::unreadCount($user->id),
        ]);
    }
}
