<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Models\Order;
use App\Services\Member\OrderSettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderSettlementService $settlement,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $keyword = trim($request->string('q')->toString());
        $shopId = $request->integer('shop_id');

        $orders = Order::query()
            ->with(['buyer', 'seller.wallet', 'shop', 'items'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($shopId > 0, fn ($q) => $q->where('shop_id', $shopId))
            ->when($shopId <= 0 && $keyword !== '', fn ($q) => $q->whereHas(
                'shop',
                fn ($shopQuery) => $shopQuery->where('name', 'like', "%{$keyword}%"),
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'status'));
    }

    public function update(OrderStatusRequest $request, Order $order): RedirectResponse
    {
        $previousStatus = $order->status;
        $newStatus = $request->validated('status');

        $this->settlement->applyStatusChange($order, $previousStatus, $newStatus);

        return back()->with('status', __('admin.orders.updated'));
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->settlement->removeOrder($order);

        return back()->with('status', __('admin.orders.deleted'));
    }
}
