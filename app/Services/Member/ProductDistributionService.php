<?php

namespace App\Services\Member;

use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductDistributionService
{
    public function distribute(User $shopUser, Product $product): ProductDistribution
    {
        return DB::transaction(function () use ($shopUser, $product): ProductDistribution {
            $cost = (float) $product->purchase_price;

            $wallet = Wallet::query()
                ->where('user_id', $shopUser->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet || (float) $wallet->balance < $cost) {
                throw new RuntimeException('insufficient_balance');
            }

            $distribution = ProductDistribution::query()->create([
                'user_id' => $shopUser->id,
                'product_id' => $product->id,
                'selling_price' => $product->selling_price,
                'purchase_price' => $product->purchase_price,
                'commission' => $product->commission,
                'commission_type' => ProductDistribution::COMMISSION_FIXED,
                'status' => ProductDistribution::STATUS_AVAILABLE,
            ]);

            if ($cost > 0) {
                $wallet->decrement('balance', $cost);

                Transaction::query()->create([
                    'user_id' => $shopUser->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $cost,
                    'type' => Transaction::TYPE_DISTRIBUTION_COST,
                    'status' => Transaction::STATUS_COMPLETED,
                    'reference' => 'distribution-'.$distribution->id,
                    'description' => 'Product distribution cost '.$product->name,
                    'processed_at' => now(),
                ]);
            }

            return $distribution;
        });
    }

    public function resolveForOrder(Product $product): ?ProductDistribution
    {
        return ProductDistribution::query()
            ->available()
            ->where('product_id', $product->id)
            ->withCount(['orders as active_orders_count' => function ($query): void {
                $query->whereNotIn('status', [
                    \App\Models\Order::STATUS_COMPLETED,
                    \App\Models\Order::STATUS_CANCELLED,
                ]);
            }])
            ->orderBy('active_orders_count')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    public function reserve(ProductDistribution $distribution): void
    {
        if ($distribution->status !== ProductDistribution::STATUS_AVAILABLE) {
            throw new RuntimeException('distribution_unavailable');
        }

        $distribution->update(['status' => ProductDistribution::STATUS_RESERVED]);
    }

    public function release(ProductDistribution $distribution): void
    {
        if ($distribution->status !== ProductDistribution::STATUS_RESERVED) {
            return;
        }

        $distribution->update(['status' => ProductDistribution::STATUS_AVAILABLE]);
    }

    public function commissionForQuantity(ProductDistribution $distribution, int $qty): float
    {
        $qty = max(1, $qty);
        $commission = (float) $distribution->commission;

        if ($distribution->commission_type === ProductDistribution::COMMISSION_PERCENT) {
            return round((float) $distribution->selling_price * $qty * ($commission / 100), 2);
        }

        return round($commission * $qty, 2);
    }
}
