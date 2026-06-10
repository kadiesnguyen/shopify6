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

    @if ($wallet)
        <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm text-gray-500">{{ __('member.my.balance') }}</p>
            <p class="text-lg font-bold text-gray-900">${{ number_format($wallet->balance, 2) }}</p>
        </div>
    @endif

    <form method="GET" class="mb-4">
        <x-member.search-field name="q" :value="request('q')" :placeholder="__('member.search.products_alt')" icon="search" />
    </form>

    @if ($products->isEmpty())
        <x-ui.empty-state :title="__('member.no_products')" class="rounded-xl bg-gray-50" />
    @else
        <section class="space-y-3">
            @foreach ($products as $product)
                @php $isDistributed = $distributedIds->contains($product->id); @endphp
                <article class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <div class="flex gap-3">
                        @if ($product->imageUrl())
                            <img src="{{ $product->imageUrl() }}" alt="" class="size-16 rounded-lg object-cover">
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-gray-900">{{ $product->name }}</p>
                            <p class="mt-1 text-sm text-emerald-600">${{ number_format($product->selling_price, 2) }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __('member.products.distribution_cost') }}: ${{ number_format($product->purchase_price, 2) }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">{{ __('member.stock') }}: {{ $product->stock }}</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        @if ($isDistributed)
                            <span class="inline-flex w-full items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                                {{ __('member.products.already_distributed') }}
                            </span>
                        @elseif (auth()->user()->canSelfDistribute())
                            @php
                                $canAfford = $wallet && (float) $wallet->balance >= (float) $product->purchase_price;
                            @endphp
                            <form method="POST" action="{{ route('member.products.distributions.store') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <button
                                    type="submit"
                                    @disabled(! $canAfford)
                                    @class([
                                        'inline-flex w-full items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition',
                                        'bg-emerald-600 text-white hover:bg-emerald-700' => $canAfford,
                                        'cursor-not-allowed bg-gray-200 text-gray-500' => ! $canAfford,
                                    ])
                                >
                                    <x-member.icon name="package" class="size-4" />
                                    {{ __('member.products.distribute') }}
                                </button>
                            </form>
                            @unless ($canAfford)
                                <p class="mt-2 text-xs text-rose-600">
                                    {{ __('member.products.insufficient_balance_distribution', ['amount' => number_format($product->purchase_price, 2)]) }}
                                </p>
                            @endunless
                        @else
                            <span class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500">
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
