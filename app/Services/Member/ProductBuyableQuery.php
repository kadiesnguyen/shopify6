<?php

namespace App\Services\Member;

use App\Models\Product;
use App\Models\ProductDistribution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductBuyableQuery
{
    public static function portalFeaturedProducts(int $limit = 12): Collection
    {
        if (! self::hasFeaturedDistributions()) {
            return collect();
        }

        $featuredSub = ProductDistribution::query()
            ->available()
            ->where('is_featured', true)
            ->selectRaw('product_id, MAX(COALESCE(featured_at, created_at)) as featured_sort_at')
            ->groupBy('product_id');

        return Product::query()
            ->with(['category', 'shop'])
            ->where('products.status', Product::STATUS_ACTIVE)
            ->joinSub($featuredSub, 'portal_fd', 'portal_fd.product_id', '=', 'products.id')
            ->orderByDesc('portal_fd.featured_sort_at')
            ->orderByDesc('products.id')
            ->select('products.*')
            ->limit($limit)
            ->get();
    }

    public static function paginateFeaturedPortalProducts(int $perPage = 12): LengthAwarePaginator
    {
        if (! self::hasFeaturedDistributions()) {
            return Product::query()->whereRaw('1 = 0')->paginate($perPage);
        }

        $featuredSub = ProductDistribution::query()
            ->available()
            ->where('is_featured', true)
            ->selectRaw('product_id, MAX(COALESCE(featured_at, created_at)) as featured_sort_at')
            ->groupBy('product_id');

        return Product::query()
            ->with(['category', 'shop'])
            ->where('products.status', Product::STATUS_ACTIVE)
            ->joinSub($featuredSub, 'portal_fd', 'portal_fd.product_id', '=', 'products.id')
            ->orderByDesc('portal_fd.featured_sort_at')
            ->orderByDesc('products.id')
            ->select('products.*')
            ->paginate($perPage);
    }

    public static function paginatePortalProducts(int $perPage = 12): LengthAwarePaginator
    {
        return self::orderByLatestDistribution(self::forPortal())
            ->paginate($perPage);
    }

    /** @param  array<int>  $shopUserIds */
    public static function portalHomeProducts(int $limit = 12, array $shopUserIds = []): Collection
    {
        if ($shopUserIds !== []) {
            return self::productsFromShopUserIds($shopUserIds, $limit);
        }

        return self::orderByLatestDistribution(self::forPortal())
            ->limit($limit)
            ->get();
    }

    /** @param  array<int>  $shopUserIds */
    public static function productsFromShopUserIds(array $shopUserIds, ?int $limit = null): Collection
    {
        if ($shopUserIds === []) {
            return collect();
        }

        $query = self::forPortal()
            ->whereHas('distributions', function (Builder $distributionQuery) use ($shopUserIds): void {
                $distributionQuery
                    ->available()
                    ->whereIn('user_id', $shopUserIds);
            });

        $query = self::orderByLatestDistribution($query, $shopUserIds);

        if ($limit !== null) {
            return $query->limit($limit)->get();
        }

        return $query->get();
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

    private static function hasFeaturedDistributions(): bool
    {
        return ProductDistribution::query()
            ->available()
            ->where('is_featured', true)
            ->exists();
    }
}
