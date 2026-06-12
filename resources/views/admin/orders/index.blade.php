@extends('layouts.admin')

@section('title', __('admin.orders.title'))

@section('content')
    <x-admin.page-header :title="__('admin.orders.title')" />
    <x-admin.shop-order-search :placeholder="__('admin.orders.search')" />

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['', 'pending_payment', 'awaiting_pickup', 'waiting_shipment', 'shipped', 'received', 'completed', 'cancelled'] as $st)
            <a href="{{ route('admin.orders.index', array_filter(['status' => $st ?: null, 'q' => request('q'), 'shop_id' => request('shop_id') ?: null])) }}" @class(['rounded-full px-3 py-1 text-xs font-medium', 'bg-brand text-white' => $status === $st, 'bg-white ring-1 ring-slate-200' => $status !== $st])>
                {{ $st ? __('member.orders.'.$st) : __('admin.orders.all') }}
            </a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    @foreach (['order_no', 'buyer', 'store', 'product', 'qty', 'total', 'commission', 'payment', 'current_balance', 'status', 'actions'] as $col)
                        <th class="px-2 py-2 text-left text-xs font-medium text-slate-600">{{ __('admin.columns.'.$col) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    @php
                        $buyerLabel = $order->buyer?->email ?? '—';
                    @endphp
                    <tr>
                        <td class="max-w-[4.5rem] px-2 py-2 whitespace-nowrap" title="{{ $order->order_no }}">{{ \Illuminate\Support\Str::limit($order->order_no, 5) }}</td>
                        <td class="max-w-[6rem] px-2 py-2 whitespace-nowrap" title="{{ $buyerLabel }}">{{ \Illuminate\Support\Str::limit($buyerLabel, 10) }}</td>
                        <td class="px-2 py-2">{{ $order->shop?->name ?? '—' }}</td>
                        <td class="max-w-[8rem] truncate px-2 py-2" title="{{ $order->items->first()?->product_name }}">{{ $order->items->first()?->product_name }}</td>
                        <td class="px-2 py-2">{{ $order->items->sum('qty') }}</td>
                        <td class="px-2 py-2 whitespace-nowrap">${{ number_format($order->total, 2) }}</td>
                        <td class="px-2 py-2 whitespace-nowrap">${{ number_format($order->commission, 2) }}</td>
                        <td class="px-2 py-2">{{ $order->payment_method }}</td>
                        <td class="px-2 py-2 text-xs whitespace-nowrap text-slate-600">${{ number_format($order->buyer?->wallet?->balance ?? 0, 2) }}</td>
                        <td class="px-2 py-2 whitespace-nowrap">{{ __('member.orders.'.$order->status) }}</td>
                        <td class="px-2 py-2">
                            <div class="flex flex-col gap-2">
                                <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="flex gap-1">
                                    @csrf @method('PATCH')
                                    <select name="status" class="rounded border-slate-300 text-xs">
                                        @foreach (\App\Models\Order::STATUSES as $s)
                                            <option value="{{ $s }}" @selected($order->status === $s)>{{ __('member.orders.'.$s) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded bg-brand px-2 py-1 text-xs text-white">{{ __('admin.orders.save') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm(@js(__('admin.orders.confirm_delete')))">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded border border-red-200 px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                                        {{ __('admin.actions.delete') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="px-4 py-8 text-center text-slate-500">{{ __('admin.orders.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">{{ $orders->links() }}</div>
    </div>
@endsection
