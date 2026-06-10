<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerOrderController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->shop, 403);

        $status = $request->string('status')->toString();
        $sort = $request->string('sort')->toString() ?: 'new';

        $orders = Order::query()
            ->with(['items', 'shop', 'buyer'])
            ->where('seller_id', auth()->id())
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($request->string('q'), function ($query, $search): void {
                $query->whereHas('items', fn ($q) => $q->where('product_name', 'like', "%{$search}%"));
            })
            ->when($sort === 'old', fn ($query) => $query->oldest(), fn ($query) => $query->latest())
            ->paginate(10)
            ->withQueryString();

        $statusCounts = Order::query()
            ->where('seller_id', auth()->id())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('member.seller.orders.index', compact('orders', 'status', 'statusCounts'));
    }
}
