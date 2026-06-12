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

        $numericKeyword = preg_replace('/\D+/', '', $keyword);

        $items = Shop::query()
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

        return response()->json(['items' => $items]);
    }
}
