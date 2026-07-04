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

        $hasShopFilter = $shopUserIds !== [];
        $products->each(function (Product $product) use ($distributions, $hasShopFilter): void {
            // withPrice: only when filtering by specific shop(s) so the displayed price
            // belongs to the seller the buyer is intentionally browsing.
            $this->applyDistributionDisplay($product, $distributions->get($product->id), withPrice: $hasShopFilter);
        });
    }

    private function applyFixedShop(Collection $products, Shop $shop): void
    {
        $distributions = ProductDistribution::query()
            ->available()
            ->where('user_id', $shop->user_id)
            ->whereIn('product_id', $products->pluck('id'))
            ->get()
            ->keyBy('product_id');

        $products->each(function (Product $product) use ($distributions): void {
            // withPrice: true — buyer is browsing this specific shop, so the shown price
            // is the price this seller set, not an ambiguous multi-seller average.
            $this->applyDistributionDisplay($product, $distributions->get($product->id), withPrice: true);
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

    /**
     * @param  bool  $withPrice  Only set display_selling_price when we know exactly which
     *                           seller will be shown (shop-filtered or single-shop context).
     *                           On the general portal home the fulfilling distribution is
     *                           chosen by load-balancing at checkout time, so showing one
     *                           random seller's custom price is misleading.
     */
    private function applyDistributionDisplay(
        Product $product,
        ?ProductDistribution $distribution,
        bool $withPrice = false,
    ): void {
        if (! $distribution) {
            return;
        }

        $shop = $distribution->user?->shop;

        if ($shop) {
            $product->setAttribute('display_shop_id', $shop->id);
            $product->setAttribute('display_shop_name', $shop->name);
            $product->setAttribute('display_shop_logo', $shop->displayLogoUrl());
        }

        if ($withPrice) {
            $product->setAttribute('display_selling_price', (float) $distribution->selling_price);
        }
    }
}
