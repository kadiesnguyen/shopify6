@extends('layouts.member')

@section('title', __('member.shop_hub.info_title'))
@section('back_url', route('member.shop-hub.menu'))
@section('portal_gray_bg', '1')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <h1 class="mb-4 text-lg font-bold text-gray-900">{{ __('member.shop_hub.info_title') }}</h1>

    <form method="POST" action="{{ route('member.shop-hub.info.update') }}" enctype="multipart/form-data" class="portal-wallet-form space-y-3">
        @csrf
        @method('PUT')

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
            <form method="POST" action="{{ route('member.shop-hub.info.update') }}" enctype="multipart/form-data" id="shop-logo-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="name" value="{{ old('name', $shop->name) }}">
                <input type="hidden" name="contact_name" value="{{ old('contact_name', $user->name) }}">
                <input type="hidden" name="phone" value="{{ old('phone', $user->phone) }}">
                <label class="flex cursor-pointer items-center justify-between border-b border-gray-50 px-4 py-4">
                    <span class="text-sm font-medium text-gray-900">{{ __('member.shop_hub.logo') }}</span>
                    <span class="flex items-center gap-2">
                        <img
                            src="{{ $shop->displayLogoUrl() ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed='.urlencode((string) $user->id) }}"
                            alt=""
                            class="size-14 rounded-full border border-gray-200 object-cover"
                        >
                        <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
                    </span>
                    <input type="file" name="logo" accept="image/*" class="hidden" onchange="document.getElementById('shop-logo-form').submit()">
                </label>
            </form>

            <div class="border-b border-gray-50 px-4 py-4">
                <label for="shop-name" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.shop_hub.shop_name') }}</label>
                <input id="shop-name" name="name" type="text" value="{{ old('name', $shop->name) }}" class="portal-plain-input" required>
                @error('name')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="border-b border-gray-50 px-4 py-4">
                <p class="text-sm font-medium text-gray-900">{{ __('member.shop_hub.industry') }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ $shop->industryLabel() }}</p>
            </div>

            <div class="border-b border-gray-50 px-4 py-4">
                <p class="text-sm font-medium text-gray-900">{{ __('member.shop_hub.industry_rate') }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ $shop->industryRate() !== null ? $shop->industryRate().'%' : '—' }}</p>
            </div>

            <div class="border-b border-gray-50 px-4 py-4">
                <p class="text-sm font-medium text-gray-900">{{ __('member.shop_hub.categories') }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ $shop->businessCategoryLabels() }}</p>
            </div>

            <div class="border-b border-gray-50 px-4 py-4">
                <label for="description" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.shop_hub.description') }}</label>
                <textarea id="description" name="description" rows="3" class="portal-plain-input">{{ old('description', $shop->description) }}</textarea>
                @error('description')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="border-b border-gray-50 px-4 py-4">
                <label for="keywords" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.shop_hub.keywords') }}</label>
                <input id="keywords" name="keywords" type="text" value="{{ old('keywords', $shop->keywords) }}" class="portal-plain-input">
                @error('keywords')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="border-b border-gray-50 px-4 py-4">
                <label for="contact_name" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.shop_hub.contact') }}</label>
                <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name', $user->name) }}" class="portal-plain-input" required>
                @error('contact_name')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="border-b border-gray-50 px-4 py-4">
                <label for="phone" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.shop_hub.phone') }}</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" class="portal-plain-input" required>
                @error('phone')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="px-4 py-4">
                <label for="address" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.shop_hub.address') }}</label>
                <input id="address" name="address" type="text" value="{{ old('address', $shop->address) }}" class="portal-plain-input">
                @error('address')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white">
            {{ __('member.shop_hub.save') }}
        </button>
    </form>
@endsection
