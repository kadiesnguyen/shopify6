<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShopApplication;
use App\Services\Member\PortalProductDisplayService;
use App\Services\Member\ProductBuyableQuery;
use App\Support\Member\ShopOrderStatusBadges;
use Illuminate\View\View;

class MyController extends Controller
{
    public function __construct(
        private readonly PortalProductDisplayService $portalProductDisplay,
    ) {}

    public function index(): View
    {
        $user = auth()->user()->load('shop');
        $isSeller = $user->isShop();

        $statusCounts = $isSeller && $user->shop
            ? ShopOrderStatusBadges::unseenCounts($user->shop, $user->id)
            : Order::query()
                ->where('user_id', $user->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

        $feedProducts = ProductBuyableQuery::portalFeaturedProducts(8);
        $this->portalProductDisplay->applyShopLabels($feedProducts, featuredOnly: true);

        $toastMessage = session('status');
        if (! $toastMessage && ! $isSeller) {
            $hasPendingApplication = ShopApplication::query()
                ->where('user_id', $user->id)
                ->where('status', ShopApplication::STATUS_PENDING)
                ->exists();

            if ($hasPendingApplication) {
                $toastMessage = __('member.shop_application.pending_toast');
            }
        }

        return view('member.my.index', compact('user', 'statusCounts', 'feedProducts', 'toastMessage'));
    }
}
