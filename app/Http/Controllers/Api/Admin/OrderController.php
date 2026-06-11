<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Api\CsvExportService;
use App\Services\Member\OrderSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderSettlementService $settlement,
    ) {}

    public function index(Request $request)
    {
        $items = $this->paginateQuery(
            Order::query()->with(['buyer', 'shop', 'items']),
            $request,
            searchColumns: ['order_no'],
            filterable: ['status'],
            sortable: ['created_at', 'total', 'status'],
        );

        return OrderResource::collection($items);
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(['buyer', 'shop', 'items']));
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
        ]);

        $previousStatus = $order->status;
        $order = $this->settlement->applyStatusChange($order, $previousStatus, $data['status']);

        return response()->json(['data' => new OrderResource($order->load(['buyer', 'shop', 'items']))]);
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->settlement->removeOrder($order);

        return response()->json(['message' => 'Order deleted.']);
    }

    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:update_status,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required_if:action,update_status', Rule::in(Order::STATUSES)],
        ]);

        if ($data['action'] === 'delete') {
            Order::query()->whereIn('id', $data['ids'])->get()->each(function (Order $order): void {
                $this->settlement->removeOrder($order);
            });

            return response()->json(['message' => 'Orders deleted.']);
        }

        Order::query()->whereIn('id', $data['ids'])->get()->each(function (Order $order) use ($data): void {
            $this->settlement->applyStatusChange($order, $order->status, $data['status']);
        });

        return response()->json(['message' => 'Orders updated.']);
    }

    public function export(Request $request, CsvExportService $export)
    {
        $query = Order::query()->with('buyer');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $export->stream(
            $query,
            ['Order No', 'Buyer', 'Total', 'Status'],
            fn (Order $o) => [$o->order_no, $o->buyer?->email, $o->total, $o->status],
            'orders-'.now()->format('Ymd-His').'.csv',
        );
    }
}
