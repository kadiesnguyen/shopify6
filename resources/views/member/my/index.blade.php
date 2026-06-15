@extends('layouts.member')

@section('title', __('member.nav.my'))
@section('portal_gray_bg', '1')
@section('full_bleed', '1')

@section('content')
    @php
        $profileImageUrl = $user->isShop()
            ? ($user->shop?->displayLogoUrl() ?: $user->avatarUrl())
            : $user->avatarUrl();
    @endphp

    <div class="pb-4">
        <div
            class="rounded-b-2xl bg-cover bg-center bg-no-repeat px-4 pb-8 pt-6 text-white"
            style="background-image: url('{{ asset('images/portal/header-bg.png') }}'); background-color: #0f172a;"
        >
            <div class="flex items-center gap-3">
                @if ($profileImageUrl)
                    <img src="{{ $profileImageUrl }}" alt="" class="size-14 rounded-full border-2 border-white/50 object-cover">
                @else
                    <img
                        src="https://api.dicebear.com/7.x/avataaars/svg?seed={{ urlencode((string) $user->id) }}"
                        alt=""
                        class="size-14 rounded-full border-2 border-white/50 object-cover"
                    >
                @endif
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ $user->isShop() ? ($user->shop?->name ?? $user->name) : $user->name }}</p>
                    <p class="truncate text-sm text-white/80">{{ $user->phone ?: $user->email }}</p>
                </div>
            </div>
        </div>

        <div class="px-4 -mt-4">
            <div class="rounded-xl bg-white shadow-sm">
                <x-member.order-status-icons
                    :status-counts="$statusCounts"
                    :orders-route="$user->isShop() ? 'member.seller.orders.index' : 'member.orders.index'"
                />
            </div>
        </div>

        @if ($user->wallet)
            <div class="mt-4 px-4">
                <div class="space-y-2 rounded-xl bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">{{ __('member.my.pending_payment_amount') }}</span>
                        <span class="font-bold text-rose-600">${{ number_format($pendingPaymentTotal, 2) }}</span>
                    </div>
                    @if ($user->isShop())
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">{{ __('member.products.profit') }}</span>
                            <span class="font-bold text-emerald-600">${{ number_format($profit, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">{{ __('member.my.total_income') }}</span>
                            <span class="font-bold text-gray-900">${{ number_format($totalIncome, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">{{ __('member.my.balance') }}</span>
                        <span class="font-bold text-gray-900">${{ number_format($walletBalance, 2) }}</span>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <a href="{{ \App\Models\RechargeMethod::memberEntryUrl($user) }}" class="flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 py-3 text-sm font-medium text-white">
                            <x-member.icon name="wallet" class="size-4" />
                            {{ __('member.my.recharge') }}
                        </a>
                        <a href="{{ route('member.wallet.withdrawal') }}" class="flex items-center justify-center gap-1.5 rounded-lg bg-rose-500 py-3 text-sm font-medium text-white">
                            <x-member.icon name="banknote" class="size-4" />
                            {{ __('member.my.withdraw') }}
                        </a>
                    </div>
                </div>
            </div>
        @endif

        @if ($shopStats)
            @include('member.my.partials.shop-data', ['stats' => $shopStats])
        @endif

        <div class="mt-3 px-4">
            <x-member.payment-warning-marquee />
        </div>

        <div class="mt-3 px-4">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                <x-member.menu-link :href="$user->isShop() ? route('member.seller.orders.index') : route('member.orders.index')" icon="clipboard-list" :label="__('member.my.order_management')" />
                <x-member.menu-link :href="route('member.profile.show')" icon="user" icon-color="text-blue-600" icon-bg="bg-blue-50" :label="__('member.my.personal')" />
                <x-member.menu-link :href="route('member.shipping.index')" icon="map-pin" icon-color="text-orange-600" icon-bg="bg-orange-50" :label="__('member.my.shipping_address')" />
                @if ($user->isShop())
                    <x-member.menu-link :href="route('member.financial-report.index')" icon="bar-chart" icon-color="text-cyan-600" icon-bg="bg-cyan-50" :label="__('member.my.financial_report')" />
                @endif
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-between px-4 py-3 text-left transition hover:bg-gray-50">
                        <span class="flex items-center gap-3">
                            <span class="inline-flex size-9 items-center justify-center rounded-full bg-red-50 text-red-600">
                                <x-member.icon name="log-out" class="size-5" />
                            </span>
                            <span class="text-gray-800">{{ __('messages.logout') }}</span>
                        </span>
                        <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
