@extends('layouts.member')

@section('title', __('member.products.management'))

@section('content')
    <header class="mb-4">
        <a href="{{ route('member.my.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-gray-600 no-underline hover:text-emerald-600">
            <x-member.icon name="chevron-left" class="size-4" />
            {{ __('member.back') }}
        </a>
        <h1 class="text-lg font-semibold text-gray-900">{{ __('member.products.management') }}</h1>
    </header>

    <form method="GET" class="mb-4">
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

    @if ($products->isEmpty())
        <x-ui.empty-state :title="__('member.no_products')" class="rounded-xl bg-gray-50" />
    @else
        <section class="space-y-3">
            @foreach ($products as $product)
                <x-member.product-list-item :product="$product" detail-from="manage" />
            @endforeach
        </section>
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
@endsection
