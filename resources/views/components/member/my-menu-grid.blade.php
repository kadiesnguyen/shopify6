@props(['user'])

@php
    $isShop = $user->isShop();
    $shopManageRoute = $isShop
        ? 'member.shop-hub.index'
        : 'member.shop-application.create';
    $shopManageLabel = $isShop
        ? __('member.my.shop_manage')
        : __('member.actions.start_selling');
@endphp

<div class="mt-3.5 px-3.5">
    <div class="grid grid-cols-4 gap-y-4 rounded-[11px] bg-white px-2 py-5">
        @foreach ([
            ['route' => $shopManageRoute, 'icon' => 'store', 'color' => 'bg-amber-100 text-amber-700', 'label' => $shopManageLabel],
            ['route' => 'member.wallet.hub', 'icon' => 'wallet', 'color' => 'bg-orange-100 text-orange-700', 'label' => __('member.my.my_wallet')],
            ['route' => 'member.shipping.index', 'icon' => 'map-pin', 'color' => 'bg-rose-100 text-rose-700', 'label' => __('member.my.shipping_address')],
            ['route' => 'member.reviews.index', 'icon' => 'file-text', 'color' => 'bg-blue-100 text-blue-700', 'label' => __('member.my.my_reviews')],
            ['route' => 'member.chat.index', 'icon' => 'headset', 'color' => 'bg-red-100 text-red-700', 'label' => __('member.actions.support')],
            ['route' => 'member.complaints.index', 'icon' => 'alert-triangle', 'color' => 'bg-pink-100 text-pink-700', 'label' => __('member.my.complaints')],
            ['route' => 'landing.about', 'icon' => 'info', 'color' => 'bg-sky-100 text-sky-700', 'label' => __('member.my.about')],
            ['route' => 'member.settings.index', 'icon' => 'settings', 'color' => 'bg-cyan-100 text-cyan-700', 'label' => __('member.settings.title')],
        ] as $item)
            <a href="{{ route($item['route']) }}" class="flex flex-col items-center gap-1.5 text-center no-underline">
                <span @class(['inline-flex size-12 items-center justify-center rounded-full', $item['color']])>
                    <x-member.icon :name="$item['icon']" class="size-[22px]" />
                </span>
                <span class="px-0.5 text-[13px] leading-tight text-gray-800">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
