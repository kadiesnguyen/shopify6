<?php

namespace App\Support\Member;

use App\Models\Order;
use App\Models\Shop;
use Illuminate\Support\Collection;

final class ShopOrderStatusBadges
{
    /** @var list<string> */
    public const ICON_STATUSES = [
        Order::STATUS_PENDING_PAYMENT,
        Order::STATUS_AWAITING_PICKUP,
        Order::STATUS_SHIPPED,
        Order::STATUS_RECEIVED,
        Order::STATUS_COMPLETED,
    ];

    public static function unseenCounts(Shop $shop, int $sellerUserId): Collection
    {
        $counts = collect();

        foreach (self::ICON_STATUSES as $status) {
            $query = Order::query()
                ->where('seller_id', $sellerUserId)
                ->where('status', $status);

            $lastSeenOrderId = self::lastSeenOrderId($shop, $status);

            if ($lastSeenOrderId !== null) {
                $query->where('id', '>', $lastSeenOrderId);
            }

            $counts[$status] = $query->count();
        }

        return $counts;
    }

    public static function markSeen(Shop $shop, string $status, int $sellerUserId): void
    {
        if (! in_array($status, self::ICON_STATUSES, true)) {
            return;
        }

        $lastOrderId = Order::query()
            ->where('seller_id', $sellerUserId)
            ->where('status', $status)
            ->max('id');

        $seen = $shop->order_status_seen_at ?? [];
        $seen[$status] = ['last_order_id' => (int) ($lastOrderId ?? 0)];

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
