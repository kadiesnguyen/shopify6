<?php

namespace App\Support\Member;

use App\Models\Order;
use App\Models\Shop;
use App\Services\Member\OrderSettlementService;
use Illuminate\Support\Collection;

final class ShopOrderStatusBadges
{
    /** @var list<string> */
    public const ICON_STATUSES = [
        Order::STATUS_PENDING_PAYMENT,
        Order::STATUS_WAITING_SHIPMENT,
        Order::STATUS_SHIPPED,
        Order::STATUS_COMPLETED,
    ];

    public static function unseenCounts(Shop $shop, int $sellerUserId): Collection
    {
        $counts = collect();

        foreach (self::ICON_STATUSES as $status) {
            $query = Order::query()
                ->where('seller_id', $sellerUserId)
                ->when(
                    $status === Order::STATUS_WAITING_SHIPMENT,
                    fn ($builder) => $builder->whereIn(
                        'status',
                        OrderSettlementService::SELLER_AWAITING_SHIPMENT_STATUSES,
                    ),
                    fn ($builder) => $builder->where('status', $status),
                );

            $lastSeenOrderId = self::lastSeenOrderId($shop, $status);

            if ($lastSeenOrderId !== null) {
                $query->where('id', '>', $lastSeenOrderId);
            }

            $counts[self::iconKey($status)] = $query->count();
        }

        return $counts;
    }

    public static function sellerStatusCounts(int $sellerUserId): Collection
    {
        $actual = Order::query()
            ->where('seller_id', $sellerUserId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect([
            'pending_payment' => (int) ($actual[Order::STATUS_PENDING_PAYMENT] ?? 0),
            'awaiting_pickup' => (int) ($actual[Order::STATUS_AWAITING_PICKUP] ?? 0)
                + (int) ($actual[Order::STATUS_WAITING_SHIPMENT] ?? 0),
            'shipped' => (int) ($actual[Order::STATUS_SHIPPED] ?? 0),
            'completed' => (int) ($actual[Order::STATUS_COMPLETED] ?? 0),
        ]);
    }

    public static function markSeen(Shop $shop, string $status, int $sellerUserId): void
    {
        $iconStatus = self::resolveIconStatus($status);

        if ($iconStatus === null) {
            return;
        }

        $seen = $shop->order_status_seen_at ?? [];

        $statusesToMark = $iconStatus === Order::STATUS_WAITING_SHIPMENT
            ? OrderSettlementService::SELLER_AWAITING_SHIPMENT_STATUSES
            : [$iconStatus];

        foreach ($statusesToMark as $s) {
            $lastOrderId = Order::query()
                ->where('seller_id', $sellerUserId)
                ->where('status', $s)
                ->max('id');

            $seen[$s] = ['last_order_id' => (int) ($lastOrderId ?? 0)];
        }

        $shop->forceFill(['order_status_seen_at' => $seen])->save();
    }

    public static function lastSeenOrderId(Shop $shop, string $status): ?int
    {
        $marker = ($shop->order_status_seen_at ?? [])[$status] ?? null;

        if (! is_array($marker) || ! array_key_exists('last_order_id', $marker)) {
            return null;
        }

        return (int) $marker['last_order_id'];
    }

    private static function iconKey(string $status): string
    {
        return match ($status) {
            Order::STATUS_PENDING_PAYMENT => 'pending_payment',
            Order::STATUS_WAITING_SHIPMENT => 'awaiting_pickup',
            Order::STATUS_SHIPPED => 'shipped',
            Order::STATUS_COMPLETED => 'completed',
            default => $status,
        };
    }

    private static function resolveIconStatus(string $status): ?string
    {
        if (in_array($status, self::ICON_STATUSES, true)) {
            return $status;
        }

        return match ($status) {
            'pending_payment' => Order::STATUS_PENDING_PAYMENT,
            'awaiting_pickup', Order::STATUS_AWAITING_PICKUP, Order::STATUS_WAITING_SHIPMENT, 'waiting_shipment' => Order::STATUS_WAITING_SHIPMENT,
            'shipped' => Order::STATUS_SHIPPED,
            'completed' => Order::STATUS_COMPLETED,
            default => null,
        };
    }
}
