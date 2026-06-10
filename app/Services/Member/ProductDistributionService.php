<?php

namespace App\Services\Member;

use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\User;
use RuntimeException;

class ProductDistributionService
{
    public function distribute(User $shopUser, Product $product): ProductDistribution
    {
        return ProductDistribution::query()->create([
            'user_id' => $shopUser->id,
            'product_id' => $product->id,
            'selling_price' => $product->selling_price,
            'purchase_price' => $product->purchase_price,
            'commission' => max(0, (float) $product->selling_price - (float) $product->purchase_price),
            'commission_type' => ProductDistribution::COMMISSION_FIXED,
            'status' => ProductDistribution::STATUS_AVAILABLE,
        ]);
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
        return $this->profitForQuantity($distribution, $qty);
    }

    public function profitForQuantity(ProductDistribution $distribution, int $qty): float
    {
        $qty = max(1, $qty);
        $profitPerUnit = max(0, (float) $distribution->selling_price - (float) $distribution->purchase_price);

        return round($profitPerUnit * $qty, 2);
    }
}
