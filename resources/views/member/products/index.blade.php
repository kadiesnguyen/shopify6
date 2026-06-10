@extends('layouts.member')

@section('title', __('member.nav.products'))

@section('content')
    <header class="mb-4">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('member.nav.products') }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">{{ __('member.products.category_subtitle') }}</p>
    </header>

    <div class="mb-4 space-y-3">
        @if (auth()->user()->isShop())
            <x-member.link-card
                :href="route('member.products.distributions.index')"
                icon="warehouse"
                :label="__('member.products.distribution_center')"
            />
            <x-member.link-card
                :href="route('member.products.manage.index')"
                icon="package"
                :label="__('member.products.management')"
            />
        @endif
    </div>

    <form method="GET" class="mb-4 space-y-3">
        <x-member.search-field
            name="q"
            :value="request('q')"
            :placeholder="__('member.search.products_alt')"
            icon="search"
        />
        <x-member.search-field
            name="shop"
            :value="request('shop')"
            :placeholder="__('member.search.shops_alt')"
            icon="store"
        />
    </form>

    @if ($products->isEmpty())
        <x-ui.empty-state :title="__('member.no_products')" class="rounded-xl bg-gray-50" />
    @else
        <section class="space-y-3">
            @foreach ($products as $product)
                <x-member.product-list-item :product="$product" />
            @endforeach
        </section>
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
@endsection
