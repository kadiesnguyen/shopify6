<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\WalletResource;
use App\Models\Order;
use App\Services\Member\PortalProductDisplayService;
use App\Services\Member\ProductBuyableQuery;
use App\Services\Member\ShopDashboardService;
use App\Support\Member\BellNotificationCache;
use App\Support\Member\ShopOrderStatusBadges;
use Illuminate\Http\JsonResponse;

class MySummaryController extends Controller
{
    public function __construct(
        private readonly ShopDashboardService $shopDashboard,
        private readonly PortalProductDisplayService $portalProductDisplay,
    ) {}

    public function index(): JsonResponse
    {
        $user = auth()->user()->load(['wallet', 'shop']);
        $isSeller = $user->isShop();

        $baseOrders = Order::query()
            ->when($isSeller, fn ($query) => $query->where('seller_id', $user->id), fn ($query) => $query->where('user_id', $user->id));

        $statusCounts = $isSeller && $user->shop
            ? ShopOrderStatusBadges::unseenCounts($user->shop, $user->id)
            : (clone $baseOrders)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

        $pendingPaymentTotal = (float) (clone $baseOrders)
            ->where('status', Order::STATUS_PENDING_PAYMENT)
            ->sum($isSeller ? 'purchase_cost' : 'total');

        $completedOrders = (clone $baseOrders)->where('status', Order::STATUS_COMPLETED);
        $completedProfit = (float) (clone $completedOrders)->sum('commission');
        $profit = $isSeller
            ? (float) (clone $baseOrders)->where('status', '!=', Order::STATUS_CANCELLED)->sum('commission')
            : $completedProfit;
        $totalIncome = $completedProfit + (float) (clone $completedOrders)->sum($isSeller ? 'purchase_cost' : 'total');

        $walletBalance = (float) ($user->wallet?->balance ?? 0);
        $shopStats = null;

        if ($isSeller && $user->shop) {
            $shopStats = $this->shopDashboard->statsFor($user);
            $walletBalance = $user->shop->resolveDisplayAmount($walletBalance, 'display_balance');
        }

        $feedProducts = ProductBuyableQuery::portalFeaturedProducts(8);
        $this->portalProductDisplay->applyShopLabels($feedProducts, featuredOnly: true);

        return response()->json([
            'user' => [
                'name' => $user->isShop() ? ($user->shop?->name ?? $user->name) : $user->name,
                'avatar' => $user->isShop()
                    ? ($user->shop?->displayLogoUrl() ?: $user->avatarUrl())
                    : $user->avatarUrl(),
                'is_shop' => $isSeller,
                'merchant_badge' => $user->shop?->isBusiness() ? 'business' : ($isSeller ? 'personal' : null),
            ],
            'wallet' => new WalletResource($user->wallet ?? $user->wallet()->create([
                'balance' => 0,
                'balance_pending' => 0,
                'balance_frozen' => 0,
            ])),
            'pending_payment_total' => $pendingPaymentTotal,
            'profit' => $profit,
            'total_income' => $totalIncome,
            'wallet_balance' => $walletBalance,
            'order_status_counts' => $statusCounts,
            'shop_stats' => $shopStats,
            'unread_notifications' => BellNotificationCache::unreadCount($user->id),
            'feed_products' => $feedProducts->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'selling_price' => (float) $product->selling_price,
                'image' => $product->imageUrl(),
            ]),
        ]);
    }
}
