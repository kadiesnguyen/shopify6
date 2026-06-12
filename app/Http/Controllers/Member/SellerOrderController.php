<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Member\ShopOrderStatusBadges;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerOrderController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isShop(), 403);

        $status = $request->string('status')->toString();
        $sort = $request->string('sort')->toString() ?: 'new';
        $keyword = trim($request->string('q')->toString());

        $orders = Order::query()
            ->with(['items', 'shop', 'buyer'])
            ->where('seller_id', auth()->id())
            ->when($status !== '', fn ($query) => $query->where('status', $status))
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

        $statusCounts = Order::query()
            ->where('seller_id', auth()->id())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('member.seller.orders.index', compact('orders', 'status', 'statusCounts'));
    }
}
