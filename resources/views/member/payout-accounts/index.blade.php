@extends('layouts.member')

@section('title', __('member.payout_accounts.title'))
@section('back_url', route('member.shop-hub.menu'))

@section('content')
    <h1 class="mb-4 text-lg font-bold text-gray-900">{{ __('member.payout_accounts.title') }}</h1>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <div class="mb-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        @forelse ($accounts as $account)
            <div class="flex items-center justify-between border-b border-gray-50 px-4 py-3 last:border-0">
                <div>
                    <p class="font-medium text-gray-900">{{ $account->label ?: ($account->type === 'bank' ? $account->bank_name : $account->crypto_currency) }}</p>
                    <p class="text-sm text-gray-500">
                        @if ($account->type === 'bank')
                            {{ $account->account_name }} · {{ $account->account_number }}
                        @else
                            {{ $account->crypto_network }} · {{ $account->crypto_address }}
                        @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('member.payout-accounts.destroy', $account) }}" onsubmit="return confirm(@js(__('member.payout_accounts.delete_confirm')))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600">{{ __('member.shop_hub.delete') }}</button>
                </form>
            </div>
        @empty
            <p class="px-4 py-6 text-center text-sm text-gray-500">{{ __('member.payout_accounts.empty') }}</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('member.payout-accounts.store') }}" class="portal-wallet-form space-y-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100" x-data="{ type: 'bank' }">
        @csrf
        <h2 class="text-sm font-semibold text-gray-900">{{ __('member.payout_accounts.add') }}</h2>
        <select name="type" class="portal-plain-input" x-model="type">
            <option value="bank">{{ __('member.payout_accounts.type_bank') }}</option>
            <option value="crypto">{{ __('member.payout_accounts.type_crypto') }}</option>
        </select>
        <input name="label" type="text" class="portal-plain-input" placeholder="{{ __('member.payout_accounts.label') }}">
        <template x-if="type === 'bank'">
            <div class="space-y-3">
                <input name="bank_name" type="text" class="portal-plain-input" placeholder="{{ __('member.wallet.bank_name_placeholder') }}">
                <input name="account_name" type="text" class="portal-plain-input" placeholder="{{ __('member.wallet.bank_account_name_placeholder') }}">
                <input name="account_number" type="text" class="portal-plain-input" placeholder="{{ __('member.wallet.bank_account_number_placeholder') }}">
            </div>
        </template>
        <template x-if="type === 'crypto'">
            <div class="space-y-3">
                <input name="crypto_currency" type="text" class="portal-plain-input" placeholder="{{ __('member.wallet.currency_placeholder') }}">
                <input name="crypto_network" type="text" class="portal-plain-input" placeholder="{{ __('member.wallet.network_placeholder') }}">
                <input name="crypto_address" type="text" class="portal-plain-input" placeholder="{{ __('member.wallet.crypto_address_placeholder') }}">
            </div>
        </template>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_default" value="1" class="rounded border-gray-300">
            {{ __('member.payout_accounts.default') }}
        </label>
        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white">{{ __('member.shop_hub.save') }}</button>
    </form>
@endsection
