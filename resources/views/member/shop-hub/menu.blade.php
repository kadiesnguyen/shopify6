@extends('layouts.member')

@section('title', __('member.shop_hub.menu_title'))
@section('back_url', route('member.shop-hub.index'))

@section('content')
    @php
        $paymentPasswordRoute = $user->hasPaymentPassword()
            ? route('member.payment-password.edit')
            : route('member.payment-password.create');
        $shopInfoRoute = $user->shop
            ? route('member.shop-hub.info')
            : route('member.shop-application.create');
    @endphp

    <h1 class="mb-4 text-lg font-bold text-gray-900">{{ __('member.shop_hub.menu_title') }}</h1>

    <div class="space-y-4">
        <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
            <h2 class="border-b border-gray-50 px-4 py-3 text-sm font-semibold text-gray-900">{{ __('member.shop_hub.section_shop') }}</h2>
            <x-member.menu-link :href="$shopInfoRoute" icon="user" icon-color="text-sky-600" icon-bg="bg-sky-50" :label="__('member.shop_hub.basic_info')" />
            <x-member.menu-link :href="route('member.financial-report.index')" icon="bar-chart" icon-color="text-cyan-600" icon-bg="bg-cyan-50" :label="__('member.shop_hub.business_detail')" />
            <x-member.menu-link :href="route('member.shop-hub.rank')" icon="crown" icon-color="text-amber-600" icon-bg="bg-amber-50" :label="__('member.shop_hub.merchant_rank')" />
            <x-member.menu-link :href="$shopInfoRoute" icon="store" icon-color="text-emerald-600" icon-bg="bg-emerald-50" :label="__('member.shop_hub.shop_entry')" />
            <x-member.menu-link :href="route('member.shipping.index')" icon="map-pin" icon-color="text-violet-600" icon-bg="bg-violet-50" :label="__('member.shop_hub.return_address')" />
            <x-member.menu-link :href="route('member.chat.index')" icon="chat-bubble" icon-color="text-blue-600" icon-bg="bg-blue-50" :label="__('member.shop_hub.service_info')" />
            <x-member.menu-link :href="route('member.profile.password.edit')" icon="lock" icon-color="text-gray-600" icon-bg="bg-gray-100" :label="__('member.shop_hub.account_password')" />
            <x-member.menu-link :href="$paymentPasswordRoute" icon="lock" icon-color="text-rose-600" icon-bg="bg-rose-50" :label="__('member.shop_hub.withdraw_password')" />
            <x-member.menu-link :href="route('member.shop-hub.sub-accounts.index')" icon="user" icon-color="text-indigo-600" icon-bg="bg-indigo-50" :label="__('member.shop_hub.sub_accounts')" />
        </section>
            <h2 class="border-b border-gray-50 px-4 py-3 text-sm font-semibold text-gray-900">{{ __('member.shop_hub.section_account') }}</h2>
            <x-member.menu-link :href="route('member.wallet.hub')" icon="wallet" icon-color="text-emerald-600" icon-bg="bg-emerald-50" :label="__('member.shop_hub.account_detail')" />
            <x-member.menu-link :href="route('member.wallet.withdrawal')" icon="banknote" icon-color="text-orange-600" icon-bg="bg-orange-50" :label="__('member.shop_hub.withdraw')" />
            <x-member.menu-link :href="route('member.wallet.withdrawal-records')" icon="clipboard-list" icon-color="text-amber-600" icon-bg="bg-amber-50" :label="__('member.shop_hub.withdraw_records')" />
            <x-member.menu-link :href="route('member.wallet.recharge')" icon="plus" icon-color="text-sky-600" icon-bg="bg-sky-50" :label="__('member.shop_hub.recharge')" />
            <x-member.menu-link :href="route('member.wallet.fund-records')" icon="file-text" icon-color="text-violet-600" icon-bg="bg-violet-50" :label="__('member.shop_hub.recharge_records')" />
            <x-member.menu-link :href="route('member.payout-accounts.index')" icon="banknote" icon-color="text-cyan-600" icon-bg="bg-cyan-50" :label="__('member.shop_hub.payout_account')" />
            <x-member.menu-link :href="route('member.wallet.hub')" icon="wallet" icon-color="text-emerald-600" icon-bg="bg-emerald-50" :label="__('member.shop_hub.balance')" />
        </section>

        <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
            <h2 class="border-b border-gray-50 px-4 py-3 text-sm font-semibold text-gray-900">{{ __('member.shop_hub.section_goods') }}</h2>
            <x-member.menu-link :href="route('member.products.manage.index')" icon="layout-grid" icon-color="text-violet-600" icon-bg="bg-violet-50" :label="__('member.shop_hub.product_manage')" />
            <x-member.menu-link :href="route('member.products.distributions.index')" icon="store" icon-color="text-emerald-600" icon-bg="bg-emerald-50" :label="__('member.products.distribution_center')" />
            <x-member.menu-link :href="route('member.financial-report.index')" icon="bar-chart" icon-color="text-cyan-600" icon-bg="bg-cyan-50" :label="__('member.my.financial_report')" />
            <x-member.menu-link :href="route('member.seller.orders.index')" icon="clipboard-list" icon-color="text-orange-600" icon-bg="bg-orange-50" :label="__('member.my.order_management')" />
            <x-member.menu-link :href="route('member.seller.refunds.index')" icon="file-text" icon-color="text-rose-600" icon-bg="bg-rose-50" :label="__('member.shop_hub.refunds')" />
        </section>
    </div>
@endsection
