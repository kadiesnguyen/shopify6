@props(['order'])

@php
    use App\Models\Order;
    use App\Services\Member\OrderSettlementService;

    $canConfirm = in_array($order->status, OrderSettlementService::SELLER_SHIP_CONFIRM_STATUSES, true);

    $statusLabel = match ($order->status) {
        Order::STATUS_PENDING_PAYMENT => __('member.orders.seller_status_pending'),
        Order::STATUS_AWAITING_PICKUP => __('member.orders.seller_status_awaiting'),
        Order::STATUS_WAITING_SHIPMENT => __('member.orders.seller_status_waiting_shipment'),
        Order::STATUS_SHIPPED => __('member.orders.seller_status_shipped'),
        Order::STATUS_COMPLETED => __('member.orders.seller_status_completed'),
        default => __('member.orders.'.$order->status),
    };

    $itemImage = function ($item): ?string {
        if (! $item->product_image) {
            return asset(config('portal.logo', 'images/portal/logo.jpg'));
        }

        return str_starts_with($item->product_image, 'images/')
            ? asset($item->product_image)
            : asset('storage/'.$item->product_image);
    };

    $formId = 'seller-ship-'.$order->id;
@endphp

<article class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
    <div class="flex items-start justify-between gap-2 px-3 pb-1 pt-3">
        <p class="min-w-0 text-xs text-gray-500">
            {{ __('member.orders.sequence_no') }}:
            <span class="font-medium text-gray-700">{{ $order->order_no }}</span>
        </p>
        <p class="shrink-0 text-right text-xs font-medium text-[#fa3534]">{{ $statusLabel }}</p>
    </div>

    @if ($order->shop)
        <p class="px-3 pb-2 text-sm text-gray-400">{{ $order->shop->name }}</p>
    @endif

    @foreach ($order->items as $item)
        <div class="flex gap-3 px-3 py-1.5">
            <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-gray-100">
                <img src="{{ $itemImage($item) }}" alt="{{ $item->product_name }}" class="size-full object-cover">
            </div>
            <div class="min-w-0 flex-1">
                <p class="line-clamp-3 text-sm leading-snug text-gray-800">{{ $item->product_name }}</p>
            </div>
            <div class="shrink-0 text-right">
                <p class="text-sm font-semibold text-gray-900">{{ number_format($item->unit_price, 2) }}</p>
                <p class="mt-0.5 text-xs text-gray-400">x{{ $item->qty }}</p>
            </div>
        </div>
    @endforeach

    <div class="mt-2 flex flex-wrap items-center justify-between gap-2 border-t border-gray-50 px-3 py-3">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-600">
            <span>
                {{ __('member.products.cost_price') }}:
                <span class="font-semibold text-[#fa3534]">${{ number_format($order->purchase_cost, 2) }}</span>
            </span>
            <span>
                {{ __('member.products.selling_price') }}:
                <span class="font-semibold text-[#fa3534]">${{ number_format($order->total, 2) }}</span>
            </span>
        </div>

        @if ($canConfirm)
            <div x-data="{ open: false }" class="shrink-0">
                <button
                    type="button"
                    class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-700 transition active:opacity-80"
                    @click="open = true"
                >
                    {{ __('member.orders.confirm_platform_shipping') }}
                </button>

                <div
                    x-show="open"
                    x-cloak
                    class="fixed inset-0 z-[120] flex items-center justify-center bg-black/40 px-6"
                    @keydown.escape.window="open = false"
                >
                    <div class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl" @click.outside="open = false">
                        <p class="text-sm leading-relaxed text-gray-700">{{ __('member.orders.confirm_shipping_prompt') }}</p>
                        <div class="mt-5 flex items-center justify-end gap-3">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600"
                                @click="open = false"
                            >
                                {{ __('member.orders.shipping_confirm_cancel') }}
                            </button>
                            <button
                                type="submit"
                                form="{{ $formId }}"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white"
                                @click="open = false"
                            >
                                {{ __('member.orders.shipping_confirm_confirm') }}
                            </button>
                        </div>
                    </div>
                </div>

                <form
                    id="{{ $formId }}"
                    method="POST"
                    action="{{ route('member.seller.orders.confirm-shipping', $order) }}"
                    class="hidden"
                >
                    @csrf
                </form>
            </div>
        @endif
    </div>
</article>
