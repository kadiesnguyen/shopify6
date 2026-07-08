<?php

namespace App\Services\Member;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;

class CartService
{
    public function __construct(private readonly ProductDistributionService $distributions) {}

    /** @return Collection<int, CartItem> */
    public function itemsFor(User $user): Collection
    {
        return CartItem::query()
            ->where('user_id', $user->id)
            ->with(['product', 'distribution.user.shop'])
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<string, Collection<int, CartItem>> */
    public function groupedByShop(User $user): Collection
    {
        return $this->itemsFor($user)
            ->groupBy(fn (CartItem $item): string => (string) ($item->shopUser?->shop?->name ?? $item->shop_user_id));
    }

    public function countFor(User $user): int
    {
        return (int) CartItem::query()->where('user_id', $user->id)->sum('quantity');
    }

    public function selectedTotal(User $user): float
    {
        return (float) $this->itemsFor($user)
            ->where('selected', true)
            ->sum(fn (CartItem $item): float => $item->lineTotal());
    }

    public function add(User $user, Product $product, int $qty = 1, ?int $shopUserId = null): CartItem
    {
        $distribution = $this->resolveDistribution($product, $shopUserId, $user->id);

        if (! $distribution) {
            throw new RuntimeException(
                $this->distributions->hasAvailableDistributionForSeller($product, $user->id)
                    ? 'cannot_buy_own_shop'
                    : 'product_not_distributed',
            );
        }

        $qty = max(1, min($qty, $product->stock));

        $item = CartItem::query()->firstOrNew([
            'user_id' => $user->id,
            'product_distribution_id' => $distribution->id,
        ]);

        $item->product_id = $product->id;
        $item->shop_user_id = $distribution->user_id;
        $item->quantity = min($product->stock, ($item->exists ? $item->quantity : 0) + $qty);
        $item->selected = true;
        $item->save();

        return $item->load(['product', 'distribution.user.shop']);
    }

    public function updateQuantity(CartItem $item, int $qty): CartItem
    {
        abort_unless($item->user_id === auth()->id(), 403);

        $qty = max(1, min($qty, $item->product?->stock ?? $qty));
        $item->update(['quantity' => $qty]);

        return $item->fresh(['product', 'distribution.user.shop']);
    }

    public function toggleSelected(CartItem $item, bool $selected): void
    {
        abort_unless($item->user_id === auth()->id(), 403);
        $item->update(['selected' => $selected]);
    }

    public function selectAll(User $user, bool $selected): void
    {
        CartItem::query()->where('user_id', $user->id)->update(['selected' => $selected]);
    }

    public function remove(CartItem $item): void
    {
        abort_unless($item->user_id === auth()->id(), 403);
        $item->delete();
    }

    /** @return Collection<int, CartItem> */
    public function selectedItems(User $user): Collection
    {
        return $this->itemsFor($user)->where('selected', true)->values();
    }

    private function resolveDistribution(Product $product, ?int $shopUserId, int $buyerUserId): ?ProductDistribution
    {
        if ($shopUserId && $shopUserId !== $buyerUserId) {
            $distribution = ProductDistribution::query()
                ->available()
                ->where('product_id', $product->id)
                ->where('user_id', $shopUserId)
                ->first();

            if ($distribution) {
                return $distribution;
            }
        }

        return ProductDistribution::query()
            ->available()
            ->where('product_id', $product->id)
            ->where('user_id', '!=', $buyerUserId)
            ->orderBy('id')
            ->first();
    }
}
