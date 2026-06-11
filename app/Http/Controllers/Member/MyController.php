<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Member\ShopDashboardService;
use Illuminate\View\View;

class MyController extends Controller
{
    public function __construct(private readonly ShopDashboardService $shopDashboard) {}

    public function index(): View
    {
        $user = auth()->user()->load(['wallet', 'shop']);
        $isSeller = $user->isShop();

        $baseOrders = Order::query()
            ->when($isSeller, fn ($query) => $query->where('seller_id', $user->id), fn ($query) => $query->where('user_id', $user->id));

        $statusCounts = (clone $baseOrders)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        if ($user->shop) {
            $shop = $user->shop;
            $statusCounts = $statusCounts->map(
                fn (int $count, string $status): int => $shop->resolveDisplayCount($status, $count),
            );
        }

        $pendingPaymentTotal = (float) (clone $baseOrders)
            ->where('status', Order::STATUS_PENDING_PAYMENT)
            ->sum($isSeller ? 'purchase_cost' : 'total');

        $completedOrders = (clone $baseOrders)
            ->where('status', Order::STATUS_COMPLETED);

        $completedProfit = (float) (clone $completedOrders)->sum('commission');
        $profit = $isSeller
            ? (float) (clone $baseOrders)->where('status', '!=', Order::STATUS_CANCELLED)->sum('commission')
            : $completedProfit;
        $totalIncome = $completedProfit + (float) (clone $completedOrders)->sum($isSeller ? 'purchase_cost' : 'total');

        $walletBalance = (float) ($user->wallet?->balance ?? 0);
        $shopStats = null;

        if ($user->shop) {
            $shopStats = $this->shopDashboard->statsFor($user);
            $walletBalance = $user->shop->resolveDisplayAmount($walletBalance, 'display_balance');
        }

        return view('member.my.index', compact(
            'user',
            'statusCounts',
            'pendingPaymentTotal',
            'totalIncome',
            'walletBalance',
            'profit',
            'shopStats',
        ));
    }
}
