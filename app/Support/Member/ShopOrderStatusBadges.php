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
        Order::STATUS_AWAITING_PICKUP,
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
                    $status === Order::STATUS_AWAITING_PICKUP,
                    fn ($builder) => $builder->whereIn(
                        'status',
                        OrderSettlementService::SELLER_SHIP_CONFIRM_STATUSES,
                    ),
                    fn ($builder) => $builder->where('status', $status),
                );

            $lastSeenOrderId = self::lastSeenOrderId($shop, $status);

            if ($lastSeenOrderId !== null) {
                $query->where('id', '>', $lastSeenOrderId);
            }

            $counts[$status] = $query->count();
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

        $counts = $actual->except([
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_AWAITING_PICKUP,
        ]);

        $counts[Order::STATUS_AWAITING_PICKUP] =
            (int) ($actual[Order::STATUS_PENDING_PAYMENT] ?? 0)
            + (int) ($actual[Order::STATUS_AWAITING_PICKUP] ?? 0);

        return $counts->map(fn ($count) => (int) $count);
    }

    public static function markSeen(Shop $shop, string $status, int $sellerUserId): void
    {
        if (! in_array($status, self::ICON_STATUSES, true)) {
            return;
        }

        $seen = $shop->order_status_seen_at ?? [];

        // The awaiting_pickup badge in unseenCounts combines pending_payment + awaiting_pickup
        // (see OrderSettlementService::SELLER_SHIP_CONFIRM_STATUSES). Marking one status seen
        // without the other leaves a stale badge after the seller opens the tab.
        $statusesToMark = $status === Order::STATUS_AWAITING_PICKUP
            ? [Order::STATUS_PENDING_PAYMENT, Order::STATUS_AWAITING_PICKUP]
            : [$status];

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
}
