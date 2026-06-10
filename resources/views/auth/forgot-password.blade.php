@extends('layouts.auth-portal')

@section('title', __('auth_portal.forgot_password'))

@section('content')
    <x-auth.portal-brand />

    <h1 class="mb-4 text-center text-2xl font-bold text-slate-900">{{ __('auth_portal.forgot_password') }}</h1>
    <p class="mb-8 text-center text-sm text-slate-600">{{ __('chat.forgot_password_intro') }}</p>

    <button
        type="button"
        @click="$dispatch('open-guest-chat', { forgot: true })"
        class="w-full rounded-lg bg-brand py-3.5 text-base font-semibold text-white shadow-sm hover:bg-brand-dark"
    >
        {{ __('chat.title') }}
    </button>

    <p class="mt-8 text-center text-sm">
        <a href="{{ route('auth.login') }}" class="font-medium text-brand hover:underline">{{ __('auth_portal.back_to_login') }}</a>
    </p>
@endsection

@section('after_chat')
    <div x-data x-init="$nextTick(() => $dispatch('open-guest-chat', { forgot: true }))"></div>
@endsection
