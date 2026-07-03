@extends('layouts.member')

@section('title', $isShopView ? __('member.products.detail_title') : $product->name)
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    @php
        $purchasePrice = $detail['purchase_price'];
        $sellingPrice = $detail['selling_price'];
        $profit = $detail['profit'];
        $description = $detail['description'];
        $isRecommended = $detail['is_recommended'];
        $salesCount = $detail['sales_count'] ?? 0;
        $displayShopName = $detail['shop']['name'] ?? null;
        $displayShopLogo = $detail['shop']['logo_url'] ?? null;
        $shopProductsUrl = $detail['shop']['products_url'] ?? null;
        $checkoutUrl = $product->stock > 0 ? route('member.checkout.show', $product) : '#';
        $canBuy = $product->stock > 0;
    @endphp

    @if ($isShopView)
        <div class="min-h-[var(--app-height,100dvh)] bg-gray-50 pb-[calc(50px+env(safe-area-inset-bottom))]">
            <header class="sticky top-0 z-10 bg-black text-white">
                <div class="relative flex items-center justify-between px-4 py-3">
                    <a href="{{ $backUrl }}" class="relative z-10 flex shrink-0 items-center gap-1.5 text-white/90 no-underline">
                        <x-member.icon name="chevron-left" class="size-5" />
                        <span class="text-sm font-medium">{{ __('member.back') }}</span>
                    </a>
                    <span class="pointer-events-none absolute left-1/2 max-w-[55%] -translate-x-1/2 truncate text-center text-base font-semibold">
                        {{ __('member.products.detail_title') }}
                    </span>
                    <span class="shrink-0 text-sm font-medium opacity-0" aria-hidden="true">{{ __('member.back') }}</span>
                </div>
            </header>

            <div class="p-4">
                <div class="flex gap-3 rounded-xl bg-white p-3 shadow-sm">
                    <div class="size-20 shrink-0 overflow-hidden rounded-lg bg-gray-100">
                        @if ($detail['image_url'])
                            <img src="{{ $detail['image_url'] }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="line-clamp-2 text-sm font-bold leading-tight text-gray-900">{{ $product->name }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ __('member.products.purchase_price') }}: ${{ number_format($purchasePrice, 2) }}
                            · {{ __('member.products.selling_price') }}: ${{ number_format($sellingPrice, 2) }}
                        </p>
                        <p class="mt-1 text-sm font-semibold text-teal-500">
                            {{ __('member.products.profit') }}: ${{ number_format($profit, 2) }}
                        </p>
                    </div>
                </div>

                <section class="mt-4 space-y-0">
                    @foreach ([
                        ['label' => __('member.products.purchase_price'), 'value' => '$'.number_format($purchasePrice, 2), 'accent' => false],
                        ['label' => __('member.products.selling_price'), 'value' => '$'.number_format($sellingPrice, 2), 'accent' => false],
                        ['label' => __('member.products.expected_profit'), 'value' => '$'.number_format($profit, 2), 'accent' => true],
                        ['label' => __('member.stock'), 'value' => number_format($detail['stock']), 'accent' => false],
                        ['label' => __('member.products.recommended'), 'value' => $isRecommended ? __('member.products.yes') : __('member.products.no'), 'accent' => false],
                    ] as $row)
                        <div class="flex items-center justify-between border-b border-gray-100 px-1 py-3">
                            <span @class(['text-sm font-medium text-teal-500' => $row['accent'], 'text-sm text-gray-500' => ! $row['accent']])>{{ $row['label'] }}</span>
                            <span @class(['text-sm font-medium text-teal-500' => $row['accent'], 'text-sm font-medium text-gray-900' => ! $row['accent']])>{{ $row['value'] }}</span>
                        </div>
                    @endforeach
                </section>

                <section class="mt-4">
                    <p class="mb-2 text-sm font-semibold text-gray-900">{{ __('member.products.description') }}</p>
                    <p class="whitespace-pre-line text-sm leading-relaxed text-gray-600">{{ $description }}</p>
                </section>
            </div>
        </div>
    @else
        <div class="min-h-[var(--app-height,100dvh)] bg-gray-100 pb-[calc(8.5rem+env(safe-area-inset-bottom,0px))]">
            <header class="fixed top-0 left-0 right-0 z-30 flex items-center bg-orange-500 px-4 py-3 text-white md:left-1/2 md:w-full md:max-w-[420px] md:-translate-x-1/2">
                <a href="{{ $backUrl }}" class="flex shrink-0 items-center gap-1 rounded-lg p-1 no-underline hover:bg-white/10">
                    <x-member.icon name="chevron-left" class="size-5" />
                    <span class="text-sm font-medium">{{ __('member.products.goods') }}</span>
                </a>
                <div class="flex flex-1 justify-end gap-3 text-sm">
                    <button type="button" onclick="document.getElementById('pvReview')?.scrollIntoView({behavior:'smooth'})" class="font-medium opacity-95 hover:opacity-100">
                        {{ __('member.products.reviews') }}
                    </button>
                    <button type="button" onclick="document.getElementById('pvDetail')?.scrollIntoView({behavior:'smooth'})" class="font-medium opacity-95 hover:opacity-100">
                        {{ __('member.products.specs') }}
                    </button>
                </div>
            </header>

            @php($images = array_filter($detail['images'] ?? []))
            <div class="pt-12">
                <div
                    x-data="{
                        idx: 0,
                        imgs: {{ json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }},
                        startX: 0,
                        tracking: false,
                        begin(clientX, el, pointerId) {
                            this.tracking = true;
                            this.startX = clientX;
                            if (el?.setPointerCapture && pointerId != null) {
                                el.setPointerCapture(pointerId);
                            }
                        },
                        finish(clientX) {
                            if (!this.tracking) return;
                            this.tracking = false;
                            const delta = this.startX - clientX;
                            if (Math.abs(delta) < 40) return;
                            if (delta > 0 && this.idx < this.imgs.length - 1) this.idx++;
                            else if (delta < 0 && this.idx > 0) this.idx--;
                        },
                    }"
                    class="relative overflow-hidden bg-gray-200/80 select-none touch-pan-y"
                    style="touch-action: pan-y;"
                    @pointerdown="begin($event.clientX, $el, $event.pointerId)"
                    @pointerup="finish($event.clientX)"
                    @pointercancel="tracking = false"
                    @touchstart.passive="begin($event.touches[0].clientX, $el, null)"
                    @touchend="finish($event.changedTouches[0].clientX)"
                    @mousedown.prevent="begin($event.clientX, $el, null)"
                    @mouseup="finish($event.clientX)"
                    @mouseleave="if (tracking) finish($event.clientX)"
                    @dragstart.prevent
                >
                    @if ($images === [])
                        <div class="flex min-h-[280px] items-center justify-center">
                            <span class="text-sm text-gray-400">{{ __('member.products.no_image') }}</span>
                        </div>
                    @else
                        <div class="flex transition-transform duration-300 ease-out" :style="`transform: translateX(-${idx * 100}%)`">
                            <template x-for="(img, i) in imgs" :key="i">
                                <div class="flex w-full shrink-0 items-center justify-center min-h-[280px]">
                                    <img :src="img" alt="{{ $product->name }}" draggable="false" class="max-h-[360px] w-full object-contain pointer-events-none">
                                </div>
                            </template>
                        </div>
                        @if (count($images) > 1)
                            <div class="absolute top-3 left-0 right-0 z-20 flex justify-center gap-1.5">
                                <template x-for="(img, i) in imgs" :key="i">
                                    <button
                                        type="button"
                                        class="block h-1.5 w-1.5 rounded-full shadow-sm"
                                        :class="i === idx ? 'bg-orange-500' : 'bg-white/80'"
                                        :aria-label="`${i + 1}`"
                                        @click.stop="idx = i"
                                    ></button>
                                </template>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="relative z-10 -mt-3 rounded-t-2xl bg-white px-4 pb-3 pt-4 shadow-sm">
                    <p class="text-2xl font-bold text-orange-600">${{ number_format($sellingPrice, 2) }}</p>
                    <h1 class="mt-2 text-base font-semibold leading-snug text-gray-900">{{ $product->name }}</h1>
                    <span class="mt-2 inline-block rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-700">
                        {{ __('member.products.self_managed') }}
                    </span>
                </div>

                <div class="mt-2 grid grid-cols-3 gap-2 border-t border-gray-100 bg-white px-4 py-3 text-center text-xs">
                    <div>
                        <p class="text-gray-500">{{ __('member.products.shipping') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">{{ __('member.products.shipping_fee') }}</p>
                        <p class="mt-0.5 font-semibold text-gray-900">{{ __('member.products.free_shipping') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">{{ __('member.products.sales') }}</p>
                        <p class="mt-0.5 font-semibold text-gray-900">{{ number_format($salesCount) }}</p>
                    </div>
                </div>

                @if ($displayShopName && $shopProductsUrl)
                    <div class="mt-2 bg-white px-4 py-3">
                        <a href="{{ $shopProductsUrl }}" class="flex w-full items-center gap-3 rounded-lg px-1 py-0.5 text-left no-underline active:bg-gray-50">
                            @if ($displayShopLogo)
                                <img src="{{ $displayShopLogo }}" alt="" class="size-12 shrink-0 rounded-full bg-gray-100 object-cover">
                            @else
                                <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-orange-100 text-sm font-bold text-orange-700">
                                    {{ strtoupper(substr($displayShopName, 0, 1)) }}
                                </span>
                            @endif
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-1">
                                    <span class="truncate font-semibold text-gray-900">{{ $displayShopName }}</span>
                                    <span class="shrink-0 text-xs font-bold text-orange-600">✓</span>
                                </span>
                                <span class="mt-0.5 block text-xs text-gray-500">{{ __('member.products.trusted_quality') }}</span>
                            </span>
                            <x-member.icon name="chevron-right" class="size-5 shrink-0 text-gray-300" />
                        </a>
                        <a href="{{ route('member.chat.index') }}" class="mt-3 inline-flex w-full items-center justify-center rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            {{ __('member.products.contact_support') }}
                        </a>
                    </div>
                @endif

                <div id="pvReview" class="mt-2 scroll-mt-14 bg-white px-4 py-3">
                    <div class="flex w-full items-center justify-between text-left font-semibold text-gray-900">
                        <span>{{ __('member.products.reviews') }} {{ $reviewsCount }}+</span>
                        <x-member.icon name="chevron-right" class="size-4 text-gray-400" />
                    </div>

                    @if ($canReview)
                        <form method="POST" action="{{ route('member.reviews.store') }}" class="mt-3 space-y-2 rounded-lg bg-gray-50 p-3">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <div class="flex items-center gap-1" x-data="{ rating: 5 }">
                                <input type="hidden" name="rating" :value="rating">
                                <template x-for="i in 5" :key="i">
                                    <button type="button" @click="rating = i" class="text-xl" :class="i <= rating ? 'text-amber-400' : 'text-gray-300'">★</button>
                                </template>
                            </div>
                            <textarea name="body" rows="2" maxlength="2000" placeholder="{{ __('member.reviews.body_placeholder') }}" class="w-full rounded-lg border-gray-200 text-sm"></textarea>
                            <button type="submit" class="rounded-lg bg-[#fa3534] px-4 py-1.5 text-sm text-white">{{ __('member.reviews.submit') }}</button>
                        </form>
                    @endif

                    @if ($reviews->isEmpty())
                        <div class="flex flex-col items-center py-8 text-gray-400">
                            <x-member.icon name="message-square-off" class="mb-2 size-14 opacity-60" />
                            <p class="text-sm">{{ __('member.products.no_reviews') }}</p>
                        </div>
                    @else
                        <div class="mt-3 space-y-3">
                            @foreach ($reviews as $review)
                                <article class="border-b border-gray-100 pb-3 last:border-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900">{{ $review->user?->name ?? '—' }}</p>
                                        <span class="text-xs text-gray-400">{{ $review->created_at->format('d/m/Y') }}</span>
                                    </div>
                                    <p class="text-amber-400">{{ str_repeat('★', $review->rating) }}</p>
                                    @if ($review->body)
                                        <p class="mt-1 text-sm text-gray-600">{{ $review->body }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div id="pvDetail" class="mt-2 scroll-mt-14 bg-white px-4 py-3">
                    <p class="mb-3 border-l-4 border-orange-500 pl-2 font-semibold text-gray-900">{{ __('member.products.specs') }}</p>
                    <dl class="space-y-0 text-sm">
                        @if ($displayShopName)
                            <div class="flex justify-between gap-4 border-b border-gray-100 py-2">
                                <dt class="shrink-0 text-gray-500">{{ __('member.products.brand') }}</dt>
                                <dd class="text-right font-medium text-gray-900">{{ $displayShopName }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4 border-b border-gray-100 py-2">
                            <dt class="shrink-0 text-gray-500">{{ __('member.products.inventory') }}</dt>
                            <dd class="text-right font-medium text-gray-900">{{ number_format($detail['stock']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2">
                            <dt class="shrink-0 text-gray-500">{{ __('member.products.price') }}</dt>
                            <dd class="text-right font-medium text-gray-900">${{ number_format($sellingPrice, 2) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="mb-2 mt-2 bg-white px-4 py-4">
                    <h2 class="mb-2 font-bold text-gray-900">{{ __('member.products.description') }}</h2>
                    <ul class="list-disc space-y-2 pl-5 text-sm text-gray-700">
                        @foreach (preg_split('/\r\n|\r|\n/', $description) as $line)
                            @if (filled(trim($line)))
                                <li>{{ trim($line) }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        @push('product_buy_bar')
            <x-member.product-buy-bar :checkout-url="$checkoutUrl" :can-buy="$canBuy" />
        @endpush
    @endif
@endsection
