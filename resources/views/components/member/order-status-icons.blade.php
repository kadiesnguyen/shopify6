@props([
    'statusCounts' => collect(),
    'ordersRoute' => 'member.orders.index',
])

@php
    $items = [
        ['key' => 'pending_payment', 'label' => __('member.my.to_pay'), 'icon' => 'wallet', 'color' => 'text-amber-600'],
        ['key' => 'awaiting_pickup', 'label' => __('member.my.to_pickup'), 'icon' => 'package', 'color' => 'text-sky-600'],
        ['key' => 'shipped', 'label' => __('member.my.in_transit'), 'icon' => 'truck', 'color' => 'text-violet-600'],
        ['key' => 'received', 'label' => __('member.my.received'), 'icon' => 'package-check', 'color' => 'text-teal-600'],
        ['key' => 'completed', 'label' => __('member.my.completed'), 'icon' => 'circle-check', 'color' => 'text-emerald-600'],
    ];
@endphp

<div class="portal-scroll-x flex gap-2 px-3 py-4">
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
            class="flex min-w-[5rem] shrink-0 flex-col items-center gap-1 active:opacity-80"
        >
            <span class="relative inline-flex size-10 items-center justify-center">
                <x-member.icon :name="$item['icon']" @class(['size-6', $item['color']]) />
                @if ($count > 0)
                    <span class="absolute -right-1 -top-1 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-red-500 px-1 text-[10px] font-semibold leading-none text-white">
                        {{ $count > 99 ? '99+' : $count }}
                    </span>
                @endif
            </span>
            <span class="whitespace-nowrap text-center text-xs text-gray-600">{{ $item['label'] }}</span>
        </a>
    @endforeach
</div>
