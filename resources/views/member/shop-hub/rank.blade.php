@extends('layouts.member')

@section('title', __('member.shop_hub.rank_title'))
@section('back_url', route('member.shop-hub.menu'))
@section('portal_gray_bg', '1')

@section('content')
    <h1 class="mb-4 text-lg font-bold text-gray-900">{{ __('member.shop_hub.rank_title') }}</h1>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-50 px-4 py-4">
            <p class="text-sm text-gray-500">{{ __('member.shop_hub.rank_type') }}</p>
            <p class="mt-1 text-base font-semibold text-gray-900">
                {{ $shop?->merchantLevel() ?? 'L1' }} · {{ $shop?->isBusiness() ? __('member.my.merchant_badge') : __('member.shop_application.type_personal') }}
            </p>
        </div>
        <div class="border-b border-gray-50 px-4 py-4">
            <p class="text-sm text-gray-500">{{ __('member.actions.loyalty') }}</p>
            <p class="mt-1 text-2xl font-bold text-violet-700">{{ number_format($shop?->loyaltyPoints((int) $stats['completed_orders']) ?? 0) }}</p>
        </div>
        <div class="border-b border-gray-50 px-4 py-4">
            <p class="text-sm text-gray-500">{{ __('member.shop_hub.rank_credit') }}</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">{{ number_format($stats['credit_score']) }}</p>
        </div>
        <div class="border-b border-gray-50 px-4 py-4">
            <p class="text-sm text-gray-500">{{ __('member.shop_hub.rank_stars') }}</p>
            <div class="mt-2 flex gap-0.5">
                @for ($i = 1; $i <= 5; $i++)
                    <span class="{{ $i <= round($stats['star_rating']) ? 'text-amber-400' : 'text-gray-300' }} text-xl">★</span>
                @endfor
            </div>
        </div>
        <div class="px-4 py-4">
            <p class="text-sm text-gray-500">{{ __('member.shop_hub.rank_followers') }}</p>
            <p class="mt-1 text-base font-semibold text-gray-900">{{ number_format($stats['followers']) }}</p>
        </div>
    </div>
@endsection
