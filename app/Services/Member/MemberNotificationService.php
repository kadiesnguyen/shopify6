<?php

namespace App\Services\Member;

use App\Models\Notification;
use App\Models\Order;

class MemberNotificationService
{
    public const TYPE_ORDER_PENDING_PAYMENT = 'order_pending_payment';

    public const TYPE_ORDER_COMPLETED = 'order_completed';

    public function notifyOrderNeedsPayment(Order $order): void
    {
        if (! $order->seller_id) {
            return;
        }

        $reference = 'order-needs-payment-'.$order->id;

        if ($this->referenceExists($order->seller_id, $reference)) {
            return;
        }

        Notification::query()->create([
            'user_id' => $order->seller_id,
            'title' => __('member.notifications.order_pending_payment_title'),
            'body' => __('member.notifications.order_pending_payment_body', [
                'order_no' => $order->order_no,
                'amount' => number_format((float) $order->purchase_cost, 2),
            ]),
            'type' => self::TYPE_ORDER_PENDING_PAYMENT,
            'data' => [
                'reference' => $reference,
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'url' => route('member.seller.orders.index', ['status' => Order::STATUS_PENDING_PAYMENT]),
            ],
        ]);
    }

    public function notifyOrderCompleted(Order $order): void
    {
        if (! $order->seller_id) {
            return;
        }

        $reference = 'order-completed-'.$order->id;

        if ($this->referenceExists($order->seller_id, $reference)) {
            return;
        }

        $credited = (float) $order->purchase_cost + (float) $order->commission;

        Notification::query()->create([
            'user_id' => $order->seller_id,
            'title' => __('member.notifications.order_completed_title'),
            'body' => __('member.notifications.order_completed_body', [
                'order_no' => $order->order_no,
                'amount' => number_format($credited, 2),
            ]),
            'type' => self::TYPE_ORDER_COMPLETED,
            'data' => [
                'reference' => $reference,
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'url' => route('member.wallet.withdrawal'),
            ],
        ]);
    }

    private function referenceExists(int $userId, string $reference): bool
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('data->reference', $reference)
            ->exists();
    }
}
