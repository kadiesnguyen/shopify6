@extends('layouts.member')

@section('title', __('member.shop_hub.title'))
@section('back_url', route('member.my.index'))

@section('content')
    <h1 class="mb-4 text-lg font-bold text-gray-900">{{ __('member.shop_hub.title') }}</h1>

    @include('member.my.partials.shop-data', ['stats' => $stats])

    <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <x-member.menu-link :href="route('member.products.manage.index')" icon="layout-grid" icon-color="text-violet-600" icon-bg="bg-violet-50" :label="__('member.products.management')" />
        <x-member.menu-link :href="route('member.products.distributions.index')" icon="store" icon-color="text-emerald-600" icon-bg="bg-emerald-50" :label="__('member.products.distribution_center')" />
        <x-member.menu-link :href="route('member.seller.orders.index')" icon="clipboard-list" icon-color="text-orange-600" icon-bg="bg-orange-50" :label="__('member.my.order_management')" />
        <x-member.menu-link :href="route('member.financial-report.index')" icon="bar-chart" icon-color="text-cyan-600" icon-bg="bg-cyan-50" :label="__('member.my.financial_report')" />
    </div>
@endsection
