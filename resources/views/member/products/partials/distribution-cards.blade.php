@foreach ($products as $product)
    @php
        $isDistributed = $distributedIds->contains($product->id);
        $purchasePrice = (float) $product->purchase_price;
        $sellingPrice = (float) $product->selling_price;
        $profit = max(0, $sellingPrice - $purchasePrice);
    @endphp
    <article class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <a href="{{ route('member.products.show', ['product' => $product, 'from' => 'distribution']) }}" class="block">
            @if ($product->imageUrl())
                <img src="{{ $product->imageUrl() }}" alt="" class="aspect-square w-full object-cover">
            @else
                <div class="aspect-square w-full bg-gray-100"></div>
            @endif
        </a>

        <div class="flex flex-1 flex-col p-3">
            <a href="{{ route('member.products.show', ['product' => $product, 'from' => 'distribution']) }}" class="line-clamp-2 text-sm font-bold leading-snug text-gray-900 no-underline">
                {{ $product->name }}
            </a>

            <dl class="mt-2 space-y-1 text-xs">
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-gray-500">{{ __('member.products.purchase_price') }}:</dt>
                    <dd class="font-semibold text-amber-600">${{ number_format($purchasePrice, 2) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-gray-500">{{ __('member.products.selling_price') }}:</dt>
                    <dd class="font-semibold text-red-600">${{ number_format($sellingPrice, 2) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-gray-500">{{ __('member.products.profit') }}:</dt>
                    <dd class="font-semibold text-emerald-600">${{ number_format($profit, 2) }}</dd>
                </div>
            </dl>

            <div class="mt-3">
                @if ($isDistributed)
                    <span class="inline-flex w-full items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1.5 text-xs font-semibold text-emerald-700">
                        {{ __('member.products.already_distributed') }}
                    </span>
                @elseif (auth()->user()->canSelfDistribute())
                    <form method="POST" action="{{ route('member.products.distributions.store') }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-black px-2 py-1.5 text-xs font-semibold text-white transition hover:bg-gray-800"
                        >
                            {{ __('member.products.distribute') }}
                        </button>
                    </form>
                @else
                    <span class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs text-gray-500">
                        {{ __('member.products.distribution_locked') }}
                    </span>
                @endif
            </div>
        </div>
    </article>
@endforeach
