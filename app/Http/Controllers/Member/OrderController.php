<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $scope = $request->string('scope')->toString();
        $scope = $scope === 'all' && auth()->user()->isShop() ? 'all' : 'buyer';
        $isBuyerScope = $scope === 'buyer';
        $sort = $request->string('sort')->toString() ?: 'new';

        if ($isBuyerScope && $status === Order::STATUS_PENDING_PAYMENT) {
            $status = '';
        }

        $orders = Order::query()
            ->with(['items', 'shop', 'buyer'])
            ->when($scope === 'all', function ($query): void {
                $query->where(function ($inner): void {
                    $inner
                        ->where('user_id', auth()->id())
                        ->orWhere('seller_id', auth()->id());
                });
            }, fn ($query) => $query->where('user_id', auth()->id()))
            ->when($status !== '', function ($query) use ($status, $isBuyerScope): void {
                if ($isBuyerScope && $status === Order::STATUS_AWAITING_PICKUP) {
                    $query->whereIn('status', [Order::STATUS_AWAITING_PICKUP, Order::STATUS_PENDING_PAYMENT]);

                    return;
                }

                $query->where('status', $status);
            })
            ->when($request->string('q'), function ($query, $search): void {
                $query->whereHas('items', fn ($q) => $q->where('product_name', 'like', "%{$search}%"));
            })
            ->when($sort === 'old', fn ($query) => $query->oldest(), fn ($query) => $query->latest())
            ->paginate(10)
            ->withQueryString();

        if ($isBuyerScope) {
            $orders->getCollection()->transform(function (Order $order): Order {
                if ($order->status === Order::STATUS_PENDING_PAYMENT) {
                    $order->setAttribute('display_status', Order::STATUS_AWAITING_PICKUP);
                }

                return $order;
            });
        }

        $statusCounts = Order::query()
            ->when($scope === 'all', function ($query): void {
                $query->where(function ($inner): void {
                    $inner
                        ->where('user_id', auth()->id())
                        ->orWhere('seller_id', auth()->id());
                });
            }, fn ($query) => $query->where('user_id', auth()->id()))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        if ($isBuyerScope && $statusCounts->has(Order::STATUS_PENDING_PAYMENT)) {
            $statusCounts[Order::STATUS_AWAITING_PICKUP] = (int) ($statusCounts[Order::STATUS_AWAITING_PICKUP] ?? 0)
                + (int) $statusCounts[Order::STATUS_PENDING_PAYMENT];
            $statusCounts->forget(Order::STATUS_PENDING_PAYMENT);
        }

        return view('member.orders.index', compact('orders', 'status', 'statusCounts', 'scope'));
    }
}
