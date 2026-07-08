<?php

namespace App\Services\Member;

use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\User;
use RuntimeException;

class ProductDistributionService
{
    /**
     * Seller profit rate tiered by price (USD): cheap goods earn a thinner
     * margin, expensive goods a fatter one.
     */
    public static function profitRateForPrice(float $price): float
    {
        if ($price >= 2000.0) {
            return 0.30;
        }

        if ($price >= 1000.0) {
            return 0.25;
        }

        return 0.10;
    }

    /**
     * Cost/giá gốc such that profit = rate% of cost, i.e. price = cost * (1 + rate).
     */
    public static function costPriceForPrice(float $price): float
    {
        $price = round($price, 2);

        if ($price <= 0) {
            return 0.0;
        }

        return round($price / (1 + self::profitRateForPrice($price)), 2);
    }

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

    public function resolveForOrder(Product $product, ?int $displayShopId = null): ?ProductDistribution
    {
        // Route the order to the shop the buyer was actually shown, so its owner
        // receives the pending order instead of a load-balanced third party.
        if ($displayShopId > 0) {
            $preferred = ProductDistribution::query()
                ->available()
                ->where('product_id', $product->id)
                ->whereHas('user.shop', fn ($query) => $query->whereKey($displayShopId))
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            if ($preferred) {
                return $preferred;
            }
        }

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

    public function previewOrderPrice(Product $product, ?int $displayShopId = null): ?float
    {
        if ($displayShopId > 0) {
            $preferred = ProductDistribution::query()
                ->available()
                ->where('product_id', $product->id)
                ->whereHas('user.shop', fn ($query) => $query->whereKey($displayShopId))
                ->orderByDesc('created_at')
                ->first();

            if ($preferred) {
                return (float) $preferred->selling_price;
            }
        }

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
