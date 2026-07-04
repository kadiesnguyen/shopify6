@props(['product', 'detailFrom' => 'products'])

@php
    $displayShopId = $product->getAttribute('display_shop_id');
    $displayShopName = $product->getAttribute('display_shop_name') ?: $product->shop?->name;
    $displayShopLogo = $product->getAttribute('display_shop_logo') ?: $product->shop?->displayLogoUrl();
    $detailUrl = route('member.products.show', array_filter([
        'product' => $product,
        'from' => $detailFrom,
        'shop_id' => $displayShopId,
    ]));
@endphp

<article class="flex min-w-0 gap-3 rounded-xl bg-white p-3 shadow-sm">
    <a href="{{ $detailUrl }}" class="size-20 shrink-0 overflow-hidden rounded-lg bg-gray-100">
        @if ($product->imageUrl())
            <x-ui.lazy-image
                :src="$product->imageUrl()"
                :alt="$product->category?->name ?? $product->name"
                class="h-full w-full object-cover"
                wrapper-class="size-20"
            />
        @endif
    </a>

    <div class="flex min-w-0 flex-1 flex-col">
        <div class="min-w-0 flex-1">
            <a href="{{ $detailUrl }}" class="truncate font-medium text-gray-900 no-underline">{{ $product->name }}</a>

            @if ($displayShopName)
                <div class="mb-1 mt-1.5 flex min-w-0 items-center gap-2 rounded-lg bg-gray-50 px-1 py-1.5">
                    @if ($displayShopLogo)
                        <x-ui.lazy-image
                            :src="$displayShopLogo"
                            alt=""
                            class="size-6 rounded-full object-cover"
                            wrapper-class="size-6 shrink-0 rounded-full"
                        />
                    @else
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-bold text-emerald-700">
                            {{ strtoupper(substr($displayShopName, 0, 1)) }}
                        </span>
                    @endif
                    <span class="truncate text-xs font-medium text-gray-700">{{ $displayShopName }}</span>
                </div>
            @endif

            <p class="mt-1 text-xs text-gray-400">{{ __('member.products.stats', ['clicks' => number_format($product->display_click_count ?? 0), 'sales' => number_format($product->display_sales_count ?? 0)]) }}</p>
            <p class="mt-1 text-base font-semibold text-emerald-600">${{ number_format($product->displaySellingPrice(), 2) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">{{ __('member.stock') }}: {{ $product->stock }}</p>
        </div>

        <a
            href="{{ $product->stock > 0 ? route('member.checkout.show', $product) : '#' }}"
            @class([
                'mt-2 inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white transition hover:bg-emerald-700',
                'pointer-events-none opacity-50' => $product->stock < 1,
            ])
        >
            <x-member.icon name="shopping-cart" class="size-4" />
            {{ __('member.buy') }}
        </a>
    </div>
</article>
