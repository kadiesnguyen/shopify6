@props(['product'])

<article class="flex h-full flex-col overflow-hidden rounded-xl bg-white shadow-sm">
    <div class="relative aspect-square overflow-hidden bg-gray-100">
        @if ($product->imageUrl())
            <img src="{{ $product->imageUrl() }}" alt="{{ $product->category?->name ?? $product->name }}" class="h-full w-full object-cover">
        @else
            <div class="flex h-full items-center justify-center text-gray-300">
                <x-member.icon name="layout-grid" class="size-12" />
            </div>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-3">
        <p class="mb-1.5 truncate text-sm font-bold leading-tight text-gray-900">{{ $product->name }}</p>

        @if ($product->shop)
            <div class="mb-2 flex items-center gap-2 rounded-lg bg-gray-50 px-1 py-1.5">
                @if ($product->shop->logoUrl())
                    <img src="{{ $product->shop->logoUrl() }}" alt="" class="size-6 shrink-0 rounded-full bg-gray-100 object-cover">
                @else
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-bold text-emerald-700">
                        {{ strtoupper(substr($product->shop->name, 0, 1)) }}
                    </span>
                @endif
                <span class="truncate text-xs text-gray-600">{{ $product->shop->name }}</span>
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
