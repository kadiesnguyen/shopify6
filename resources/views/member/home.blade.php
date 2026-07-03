@extends('layouts.member')

@section('title', __('member.nav.home'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    {{-- Reference: hero banner with search bar overlaid on top --}}
    <div class="relative">
        <x-member.banner-carousel :banners="$banners" />
        <x-member.home-header />
    </div>

    <x-member.quick-actions />

    {{-- Reference: secondary brand banner strip below the quick menu --}}
    <div class="px-[7px] pt-4">
        <img src="{{ asset('images/portal/banners/brand-banner.jpg') }}" alt="" class="w-full" loading="lazy">
    </div>

    <div class="mt-3.5 h-3.5 bg-[#f4f4f4]"></div>

    <section class="bg-[#f5f5f5] pb-4">
        <div class="flex items-center gap-2 px-3 pb-1 pt-2">
            <h2 class="text-lg font-bold text-[#444]">{{ __('member.guess_you_like') }}</h2>
            <span class="rounded-bl-[10px] rounded-tr-[10px] bg-[#ff4444] px-1.5 py-px text-[10px] text-white">{{ __('member.pick_quality') }}</span>
        </div>

        @if ($products->isEmpty())
            <x-ui.empty-state :title="__('member.no_products')" class="mx-3 rounded-xl bg-white" />
        @else
            <div class="grid grid-cols-2 gap-2 px-2">
                @foreach ($products as $index => $product)
                    <x-member.product-card :product="$product" :image-eager="$index < 4" />
                @endforeach
            </div>
        @endif
    </section>
@endsection
