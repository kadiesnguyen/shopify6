@extends('layouts.member')

@section('title', __('member.products.goods'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    <div class="min-h-[var(--app-height,100dvh)] bg-gray-50 pb-[calc(6rem+env(safe-area-inset-bottom))]">
        <header class="sticky top-0 z-10 bg-black text-white">
            <div class="relative flex items-center justify-between px-4 py-3">
                <a href="{{ route('member.shop-hub.index') }}" class="relative z-10 flex shrink-0 items-center gap-1.5 text-white/90 no-underline">
                    <x-member.icon name="chevron-left" class="size-5" />
                    <span class="text-sm font-medium">{{ __('member.back') }}</span>
                </a>
                <span class="pointer-events-none absolute left-1/2 max-w-[55%] -translate-x-1/2 truncate text-center text-base font-semibold">
                    {{ __('member.products.goods') }}
                </span>
                <span class="shrink-0 text-sm font-medium opacity-0" aria-hidden="true">{{ __('member.back') }}</span>
            </div>
        </header>

        <div class="space-y-3 p-4">
            @if (session('status'))
                <div class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-800">{{ $errors->first() }}</div>
            @endif

            <form method="GET">
                <x-member.search-field
                    name="q"
                    :value="request('q')"
                    :placeholder="__('member.search.products_alt')"
                    :autocomplete="true"
                    suggest-target="product"
                    suggest-context="manage"
                    icon="search"
                />
            </form>

            @if ($distributions->isEmpty())
                <x-ui.empty-state :title="__('member.shop_dashboard.no_distributions')" class="rounded-xl bg-white" />
            @else
                <section class="divide-y divide-gray-100 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
                    @foreach ($distributions as $distribution)
                        @php
                            $product = $distribution->product;
                            $marketPrice = (float) $product->selling_price;
                        @endphp
                        <article
                            class="flex min-w-0 items-center gap-3 p-3"
                            x-data="{
                                menuOpen: false,
                                editing: false,
                                sellingPrice: @js(number_format((float) $distribution->selling_price, 2, '.', '')),
                                marketPrice: @js(number_format($marketPrice, 2, '.', '')),
                                saving: false,
                                error: '',
                            }"
                        >
                            <a href="{{ route('member.products.show', ['product' => $product, 'from' => 'manage']) }}" class="size-16 shrink-0 overflow-hidden rounded-lg bg-gray-100">
                                @if ($product->imageUrl())
                                    <img src="{{ $product->imageUrl() }}" alt="" class="h-full w-full object-cover">
                                @endif
                            </a>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('member.products.show', ['product' => $product, 'from' => 'manage']) }}" class="line-clamp-2 text-sm font-medium text-gray-900 no-underline">
                                    {{ $product->name }}
                                </a>
                                <p class="mt-1 text-base font-semibold text-red-600">${{ number_format($distribution->selling_price, 2) }}</p>
                            </div>

                            <div class="relative shrink-0">
                                <button
                                    type="button"
                                    class="inline-flex size-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600"
                                    @click="menuOpen = !menuOpen; editing = false"
                                    aria-label="{{ __('member.products.edit') }}"
                                >
                                    <x-member.icon name="dots-horizontal" class="size-5" />
                                </button>

                                <div
                                    x-show="menuOpen && !editing"
                                    x-cloak
                                    class="absolute inset-y-0 right-0 z-20 flex items-center gap-2 rounded-lg bg-gray-900/85 px-2 py-1 shadow-lg"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex size-11 flex-col items-center justify-center rounded-full bg-orange-500 text-white"
                                        @click="editing = true; menuOpen = false; error = ''"
                                    >
                                        <x-member.icon name="file-text" class="size-4" />
                                        <span class="mt-0.5 text-[10px] leading-none">{{ __('member.products.edit') }}</span>
                                    </button>
                                </div>
                            </div>

                            <div
                                x-show="editing"
                                x-cloak
                                class="fixed inset-0 z-30 flex items-end justify-center bg-black/40 p-4 sm:items-center"
                                @keydown.escape.window="editing = false"
                            >
                                <form
                                    method="POST"
                                    action="{{ route('member.products.manage.update', $distribution) }}"
                                    class="w-full max-w-md rounded-xl bg-white p-4 shadow-xl"
                                    @submit.prevent="
                                        saving = true;
                                        error = '';
                                        fetch($el.action, {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': $el.querySelector('[name=_token]').value,
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest',
                                            },
                                            body: new FormData($el),
                                        })
                                        .then(async (response) => {
                                            const payload = await response.json().catch(() => ({}));
                                            if (!response.ok) {
                                                throw new Error(payload.message || @js(__('member.products.price_update_failed')));
                                            }
                                            window.location.reload();
                                        })
                                        .catch((err) => {
                                            error = err.message;
                                            saving = false;
                                        });
                                    "
                                >
                                    @csrf
                                    @method('PATCH')

                                    <div class="mb-4 flex items-center justify-between">
                                        <h2 class="text-base font-semibold text-gray-900">{{ __('member.products.edit') }}</h2>
                                        <button type="button" class="text-gray-400" @click="editing = false">
                                            <x-member.icon name="x" class="size-5" />
                                        </button>
                                    </div>


                                    <label for="selling-price-{{ $distribution->id }}" class="mb-1 block text-xs font-medium text-gray-500">{{ __('member.products.selling_price') }}</label>
                                    <div class="relative mb-3">
                                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-base text-gray-400">$</span>
                                        <input
                                            id="selling-price-{{ $distribution->id }}"
                                            type="text"
                                            inputmode="decimal"
                                            name="selling_price"
                                            x-model="sellingPrice"
                                            required
                                            autocomplete="off"
                                            class="block w-full min-w-0 rounded-lg border border-gray-200 py-3 pl-8 pr-3 text-base leading-normal text-gray-900"
                                            style="font-size: 16px;"
                                        >
                                    </div>

                                    <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('member.products.price_to') }}</label>
                                    <p class="mb-4 text-base font-semibold text-gray-900">${{ number_format($marketPrice, 2) }}</p>

                                    <p x-show="error" x-text="error" class="mb-3 text-sm text-rose-600"></p>

                                    <button
                                        type="submit"
                                        class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                                        :disabled="saving"
                                    >
                                        {{ __('member.products.save_content') }}
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </section>

                <div class="mt-4">{{ $distributions->links() }}</div>
            @endif
        </div>
    </div>
@endsection
