@extends('layouts.member')

@section('title', __('member.settings.change_account_title'))
@section('full_bleed', '1')
@section('portal_gray_bg', '1')

@section('content')
    @php
        $showPhoneTab = $user->canEditPhone();
        $showEmailTab = $user->canEditEmail();
        $defaultTab = $showPhoneTab ? 'phone' : 'email';
    @endphp

    <div
        x-data="{ tab: @js(old('tab', request('tab', $defaultTab))) }"
        class="min-h-[var(--app-height,100dvh)] pb-24"
    >
        <header class="sticky top-14 z-10 flex items-center justify-center border-b border-gray-100 bg-white px-4 py-3">
            <a href="{{ route('member.settings.index') }}" class="absolute left-2 flex size-10 items-center justify-center text-gray-700 no-underline" aria-label="{{ __('member.back') }}">
                <x-member.icon name="chevron-left" class="size-6" />
            </a>
            <h1 class="text-base font-semibold text-gray-900">{{ __('member.settings.change_account_title') }}</h1>
        </header>

        @if ($showPhoneTab && $showEmailTab)
            <div class="flex border-b border-gray-100 bg-white">
                <button
                    type="button"
                    @click="tab = 'phone'"
                    :class="tab === 'phone' ? 'border-b-2 border-emerald-600 text-emerald-600' : 'text-gray-500'"
                    class="flex-1 py-3 text-sm font-medium"
                >
                    {{ __('member.settings.tab_phone') }}
                </button>
                <button
                    type="button"
                    @click="tab = 'email'"
                    :class="tab === 'email' ? 'border-b-2 border-emerald-600 text-emerald-600' : 'text-gray-500'"
                    class="flex-1 py-3 text-sm font-medium"
                >
                    {{ __('member.settings.tab_email') }}
                </button>
            </div>
        @endif

        @if ($showPhoneTab)
            <form
                x-show="tab === 'phone'"
                x-cloak
                method="POST"
                action="{{ route('member.profile.phone.update') }}"
                class="portal-wallet-form mt-2"
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="tab" value="phone">

                <div class="bg-white">
                    <div class="border-b border-gray-100 px-4 py-4">
                        <label for="change_phone" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.settings.phone_label') }}</label>
                        <input
                            id="change_phone"
                            name="phone"
                            type="tel"
                            value="{{ old('phone', $user->phone) }}"
                            placeholder="{{ __('member.profile.phone_placeholder') }}"
                            class="portal-plain-input"
                            required
                        >
                        @error('phone')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="border-b border-gray-100 px-4 py-4">
                        <label for="change_phone_password" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.profile.current_password') }}</label>
                        <input
                            id="change_phone_password"
                            name="current_password"
                            type="password"
                            placeholder="{{ __('member.profile.current_password_placeholder') }}"
                            class="portal-plain-input"
                            required
                            autocomplete="current-password"
                        >
                        @error('current_password')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="p-4">
                    <button type="submit" class="w-full rounded-lg bg-emerald-600 py-3 font-medium text-white hover:bg-emerald-700 active:opacity-90">
                        {{ __('member.settings.submit') }}
                    </button>
                </div>
            </form>
        @endif

        @if ($showEmailTab)
            <form
                x-show="tab === 'email'"
                x-cloak
                method="POST"
                action="{{ route('member.profile.email.update') }}"
                class="portal-wallet-form mt-2"
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="tab" value="email">

                <div class="bg-white">
                    <div class="border-b border-gray-100 px-4 py-4">
                        <label for="change_email" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.settings.email_label') }}</label>
                        <input
                            id="change_email"
                            name="email"
                            type="email"
                            value="{{ old('email', $user->canEditEmail() ? '' : $user->email) }}"
                            placeholder="{{ __('member.profile.email_placeholder') }}"
                            class="portal-plain-input"
                            required
                        >
                        @error('email')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="border-b border-gray-100 px-4 py-4">
                        <label for="change_email_password" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.profile.current_password') }}</label>
                        <input
                            id="change_email_password"
                            name="current_password"
                            type="password"
                            placeholder="{{ __('member.profile.current_password_placeholder') }}"
                            class="portal-plain-input"
                            required
                            autocomplete="current-password"
                        >
                        @error('current_password')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="p-4">
                    <button type="submit" class="w-full rounded-lg bg-emerald-600 py-3 font-medium text-white hover:bg-emerald-700 active:opacity-90">
                        {{ __('member.settings.submit') }}
                    </button>
                </div>
            </form>
        @endif

        @if (! $showPhoneTab && ! $showEmailTab)
            <p class="px-4 py-8 text-center text-sm text-gray-500">{{ __('member.settings.change_account_unavailable') }}</p>
        @endif
    </div>
@endsection
