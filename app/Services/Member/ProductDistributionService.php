<?php

namespace App\Services\Member;

use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\User;
use RuntimeException;

class ProductDistributionService
{
    /** Seller commission as a share of the selling price. */
    public const COMMISSION_RATE = 0.30;

    /**
     * Cost/giá gốc: the selling price minus the seller's commission.
     */
    public static function costPriceForPrice(float $price): float
    {
        $price = round($price, 2);

        if ($price <= 0) {
            return 0.0;
        }

        return round($price - round($price * self::COMMISSION_RATE, 2), 2);
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
        $marketPrice = round((float) $product->selling_price, 2);
        $purchasePrice = round((float) $product->purchase_price, 2);

        return ProductDistribution::query()->create([
            'user_id' => $shopUser->id,
            'product_id' => $product->id,
            'selling_price' => $marketPrice,
            'purchase_price' => $purchasePrice,
            'commission' => max(0, round($marketPrice - $purchasePrice, 2)),
            'commission_type' => ProductDistribution::COMMISSION_FIXED,
            'status' => ProductDistribution::STATUS_AVAILABLE,
        ]);
    }

    public function syncListedPrices(Product $product): void
    {
        $product->distributions()->update([
            'selling_price' => $product->selling_price,
            'purchase_price' => $product->purchase_price,
            'commission' => $product->commission,
            'commission_type' => ProductDistribution::COMMISSION_FIXED,
        ]);
    }

    public function resolveForOrder(Product $product, ?int $displayShopId = null, ?int $excludeSellerUserId = null): ?ProductDistribution
    {
        // Route the order to the shop the buyer was actually shown, so its owner
        // receives the pending order instead of a load-balanced third party.
        if ($displayShopId > 0) {
            $preferred = ProductDistribution::query()
                ->available()
                ->where('product_id', $product->id)
                ->whereHas('user.shop', fn ($query) => $query->whereKey($displayShopId))
                ->when($excludeSellerUserId, fn ($query) => $query->where('user_id', '!=', $excludeSellerUserId))
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
            ->when($excludeSellerUserId, fn ($query) => $query->where('user_id', '!=', $excludeSellerUserId))
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

    public function hasAvailableDistributionForSeller(Product $product, int $sellerUserId): bool
    {
        return ProductDistribution::query()
            ->available()
            ->where('product_id', $product->id)
            ->where('user_id', $sellerUserId)
            ->exists();
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

    public function previewOrderPrice(Product $product, ?int $displayShopId = null, ?int $excludeSellerUserId = null): ?float
    {
        if (! ($displayShopId > 0)) {
            return (float) $product->selling_price;
        }

        $preferred = ProductDistribution::query()
            ->available()
            ->where('product_id', $product->id)
            ->whereHas('user.shop', fn ($query) => $query->whereKey($displayShopId))
            ->when($excludeSellerUserId, fn ($query) => $query->where('user_id', '!=', $excludeSellerUserId))
            ->orderByDesc('created_at')
            ->first();

        return $preferred ? (float) $preferred->selling_price : (float) $product->selling_price;
    }
}
