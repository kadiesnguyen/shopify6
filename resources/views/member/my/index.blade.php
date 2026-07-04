@extends('layouts.member')

@section('title', __('member.nav.my'))
@section('portal_gray_bg', '1')
@section('hide_portal_header', '1')
@section('full_bleed', '1')
@section('hide_status_alert', '1')

@section('content')
    @php
        $profileImageUrl = $user->isShop()
            ? ($user->shop?->displayLogoUrl() ?: $user->avatarUrl())
            : $user->avatarUrl();
        $isBusiness = $user->shop?->isBusiness() ?? false;
    @endphp

    <div class="bg-[#f4f4f4] pb-4">
        <x-member.toast :message="$toastMessage ?? null" />
        {{-- Reference top-bg: green brand header image --}}
        <div
            class="relative h-[186px] bg-[#333] bg-cover bg-center px-4 pt-8"
            style="background-image: url('{{ asset('images/portal/my-header-bg.jpg') }}');"
        >
            <div class="absolute right-4 top-24 flex items-center gap-3">
                <a href="{{ route('member.chat.index') }}" class="text-white/95" aria-label="{{ __('member.nav.support') }}">
                    <x-member.icon name="chat-bubble" class="size-6" />
                </a>
                <a href="{{ route('member.settings.index') }}" class="text-white/95" aria-label="{{ __('member.settings.title') }}">
                    <x-member.icon name="settings" class="size-6" />
                </a>
            </div>

            <div class="flex items-center gap-3 pt-12">
                <span class="relative inline-block">
                    <img
                        src="{{ $profileImageUrl ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed='.urlencode((string) $user->id) }}"
                        alt=""
                        class="size-[54px] rounded-full border border-white/40 bg-white object-cover"
                    >
                    @if ($isBusiness)
                        <span class="absolute -bottom-0.5 -right-0.5 grid size-4 place-items-center rounded-full bg-amber-500 text-[9px] font-bold text-white">V</span>
                    @endif
                </span>
                <div class="min-w-0">
                    <p class="truncate text-lg font-bold text-white">{{ $user->isShop() ? ($user->shop?->name ?? $user->name) : $user->name }}</p>
                    @if ($isBusiness)
                        <span class="mt-1 inline-block rounded-full border border-[#f5d199] bg-[#fdf6ec] px-2.5 py-px text-xs text-[#f5a623]">{{ __('member.my.merchant_badge') }}</span>
                    @elseif ($user->isShop())
                        <span class="mt-1 inline-block rounded-full bg-white/25 px-2.5 py-px text-xs text-white">{{ __('member.shop_application.type_personal') }}</span>
                    @else
                        <span class="mt-1 inline-block rounded-full bg-white/25 px-2.5 py-px text-xs text-white">{{ __('member.my.regular_user') }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Reference: white orders card overlapping the header --}}
        <div class="relative z-10 -mt-6 px-3.5">
            <div class="rounded-[11px] bg-white">
                <x-member.order-status-icons
                    :status-counts="$statusCounts"
                    :orders-route="$user->isShop() ? 'member.seller.orders.index' : 'member.orders.index'"
                    :merchant="$user->isShop()"
                />
            </div>
        </div>

        {{-- Admin-configurable warning marquee (hidden when admin clears the text) --}}
        <div class="mt-3.5 px-3.5 empty:hidden">
            <x-member.payment-warning-marquee />
        </div>

        {{-- Reference: promo banner between orders and menu grid --}}
        <div class="mt-3.5 px-3.5">
            <img src="{{ asset('images/portal/banners/my-promo.jpg') }}" alt="" class="w-full rounded-[11px]" loading="lazy">
        </div>

        <x-member.my-menu-grid :user="$user" />

        @if ($feedProducts->isNotEmpty())
            <section class="mt-4">
                <div class="flex items-center justify-center gap-2 pb-2">
                    <h2 class="text-lg font-bold text-[#444]">{{ __('member.guess_you_like') }}</h2>
                    <span class="rounded-bl-[10px] rounded-tr-[10px] bg-[#ff4444] px-1.5 py-px text-[10px] text-white">{{ __('member.pick_quality') }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 px-2">
                    @foreach ($feedProducts as $index => $product)
                        <x-member.product-card :product="$product" :image-eager="$index < 2" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
