@extends('layouts.member')

@section('title', __('member.settings.title'))
@section('full_bleed', '1')
@section('portal_gray_bg', '1')

@section('content')
    @php
        $avatarUrl = $user->avatarUrl();
        $displayPhone = $user->phone ?: ($user->registeredViaEmail() ? $user->email : null);
    @endphp

    <div class="min-h-[var(--app-height,100dvh)] pb-24">
        <header class="sticky top-14 z-10 flex items-center justify-center border-b border-gray-100 bg-white px-4 py-3">
            <a href="{{ route('member.my.index') }}" class="absolute left-2 flex size-10 items-center justify-center text-gray-700 no-underline" aria-label="{{ __('member.back') }}">
                <x-member.icon name="chevron-left" class="size-6" />
            </a>
            <h1 class="text-base font-semibold text-gray-900">{{ __('member.settings.title') }}</h1>
        </header>

        <a href="{{ route('member.profile.show') }}" class="mb-5 flex items-center gap-3 border-b border-gray-100 bg-white px-4 py-4 no-underline">
            @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="" class="size-[62px] shrink-0 rounded-full border border-gray-200 object-cover">
            @else
                <span class="flex size-[62px] shrink-0 items-center justify-center rounded-full border border-gray-200 bg-gray-100 text-gray-400">
                    <x-member.icon name="user" class="size-8" />
                </span>
            @endif
            <span class="min-w-0 flex-1">
                <span class="block truncate text-base font-semibold text-gray-900">{{ $user->name ?: $user->username }}</span>
                @if ($displayPhone)
                    <span class="mt-0.5 block truncate text-sm text-gray-500">{{ $displayPhone }}</span>
                @endif
            </span>
            <x-member.icon name="chevron-right" class="size-5 shrink-0 text-gray-300" />
        </a>

        <div class="bg-white">
            <x-member.settings-cell :href="route('member.shipping.index')" :label="__('member.settings.shipping')" />
            <x-member.settings-cell :href="route('member.settings.bind-login')" :label="__('member.settings.bind_login')" />
            <x-member.settings-cell :href="route('member.settings.change-account')" :label="__('member.settings.change_account')" />
            <x-member.settings-cell :href="route('member.profile.password.edit')" :label="__('member.settings.login_password')" />
            <x-member.settings-cell
                :href="$user->hasPaymentPassword() ? route('member.payment-password.edit') : route('member.payment-password.create')"
                :label="$user->hasPaymentPassword() ? __('member.settings.payment_password') : __('member.settings.payment_password_create')"
            />
            <form method="POST" action="{{ route('auth.logout') }}" id="settings-logout-cell" class="hidden">
                @csrf
            </form>
            <button
                type="submit"
                form="settings-logout-cell"
                onclick="return confirm(@js(__('member.settings.logout_confirm')))"
                class="flex w-full items-center justify-between border-b border-gray-100 bg-white px-4 py-3.5 text-left text-sm text-gray-900"
            >
                <span>{{ __('member.settings.logout_cell') }}</span>
                <x-member.icon name="chevron-right" class="size-5 shrink-0 text-gray-300" />
            </button>
        </div>

        <div class="mt-5 bg-white">
            <x-member.settings-cell :href="route('member.settings.language')" :label="__('member.settings.language')" />
            <x-member.settings-cell :href="route('landing.about')" :label="__('member.settings.about')" />
        </div>

        <form method="POST" action="{{ route('auth.logout') }}" class="mt-8 px-4">
            @csrf
            <button type="submit" class="w-full py-3 text-center text-base font-medium text-red-600">
                {{ __('member.settings.logout') }}
            </button>
        </form>
    </div>
@endsection
