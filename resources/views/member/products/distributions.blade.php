@extends('layouts.member')

@section('title', __('member.products.distribution_center'))

@section('content')
    <header class="mb-4">
        <a href="{{ route('member.products.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-gray-600 no-underline hover:text-emerald-600">
            <x-member.icon name="chevron-left" class="size-4" />
            {{ __('member.back') }}
        </a>
        <h1 class="text-lg font-semibold text-gray-900">{{ __('member.products.distribution_center') }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">{{ __('member.products.distribution_center_hint') }}</p>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
    @endif

    <form method="GET" class="mb-4">
        <x-member.search-field name="q" :value="request('q')" :placeholder="__('member.search.products_alt')" icon="search" />
    </form>

    @if ($products->isEmpty())
        <x-ui.empty-state :title="__('member.no_products')" class="rounded-xl bg-gray-50" />
    @else
        <section class="grid gap-4 sm:grid-cols-2">
            @foreach ($products as $product)
                @php
                    $isDistributed = $distributedIds->contains($product->id);
                    $purchasePrice = (float) $product->purchase_price;
                    $sellingPrice = (float) $product->selling_price;
                    $profit = max(0, $sellingPrice - $purchasePrice);
                @endphp
                <article class="flex flex-col rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    @if ($product->imageUrl())
                        <img src="{{ $product->imageUrl() }}" alt="" class="mb-3 aspect-square w-full rounded-xl object-cover">
                    @endif

                    <h2 class="truncate text-base font-bold uppercase tracking-wide text-gray-900">{{ $product->name }}</h2>

                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('member.products.purchase_price') }}</dt>
                            <dd class="font-semibold text-amber-600">${{ number_format($purchasePrice, 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('member.products.selling_price') }}</dt>
                            <dd class="font-semibold text-rose-600">${{ number_format($sellingPrice, 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('member.products.profit') }}</dt>
                            <dd class="font-semibold text-emerald-600">${{ number_format($profit, 2) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4">
                        @if ($isDistributed)
                            <span class="inline-flex w-full items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3 text-sm font-semibold text-emerald-700">
                                {{ __('member.products.already_distributed') }}
                            </span>
                        @elseif (auth()->user()->canSelfDistribute())
                            <form method="POST" action="{{ route('member.products.distributions.store') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center justify-center rounded-xl bg-gray-900 px-3 py-3 text-sm font-semibold text-white transition hover:bg-gray-800"
                                >
                                    {{ __('member.products.distribute') }}
                                </button>
                            </form>
                        @else
                            <span class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-3 py-3 text-sm text-gray-500">
                                {{ __('member.products.distribution_locked') }}
                            </span>
                        @endif
                    </div>
                </article>
            @endforeach
        </section>
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
@endsection
