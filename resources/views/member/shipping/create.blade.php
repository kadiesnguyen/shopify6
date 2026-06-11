@extends('layouts.member')

@section('title', __('member.shipping.add_title'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    <div class="min-h-[var(--app-height,100dvh)] bg-gray-50">
        <header class="sticky top-0 z-10 flex items-center bg-black px-4 py-3 text-white">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('member.my.index') }}" class="flex items-center gap-1.5">
                <x-member.icon name="chevron-left" class="size-5" />
                <span class="text-sm">{{ __('member.back') }}</span>
            </a>
            <h1 class="absolute left-1/2 -translate-x-1/2 text-base font-semibold">{{ __('member.shipping.add_title') }}</h1>
        </header>

        <form method="POST" action="{{ route('member.shipping.store') }}" class="portal-wallet-form mt-2 bg-white">
            @csrf
            @if ($redirect)
                <input type="hidden" name="redirect" value="{{ $redirect }}">
            @endif

            <div class="divide-y divide-gray-100">
                <div class="px-4 py-4">
                    <input name="recipient_name" value="{{ old('recipient_name') }}" required placeholder="{{ __('member.shipping.recipient_placeholder') }}" class="portal-plain-input">
                    @error('recipient_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="px-4 py-4">
                    <input name="phone" value="{{ old('phone') }}" required placeholder="{{ __('member.shipping.phone_placeholder') }}" class="portal-plain-input">
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="px-4 py-4">
                    <input name="country" value="{{ old('country', 'Việt Nam') }}" required placeholder="{{ __('member.shipping.country_placeholder') }}" class="portal-plain-input">
                    @error('country')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="px-4 py-4">
                    <input name="state" value="{{ old('state') }}" required placeholder="{{ __('member.shipping.state_placeholder') }}" class="portal-plain-input">
                    @error('state')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="px-4 py-4">
                    <input name="city" value="{{ old('city') }}" required placeholder="{{ __('member.shipping.city_placeholder') }}" class="portal-plain-input">
                    @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="px-4 py-4">
                    <input name="address_line" value="{{ old('address_line') }}" required placeholder="{{ __('member.shipping.address_placeholder') }}" class="portal-plain-input">
                    @error('address_line')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 px-4 py-4 text-sm text-gray-800">
                    <input type="checkbox" name="is_default" value="1" checked class="rounded border-gray-300 text-emerald-600">
                    {{ __('member.shipping.default') }}
                </label>
            </div>

            <div class="p-4">
                <button type="submit" class="w-full rounded-lg bg-emerald-600 py-3 font-medium text-white hover:bg-emerald-700">
                    {{ __('member.shipping.save_address') }}
                </button>
            </div>
        </form>
    </div>
@endsection
