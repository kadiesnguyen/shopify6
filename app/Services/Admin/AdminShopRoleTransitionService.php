<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\User;

class AdminShopRoleTransitionService
{
    public function beforeRoleChange(User $user, string $newRole): void
    {
        if (! $user->isShop() || $newRole !== 'member') {
            return;
        }

        Order::query()
            ->where('seller_id', $user->id)
            ->where('status', Order::STATUS_PENDING_PAYMENT)
            ->orderBy('id')
            ->each(function (Order $order): void {
                $order->items()->delete();
                $order->delete();
            });
    }
}
