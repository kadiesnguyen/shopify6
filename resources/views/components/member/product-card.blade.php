@props(['product', 'detailFrom' => 'home', 'imageEager' => false, 'showMallBadge' => true])

@php
    $displayShopId = $product->getAttribute('display_shop_id');
    $hasShopPrice = $product->getAttribute('display_selling_price') !== null;
    $productImageUrl = $product->imageUrl();
    $detailParams = array_filter([
        'product' => $product,
        'from' => $detailFrom,
        'shop_id' => $hasShopPrice ? $displayShopId : null,
    ]);
    $detailUrl = route('member.products.show', $detailParams);
@endphp

{{-- Reference goodsCard: white, square corners, MALL ribbon overhanging the left edge --}}
<article class="relative flex h-full min-w-0 flex-col bg-white">
    @if ($showMallBadge)
        <span class="absolute -left-[5px] top-[5px] z-10 rounded-r bg-[#ff4243] px-1.5 py-px text-[11px] font-semibold text-white">MALL</span>
    @endif

    <a href="{{ $detailUrl }}" class="block aspect-square min-w-0 overflow-hidden bg-gray-100">
        @if ($productImageUrl)
            <x-ui.lazy-image
                :src="$productImageUrl"
                :alt="$product->name"
                :eager="$imageEager"
                :high-priority="$imageEager"
                class="h-full w-full object-cover"
                wrapper-class="h-full w-full"
            />
        @else
            <div class="flex h-full items-center justify-center text-gray-300">
                <x-member.icon name="layout-grid" class="size-12" />
            </div>
        @endif
    </a>

    <div class="flex min-w-0 flex-1 flex-col p-2">
        <a href="{{ $detailUrl }}" class="line-clamp-2 min-h-[2.4rem] text-[13px] leading-snug text-[#444] no-underline">{{ $product->name }}</a>
        <p class="mt-1 text-[#ed5435]">
            <span class="text-[11px]">$</span><span class="text-lg font-semibold">{{ number_format($product->displaySellingPrice(), 2) }}</span>
        </p>
    </div>
</article>
