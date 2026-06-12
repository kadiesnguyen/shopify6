<?php

namespace App\Services\Member;

use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\Shop;
use Illuminate\Support\Collection;

class PortalProductDisplayService
{
    /** @param  array<int>  $shopUserIds */
    public function applyShopLabels(
        Collection $products,
        array $shopUserIds = [],
        ?Shop $selectedShop = null,
        bool $featuredOnly = false,
    ): void {
        if ($products->isEmpty()) {
            return;
        }

        if ($selectedShop) {
            $this->applyFixedShop($products, $selectedShop);

            return;
        }

        $distributions = $this->resolveDisplayDistributions(
            $products->pluck('id')->all(),
            $shopUserIds,
            $featuredOnly,
        );

        $products->each(function (Product $product) use ($distributions): void {
            $distribution = $distributions->get($product->id);

            $this->applyDistributionShop($product, $distribution?->user?->shop);
        });
    }

    private function applyFixedShop(Collection $products, Shop $shop): void
    {
        $shopName = $shop->name;
        $shopLogo = $shop->displayLogoUrl();

        $products->each(function (Product $product) use ($shop, $shopName, $shopLogo): void {
            $product->setAttribute('display_shop_id', $shop->id);
            $product->setAttribute('display_shop_name', $shopName);
            $product->setAttribute('display_shop_logo', $shopLogo);
        });
    }

    /** @param  array<int>  $productIds
     * @param  array<int>  $shopUserIds
     * @return Collection<int, ProductDistribution> */
    private function resolveDisplayDistributions(
        array $productIds,
        array $shopUserIds = [],
        bool $featuredOnly = false,
    ): Collection {
        if ($productIds === []) {
            return collect();
        }

        $distributions = ProductDistribution::query()
            ->available()
            ->whereIn('product_id', $productIds)
            ->when($featuredOnly, fn ($query) => $query->where('is_featured', true))
            ->when($shopUserIds !== [], fn ($query) => $query->whereIn('user_id', $shopUserIds))
            ->with(['user.shop', 'user:id,avatar'])
            ->when(
                $featuredOnly,
                fn ($query) => $query->orderByDesc('featured_at')->orderByDesc('created_at'),
                fn ($query) => $query->orderByDesc('created_at'),
            )
            ->orderByDesc('id')
            ->get();

        return $distributions->unique('product_id')->keyBy('product_id');
    }

    private function applyDistributionShop(Product $product, ?Shop $shop): void
    {
        if (! $shop) {
            return;
        }

        $product->setAttribute('display_shop_id', $shop->id);
        $product->setAttribute('display_shop_name', $shop->name);
        $product->setAttribute('display_shop_logo', $shop->displayLogoUrl());
    }
}
