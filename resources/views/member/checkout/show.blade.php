@extends('layouts.member')

@section('title', __('member.checkout.title'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    @php
        $unitPrice = (float) $product->displaySellingPrice();
        $walletBalance = (float) ($wallet->balance ?? 0);
    @endphp

    @php
        $checkoutSelfUrl = route('member.checkout.show', array_filter(['product' => $product, 'shop_id' => $shopId ?? null]));
    @endphp
    <div
        class="portal-checkout-shell"
        x-data="{
            qty: {{ old('qty', 1) }},
            paymentMethod: '{{ old('payment_method', 'balance') }}',
            sheetOpen: false,
            unitPrice: {{ $unitPrice }},
            maxQty: {{ $product->stock }},
            money(value) {
                return '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            get total() { return this.unitPrice * this.qty; },
            changeQty(delta) {
                this.qty = Math.max(1, Math.min(this.maxQty, this.qty + delta));
            },
            setQty(value) {
                const parsed = parseInt(value, 10);
                this.qty = Number.isNaN(parsed) || parsed < 1 ? 1 : Math.min(this.maxQty, parsed);
            },
            openSheet() {
                @if ($address)
                    this.sheetOpen = true;
                @else
                    window.location.href = '{{ route('member.shipping.index', ['redirect' => $checkoutSelfUrl]) }}';
                @endif
            }
        }"
    >
        <header class="sticky top-0 z-20 flex items-center justify-center border-b border-gray-100 bg-white px-4 py-3">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('member.home') }}" class="absolute left-2 flex size-10 items-center justify-center text-gray-700" aria-label="{{ __('member.back') }}">
                <x-member.icon name="chevron-left" class="size-6" />
            </a>
            <h1 class="text-base font-semibold text-gray-900">{{ __('member.checkout.title') }}</h1>
        </header>

        <div class="px-4 pt-3">
            <a href="{{ route('member.shipping.index', ['redirect' => $checkoutSelfUrl]) }}" class="flex w-full items-center gap-2 py-3 text-left active:bg-gray-50">
                <x-member.icon name="map-pin" class="size-5 shrink-0 text-gray-600" />
                @if ($address)
                    <span class="min-w-0 flex-1 text-sm text-gray-800">
                        <span class="font-medium">{{ $address->recipient_name }} · {{ $address->phone }}</span><br>
                        <span class="text-gray-500">{{ collect([$address->address_line, $address->city, $address->state, $address->country])->filter()->implode(', ') }}</span>
                    </span>
                @else
                    <span class="flex-1 text-sm text-gray-500">{{ __('member.checkout.add_address') }}</span>
                @endif
                <x-member.icon name="chevron-right" class="size-5 shrink-0 text-gray-400" />
            </a>

            <div class="checkout-airmail my-1" aria-hidden="true"></div>

            @if ($product->shop)
                <div class="flex items-center gap-2 py-3 text-sm text-gray-800">
                    <x-member.icon name="store" class="size-4 shrink-0 text-gray-500" />
                    <span class="truncate font-medium">{{ $product->shop->name }}</span>
                </div>
            @endif

            <div class="flex gap-3 pb-4">
                <div class="size-20 shrink-0 overflow-hidden rounded-lg bg-gray-100">
                    @if ($product->imageUrl())
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="size-full object-cover">
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="line-clamp-3 text-sm font-semibold leading-snug text-gray-900">{{ $product->name }}</p>
                    <p class="mt-1 text-sm font-semibold text-[#FF4D4F]">
                        <span x-text="money(unitPrice)"></span>
                        <span class="font-normal text-gray-500"> / {{ __('member.checkout.unit') }}</span>
                    </p>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <p class="text-xs text-gray-500">
                            {{ __('member.checkout.quantity_label') }}: <span x-text="qty"></span>
                        </p>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="changeQty(-1)" class="flex size-9 items-center justify-center rounded-full border border-gray-200 text-gray-700 active:bg-gray-50 disabled:opacity-50" :disabled="qty <= 1">
                                <x-member.icon name="minus" class="size-4" />
                            </button>
                            <input type="number" min="1" :max="maxQty" x-model.number="qty" @input="setQty($event.target.value)" class="h-9 w-20 rounded-full border border-gray-200 bg-white px-3 text-center text-sm tabular-nums text-gray-900 outline-none focus:border-gray-300" aria-label="{{ __('member.checkout.quantity') }}">
                            <button type="button" @click="changeQty(1)" class="flex size-9 items-center justify-center rounded-full border border-gray-200 text-gray-700 active:bg-gray-50" :disabled="qty >= maxQty">
                                <x-member.icon name="plus" class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100">
                <button type="button" class="flex w-full items-center justify-between py-3 text-left text-sm active:bg-gray-50">
                    <span class="text-gray-800">{{ __('member.checkout.promotion') }}</span>
                    <span class="flex items-center gap-1 text-gray-600">$0.00 <x-member.icon name="chevron-right" class="size-4 text-gray-400" /></span>
                </button>
                <div class="border-t border-gray-100"></div>
                <div class="flex items-center justify-between py-3 text-sm">
                    <span class="text-gray-800">{{ __('member.checkout.shipping_fee') }}</span>
                    <span class="text-gray-600">{{ __('member.checkout.free_shipping') }}</span>
                </div>
                <div class="border-t border-gray-100"></div>
                <div class="flex items-center justify-between py-3 text-sm font-semibold text-gray-900">
                    <span>{{ __('member.checkout.grand_total') }}</span>
                    <span x-text="money(total)"></span>
                </div>
            </div>
        </div>

        <div class="portal-checkout-dock">
            <div class="min-w-0 flex-1">
                <p class="text-xl font-bold tabular-nums text-[#FF4D4F]" x-text="money(total)"></p>
            </div>
            <button
                type="button"
                @click="openSheet()"
                class="checkout-cta shrink-0 rounded-full px-8 py-3 text-sm font-semibold text-white"
            >
                {{ __('member.checkout.place_order') }}
            </button>
        </div>

        <div x-show="sheetOpen" x-cloak class="fixed inset-0 z-[60]">
            <div class="absolute inset-0 bg-black/40" @click="sheetOpen = false"></div>
            <div class="absolute bottom-0 left-1/2 w-full max-w-[420px] -translate-x-1/2 rounded-t-2xl bg-white">
                <div class="flex items-center justify-between px-5 pb-2 pt-4">
                    <span class="w-6"></span>
                    <h3 class="font-semibold text-gray-900">{{ __('member.checkout.cashier') }}</h3>
                    <button type="button" @click="sheetOpen = false" class="p-1 text-gray-400" aria-label="{{ __('member.back') }}">
                        <x-member.icon name="x" class="size-5" />
                    </button>
                </div>
                <p class="py-2 text-center text-2xl font-bold text-[#FF4D4F]" x-text="money(total)"></p>

                <form method="POST" action="{{ route('member.checkout.store', $product) }}">
                    @csrf
                    <input type="hidden" name="qty" :value="qty">
                    <input type="hidden" name="payment_method" :value="paymentMethod">
                    @if (! empty($shopId))
                        <input type="hidden" name="shop_id" value="{{ $shopId }}">
                    @endif

                    <button type="button" @click="paymentMethod = 'balance'" class="flex w-full items-center gap-3 border-t border-gray-100 px-5 py-4 text-left">
                        <span class="grid size-9 place-items-center rounded-full bg-violet-500 text-white">
                            <x-member.icon name="wallet" class="size-5" />
                        </span>
                        <span class="flex-1 text-sm text-gray-800">
                            {{ __('member.checkout.current_balance') }}:
                            <span class="font-semibold">${{ number_format($walletBalance, 2) }}</span>
                        </span>
                        <span class="grid size-5 place-items-center rounded-full" :class="paymentMethod === 'balance' ? 'bg-[#FF4D4F] text-white' : 'ring-1 ring-gray-300'">
                            <x-member.icon x-show="paymentMethod === 'balance'" name="check-circle-2" class="size-3.5" />
                        </span>
                    </button>

                    <button type="button" @click="paymentMethod = 'cskh'" class="flex w-full items-center gap-3 border-t border-gray-100 px-5 py-4 text-left">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-orange-500 text-white">
                            <x-member.icon name="headset" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm text-gray-800">{{ __('member.checkout.contact_recharge') }}</span>
                            <span class="block text-xs text-gray-400 underline">{{ __('member.checkout.contact_link') }}</span>
                        </span>
                        <span class="grid size-5 place-items-center rounded-full" :class="paymentMethod === 'cskh' ? 'bg-[#FF4D4F] text-white' : 'ring-1 ring-gray-300'">
                            <x-member.icon x-show="paymentMethod === 'cskh'" name="check-circle-2" class="size-3.5" />
                        </span>
                    </button>

                    @error('payment_method')
                        <p class="px-5 pb-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="p-4 pt-3">
                        <button type="submit" class="checkout-cta w-full rounded-full py-3 text-sm font-semibold text-white">
                            {{ __('member.checkout.confirm_payment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
