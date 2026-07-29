<?php

namespace App\Services\Member;

use App\Models\Order;
use App\Models\ProductReview;
use App\Models\User;

class ShopDashboardService
{
    /** @return array<string, mixed> */
    public function statsFor(User $user): array
    {
        $user->loadMissing(['shop', 'wallet']);
        $shop = $user->shop;

        $completedOrders = Order::query()
            ->where('seller_id', $user->id)
            ->where('status', Order::STATUS_COMPLETED);

        $activeOrders = Order::query()
            ->where('seller_id', $user->id)
            ->where('status', '!=', Order::STATUS_CANCELLED);

        $today = now()->startOfDay();

        $totalOrders = (clone $activeOrders)->count();
        $totalSales = (float) (clone $completedOrders)->sum('total');
        $availableBalance = (float) ($user->wallet?->spendableBalance() ?? 0);
        $completedOrderCount = (clone $completedOrders)->count();
        $failedOrderCount = Order::query()
            ->where('seller_id', $user->id)
            ->where('status', Order::STATUS_CANCELLED)
            ->count();
        $orderReviewCount = $shop
            ? ProductReview::query()
                ->published()
                ->whereHas('product', fn ($product) => $product->where('shop_id', $shop->id))
                ->count()
            : 0;
        $totalProfit = (float) (clone $completedOrders)->sum('commission');
        $ordersToday = (clone $activeOrders)->where('created_at', '>=', $today)->count();
        $salesToday = (float) (clone $completedOrders)->where('created_at', '>=', $today)->sum('total');
        $profitToday = (float) (clone $completedOrders)->where('created_at', '>=', $today)->sum('commission');

        $chart = $this->salesChart($user->id);
        $monthlyChart = $this->monthlyOrderChart($user->id);

        if ($shop) {
            $totalOrders = $shop->resolveDisplayInt($totalOrders, 'display_total_orders');
            $availableBalance = $shop->resolveDisplayAmount($availableBalance, 'display_balance');
            $totalSales = $shop->resolveDisplayAmount($totalSales, 'display_total_sales');
            $totalProfit = $shop->resolveDisplayAmount($totalProfit, 'display_total_profit');
            $ordersToday = $shop->resolveDisplayInt($ordersToday, 'display_orders_today');
            $salesToday = $shop->resolveDisplayAmount($salesToday, 'display_sales_today');
            $profitToday = $shop->resolveDisplayAmount($profitToday, 'display_profit_today');
        }

        return [
            'total_orders' => $totalOrders,
            'available_balance' => $availableBalance,
            'completed_orders' => $completedOrderCount,
            'failed_orders' => $failedOrderCount,
            'order_reviews' => $orderReviewCount,
            'total_sales' => $totalSales,
            'total_profit' => $totalProfit,
            'orders_today' => $ordersToday,
            'sales_today' => $salesToday,
            'profit_today' => $profitToday,
            'visitors_today' => $shop?->resolveDisplayInt(0, 'display_visitors_today') ?? 0,
            'visitors_7d' => $shop?->resolveDisplayInt(0, 'display_visitors_7d') ?? 0,
            'visitors_30d' => $shop?->resolveDisplayInt(0, 'display_visitors_30d') ?? 0,
            'followers' => $shop?->followers ?? 0,
            'credit_score' => $shop?->credit_score ?? 0,
            'star_rating' => (float) ($shop?->star_rating ?? 0),
            'chart_labels' => $chart['labels'],
            'chart_sales' => $chart['sales'],
            'monthly_chart_labels' => $monthlyChart['labels'],
            'monthly_chart_orders' => $monthlyChart['orders'],
        ];
    }

    /** @return array{labels: list<string>, orders: list<int>} */
    private function monthlyOrderChart(int $sellerId): array
    {
        $start = now()->startOfMonth();
        $days = now()->day;
        $labels = [];
        $orders = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $labels[] = $day->format('m-d');
            $orders[] = Order::query()
                ->where('seller_id', $sellerId)
                ->where('status', '!=', Order::STATUS_CANCELLED)
                ->whereDate('created_at', $day)
                ->count();
        }

        return compact('labels', 'orders');
    }

    /** @return array{labels: list<string>, sales: list<float>} */
    private function salesChart(int $sellerId): array
    {
        $start = now()->subDays(9)->startOfDay();
        $labels = [];
        $sales = [];

        for ($i = 0; $i < 10; $i++) {
            $day = $start->copy()->addDays($i);
            $labels[] = $day->format('Y-m-d');
            $sales[] = (float) Order::query()
                ->where('seller_id', $sellerId)
                ->where('status', Order::STATUS_COMPLETED)
                ->whereDate('created_at', $day)
                ->sum('total');
        }

        return compact('labels', 'sales');
    }
}
