<?php

namespace App\Services\Member;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class FinancialReportService
{
    /** @var list<string> */
    private const WEEK_LABELS = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];

    /** @return array<string, mixed> */
    public function reportFor(User $user, string $period, string $date, ?string $from = null, ?string $to = null): array
    {
        $user->loadMissing('shop');
        $anchor = Carbon::parse($date)->startOfDay();
        [$rangeStart, $rangeEnd] = $this->resolveRange($period, $anchor, $from, $to);

        $pendingPayment = $this->pendingPaymentTotal($user);
        $totalProfit = $this->totalProfit($user);
        $totalIncome = $this->totalIncome($user);

        $periodOrders = $this->ordersQuery($user)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd]);

        $periodCompleted = (clone $periodOrders)->where('status', Order::STATUS_COMPLETED);

        $periodOrderCount = (clone $periodOrders)->count();
        $periodProfit = (float) (clone $periodCompleted)->sum('commission');

        $chart = $this->chartData($user, $period, $rangeStart, $rangeEnd);

        return [
            'pending_payment' => $pendingPayment,
            'total_profit' => $totalProfit,
            'total_income' => $totalIncome,
            'period_label' => $this->periodLabel($period, $rangeStart, $rangeEnd),
            'period_order_count' => $periodOrderCount,
            'period_profit' => $periodProfit,
            'chart_labels' => $chart['labels'],
            'chart_purchases' => $chart['purchases'],
            'chart_profits' => $chart['profits'],
            'summary_purchases' => array_sum($chart['purchases']),
            'summary_profit' => array_sum($chart['profits']),
            'summary_orders' => max(array_sum($chart['orders']), $periodOrderCount),
        ];
    }

    private function pendingPaymentTotal(User $user): float
    {
        $query = $this->ordersQuery($user)
            ->where('status', Order::STATUS_PENDING_PAYMENT);

        if ($user->isShop()) {
            return (float) $query->sum('purchase_cost');
        }

        return (float) $query->sum('total');
    }

    private function totalProfit(User $user): float
    {
        $query = $this->ordersQuery($user);

        if ($user->isShop()) {
            return (float) $query
                ->where('status', '!=', Order::STATUS_CANCELLED)
                ->sum('commission');
        }

        return (float) $query
            ->where('status', Order::STATUS_COMPLETED)
            ->sum('commission');
    }

    private function totalIncome(User $user): float
    {
        $completedOrders = $this->ordersQuery($user)
            ->where('status', Order::STATUS_COMPLETED);

        if ($user->isShop()) {
            return (float) (clone $completedOrders)->sum('purchase_cost')
                + (float) (clone $completedOrders)->sum('commission');
        }

        return (float) (clone $completedOrders)->sum('total');
    }

    /** @return Builder<Order> */
    private function ordersQuery(User $user): Builder
    {
        if ($user->isShop()) {
            return Order::query()->where('seller_id', $user->id);
        }

        return Order::query()->where('user_id', $user->id);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(string $period, Carbon $anchor, ?string $from, ?string $to): array
    {
        if ($period === 'custom' && $from && $to) {
            $start = Carbon::parse($from)->startOfDay();
            $end = Carbon::parse($to)->endOfDay();

            if ($start->gt($end)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            return [$start, $end];
        }

        return match ($period) {
            'week' => [
                $anchor->copy()->startOfWeek(Carbon::MONDAY),
                $anchor->copy()->endOfWeek(Carbon::SUNDAY),
            ],
            'month' => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
            'year' => [$anchor->copy()->startOfYear(), $anchor->copy()->endOfYear()],
            default => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
        };
    }

    private function periodLabel(string $period, Carbon $start, Carbon $end): string
    {
        return match ($period) {
            'custom' => __('member.financial_report.custom_label', [
                'start' => $start->format('d/m/Y'),
                'end' => $end->format('d/m/Y'),
            ]),
            'week' => __('member.financial_report.week_label', [
                'start' => $start->format('d/m/Y'),
                'end' => $end->format('d/m/Y'),
            ]),
            'month' => __('member.financial_report.month_label', [
                'month' => $start->format('m'),
                'year' => $start->format('Y'),
            ]),
            'year' => __('member.financial_report.year_label', ['year' => $start->format('Y')]),
            default => __('member.financial_report.day_label', ['date' => $start->format('Y-m-d')]),
        };
    }

    /**
     * @return array{
     *     labels: list<string>,
     *     purchases: list<float>,
     *     profits: list<float>,
     *     orders: list<int>
     * }
     */
    private function chartData(User $user, string $period, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $orders = $this->ordersQuery($user)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->get(['created_at', 'status', 'purchase_cost', 'total', 'commission']);

        $buckets = $this->chartBuckets($period, $rangeStart, $rangeEnd);
        $purchases = array_fill(0, count($buckets), 0.0);
        $profits = array_fill(0, count($buckets), 0.0);
        $orderCounts = array_fill(0, count($buckets), 0);

        foreach ($orders as $order) {
            $index = $this->bucketIndex($period, $rangeStart, $order->created_at, count($buckets));

            if ($index === null) {
                continue;
            }

            $orderCounts[$index]++;

            if ($order->status !== Order::STATUS_COMPLETED) {
                continue;
            }

            $purchases[$index] += $user->isShop()
                ? (float) $order->purchase_cost
                : (float) $order->total;
            $profits[$index] += (float) $order->commission;
        }

        return [
            'labels' => array_column($buckets, 'label'),
            'purchases' => $purchases,
            'profits' => $profits,
            'orders' => $orderCounts,
        ];
    }

    /**
     * @return list<array{label: string, start: Carbon, end: Carbon}>
     */
    private function chartBuckets(string $period, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        if ($period === 'day') {
            $buckets = [];

            foreach ([0, 4, 8, 12, 16, 20] as $hour) {
                $start = $rangeStart->copy()->addHours($hour);
                $buckets[] = [
                    'label' => sprintf('%02d:00', $hour),
                    'start' => $start,
                    'end' => $start->copy()->addHours(3)->endOfHour(),
                ];
            }

            return $buckets;
        }

        if ($period === 'week') {
            $buckets = [];
            $cursor = $rangeStart->copy()->startOfWeek(Carbon::MONDAY);

            for ($i = 0; $i < 7; $i++) {
                $buckets[] = [
                    'label' => self::WEEK_LABELS[$i],
                    'start' => $cursor->copy()->startOfDay(),
                    'end' => $cursor->copy()->endOfDay(),
                ];
                $cursor->addDay();
            }

            return $buckets;
        }

        if ($period === 'month') {
            $daysInMonth = $rangeStart->daysInMonth;
            $ranges = [[1, 7], [8, 14], [15, 21], [22, $daysInMonth]];
            $buckets = [];

            foreach ($ranges as $index => [$startDay, $endDay]) {
                $buckets[] = [
                    'label' => __('member.financial_report.month_week', ['week' => $index + 1]),
                    'start' => $rangeStart->copy()->day($startDay)->startOfDay(),
                    'end' => $rangeStart->copy()->day($endDay)->endOfDay(),
                ];
            }

            return $buckets;
        }

        if ($period === 'custom') {
            $buckets = [];
            $cursor = $rangeStart->copy();
            $guard = 0;

            while ($cursor->lte($rangeEnd) && $guard < 60) {
                $buckets[] = [
                    'label' => $cursor->format('n/j'),
                    'start' => $cursor->copy()->startOfDay(),
                    'end' => $cursor->copy()->endOfDay(),
                ];
                $cursor->addDay();
                $guard++;
            }

            if ($buckets === []) {
                $buckets[] = [
                    'label' => '—',
                    'start' => $rangeStart,
                    'end' => $rangeEnd,
                ];
            }

            return $buckets;
        }

        $buckets = [];

        for ($month = 1; $month <= 12; $month++) {
            $start = $rangeStart->copy()->month($month)->startOfMonth();
            $buckets[] = [
                'label' => 'T'.$month,
                'start' => $start,
                'end' => $start->copy()->endOfMonth(),
            ];
        }

        return $buckets;
    }

    private function bucketIndex(string $period, Carbon $rangeStart, Carbon $timestamp, int $bucketCount): ?int
    {
        if ($period === 'day') {
            return min((int) floor($timestamp->hour / 4), $bucketCount - 1);
        }

        if ($period === 'week' || $period === 'custom') {
            $index = (int) $rangeStart->copy()->startOfDay()->diffInDays($timestamp->copy()->startOfDay());

            return $index >= 0 && $index < $bucketCount ? $index : null;
        }

        if ($period === 'month') {
            return match (true) {
                $timestamp->day <= 7 => 0,
                $timestamp->day <= 14 => 1,
                $timestamp->day <= 21 => 2,
                default => 3,
            };
        }

        $index = $timestamp->month - 1;

        return $index >= 0 && $index < $bucketCount ? $index : null;
    }
}
