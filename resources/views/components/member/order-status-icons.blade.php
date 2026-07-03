@props([
    'statusCounts' => collect(),
    'ordersRoute' => 'member.orders.index',
    'merchant' => false,
])

@php
    $items = $merchant
        ? [
            ['key' => 'pending_payment', 'label' => __('member.my.merchant_pending_payment'), 'icon' => 'wallet'],
            ['key' => 'waiting_shipment', 'label' => __('member.my.merchant_shipping'), 'icon' => 'package'],
            ['key' => 'shipped', 'label' => __('member.my.merchant_in_transit'), 'icon' => 'truck'],
            ['key' => 'completed', 'label' => __('member.my.merchant_completed'), 'icon' => 'chat-bubble'],
            ['key' => 'received', 'label' => __('member.my.merchant_after_sales'), 'icon' => 'package-check'],
        ]
        : [
            ['key' => 'pending_payment', 'label' => __('member.my.to_pay'), 'icon' => 'wallet'],
            ['key' => 'awaiting_pickup', 'label' => __('member.my.to_pickup'), 'icon' => 'package'],
            ['key' => 'shipped', 'label' => __('member.my.in_transit'), 'icon' => 'truck'],
            ['key' => 'received', 'label' => __('member.my.received'), 'icon' => 'package-check'],
            ['key' => 'completed', 'label' => __('member.my.completed'), 'icon' => 'circle-check'],
        ];
@endphp

<div class="px-3.5 py-4">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-[17px] font-bold text-gray-900">{{ __('member.my.my_orders') }}</h2>
        <a href="{{ route($ordersRoute) }}" class="flex items-center text-[14px] text-gray-400 no-underline">
            {{ __('member.my.view_all') }}
            <x-member.icon name="chevron-right" class="size-4" />
        </a>
    </div>
    {{-- Reference: monochrome line icons with red count badges --}}
    <div class="flex items-start justify-between">
        @foreach ($items as $item)
            @php
                $count = (int) ($statusCounts[$item['key']] ?? 0);
                $status = $item['key'];
                if ($ordersRoute === 'member.orders.index' && $status === 'pending_payment') {
                    $status = 'awaiting_pickup';
                }
            @endphp
            <a
                href="{{ route($ordersRoute, ['status' => $status]) }}"
                class="flex min-w-0 flex-1 flex-col items-center gap-1.5 no-underline active:opacity-80"
            >
                <span class="relative inline-flex">
                    <x-member.icon :name="$item['icon']" class="size-8 text-gray-800" />
                    @if ($count > 0)
                        <span class="absolute -right-2.5 -top-1.5 grid h-[17px] min-w-[17px] place-items-center rounded-full bg-[#fa3534] px-1 text-[10px] font-semibold leading-none text-white">
                            {{ $count > 99 ? '99+' : $count }}
                        </span>
                    @endif
                </span>
                <span class="w-full truncate text-center text-[13px] text-gray-700">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
