@extends('layouts.member')

@section('title', __('member.profile.login_password'))
@section('full_bleed', '1')
@section('portal_gray_bg', '1')

@section('content')
    <div class="min-h-[var(--app-height,100dvh)] bg-gray-50 pb-24">
        <header class="sticky top-14 z-10 flex items-center justify-center border-b border-gray-100 bg-white px-4 py-3">
            <a href="{{ route('member.profile.show') }}" class="absolute left-2 flex size-10 items-center justify-center text-gray-700 no-underline">
                <x-member.icon name="chevron-left" class="size-6" />
            </a>
            <h1 class="text-base font-semibold text-gray-900">{{ __('member.profile.login_password') }}</h1>
        </header>

        <form method="POST" action="{{ route('member.profile.password.update') }}" class="portal-wallet-form mt-2">
            @csrf
            @method('PUT')

            <div class="bg-white">
                @foreach ([
                    ['name' => 'current_password', 'label' => __('member.profile.current_password'), 'placeholder' => __('member.profile.current_password_placeholder')],
                    ['name' => 'password', 'label' => __('member.profile.new_password'), 'placeholder' => __('member.profile.new_password_placeholder')],
                    ['name' => 'password_confirmation', 'label' => __('member.profile.confirm_password'), 'placeholder' => __('member.profile.confirm_password_placeholder')],
                ] as $field)
                    <div class="border-b border-gray-100 px-4 py-4">
                        <label for="{{ $field['name'] }}" class="mb-2 block text-sm font-medium text-gray-900">{{ $field['label'] }}</label>
                        <input
                            id="{{ $field['name'] }}"
                            name="{{ $field['name'] }}"
                            type="password"
                            autocomplete="{{ $field['name'] === 'current_password' ? 'current-password' : 'new-password' }}"
                            placeholder="{{ $field['placeholder'] }}"
                            class="portal-plain-input"
                            required
                        >
                        @error($field['name'])<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>

            <div class="p-4">
                <button type="submit" class="w-full rounded-lg bg-emerald-600 py-3 font-medium text-white hover:bg-emerald-700 active:opacity-90">
                    {{ __('member.profile.save_password') }}
                </button>
            </div>
        </form>
    </div>
@endsection
