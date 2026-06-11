<?php

namespace App\Services\Member;

use App\Models\Product;
use App\Models\ProductDistribution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductBuyableQuery
{
    /** @param  array<int>  $shopUserIds */
    public static function portalHomeProducts(int $limit = 12, array $shopUserIds = []): Collection
    {
        $distributionSub = ProductDistribution::query()
            ->available()
            ->when($shopUserIds !== [], fn (Builder $query) => $query->whereIn('user_id', $shopUserIds))
            ->selectRaw('product_id, MAX(created_at) as latest_distribution_at')
            ->groupBy('product_id');

        return Product::query()
            ->with(['shop:id,user_id,name,logo'])
            ->where('products.status', Product::STATUS_ACTIVE)
            ->joinSub($distributionSub, 'portal_pd', 'portal_pd.product_id', '=', 'products.id')
            ->orderByDesc('portal_pd.latest_distribution_at')
            ->orderByDesc('products.id')
            ->select('products.*')
            ->limit($limit)
            ->get();
    }
    public static function forPortal(): Builder
    {
        return Product::query()
            ->with(['category', 'shop'])
            ->where('status', Product::STATUS_ACTIVE)
            ->whereHas('distributions', fn (Builder $query) => $query->available());
    }

    public static function forShop(int $shopUserId): Builder
    {
        return Product::query()
            ->with(['category', 'shop'])
            ->where('status', Product::STATUS_ACTIVE)
            ->whereHas(
                'distributions',
                fn (Builder $query) => $query
                    ->where('user_id', $shopUserId)
                    ->available(),
            );
    }

    public static function isBuyable(Product $product): bool
    {
        if ($product->status !== Product::STATUS_ACTIVE || $product->stock < 1) {
            return false;
        }

        return $product->distributions()->available()->exists();
    }

    /** @param  array<int>  $shopUserIds */
    public static function orderByLatestDistribution(Builder $query, array $shopUserIds = []): Builder
    {
        $latestDistributionAt = ProductDistribution::query()
            ->selectRaw('max(product_distributions.created_at)')
            ->whereColumn('product_distributions.product_id', 'products.id')
            ->available();

        if ($shopUserIds !== []) {
            $latestDistributionAt->whereIn('user_id', $shopUserIds);
        }

        return $query
            ->orderByDesc($latestDistributionAt)
            ->orderByDesc('products.id');
    }
}
