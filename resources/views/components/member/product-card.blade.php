@props(['product', 'detailFrom' => 'home'])

@php
    $displayShopId = $product->getAttribute('display_shop_id');
    $displayShopName = $product->getAttribute('display_shop_name') ?: $product->shop?->name;
    $displayShopLogo = $product->getAttribute('display_shop_logo') ?: $product->shop?->displayLogoUrl();
    $detailParams = array_filter([
        'product' => $product,
        'from' => $detailFrom,
        'shop_id' => $displayShopId,
    ]);
    $detailUrl = route('member.products.show', $detailParams);
@endphp

<article class="flex h-full min-w-0 flex-col overflow-hidden rounded-xl bg-white shadow-sm">
    <a href="{{ $detailUrl }}" class="relative block aspect-square min-w-0 overflow-hidden bg-gray-100">
        @if ($product->imageUrl())
            <x-ui.lazy-image
                :src="$product->imageUrl()"
                :alt="$product->category?->name ?? $product->name"
                class="h-full w-full object-cover"
                wrapper-class="h-full w-full"
            />
        @else
            <div class="flex h-full items-center justify-center text-gray-300">
                <x-member.icon name="layout-grid" class="size-12" />
            </div>
        @endif
    </a>

    <div class="flex min-w-0 flex-1 flex-col p-3">
        <a href="{{ $detailUrl }}" class="mb-1.5 block truncate text-sm font-bold leading-tight text-gray-900 no-underline">{{ $product->name }}</a>

        @if ($displayShopName)
            <div class="mb-2 flex min-w-0 items-center gap-2 rounded-lg bg-gray-50 px-1 py-1.5">
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
                <span class="truncate text-xs text-gray-600">{{ $displayShopName }}</span>
            </div>
        @endif

        <p class="text-base font-bold text-emerald-600">${{ number_format($product->selling_price, 2) }}</p>
        <p class="text-xs text-gray-500">{{ __('member.stock') }}: {{ $product->stock }}</p>

        <a
            href="{{ route('member.checkout.show', $product) }}"
            @class([
                'mt-auto flex w-full items-center justify-center gap-1.5 rounded-lg bg-emerald-600 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700',
                'pointer-events-none opacity-50' => $product->stock < 1,
            ])
        >
            <x-member.icon name="shopping-cart" class="size-4" />
            {{ __('member.buy') }}
        </a>
    </div>
</article>
