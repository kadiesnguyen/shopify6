@php
    $tabs = [
        ['route' => 'member.home', 'label' => __('member.nav.home'), 'icon' => 'home', 'patterns' => ['member.home']],
        ['route' => 'member.products.index', 'label' => __('member.nav.products'), 'icon' => 'layout-grid', 'patterns' => ['member.products.*']],
        ['route' => 'member.chat.index', 'label' => __('member.nav.support'), 'icon' => 'headset', 'patterns' => ['member.chat.*']],
        ['route' => 'member.orders.index', 'label' => __('member.nav.orders'), 'icon' => 'clipboard-list', 'patterns' => ['member.orders.*', 'member.seller.orders.*']],
        ['route' => 'member.my.index', 'label' => __('member.nav.my'), 'icon' => 'user', 'patterns' => ['member.my.*', 'member.profile.*', 'member.shipping.*', 'member.wallet.*', 'member.promotions.*', 'member.notifications.*']],
    ];
@endphp

<nav class="portal-bottom-nav fixed inset-x-0 bottom-0 z-50 flex shrink-0 flex-nowrap items-center justify-around border-t border-gray-200 bg-white py-2 pb-[calc(0.5rem+env(safe-area-inset-bottom))] md:left-1/2 md:right-auto md:w-full md:max-w-[420px] md:-translate-x-1/2">
    @foreach ($tabs as $tab)
        @php
            $active = collect($tab['patterns'])->contains(fn (string $pattern) => request()->routeIs($pattern));
        @endphp
        <a
            href="{{ route($tab['route']) }}"
            @class([
                'flex min-w-0 flex-1 flex-col items-center gap-0.5 rounded-lg py-1 no-underline',
                'font-bold text-emerald-600' => $active,
                'font-normal text-gray-900' => ! $active,
            ])
        >
            <x-member.icon :name="$tab['icon']" class="size-8 shrink-0" />
            <span class="portal-bottom-nav__label max-w-full truncate">{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
