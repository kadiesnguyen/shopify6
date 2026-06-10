@extends('layouts.member')

@section('title', __('member.nav.my'))
@section('portal_gray_bg', '1')
@section('full_bleed', '1')

@section('content')
    <div class="pb-4">
        <div
            class="rounded-b-2xl bg-cover bg-center bg-no-repeat px-4 pb-8 pt-6 text-white"
            style="background-image: url('{{ asset('images/portal/header-bg.png') }}'); background-color: #0f172a;"
        >
            <div class="flex items-center gap-3">
                <img
                    src="https://api.dicebear.com/7.x/avataaars/svg?seed={{ urlencode($user->user_code ?? $user->id) }}"
                    alt=""
                    class="size-14 rounded-full border-2 border-white/50 object-cover"
                >
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ $user->name }}</p>
                    <p class="truncate text-sm text-white/80">{{ $user->email }}</p>
                    <p class="text-xs text-white/60">ID: {{ $user->user_code ?? str_pad((string) $user->id, 6, '0', STR_PAD_LEFT) }}</p>
                </div>
            </div>
        </div>

        <div class="px-4 -mt-4">
            <div class="rounded-xl bg-white shadow-sm">
                <x-member.order-status-icons :status-counts="$statusCounts" />
            </div>
        </div>

        @if ($user->wallet)
            <div class="mt-4 px-4">
                <div class="space-y-2 rounded-xl bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">{{ __('member.my.pending_payment_amount') }}</span>
                        <span class="font-bold text-rose-600">${{ number_format($pendingPaymentTotal, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">{{ __('member.my.balance') }}</span>
                        <span class="font-bold text-gray-900">${{ number_format($user->wallet->balance, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">{{ __('member.my.total_income') }}</span>
                        <span class="text-gray-900">${{ number_format($totalIncome, 2) }}</span>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <a href="{{ route('member.wallet.recharge') }}" class="flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 py-3 text-sm font-medium text-white">
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

        <div class="mt-3 px-4">
            <x-member.payment-warning-marquee />
        </div>

        <div class="mt-3 px-4">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                @if ($user->shop)
                    <x-member.menu-link :href="route('member.shop-dashboard.index')" icon="layout-dashboard" :label="__('member.my.shop_dashboard')" />
                    <x-member.menu-link :href="route('member.seller.orders.index')" icon="clipboard-list" :label="__('member.my.order_management')" />
                @endif
                <x-member.menu-link :href="route('member.profile.show')" icon="user" icon-color="text-blue-600" icon-bg="bg-blue-50" :label="__('member.my.personal')" />
                <x-member.menu-link :href="route('member.shipping.index')" icon="map-pin" icon-color="text-orange-600" icon-bg="bg-orange-50" :label="__('member.my.shipping_address')" />
                <x-member.menu-link :href="route('member.wallet.fund-records')" icon="wallet" icon-color="text-violet-600" icon-bg="bg-violet-50" :label="__('member.my.transactions')" />
                <x-member.menu-link :href="route('member.wallet.fund-records')" icon="bar-chart" icon-color="text-cyan-600" icon-bg="bg-cyan-50" :label="__('member.my.financial_report')" />
                <x-member.menu-link :href="route('member.contract.show')" icon="file-text" icon-color="text-gray-600" icon-bg="bg-gray-50" :label="__('member.my.about')" />
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
