<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderRefundRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerRefundController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isShop(), 403);

        $refunds = OrderRefundRequest::query()
            ->with(['order', 'buyer'])
            ->where('seller_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('member.seller.refunds.index', compact('refunds'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isShop(), 403);

        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = Order::query()->findOrFail($data['order_id']);
        abort_unless($order->seller_id === auth()->id(), 403);
        abort_if($order->status === Order::STATUS_CANCELLED, 422);

        $exists = OrderRefundRequest::query()
            ->where('order_id', $order->id)
            ->where('status', OrderRefundRequest::STATUS_PENDING)
            ->exists();

        abort_if($exists, 422);

        OrderRefundRequest::query()->create([
            'order_id' => $order->id,
            'seller_id' => auth()->id(),
            'buyer_id' => $order->user_id,
            'amount' => $order->total,
            'reason' => $data['reason'] ?? null,
            'status' => OrderRefundRequest::STATUS_PENDING,
        ]);

        return redirect()
            ->route('member.seller.refunds.index')
            ->with('status', __('member.shop_hub.refund_submitted'));
    }
}
