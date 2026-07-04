@props(['order'])

@php
    $canConfirm = in_array($order->status, \App\Services\Member\OrderSettlementService::SELLER_SHIP_CONFIRM_STATUSES, true);

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
    <div class="mb-2 text-xs text-gray-500">
        {{ __('member.orders.sequence_no') }}: <span class="font-medium text-gray-700">{{ $order->order_no }}</span>
    </div>

    @if ($order->shop)
        <p class="mb-3 text-sm font-semibold text-gray-900">{{ $order->shop->name }}</p>
    @endif

    @foreach ($order->items as $item)
        <div class="flex gap-3 py-1.5">
            <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-gray-100">
                <img src="{{ $itemImage($item) }}" alt="{{ $item->product_name }}" class="size-full object-cover">
            </div>
            <div class="min-w-0 flex-1">
                <p class="line-clamp-2 text-sm text-gray-800">{{ $item->product_name }}</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">${{ number_format($item->unit_price, 2) }}</p>
            </div>
            <span class="self-start text-xs text-gray-400">x{{ $item->qty }}</span>
        </div>
    @endforeach

    <div class="mt-3 space-y-1 border-t border-gray-50 pt-3 text-sm">
        <div class="flex items-center justify-between">
            <span class="text-gray-600">{{ __('member.orders.purchase_cost') }}</span>
            <span class="font-semibold text-[#fa3534]">${{ number_format($order->purchase_cost, 2) }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-gray-600">{{ __('member.orders.actual_amount') }}</span>
            <span class="font-semibold text-[#fa3534]">${{ number_format($order->total, 2) }}</span>
        </div>
    </div>

    @if ($canConfirm)
        <form
            method="POST"
            action="{{ route('member.seller.orders.confirm-shipping', $order) }}"
            class="mt-4 flex justify-end"
            onsubmit="return confirm(@js(__('member.orders.confirm_shipping_prompt')))"
        >
            @csrf
            <button
                type="submit"
                class="rounded-full border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-700 transition active:opacity-80"
            >
                {{ __('member.orders.confirm_platform_shipping') }}
            </button>
        </form>
    @endif
</article>
