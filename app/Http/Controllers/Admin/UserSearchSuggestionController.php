<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchSuggestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $keyword = trim($request->string('q')->toString());

        if ($keyword === '') {
            return response()->json(['items' => []]);
        }

        $items = User::query()
            ->withoutAdmins()
            ->with('shop')
            ->adminKeywordSearch($keyword)
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(function (User $user): array {
                $login = $user->loginIdentifier();
                $meta = collect([$login, $user->shop?->name])
                    ->filter()
                    ->unique()
                    ->values()
                    ->join(' · ');

                return [
                    'id' => $user->id,
                    'value' => $user->name ?: ($login ?? '—'),
                    'meta' => $meta !== '' ? $meta : null,
                    'type' => 'user',
                ];
            })
            ->values()
            ->all();

        return response()->json(['items' => $items]);
    }
}
