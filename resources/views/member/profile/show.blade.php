@extends('layouts.member')

@section('title', __('member.profile.title'))
@section('full_bleed', '1')
@section('portal_gray_bg', '1')

@section('content')
    @php
        $avatarUrl = $user->avatarUrl();
        $profileRedirect = route('member.profile.show');
    @endphp

    <div
        x-data="{
            nameModalOpen: @js($errors->has('name')),
            phoneModalOpen: @js($errors->has('phone')),
            emailModalOpen: @js($errors->has('email') || $errors->has('current_password')),
        }"
        class="min-h-[var(--app-height,100dvh)] bg-gray-50 pb-24"
    >
        <header class="sticky top-14 z-10 flex items-center justify-center border-b border-gray-100 bg-white px-4 py-3">
            <a href="{{ route('member.my.index') }}" class="absolute left-2 flex size-10 items-center justify-center text-gray-700 no-underline" aria-label="{{ __('member.back') }}">
                <x-member.icon name="chevron-left" class="size-6" />
            </a>
            <h1 class="text-base font-semibold text-gray-900">{{ __('member.profile.title') }}</h1>
        </header>

        <div class="m-4 space-y-1 rounded-xl bg-white p-4 shadow-sm">
            <form method="POST" action="{{ route('member.profile.avatar.update') }}" enctype="multipart/form-data" id="avatar-form">
                @csrf
                <label class="flex w-full cursor-pointer items-center justify-between border-b border-gray-100 py-3 text-left">
                    <span class="text-sm text-gray-900">{{ __('member.profile.avatar') }}</span>
                    <span class="flex items-center gap-2">
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="" class="size-12 rounded-full border border-gray-200 object-cover">
                        @else
                            <span class="flex size-12 items-center justify-center rounded-full border border-gray-200 bg-gray-100 text-gray-400">
                                <x-member.icon name="user" class="size-6" />
                            </span>
                        @endif
                        <input id="avatar-input" type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" onchange="if (this.files.length) document.getElementById('avatar-form').submit()">
                        <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
                    </span>
                </label>
            </form>
            @error('avatar')<p class="pb-2 text-xs text-red-600">{{ $message }}</p>@enderror

            <button type="button" @click="nameModalOpen = true" class="flex w-full items-center justify-between border-b border-gray-100 py-3 text-left">
                <span class="text-sm text-gray-900">{{ __('member.profile.full_name') }}</span>
                <span class="flex items-center gap-1.5">
                    @if ($user->isNameVerified())
                        <span class="flex items-center gap-1 text-sm text-green-600">
                            {{ __('member.profile.verified') }}
                            <x-member.icon name="circle-check" class="size-4" />
                        </span>
                    @endif
                    <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
                </span>
            </button>

            @if ($user->canEditPhone())
                <button type="button" @click="phoneModalOpen = true" class="flex w-full items-center justify-between border-b border-gray-100 py-3 text-left">
                    <span class="text-sm text-gray-900">{{ __('member.profile.phone_verification') }}</span>
                    <span class="flex items-center gap-1.5">
                        @if ($user->isPhoneVerified())
                            <span class="flex items-center gap-1 text-sm text-green-600">
                                {{ __('member.profile.verified') }}
                                <x-member.icon name="circle-check" class="size-4" />
                            </span>
                        @else
                            <span class="text-sm text-gray-400">{{ __('member.profile.not_verified') }}</span>
                        @endif
                        <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
                    </span>
                </button>
            @else
                <div class="flex items-center justify-between border-b border-gray-100 py-3">
                    <span class="text-sm text-gray-900">{{ __('member.profile.phone_verification') }}</span>
                    <span class="flex items-center gap-1.5">
                        @if ($user->isPhoneVerified())
                            <span class="flex items-center gap-1 text-sm text-green-600">
                                {{ __('member.profile.verified') }}
                                <x-member.icon name="circle-check" class="size-4" />
                            </span>
                        @else
                            <span class="text-sm text-gray-400">{{ __('member.profile.not_verified') }}</span>
                        @endif
                        <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
                    </span>
                </div>
            @endif

            @if ($user->canEditEmail())
                <button type="button" @click="emailModalOpen = true" class="flex w-full items-center justify-between border-b border-gray-100 py-3 text-left">
                    <span class="text-sm text-gray-900">{{ __('member.profile.email') }}</span>
                    <span class="flex items-center gap-1.5">
                        <span class="text-sm text-gray-400">{{ __('member.profile.not_verified') }}</span>
                        <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
                    </span>
                </button>
            @else
                <div class="flex items-center justify-between border-b border-gray-100 py-3">
                    <span class="text-sm text-gray-900">{{ __('member.profile.email') }}</span>
                    <span class="flex items-center gap-1.5">
                        @if ($user->isEmailVerified())
                            <span class="flex items-center gap-1 text-sm text-green-600">
                                {{ __('member.profile.verified') }}
                                <x-member.icon name="circle-check" class="size-4" />
                            </span>
                        @else
                            <span class="text-sm text-gray-400">{{ __('member.profile.not_verified') }}</span>
                        @endif
                        <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
                    </span>
                </div>
            @endif

            <a href="{{ route('member.payment-password.create', ['redirect' => $profileRedirect]) }}" class="flex items-center justify-between border-b border-gray-100 py-3 text-sm text-gray-900 no-underline">
                <span>{{ __('member.profile.payment_password') }}</span>
                <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
            </a>

            <a href="{{ route('member.profile.password.edit') }}" class="flex items-center justify-between border-b border-gray-100 py-3 text-sm text-gray-900 no-underline">
                <span>{{ __('member.profile.login_password') }}</span>
                <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
            </a>

            <a href="{{ route('member.payment-password.edit') }}" class="flex items-center justify-between py-3 text-sm text-gray-900 no-underline">
                <span>{{ __('member.profile.funds_password') }}</span>
                <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
            </a>
        </div>

        <div
            x-show="nameModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-6 md:left-1/2 md:w-full md:max-w-[420px] md:-translate-x-1/2"
            @click.self="nameModalOpen = false"
        >
            <div class="w-full max-w-xs rounded-xl bg-white p-4 shadow-lg">
                <h3 class="mb-3 text-base font-semibold text-gray-900">{{ __('member.profile.full_name') }}</h3>
                <form method="POST" action="{{ route('member.profile.name.update') }}">
                    @csrf
                    @method('PUT')
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        placeholder="{{ __('member.profile.full_name_placeholder') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none ring-1 ring-inset ring-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                        required
                    >
                    @error('name')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    <div class="mt-4 flex gap-2">
                        <button type="button" @click="nameModalOpen = false" class="flex-1 rounded-lg border border-gray-300 py-2 text-sm text-gray-700">
                            {{ __('member.profile.cancel') }}
                        </button>
                        <button type="submit" class="flex-1 rounded-lg bg-emerald-600 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            {{ __('member.profile.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            x-show="phoneModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-6 md:left-1/2 md:w-full md:max-w-[420px] md:-translate-x-1/2"
            @click.self="phoneModalOpen = false"
        >
            <div class="w-full max-w-xs rounded-xl bg-white p-4 shadow-lg">
                <h3 class="mb-3 text-base font-semibold text-gray-900">{{ __('member.profile.phone_verification') }}</h3>
                <form method="POST" action="{{ route('member.profile.phone.update') }}">
                    @csrf
                    @method('PUT')
                    <input
                        type="tel"
                        name="phone"
                        value="{{ old('phone', $user->phone) }}"
                        placeholder="{{ __('member.profile.phone_placeholder') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none ring-1 ring-inset ring-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                        required
                    >
                    @error('phone')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    <div class="mt-4 flex gap-2">
                        <button type="button" @click="phoneModalOpen = false" class="flex-1 rounded-lg border border-gray-300 py-2 text-sm text-gray-700">
                            {{ __('member.profile.cancel') }}
                        </button>
                        <button type="submit" class="flex-1 rounded-lg bg-emerald-600 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            {{ __('member.profile.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            x-show="emailModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-6 md:left-1/2 md:w-full md:max-w-[420px] md:-translate-x-1/2"
            @click.self="emailModalOpen = false"
        >
            <div class="w-full max-w-xs rounded-xl bg-white p-4 shadow-lg">
                <h3 class="mb-3 text-base font-semibold text-gray-900">{{ __('member.profile.email') }}</h3>
                <form method="POST" action="{{ route('member.profile.email.update') }}">
                    @csrf
                    @method('PUT')
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $user->canEditEmail() ? '' : $user->email) }}"
                        placeholder="{{ __('member.profile.email_placeholder') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none ring-1 ring-inset ring-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                        required
                    >
                    @error('email')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    <input
                        type="password"
                        name="current_password"
                        placeholder="{{ __('member.profile.current_password_placeholder') }}"
                        class="mt-3 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none ring-1 ring-inset ring-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                        required
                        autocomplete="current-password"
                    >
                    @error('current_password')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    <div class="mt-4 flex gap-2">
                        <button type="button" @click="emailModalOpen = false" class="flex-1 rounded-lg border border-gray-300 py-2 text-sm text-gray-700">
                            {{ __('member.profile.cancel') }}
                        </button>
                        <button type="submit" class="flex-1 rounded-lg bg-emerald-600 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            {{ __('member.profile.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
