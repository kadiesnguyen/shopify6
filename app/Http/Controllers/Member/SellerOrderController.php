<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Member\OrderSettlementService;
use App\Support\Member\ShopOrderStatusBadges;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SellerOrderController extends Controller
{
    public function __construct(
        private readonly OrderSettlementService $settlement,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isShop(), 403);

        $status = $request->string('status')->toString();
        $sort = $request->string('sort')->toString() ?: 'new';
        $keyword = trim($request->string('q')->toString());

        $orders = Order::query()
            ->with(['items', 'shop', 'buyer'])
            ->where('seller_id', auth()->id())
            ->when(
                in_array($status, [Order::STATUS_AWAITING_PICKUP, Order::STATUS_WAITING_SHIPMENT], true),
                fn ($query) => $query->whereIn('status', OrderSettlementService::SELLER_AWAITING_SHIPMENT_STATUSES),
                fn ($query) => $query->when($status !== '', fn ($inner) => $inner->where('status', $status)),
            )
            ->when($keyword !== '', fn ($query) => $query->whereHas(
                'items',
                fn ($q) => $q->where('product_name', 'like', "%{$keyword}%"),
            ))
            ->when($sort === 'old', fn ($query) => $query->oldest(), fn ($query) => $query->latest())
            ->paginate(10)
            ->withQueryString();

        if ($status !== '' && ($shop = auth()->user()->shop)) {
            ShopOrderStatusBadges::markSeen($shop, $status, auth()->id());
        }

        $statusCounts = ShopOrderStatusBadges::sellerStatusCounts(auth()->id());

        return view('member.seller.orders.index', compact('orders', 'status', 'statusCounts'));
    }

    public function confirmShipping(Order $order): RedirectResponse
    {
        abort_unless(auth()->user()->isShop(), 403);
        abort_unless($order->seller_id === auth()->id(), 403);

        try {
            $this->settlement->confirmPlatformShipping($order);
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'order' => match ($exception->getMessage()) {
                    'insufficient_balance' => __('member.orders.insufficient_balance'),
                    default => __('member.orders.confirm_shipping_failed'),
                },
            ]);
        }

        return back()->with('status', __('member.orders.confirm_shipping_success'));
    }
}
