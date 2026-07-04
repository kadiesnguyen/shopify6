@extends('layouts.member')

@section('title', __('member.shop_hub.sub_accounts'))
@section('back_url', route('member.shop-hub.menu'))

@section('content')
    <h1 class="mb-4 text-lg font-bold text-gray-900">{{ __('member.shop_hub.sub_accounts') }}</h1>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <div class="mb-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        @forelse ($accounts as $account)
            <div class="flex items-center justify-between border-b border-gray-50 px-4 py-3 last:border-0">
                <div>
                    <p class="font-medium text-gray-900">{{ $account->name }}</p>
                    <p class="text-sm text-gray-500">{{ $account->username }}@if($account->phone) · {{ $account->phone }}@endif</p>
                </div>
                <form method="POST" action="{{ route('member.shop-hub.sub-accounts.destroy', $account) }}" onsubmit="return confirm(@js(__('member.shop_hub.sub_account_delete_confirm')))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600">{{ __('member.shop_hub.delete') }}</button>
                </form>
            </div>
        @empty
            <p class="px-4 py-6 text-center text-sm text-gray-500">{{ __('member.shop_hub.sub_accounts_empty') }}</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('member.shop-hub.sub-accounts.store') }}" class="portal-wallet-form space-y-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        @csrf
        <h2 class="text-sm font-semibold text-gray-900">{{ __('member.shop_hub.add_sub_account') }}</h2>
        <input name="name" type="text" class="portal-plain-input" placeholder="{{ __('member.shop_hub.sub_account_name') }}" required>
        <input name="username" type="text" class="portal-plain-input" placeholder="{{ __('member.shop_hub.sub_account_username') }}" required>
        <input name="phone" type="text" class="portal-plain-input" placeholder="{{ __('member.shop_hub.phone') }}">
        <input name="password" type="password" class="portal-plain-input" placeholder="{{ __('member.shop_hub.sub_account_password') }}" required>
        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white">{{ __('member.shop_hub.save') }}</button>
    </form>
@endsection
