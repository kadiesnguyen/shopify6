@php
    use App\Support\Member\BellNotificationCache;

    $isShop = auth()->check() && auth()->user()->isShop();

    $tabs = $isShop
        ? [
            ['route' => 'member.shop-hub.index', 'label' => __('member.nav.shop'), 'icon' => 'store', 'patterns' => ['member.shop-hub.*']],
            ['route' => 'member.seller.orders.index', 'label' => __('member.nav.place_order'), 'icon' => 'layout-grid', 'patterns' => ['member.seller.orders.*', 'member.seller.refunds.*']],
            ['route' => 'member.products.manage.index', 'label' => __('member.products.goods'), 'icon' => 'package', 'patterns' => ['member.products.manage.*', 'member.products.distributions.*', 'member.categories.*']],
            ['route' => 'member.my.index', 'label' => __('member.nav.my'), 'icon' => 'user', 'patterns' => ['member.my.*', 'member.profile.*', 'member.shipping.*', 'member.wallet.*', 'member.promotions.*', 'member.notifications.*', 'member.settings.*', 'member.reviews.*', 'member.complaints.*'], 'badge' => true],
        ]
        : [
            ['route' => 'member.home', 'label' => __('member.nav.home'), 'icon' => 'home', 'patterns' => ['member.home']],
            ['route' => 'member.categories.index', 'label' => __('member.nav.categories'), 'icon' => 'layout-grid', 'patterns' => ['member.categories.*']],
            ['route' => 'member.cart.index', 'label' => __('member.nav.cart'), 'icon' => 'shopping-cart', 'patterns' => ['member.cart.*']],
            ['route' => 'member.my.index', 'label' => __('member.nav.my'), 'icon' => 'user', 'patterns' => ['member.my.*', 'member.profile.*', 'member.shipping.*', 'member.wallet.*', 'member.promotions.*', 'member.notifications.*', 'member.shop-hub.*', 'member.settings.*', 'member.reviews.*', 'member.complaints.*', 'member.seller.orders.*'], 'badge' => true],
        ];
    $unreadCount = auth()->check() ? BellNotificationCache::unreadCount(auth()->id()) : 0;
@endphp

<nav class="portal-bottom-nav fixed inset-x-0 bottom-0 z-50 flex h-[50px] shrink-0 flex-nowrap items-center justify-around border-t border-gray-100 bg-white pb-[env(safe-area-inset-bottom)] md:left-1/2 md:right-auto md:w-full md:max-w-[420px] md:-translate-x-1/2">
    @foreach ($tabs as $tab)
        @php
            $active = collect($tab['patterns'])->contains(fn (string $pattern) => request()->routeIs($pattern));
        @endphp
        <a
            href="{{ route($tab['route']) }}"
            @class([
                'relative flex min-w-0 flex-1 flex-col items-center gap-0.5 py-1 font-normal no-underline',
                'text-[#ff4c15]' => $active,
                'text-[#6d7074]' => ! $active,
            ])
        >
            <span class="relative inline-flex">
                <x-member.icon :name="$tab['icon']" class="size-6 shrink-0" />
                @if (($tab['badge'] ?? false) && $unreadCount > 0)
                    <span class="absolute -right-1.5 -top-1 grid h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[9px] font-semibold leading-none text-white">
                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                    </span>
                @endif
            </span>
            <span class="portal-bottom-nav__label max-w-full truncate">{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
