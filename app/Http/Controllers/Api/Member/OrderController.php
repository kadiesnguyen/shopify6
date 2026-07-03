<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->string('role', 'buyer')->toString();
        $userId = auth()->id();

        $query = Order::query()->with(['items', 'shop']);

        if ($role === 'seller') {
            $query->where('seller_id', $userId);
        } else {
            $query->where('user_id', $userId);
        }

        $orders = $this->paginateQuery(
            $query,
            $request,
            searchColumns: ['order_no'],
            filterable: ['status'],
            sortable: ['created_at', 'total', 'status'],
        );

        return OrderResource::collection($orders);
    }

    public function show(int $order): OrderResource
    {
        $model = Order::query()->with(['items', 'shop'])->findOrFail($order);
        abort_unless(
            $model->user_id === auth()->id() || $model->seller_id === auth()->id(),
            403,
        );

        return new OrderResource($model);
    }
}
