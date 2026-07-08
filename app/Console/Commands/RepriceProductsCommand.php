<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Services\Member\ProductDistributionService;
use Illuminate\Console\Command;

class RepriceProductsCommand extends Command
{
    protected $signature = 'products:reprice {--dry-run : Report changes without saving}';

    protected $description = 'Recompute cost price (giá gốc) from the tiered profit rate for products, distributions and open orders';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $products = 0;
        Product::query()->chunkById(200, function ($chunk) use (&$products, $dryRun): void {
            foreach ($chunk as $product) {
                $price = (float) $product->selling_price;
                $cost = ProductDistributionService::costPriceForPrice($price);
                $commission = round($price - $cost, 2);

                if (! $dryRun) {
                    $product->forceFill([
                        'purchase_price' => $cost,
                        'commission' => $commission,
                    ])->save();
                }

                $products++;
            }
        });

        $distributions = 0;
        ProductDistribution::query()->chunkById(200, function ($chunk) use (&$distributions, $dryRun): void {
            foreach ($chunk as $distribution) {
                $selling = (float) $distribution->selling_price;
                $cost = ProductDistributionService::costPriceForPrice($selling);

                if (! $dryRun) {
                    $distribution->forceFill([
                        'purchase_price' => $cost,
                        'commission' => max(0, round($selling - $cost, 2)),
                    ])->save();
                }

                $distributions++;
            }
        });

        // Orders still awaiting the shop's cost payment (confirm not pressed yet):
        // realign their cost/profit split. Buyer total is left untouched.
        $orders = 0;
        Order::query()
            ->whereIn('status', [Order::STATUS_PENDING_PAYMENT, Order::STATUS_AWAITING_PICKUP])
            ->with('items')
            ->chunkById(200, function ($chunk) use (&$orders, $dryRun): void {
                foreach ($chunk as $order) {
                    $cost = 0.0;

                    foreach ($order->items as $item) {
                        $cost += ProductDistributionService::costPriceForPrice((float) $item->unit_price) * (int) $item->qty;
                    }

                    $cost = round($cost, 2);
                    $commission = round((float) $order->total - $cost, 2);

                    if (! $dryRun) {
                        $order->forceFill([
                            'purchase_cost' => $cost,
                            'commission' => max(0, $commission),
                        ])->save();
                    }

                    $orders++;
                }
            });

        $verb = $dryRun ? 'Would reprice' : 'Repriced';
        $this->info("{$verb}: products={$products}, distributions={$distributions}, open_orders={$orders}");

        return self::SUCCESS;
    }
}
