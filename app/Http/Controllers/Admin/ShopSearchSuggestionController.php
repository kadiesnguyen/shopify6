<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopSearchSuggestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $keyword = trim($request->string('q')->toString());

        if ($keyword === '') {
            return response()->json(['items' => []]);
        }

        $items = Shop::query()
            ->where('status', Shop::STATUS_ACTIVE)
            ->where('name', 'like', "%{$keyword}%")
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name'])
            ->map(fn (Shop $shop) => [
                'id' => $shop->id,
                'value' => $shop->name,
                'type' => 'shop',
            ])
            ->values()
            ->all();

        return response()->json(['items' => $items]);
    }
}
