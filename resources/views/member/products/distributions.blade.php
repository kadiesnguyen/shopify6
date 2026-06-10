@extends('layouts.member')

@section('title', __('member.products.distribution_center'))

@section('content')
    <header class="mb-4">
        <a href="{{ route('member.products.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-gray-600 no-underline hover:text-emerald-600">
            <x-member.icon name="chevron-left" class="size-4" />
            {{ __('member.back') }}
        </a>
        <h1 class="text-lg font-semibold text-gray-900">{{ __('member.products.distribution_center') }}</h1>
    </header>

    <form method="GET" class="mb-4">
        <x-member.search-field name="q" :value="request('q')" :placeholder="__('member.search.products_alt')" icon="search" />
    </form>

    @if ($distributions->isEmpty())
        <x-ui.empty-state :title="__('member.shop_dashboard.no_distributions')" class="rounded-xl bg-gray-50" />
    @else
        <section class="space-y-3">
            @foreach ($distributions as $distribution)
                @php $product = $distribution->product; @endphp
                <article class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <div class="flex gap-3">
                        @if ($product?->imageUrl())
                            <img src="{{ $product->imageUrl() }}" alt="" class="size-16 rounded-lg object-cover">
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-gray-900">{{ $product?->name }}</p>
                            <p class="mt-1 text-sm text-emerald-600">${{ number_format($distribution->selling_price, 2) }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __('member.products.commission') }}:
                                {{ $distribution->commission_type === 'percent' ? $distribution->commission.'%' : '$'.number_format($distribution->commission, 2) }}
                            </p>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>
        <div class="mt-4">{{ $distributions->links() }}</div>
    @endif
@endsection
