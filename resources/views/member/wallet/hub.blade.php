@extends('layouts.member')

@section('title', __('member.wallet.hub_title'))
@section('back_url', route('member.my.index'))

@section('content')
    @php
        $wallet = auth()->user()->wallet;
        $balance = (float) ($wallet?->balance ?? 0);
    @endphp

    <h1 class="mb-4 text-lg font-bold text-gray-900">{{ __('member.wallet.hub_title') }}</h1>

    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <p class="text-sm text-gray-500">{{ __('member.my.balance') }}</p>
        <p class="text-2xl font-bold text-gray-900">${{ number_format($balance, 2) }}</p>
    </div>

    <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <x-member.menu-link :href="route('member.wallet.recharge')" icon="wallet" icon-color="text-emerald-600" icon-bg="bg-emerald-50" :label="__('member.my.recharge')" />
        <x-member.menu-link :href="route('member.wallet.withdrawal')" icon="banknote" icon-color="text-rose-600" icon-bg="bg-rose-50" :label="__('member.my.withdraw')" />
        <x-member.menu-link :href="route('member.wallet.fund-records')" icon="file-text" icon-color="text-blue-600" icon-bg="bg-blue-50" :label="__('member.my.transactions')" />
        <x-member.menu-link :href="route('member.wallet.withdrawal-records')" icon="clipboard-list" icon-color="text-amber-600" icon-bg="bg-amber-50" :label="__('member.wallet.withdrawal_records')" />
    </div>
@endsection
