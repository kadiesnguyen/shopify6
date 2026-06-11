<?php

namespace App\Services\Member;

use App\Models\Order;
use App\Models\User;

class ShopDashboardService
{
    /** @return array<string, mixed> */
    public function statsFor(User $user): array
    {
        $user->loadMissing('shop');
        $shop = $user->shop;

        $completedOrders = Order::query()
            ->where('seller_id', $user->id)
            ->where('status', Order::STATUS_COMPLETED);

        $activeOrders = Order::query()
            ->where('seller_id', $user->id)
            ->where('status', '!=', Order::STATUS_CANCELLED);

        $today = now()->startOfDay();

        $totalSales = (float) (clone $completedOrders)->sum('total');
        $totalProfit = (float) (clone $completedOrders)->sum('commission');
        $ordersToday = (clone $activeOrders)->where('created_at', '>=', $today)->count();
        $salesToday = (float) (clone $completedOrders)->where('created_at', '>=', $today)->sum('total');
        $profitToday = (float) (clone $completedOrders)->where('created_at', '>=', $today)->sum('commission');

        $chart = $this->salesChart($user->id);

        if ($shop) {
            $totalSales = $shop->resolveDisplayAmount($totalSales, 'display_total_sales');
            $totalProfit = $shop->resolveDisplayAmount($totalProfit, 'display_total_profit');
            $ordersToday = $shop->resolveDisplayInt($ordersToday, 'display_orders_today');
            $salesToday = $shop->resolveDisplayAmount($salesToday, 'display_sales_today');
            $profitToday = $shop->resolveDisplayAmount($profitToday, 'display_profit_today');
        }

        return [
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
        ];
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
