@props([
    'productId',
    'productName',
    'sellingPrice',
    'stock',
    'imageUrl' => null,
    'images' => [],
    'shopUserId' => null,
    'shopUrl' => null,
    'checkoutUrl',
    'canBuy' => true,
])

@php
    $gallery = array_values(array_filter($images ?: ($imageUrl ? [$imageUrl] : [])));
    $navOffset = 'calc(50px + env(safe-area-inset-bottom, 0px))';
    $buyBarOffset = 'calc(50px + 3.75rem + env(safe-area-inset-bottom, 0px))';
@endphp

<div
    x-data="{
        cartSheetOpen: false,
        toastOpen: false,
        toastMessage: '',
        submitting: false,
        qty: 1,
        maxQty: {{ max(1, (int) $stock) }},
        unitPrice: {{ (float) $sellingPrice }},
        money(value) {
            return '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        openCartSheet() {
            if (! {{ $canBuy ? 'true' : 'false' }}) return;
            this.qty = 1;
            this.cartSheetOpen = true;
        },
        closeCartSheet() {
            this.cartSheetOpen = false;
        },
        changeQty(delta) {
            this.qty = Math.max(1, Math.min(this.maxQty, this.qty + delta));
        },
        setQty(value) {
            const parsed = parseInt(value, 10);
            this.qty = Number.isNaN(parsed) || parsed < 1 ? 1 : Math.min(this.maxQty, parsed);
        },
        showToast(message) {
            this.toastMessage = message;
            this.toastOpen = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.toastOpen = false; }, 2200);
        },
        async submitCart() {
            if (this.submitting) return;
            this.submitting = true;
            try {
                const response = await fetch('{{ route('member.cart.store') }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        product_id: {{ $productId }},
                        qty: this.qty,
                        @if ($shopUserId) shop_user_id: {{ $shopUserId }}, @endif
                    }),
                });
                const payload = await response.json().catch(() => ({}));
                if (! response.ok) {
                    this.showToast(payload.message ?? '{{ __('member.cart.add_failed') }}');
                    return;
                }
                this.closeCartSheet();
                this.showToast(payload.message ?? '{{ __('member.cart.added_toast') }}');
            } catch (error) {
                this.showToast('{{ __('member.cart.add_failed') }}');
            } finally {
                this.submitting = false;
            }
        },
    }"
    class="contents"
>
    <div class="portal-product-buy-bar fixed inset-x-0 bottom-[calc(50px+env(safe-area-inset-bottom,0px))] z-[55] border-t border-gray-200 bg-white px-2 py-2 md:left-1/2 md:w-full md:max-w-[420px] md:-translate-x-1/2">
        <div class="flex items-stretch gap-2">
            <div class="flex shrink-0 gap-1 border-r border-gray-100 pr-2">
                <a href="{{ route('member.chat.index') }}" class="flex w-14 flex-col items-center justify-center text-[10px] leading-tight text-gray-600 no-underline active:opacity-70">
                    <x-member.icon name="headset" class="mb-0.5 size-5" />
                    {{ __('member.nav.support') }}
                </a>
                <a
                    href="{{ $shopUrl ?: '#' }}"
                    @class([
                        'flex w-14 flex-col items-center justify-center text-[10px] leading-tight text-gray-600 no-underline active:opacity-70',
                        'pointer-events-none opacity-40' => ! $shopUrl,
                    ])
                >
                    <x-member.icon name="bookmark" class="mb-0.5 size-5" />
                    {{ __('member.products.shop_short') }}
                </a>
                <a href="{{ route('member.cart.index') }}" class="flex w-14 flex-col items-center justify-center text-[10px] leading-tight text-gray-600 no-underline active:opacity-70">
                    <x-member.icon name="shopping-cart" class="mb-0.5 size-5" />
                    {{ __('member.nav.cart') }}
                </a>
            </div>
            <div class="flex min-w-0 flex-1 gap-2">
                <button
                    type="button"
                    @click="openCartSheet()"
                    @class([
                        'inline-flex min-h-11 flex-1 items-center justify-center rounded-md bg-amber-400 px-1.5 py-2 text-center text-[11px] font-semibold leading-tight text-gray-900 hover:bg-amber-500',
                        'pointer-events-none opacity-50' => ! $canBuy,
                    ])
                    @disabled(! $canBuy)
                >
                    {{ __('member.products.add_to_cart') }}
                </button>
                <a
                    href="{{ $checkoutUrl }}"
                    @class([
                        'inline-flex min-h-11 flex-1 items-center justify-center rounded-md bg-[#fa3534] px-1.5 py-2 text-center text-xs font-semibold leading-tight text-white no-underline hover:bg-[#e62e2d]',
                        'pointer-events-none opacity-50' => ! $canBuy,
                    ])
                >
                    {{ __('member.products.buy_now') }}
                </a>
            </div>
        </div>
    </div>

    <div x-show="cartSheetOpen" x-cloak class="fixed inset-x-0 top-0 z-[200] md:left-1/2 md:w-full md:max-w-[420px] md:-translate-x-1/2" style="bottom: 0">
        <div class="absolute inset-x-0 top-0 bg-black/40" style="bottom: {{ $navOffset }}" @click="closeCartSheet()"></div>

        <div class="absolute inset-x-0 z-10 rounded-t-2xl bg-white shadow-[0_-4px_24px_rgba(15,23,42,0.12)]" style="bottom: {{ $navOffset }}" @click.stop>
            <button type="button" @click="closeCartSheet()" class="absolute right-4 top-4 z-10 text-gray-400" aria-label="{{ __('member.back') }}">
                <x-member.icon name="x" class="size-5" />
            </button>

            <div class="flex gap-3 px-4 pb-4 pt-5">
                <div class="flex shrink-0 gap-2">
                    @forelse (array_slice($gallery, 0, 2) as $thumb)
                        <div class="size-[72px] overflow-hidden rounded-md bg-gray-100">
                            <img src="{{ $thumb }}" alt="{{ $productName }}" class="size-full object-cover">
                        </div>
                    @empty
                        <div class="flex size-[72px] items-center justify-center rounded-md bg-gray-100 text-xs text-gray-400">{{ __('member.products.no_image') }}</div>
                    @endforelse
                </div>
                <div class="min-w-0 flex-1 pr-8 pt-1">
                    <p class="text-[26px] font-bold leading-none text-[#fa3534]" x-text="money(unitPrice)"></p>
                    <p class="mt-2 text-xs text-gray-600">{{ __('member.products.stock_on_hand', ['count' => number_format($stock)]) }}</p>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-gray-100 px-4 py-3.5">
                <span class="text-sm text-gray-800">{{ __('member.checkout.quantity') }}</span>
                <div class="flex items-center gap-2">
                    <button type="button" @click="changeQty(-1)" class="flex size-9 items-center justify-center rounded-full border border-gray-200 text-gray-700 active:bg-gray-50 disabled:opacity-40" :disabled="qty <= 1">
                        <x-member.icon name="minus" class="size-4" />
                    </button>
                    <input type="number" min="1" :max="maxQty" x-model.number="qty" @input="setQty($event.target.value)" class="h-9 w-16 rounded-md border border-gray-200 bg-white text-center text-sm tabular-nums text-gray-900 outline-none">
                    <button type="button" @click="changeQty(1)" class="flex size-9 items-center justify-center rounded-full border border-gray-200 text-gray-700 active:bg-gray-50 disabled:opacity-40" :disabled="qty >= maxQty">
                        <x-member.icon name="plus" class="size-4" />
                    </button>
                </div>
            </div>

            <div class="px-4 pb-5 pt-2">
                <button
                    type="button"
                    @click="submitCart()"
                    :disabled="submitting"
                    class="flex w-full items-center justify-center rounded-full bg-[#fa3534] py-3.5 text-base font-semibold text-white disabled:opacity-60"
                >
                    {{ __('member.products.submit') }}
                </button>
            </div>
        </div>
    </div>

    <div
        x-show="toastOpen"
        x-cloak
        x-transition.opacity
        class="pointer-events-none fixed inset-x-0 top-1/2 z-[250] flex -translate-y-1/2 justify-center px-6 md:left-1/2 md:w-full md:max-w-[420px] md:-translate-x-1/2"
    >
        <p class="rounded-lg bg-[#4a4a4a]/95 px-5 py-3 text-center text-sm text-white shadow-lg" x-text="toastMessage"></p>
    </div>
</div>
