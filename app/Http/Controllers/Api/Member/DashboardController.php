<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\WalletResource;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $wallet = $user->wallet ?? $user->wallet()->create([
            'balance' => 0,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $orderCounts = Order::query()
            ->where('user_id', $user->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $products = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->latest()
            ->limit(8)
            ->get();

        return response()->json([
            'wallet' => new WalletResource($wallet),
            'order_counts' => $orderCounts,
            'products' => ProductResource::collection($products),
            'unread_notifications' => Notification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count(),
        ]);
    }
}
