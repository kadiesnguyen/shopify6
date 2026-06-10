@props(['order'])

@php
    $statusColor = match ($order->status) {
        'pending_payment' => 'text-amber-600',
        'awaiting_pickup' => 'text-blue-500',
        'waiting_shipment' => 'text-orange-500',
        'shipped' => 'text-violet-600',
        'received' => 'text-teal-600',
        'completed' => 'text-emerald-600',
        'cancelled' => 'text-gray-500',
        default => 'text-gray-600',
    };

    $itemImage = function ($item): ?string {
        if (! $item->product_image) {
            return asset(config('portal.logo', 'images/portal/logo.jpg'));
        }

        return str_starts_with($item->product_image, 'images/')
            ? asset($item->product_image)
            : asset('storage/'.$item->product_image);
    };
@endphp

<article class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
    <div class="mb-3 flex items-center justify-between">
        <span class="font-semibold text-gray-900">#{{ $order->order_no }}</span>
        <span @class(['text-sm font-medium', $statusColor])>{{ __('member.orders.'.$order->status) }}</span>
    </div>

    @foreach ($order->items as $item)
        <div class="flex gap-3 py-1.5">
            <div class="size-14 shrink-0 overflow-hidden rounded-lg bg-gray-100">
                <img src="{{ $itemImage($item) }}" alt="{{ $item->product_name }}" class="size-full object-cover">
            </div>
            <div class="min-w-0 flex-1">
                <p class="line-clamp-1 text-sm font-medium text-gray-800">{{ $item->product_name }}</p>
                <p class="text-sm font-semibold text-gray-900">${{ number_format($item->unit_price, 2) }}</p>
                <p class="flex items-center gap-1 text-xs text-emerald-600">
                    <x-member.icon name="check-circle-2" class="size-3.5" />
                    {{ __('member.orders.cash_commission', ['amount' => number_format($item->commission, 2)]) }}
                </p>
            </div>
            <span class="self-start text-xs text-gray-400">x{{ $item->qty }}</span>
        </div>
    @endforeach

    <div class="mt-2 flex justify-between border-t border-gray-50 pt-2 text-sm font-bold">
        <span>{{ __('member.orders.grand_total') }}</span>
        <span class="text-emerald-600">${{ number_format($order->total, 2) }}</span>
    </div>
</article>
