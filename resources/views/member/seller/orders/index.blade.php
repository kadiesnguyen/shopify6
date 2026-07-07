@extends('layouts.member')

@section('title', __('member.my.seller_orders_title'))
@section('full_bleed', '1')

@section('content')
    <div class="px-4 pt-3 pb-2">
        <h1 class="text-lg font-bold text-gray-900">{{ __('member.my.seller_orders_title') }}</h1>
    </div>

    <x-member.order-type-tabs active="seller" />

    <x-member.order-tabs
        :status="$status"
        :status-counts="$statusCounts"
        route-name="member.seller.orders.index"
        :show-pending-payment="false"
        :tab-labels="[
            'awaiting_pickup' => __('member.my.merchant_shipping'),
            'shipped' => __('member.orders.seller_status_shipped'),
            'completed' => __('member.my.merchant_completed'),
        ]"
        :hidden-tabs="['received']"
    />

    @if ($errors->has('order'))
        <p class="px-4 pb-2 text-sm text-[#fa3534]">{{ $errors->first('order') }}</p>
    @endif

    <x-member.filter-toolbar
        class="px-4 pb-3"
        :search-value="request('q')"
        :search-placeholder="__('member.orders.search_alt')"
        :sort-value="request('sort', 'new')"
        :sort-options="[
            'new' => __('member.orders.sort_newest'),
            'old' => __('member.orders.sort_oldest'),
        ]"
    >
        @if (request('status'))
            <x-slot:hidden>
                <input type="hidden" name="status" value="{{ request('status') }}">
            </x-slot:hidden>
        @endif
    </x-member.filter-toolbar>

    @if ($orders->isEmpty())
        <div class="py-24 text-center text-gray-400">
            <x-member.icon name="file-text" class="mx-auto mb-2 size-12 opacity-50" />
            <p>{{ __('member.orders.empty_alt') }}</p>
        </div>
    @else
        <div class="flex flex-col gap-3 px-4 pb-4">
            @foreach ($orders as $order)
                <x-member.seller-order-card :order="$order" />
            @endforeach
        </div>
        <div class="px-4 pb-4">{{ $orders->links() }}</div>
    @endif
@endsection
