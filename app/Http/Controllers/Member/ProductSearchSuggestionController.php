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

        $target = $request->string('target')->toString() === 'shop' ? 'shop' : 'product';
        $context = $request->string('context')->toString();

        $items = $target === 'shop'
            ? $this->shopSuggestions($keyword, $context)
            : $this->productSuggestions($keyword, $context);

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
        $numericKeyword = preg_replace('/\D+/', '', $keyword);

        return Shop::query()
            ->with('user:id,user_code')
            ->where('status', Shop::STATUS_ACTIVE)
            ->where(function (Builder $query) use ($keyword, $numericKeyword): void {
                $query
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('user_code', 'like', "%{$keyword}%"));

                if ($numericKeyword !== '') {
                    $query->orWhere('id', (int) $numericKeyword);
                }
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'user_id', 'name'])
            ->map(fn (Shop $shop) => [
                'id' => $shop->id,
                'value' => $shop->name,
                'meta' => $shop->user?->user_code ?: 'ID '.$shop->id,
                'type' => 'shop',
            ])
            ->values()
            ->all();
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
