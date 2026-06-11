@extends('layouts.member')

@section('title', __('member.profile.payment_password'))
@section('full_bleed', '1')
@section('portal_gray_bg', '1')

@section('content')
    <div class="min-h-[var(--app-height,100dvh)] bg-gray-50 pb-24">
        <header class="sticky top-14 z-10 flex items-center justify-center border-b border-gray-100 bg-white px-4 py-3">
            <a href="{{ $redirect ?: route('member.profile.show') }}" class="absolute left-2 flex size-10 items-center justify-center text-gray-700 no-underline">
                <x-member.icon name="chevron-left" class="size-6" />
            </a>
            <h1 class="text-base font-semibold text-gray-900">{{ __('member.profile.payment_password') }}</h1>
        </header>

        <form method="POST" action="{{ route('member.payment-password.store') }}" class="portal-wallet-form mt-2">
            @csrf
            @if ($redirect)
                <input type="hidden" name="redirect" value="{{ $redirect }}">
            @endif

            <div class="bg-white">
                <div class="border-b border-gray-100 px-4 py-4">
                    <label for="payment_password" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.payment_password.fund_label') }}</label>
                    <input
                        id="payment_password"
                        name="payment_password"
                        type="password"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="off"
                        placeholder="{{ __('member.payment_password.placeholder') }}"
                        class="portal-plain-input"
                        required
                    >
                </div>
                <div class="border-b border-gray-100 px-4 py-4">
                    <label for="payment_password_confirmation" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.payment_password.confirm_label') }}</label>
                    <input
                        id="payment_password_confirmation"
                        name="payment_password_confirmation"
                        type="password"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="off"
                        placeholder="{{ __('member.payment_password.confirm_placeholder') }}"
                        class="portal-plain-input"
                        required
                    >
                </div>
            </div>

            <div class="p-4">
                @if ($errors->any())
                    <div class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">
                        {{ $errors->first() }}
                    </div>
                @endif

                <button type="submit" class="w-full rounded-lg bg-emerald-600 py-3 font-medium text-white hover:bg-emerald-700 active:opacity-90">
                    {{ __('member.payment_password.submit') }}
                </button>
            </div>
        </form>
    </div>
@endsection
