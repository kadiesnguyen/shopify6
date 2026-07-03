<div id="productDetail" class="scroll-mt-12 mt-2 bg-white px-4 py-4">
    <p class="mb-3 border-l-4 border-orange-500 pl-2 font-semibold text-gray-900">{{ __('member.products.specs') }}</p>
    @if ($descriptionHtml)
        <div class="product-detail-html text-sm leading-relaxed text-gray-800">{!! $description !!}</div>
    @elseif (filled(trim($description)) && trim($description) !== $product->name)
        <div class="whitespace-pre-line text-sm leading-relaxed text-gray-800">{{ $description }}</div>
    @else
        <p class="py-10 text-center text-sm text-gray-400">{{ __('member.products.no_detail') }}</p>
    @endif
</div>
