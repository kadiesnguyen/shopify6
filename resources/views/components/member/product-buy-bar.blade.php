@props([
    'checkoutUrl',
    'canBuy' => true,
])

<div class="portal-product-buy-bar fixed inset-x-0 bottom-[calc(50px+env(safe-area-inset-bottom,0px))] z-[55] border-t border-gray-200 bg-white px-2 py-2 md:left-1/2 md:w-full md:max-w-[420px] md:-translate-x-1/2">
    <div class="flex items-stretch gap-2">
        <div class="flex shrink-0 gap-1 border-r border-gray-100 pr-2">
            <a href="{{ route('member.chat.index') }}" class="flex w-14 flex-col items-center justify-center text-[10px] leading-tight text-gray-600 no-underline active:opacity-70">
                <x-member.icon name="headset" class="mb-0.5 size-5" />
                {{ __('member.nav.support') }}
            </a>
            <a href="{{ route('member.my.index') }}" class="flex w-14 flex-col items-center justify-center text-[10px] leading-tight text-gray-600 no-underline active:opacity-70">
                <x-member.icon name="bookmark" class="mb-0.5 size-5" />
                {{ __('member.products.my_short') }}
            </a>
            <a href="{{ route('member.orders.index') }}" class="flex w-14 flex-col items-center justify-center text-[10px] leading-tight text-gray-600 no-underline active:opacity-70">
                <x-member.icon name="shopping-cart" class="mb-0.5 size-5" />
                {{ __('member.products.cart') }}
            </a>
        </div>
        <div class="flex min-w-0 flex-1 gap-2">
            <a
                href="{{ $checkoutUrl }}"
                @class([
                    'inline-flex min-h-11 flex-1 items-center justify-center rounded-md bg-amber-400 px-1.5 py-2 text-center text-[11px] font-semibold leading-tight text-gray-900 no-underline hover:bg-amber-500',
                    'pointer-events-none opacity-50' => ! $canBuy,
                ])
            >
                {{ __('member.products.add_to_cart') }}
            </a>
            <a
                href="{{ $checkoutUrl }}"
                @class([
                    'inline-flex min-h-11 flex-1 items-center justify-center rounded-md bg-orange-500 px-1.5 py-2 text-center text-xs font-semibold leading-tight text-white no-underline hover:bg-orange-600',
                    'pointer-events-none opacity-50' => ! $canBuy,
                ])
            >
                {{ __('member.products.buy_now') }}
            </a>
        </div>
    </div>
</div>
