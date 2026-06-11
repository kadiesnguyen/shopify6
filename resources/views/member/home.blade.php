@extends('layouts.member')

@section('title', __('member.nav.home'))
@section('full_bleed', '1')

@section('content')
    <x-member.banner-carousel :banners="$banners" />

    <x-member.portal-marquee />

    <x-member.quick-actions />

    <form method="GET" action="{{ route('member.home') }}" class="mt-3 space-y-2 px-4">
        <x-member.search-field
            name="q"
            :value="request('q')"
            :placeholder="__('member.search.products')"
            :autocomplete="true"
            suggest-target="product"
            suggest-context="portal"
            icon="search"
        />
        <x-member.search-field
            name="shop"
            :value="request('shop')"
            :placeholder="__('member.search.shops')"
            :autocomplete="true"
            suggest-target="shop"
            suggest-context="portal"
            hidden-field-name="shop_id"
            :hidden-field-value="request('shop_id')"
            icon="store"
        />
    </form>

    <section class="px-4 pt-4">
        <h2 class="mb-3 text-base font-bold text-gray-900">{{ __('member.products_for_you') }}</h2>

        @if ($products->isEmpty())
            <x-ui.empty-state :title="__('member.no_products')" class="rounded-xl bg-gray-50" />
        @else
            <div class="ui-content-grid">
                @foreach ($products as $product)
                    <x-member.product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </section>
@endsection
