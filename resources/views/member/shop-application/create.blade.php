@extends('layouts.member')

@section('title', __('member.shop_application.title'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    <div
        class="min-h-screen bg-gray-50 pb-8"
        x-data="{ step: 1, sellerType: '{{ old('seller_type', 'personal') }}' }"
    >
        <header class="sticky top-0 z-10 flex items-center bg-black px-4 py-3 text-white">
            <a
                href="{{ route('member.home') }}"
                class="flex items-center gap-1.5"
                x-show="step === 1"
            >
                <x-member.icon name="chevron-left" class="size-5" />
                <span class="text-sm">{{ __('member.shop_application.back_home') }}</span>
            </a>
            <button
                type="button"
                class="flex items-center gap-1.5"
                x-show="step === 2"
                x-cloak
                @click="step = 1"
            >
                <x-member.icon name="chevron-left" class="size-5" />
                <span class="text-sm">{{ __('member.shop_application.back_type') }}</span>
            </button>
            <h1 class="absolute left-1/2 -translate-x-1/2 text-base font-semibold">{{ __('member.shop_application.title') }}</h1>
        </header>

        @if (session('status'))
            <div class="mx-4 mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div x-show="step === 1" class="px-4 pt-6">
            <p class="mb-4 text-sm font-medium text-gray-900">{{ __('member.shop_application.choose_type') }}</p>

            <div class="space-y-3">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-white p-4">
                    <input type="radio" name="seller_type_pick" value="personal" x-model="sellerType" class="mt-1 text-emerald-600">
                    <span>
                        <span class="block text-sm font-semibold text-gray-900">{{ __('member.shop_application.type_personal') }}</span>
                        <span class="mt-1 block text-xs text-gray-500">{{ __('member.shop_application.type_personal_hint') }}</span>
                    </span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-white p-4">
                    <input type="radio" name="seller_type_pick" value="business" x-model="sellerType" class="mt-1 text-emerald-600">
                    <span>
                        <span class="block text-sm font-semibold text-gray-900">{{ __('member.shop_application.type_business') }}</span>
                        <span class="mt-1 block text-xs text-gray-500">{{ __('member.shop_application.type_business_hint') }}</span>
                    </span>
                </label>
            </div>

            <button
                type="button"
                @click="step = 2"
                class="mt-6 w-full rounded-lg bg-emerald-600 py-3 text-sm font-semibold text-white hover:bg-emerald-700"
            >
                {{ __('member.shop_application.continue') }}
            </button>
        </div>

        <form
            x-show="step === 2"
            x-cloak
            method="POST"
            action="{{ route('member.shop-application.store') }}"
            enctype="multipart/form-data"
            class="mt-2"
        >
            @csrf
            <input type="hidden" name="seller_type" :value="sellerType">

            <div class="bg-white">
                <div class="border-b border-gray-100 px-4 py-4">
                    <label class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.shop_application.logo') }}</label>
                    <input type="file" name="logo" accept="image/*" class="w-full text-sm text-gray-600">
                    @error('logo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="border-b border-gray-100 px-4 py-4">
                    <input name="shop_name" value="{{ old('shop_name') }}" required placeholder="{{ __('member.shop_application.shop_name_placeholder') }}" class="w-full border-0 bg-transparent p-0 text-sm outline-none placeholder:text-gray-400">
                    @error('shop_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="border-b border-gray-100 px-4 py-4">
                    <input name="address" value="{{ old('address') }}" required placeholder="{{ __('member.shop_application.address_placeholder') }}" class="w-full border-0 bg-transparent p-0 text-sm outline-none placeholder:text-gray-400">
                    @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="border-b border-gray-100 px-4 py-4">
                    <input name="country" value="{{ old('country') }}" required placeholder="{{ __('member.shop_application.country_placeholder') }}" class="w-full border-0 bg-transparent p-0 text-sm outline-none placeholder:text-gray-400">
                    @error('country')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="border-b border-gray-100 px-4 py-4">
                    <input name="phone" value="{{ old('phone', auth()->user()->phone) }}" required placeholder="{{ __('member.shop_application.phone_placeholder') }}" class="w-full border-0 bg-transparent p-0 text-sm outline-none placeholder:text-gray-400">
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="border-b border-gray-100 px-4 py-4">
                    <input name="real_name" value="{{ old('real_name', auth()->user()->name) }}" required placeholder="{{ __('member.shop_application.real_name_placeholder') }}" class="w-full border-0 bg-transparent p-0 text-sm outline-none placeholder:text-gray-400">
                    @error('real_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="border-b border-gray-100 px-4 py-4">
                    <input name="referral_code" value="{{ old('referral_code') }}" placeholder="{{ __('member.shop_application.referral_placeholder') }}" class="w-full border-0 bg-transparent p-0 text-sm outline-none placeholder:text-gray-400">
                    @error('referral_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="border-b border-gray-100 px-4 py-4">
                    <input name="id_number" value="{{ old('id_number') }}" required placeholder="{{ __('member.shop_application.id_number_placeholder') }}" class="w-full border-0 bg-transparent p-0 text-sm outline-none placeholder:text-gray-400">
                    @error('id_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="border-b border-gray-100 px-4 py-4">
                    <label class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.shop_application.id_front') }}</label>
                    <input type="file" name="id_front" accept="image/*" required class="w-full text-sm text-gray-600">
                    @error('id_front')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="border-b border-gray-100 px-4 py-4">
                    <label class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.shop_application.id_back') }}</label>
                    <input type="file" name="id_back" accept="image/*" required class="w-full text-sm text-gray-600">
                    @error('id_back')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mx-4 mt-4 rounded-xl bg-white p-4 text-xs leading-relaxed text-gray-600">
                <h3 class="mb-2 text-sm font-semibold text-gray-900">{{ __('member.shop_application.terms_title') }}</h3>
                <p>{{ __('member.shop_application.terms_body') }}</p>
            </div>

            <label class="mx-4 mt-4 flex items-start gap-2 text-sm text-gray-800">
                <input type="checkbox" name="terms" value="1" required class="mt-0.5 rounded border-gray-300 text-emerald-600" {{ old('terms') ? 'checked' : '' }}>
                <span>{{ __('member.shop_application.terms_agree') }}</span>
            </label>
            @error('terms')<p class="mx-4 mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

            <div class="p-4">
                <button type="submit" class="w-full rounded-lg bg-emerald-600 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                    {{ __('member.shop_application.submit') }}
                </button>
            </div>
        </form>
    </div>
@endsection
