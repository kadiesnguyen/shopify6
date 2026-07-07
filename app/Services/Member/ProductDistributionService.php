<?php

namespace App\Services\Member;

use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\User;
use RuntimeException;

class ProductDistributionService
{
    public static function suggestedSellingPrice(float $marketPrice, float $purchasePrice): float
    {
        $marketPrice = round($marketPrice, 2);
        $purchasePrice = round($purchasePrice, 2);

        if ($marketPrice <= $purchasePrice) {
            return $marketPrice;
        }

        $discount = min(10.0, max(2.0, round($marketPrice * 0.05, 2)));

        return max($purchasePrice, round($marketPrice - $discount, 2));
    }

    public function distribute(User $shopUser, Product $product): ProductDistribution
    {
        $marketPrice = (float) $product->selling_price;
        $purchasePrice = (float) $product->purchase_price;
        $sellingPrice = self::suggestedSellingPrice($marketPrice, $purchasePrice);

        return ProductDistribution::query()->create([
            'user_id' => $shopUser->id,
            'product_id' => $product->id,
            'selling_price' => $sellingPrice,
            'purchase_price' => $purchasePrice,
            'commission' => max(0, $sellingPrice - $purchasePrice),
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

    public function updateSellingPrice(ProductDistribution $distribution, float $sellingPrice): ProductDistribution
    {
        $distribution->loadMissing('product');

        $sellingPrice = round($sellingPrice, 2);
        $marketPrice = (float) $distribution->product->selling_price;
        $purchasePrice = (float) $distribution->purchase_price;

        if ($sellingPrice < $purchasePrice) {
            throw new RuntimeException('below_purchase');
        }

        if ($sellingPrice > $marketPrice) {
            throw new RuntimeException('above_market');
        }

        $distribution->update([
            'selling_price' => $sellingPrice,
            'commission' => max(0, $sellingPrice - $purchasePrice),
        ]);

        return $distribution->fresh();
    }

    public function previewOrderPrice(Product $product): ?float
    {
        $distribution = ProductDistribution::query()
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
            ->first();

        return $distribution ? (float) $distribution->selling_price : null;
    }
}
