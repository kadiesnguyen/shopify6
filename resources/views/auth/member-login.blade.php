@extends('layouts.auth-portal')

@section('title', __('auth_portal.login_title'))

@section('content')
    <x-auth.portal-brand />

    <h1 class="mb-6 text-center text-2xl font-bold text-slate-900 sm:mb-8">{{ __('auth_portal.login_title') }}</h1>

    <form method="POST" action="{{ route('auth.login') }}" class="portal-wallet-form space-y-5">
        @csrf

        <div>
            <label for="login" class="mb-1.5 block text-sm font-medium text-slate-800">{{ __('auth_portal.login_label') }}</label>
            <input
                id="login"
                name="login"
                type="text"
                value="{{ old('login') }}"
                placeholder="{{ __('auth_portal.login_placeholder') }}"
                required
                autofocus
                autocomplete="username"
                class="auth-field"
            >
            @error('login')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <x-auth.password-input
            id="password"
            name="password"
            :label="__('auth_portal.password_label')"
            :placeholder="__('auth_portal.password_placeholder')"
        />

        <div class="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
            <p class="text-slate-600">
                {{ __('auth_portal.no_account') }}
                <a href="{{ route('auth.register') }}" class="font-medium text-brand underline decoration-brand/30 underline-offset-2 hover:decoration-brand">
                    {{ __('auth_portal.register_link') }}
                </a>
            </p>
            <button
                type="button"
                @click="$dispatch('open-guest-chat', { forgot: true })"
                class="font-medium text-brand hover:underline sm:shrink-0"
            >
                {{ __('auth_portal.forgot_password') }}
            </button>
        </div>

        <button type="submit" class="w-full rounded-lg bg-brand py-3.5 text-base font-semibold text-white shadow-sm hover:bg-brand-dark">
            {{ __('messages.login') }}
        </button>
    </form>
@endsection
