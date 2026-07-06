<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Services\Member\ProductBuyableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSearchSuggestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $keyword = trim($request->string('q')->toString());

        if ($keyword === '') {
            return response()->json(['items' => []]);
        }

        $target = match ($request->string('target')->toString()) {
            'shop' => 'shop',
            'combined' => 'combined',
            default => 'product',
        };
        $context = $request->string('context')->toString();

        $items = match ($target) {
            'shop' => $this->shopSuggestions($keyword, $context),
            'combined' => $this->combinedSuggestions($keyword, $context),
            default => $this->productSuggestions($keyword, $context),
        };

        return response()->json(['items' => $items]);
    }

    private function productSuggestions(string $keyword, string $context): array
    {
        return (clone $this->baseQueryFor($context))
            ->where('products.name', 'like', "%{$keyword}%")
            ->whereNotNull('products.name')
            ->orderBy('products.name')
            ->limit(8)
            ->pluck('products.name')
            ->map(fn (string $name) => [
                'value' => $name,
                'type' => 'product',
            ])
            ->values()
            ->all();
    }

    private function shopSuggestions(string $keyword, string $context): array
    {
        return Shop::query()
            ->where('status', Shop::STATUS_ACTIVE)
            ->where('name', 'like', "%{$keyword}%")
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'user_id', 'name'])
            ->map(fn (Shop $shop) => [
                'id' => $shop->id,
                'value' => $shop->name,
                'type' => 'shop',
            ])
            ->values()
            ->all();
    }

    private function combinedSuggestions(string $keyword, string $context): array
    {
        $shops = collect($this->shopSuggestions($keyword, $context))
            ->take(4)
            ->map(fn (array $item) => [
                ...$item,
                'meta' => __('member.search.suggestion_shop'),
            ]);

        $products = collect($this->productSuggestions($keyword, $context))
            ->take(4)
            ->map(fn (array $item) => [
                ...$item,
                'meta' => __('member.search.suggestion_product'),
            ]);

        return $shops->concat($products)->values()->all();
    }

    private function baseQueryFor(string $context): Builder
    {
        $user = auth()->user();

        if ($context === 'manage' && $user?->isShop()) {
            return ProductBuyableQuery::forShop((int) $user->id);
        }

        if ($context === 'distribution' && $user?->isShop()) {
            return Product::query()
                ->where('status', Product::STATUS_ACTIVE);
        }

        return ProductBuyableQuery::forPortal();
    }
}
