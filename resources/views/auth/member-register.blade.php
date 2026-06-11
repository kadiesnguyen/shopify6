@extends('layouts.auth-portal')

@section('title', __('auth_portal.register_title'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('auth_portal.register_title') }}</h1>
        <p class="text-sm leading-relaxed text-slate-600 sm:max-w-[11rem] sm:text-right">
            {{ __('auth_portal.has_account') }}
            <a href="{{ route('auth.login') }}" class="font-medium text-brand hover:underline">
                {{ __('auth_portal.back_to_login') }}
            </a>
        </p>
    </div>

    <form method="POST" action="{{ route('auth.register') }}" class="portal-wallet-form space-y-5">
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
            :label="__('auth_portal.password_register_label')"
        />

        <x-auth.password-input
            id="password_confirmation"
            name="password_confirmation"
            :label="__('auth_portal.password_confirm_label')"
            :placeholder="__('auth_portal.password_confirm_placeholder')"
        />

        <label class="flex items-start gap-2.5 text-sm leading-relaxed text-slate-700">
            <input
                type="checkbox"
                name="terms"
                value="1"
                @checked(old('terms'))
                required
                class="mt-0.5 size-4 shrink-0 rounded border-slate-300 text-brand focus:ring-brand"
            >
            <span>{{ __('auth_portal.terms_label') }}</span>
        </label>
        @error('terms')
            <p class="-mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <button type="submit" class="w-full rounded-lg bg-brand py-3.5 text-base font-semibold text-white shadow-sm hover:bg-brand-dark">
            {{ __('messages.register') }}
        </button>
    </form>

    <p class="mt-8 text-center text-sm leading-relaxed text-slate-600">
        {!! __('auth_portal.register_footer', [
            'login' => '<a href="'.route('auth.login').'" class="font-medium text-brand hover:underline">'.__('auth_portal.register_footer_login').'</a>',
        ]) !!}
    </p>
@endsection
