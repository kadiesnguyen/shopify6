<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Member\OrderSettlementService;
use Illuminate\Console\Command;

class AutoCompleteShippedOrdersCommand extends Command
{
    protected $signature = 'orders:auto-complete-shipped';

    protected $description = 'Move shipped orders to completed after the configured delivery window';

    public function handle(OrderSettlementService $settlement): int
    {
        $hours = max(1, (int) config('portal.order_auto_complete_hours', 1));
        $cutoff = now()->subHours($hours);

        $completed = 0;

        Order::query()
            ->where('status', Order::STATUS_SHIPPED)
            ->whereNotNull('shipped_at')
            ->where('shipped_at', '<=', $cutoff)
            ->orderBy('id')
            ->eachById(function (Order $order) use ($settlement, &$completed): void {
                $settlement->applyStatusChange(
                    $order,
                    Order::STATUS_SHIPPED,
                    Order::STATUS_COMPLETED,
                );
                $completed++;
            });

        $this->info("Auto-completed {$completed} shipped order(s) (after {$hours} hour(s)).");

        return self::SUCCESS;
    }
}
