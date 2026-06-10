@props(['product'])

<article class="flex gap-3 rounded-xl bg-white p-3 shadow-sm">
    <div class="size-20 shrink-0 overflow-hidden rounded-lg bg-gray-100">
        @if ($product->imageUrl())
            <img src="{{ $product->imageUrl() }}" alt="{{ $product->category?->name ?? $product->name }}" class="h-full w-full object-cover">
        @endif
    </div>

    <div class="flex min-w-0 flex-1 flex-col">
        <div class="min-w-0 flex-1">
            <p class="truncate font-medium text-gray-900">{{ $product->name }}</p>

            @if ($product->shop)
                <div class="mb-1 mt-1.5 flex items-center gap-2 rounded-lg bg-gray-50 px-1 py-1.5">
                    @if ($product->shop->logoUrl())
                        <img src="{{ $product->shop->logoUrl() }}" alt="" class="size-6 shrink-0 rounded-full bg-gray-100 object-cover">
                    @endif
                    <span class="truncate text-xs font-medium text-gray-700">{{ $product->shop->name }}</span>
                </div>
            @endif

            <p class="mt-1 text-xs text-gray-400">{{ __('member.products.stats', ['clicks' => 0, 'sales' => 0]) }}</p>
            <p class="mt-1 text-base font-semibold text-emerald-600">${{ number_format($product->selling_price, 2) }}</p>
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
