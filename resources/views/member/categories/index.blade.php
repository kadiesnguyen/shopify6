@extends('layouts.member')

@section('title', $distributeMode ? __('member.products.distribute') : __('member.nav.categories'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    <div @class([
        'flex min-h-[calc(100vh-50px)] flex-col',
        'pb-[calc(4.5rem+env(safe-area-inset-bottom))]' => $distributeMode,
    ])>
        <div class="px-4 py-2.5">
            <h1 class="text-center text-[15px] font-normal text-gray-600">
                {{ $distributeMode ? __('member.products.distribute') : __('member.nav.categories') }}
            </h1>
        </div>

        @if ($distributeMode)
            <form method="GET" class="border-b border-gray-100 bg-white px-3.5 pb-3">
                <input type="hidden" name="mode" value="distribute">
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory->id }}">
                @endif
                <x-member.search-field
                    name="q"
                    :value="$keyword ?? request('q')"
                    :placeholder="__('member.products.distribution_search')"
                    :autocomplete="true"
                    suggest-target="product"
                    suggest-context="distribution"
                    icon="search"
                />
            </form>
        @endif

        <div class="flex min-h-0 flex-1">
            <aside class="w-28 shrink-0 overflow-y-auto bg-[#f8f8f8]">
                @foreach ($categories as $category)
                    <a
                        href="{{ route('member.categories.index', array_filter([
                            'category' => $category->id,
                            'mode' => $distributeMode ? 'distribute' : null,
                            'q' => ($keyword ?? '') !== '' ? $keyword : null,
                        ])) }}"
                        @class([
                            'flex h-14 items-center justify-center px-1 text-center leading-tight no-underline',
                            'bg-white text-[16px] font-semibold text-[#ff4c15]' => $activeCategory?->id === $category->id,
                            'text-[14px] text-[#555]' => $activeCategory?->id !== $category->id,
                        ])
                    >
                        <span class="line-clamp-2">{{ $category->name }}</span>
                    </a>
                @endforeach
            </aside>

            <div class="min-w-0 flex-1 overflow-y-auto bg-white">
                @if (! $distributeMode && $banners->isNotEmpty())
                    <div class="px-3.5 pt-3.5">
                        <x-member.banner-carousel :banners="$banners" :rounded="true" />
                    </div>
                @endif

                @if ($products->isEmpty())
                    <x-ui.empty-state :title="__('member.no_products')" class="m-3.5 rounded-xl bg-gray-50 py-8" />
                @elseif ($distributeMode)
                    <form
                        id="distribute-batch-form"
                        method="POST"
                        action="{{ route('member.products.distributions.store') }}"
                        class="divide-y divide-gray-100"
                    >
                        @csrf

                        @foreach ($products as $product)
                            @php
                                $isDistributed = $distributedIds->contains($product->id);
                            @endphp
                            <label @class([
                                'flex items-start gap-3 px-3.5 py-3',
                                'opacity-50' => $isDistributed,
                            ])>
                                <input
                                    type="checkbox"
                                    name="product_ids[]"
                                    value="{{ $product->id }}"
                                    @disabled($isDistributed)
                                    class="mt-1 size-5 shrink-0 rounded-full border-gray-300 text-[#ff4c15] focus:ring-[#ff4c15]/30"
                                >
                                <span class="size-16 shrink-0 overflow-hidden rounded-md bg-gray-100">
                                    @if ($product->imageUrl())
                                        <img src="{{ $product->imageUrl() }}" alt="" class="size-full object-cover">
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="line-clamp-2 text-sm font-medium text-gray-900">{{ $product->name }}</span>
                                    <span class="mt-1 block text-xs text-gray-500">
                                        {{ __('member.products.category_label') }}: {{ $product->category?->name }}
                                    </span>
                                    <span class="mt-2 flex flex-wrap items-baseline gap-x-3 gap-y-1 text-sm">
                                        <span class="font-semibold text-red-600">
                                            {{ __('member.products.purchase_price') }}: ${{ number_format((float) $product->purchase_price, 2) }}
                                        </span>
                                        <span class="text-gray-400 line-through">
                                            {{ __('member.products.market_price') }}: ${{ number_format((float) $product->selling_price, 2) }}
                                        </span>
                                    </span>
                                    @if ($isDistributed)
                                        <span class="mt-1 inline-flex rounded bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                            {{ __('member.products.already_distributed') }}
                                        </span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </form>
                @else
                    <div class="flex flex-wrap p-3.5">
                        @foreach ($products as $product)
                            <a
                                href="{{ route('member.products.show', ['product' => $product, 'from' => 'category']) }}"
                                class="mb-4 flex w-1/3 flex-col items-center px-1 no-underline"
                            >
                                <span class="block size-14 overflow-hidden rounded-[5px] bg-gray-50">
                                    @if ($product->imageUrl())
                                        <img src="{{ $product->imageUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                                    @endif
                                </span>
                                <span class="mt-1.5 w-full truncate text-center text-[13px] text-gray-900">{{ $product->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($distributeMode && $products->contains(fn ($product) => ! $distributedIds->contains($product->id)))
        <div class="fixed inset-x-0 bottom-[calc(50px+env(safe-area-inset-bottom))] z-40 border-t border-gray-200 bg-white px-4 py-3 md:left-1/2 md:max-w-[420px] md:-translate-x-1/2">
            <button
                type="submit"
                form="distribute-batch-form"
                class="inline-flex w-full items-center justify-center rounded-lg bg-[#ff4c15] px-4 py-3 text-sm font-semibold text-white hover:bg-[#e64512]"
            >
                {{ __('member.products.confirm_distribution') }}
            </button>
        </div>
    @endif
@endsection

@if ($distributeMode)
    @push('scripts')
        <script>
            document.getElementById('distribute-batch-form')?.addEventListener('submit', async (event) => {
                event.preventDefault();

                const form = event.currentTarget;
                const checked = form.querySelectorAll('input[name="product_ids[]"]:checked');

                if (checked.length === 0) {
                    window.alert(@js(__('member.products.select_products_first')));

                    return;
                }

                const button = document.querySelector('button[form="distribute-batch-form"]');

                if (button instanceof HTMLButtonElement) {
                    button.disabled = true;
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': form.querySelector('[name=_token]')?.value ?? '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(form),
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (response.ok && payload?.redirect) {
                        window.location.href = payload.redirect;

                        return;
                    }

                    window.alert(
                        payload?.message
                            ?? Object.values(payload?.errors ?? {})?.flat()?.[0]
                            ?? @js(__('member.products.distribute_failed')),
                    );
                } catch (_) {
                    form.submit();
                } finally {
                    if (button instanceof HTMLButtonElement) {
                        button.disabled = false;
                    }
                }
            });
        </script>
    @endpush
@endif
