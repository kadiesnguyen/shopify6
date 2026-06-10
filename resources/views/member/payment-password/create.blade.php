@extends('layouts.member')

@section('title', __('member.payment_password.title'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    <div class="min-h-screen bg-gray-50">
        <header class="sticky top-0 z-10 flex items-center bg-black px-4 py-3 text-white">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('member.home') }}" class="flex items-center gap-1.5">
                <x-member.icon name="chevron-left" class="size-5" />
                <span class="text-sm">{{ __('member.back') }}</span>
            </a>
            <h1 class="absolute left-1/2 -translate-x-1/2 text-base font-semibold">{{ __('member.payment_password.title') }}</h1>
        </header>

        @if (session('status'))
            <div class="mx-4 mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('member.payment-password.store') }}" class="mt-2">
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
                        class="w-full border-0 bg-transparent p-0 text-sm text-gray-900 outline-none placeholder:text-gray-400"
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
                        class="w-full border-0 bg-transparent p-0 text-sm text-gray-900 outline-none placeholder:text-gray-400"
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

                <button type="submit" class="w-full rounded-lg bg-gray-500 py-3 font-medium text-white hover:bg-gray-600 active:opacity-90">
                    {{ __('member.payment_password.submit') }}
                </button>
            </div>
        </form>
    </div>
@endsection
