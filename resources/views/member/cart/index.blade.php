@extends('layouts.member')

@section('title', __('member.nav.cart'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')
@section('portal_gray_bg', '1')

@section('content')
    <div class="flex min-h-[calc(100vh-50px)] flex-col bg-[#f4f4f4] pb-24" x-data="{ manage: false }">
        <h1 class="py-2.5 text-center text-[15px] font-normal text-gray-600">{{ __('member.nav.cart') }}</h1>

        <div class="flex items-center justify-between px-3.5 pb-2">
            <span class="text-[17px] text-gray-900">{{ __('member.cart.total_items', ['count' => $itemCount]) }}</span>
            <button type="button" @click="manage = !manage" class="text-[15px] text-gray-900">{{ __('member.cart.manage') }}</button>
        </div>

        @if ($groups->isEmpty())
            <div class="flex flex-1 flex-col items-center justify-center py-16 text-gray-400">
                <x-member.icon name="shopping-cart" class="mb-2 size-12 opacity-40" />
                <p>{{ __('member.cart.empty') }}</p>
                <a href="{{ route('member.home') }}" class="mt-4 text-sm font-medium text-[#ff4c15]">{{ __('member.cart.go_shopping') }}</a>
            </div>
        @else
            <div class="space-y-3.5 px-3.5">
                @foreach ($groups as $shopName => $items)
                    <section class="rounded-[8px] bg-white p-3.5">
                        <div class="flex items-center gap-2.5 pb-2">
                            <span class="size-[18px] shrink-0 rounded-full border border-gray-200"></span>
                            <span class="text-[17px] text-gray-900">{{ $shopName }}</span>
                            <x-member.icon name="chevron-right" class="size-4 text-gray-300" />
                        </div>

                        @foreach ($items as $item)
                            <div class="flex items-center gap-2.5 py-2.5">
                                <form method="POST" action="{{ route('member.cart.update', $item) }}" class="shrink-0">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="selected" value="{{ $item->selected ? '0' : '1' }}">
                                    <button type="submit" @class([
                                        'grid size-[18px] place-items-center rounded-full border',
                                        'border-[#fa3534] bg-[#fa3534] text-white' => $item->selected,
                                        'border-gray-200 bg-white' => ! $item->selected,
                                    ]) aria-label="{{ __('member.cart.select_all') }}">
                                        @if ($item->selected)
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="size-3"><path d="m5 13 4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @endif
                                    </button>
                                </form>

                                <a href="{{ route('member.products.show', $item->product) }}" class="size-[98px] shrink-0 overflow-hidden rounded-[6px] bg-gray-100">
                                    @if ($item->product?->imageUrl())
                                        <img src="{{ $item->product->imageUrl() }}" alt="" class="size-full object-cover">
                                    @endif
                                </a>

                                <div class="min-w-0 flex-1 self-stretch">
                                    <p class="line-clamp-1 text-[15px] text-gray-900">{{ $item->product?->name }}</p>
                                    <p class="mt-1.5 text-[#fa3f3f]"><span class="text-[13px]">$</span><span class="text-[20px]">{{ number_format($item->unitPrice(), 2) }}</span></p>

                                    <div class="mt-2 flex items-center justify-between">
                                        <form method="POST" action="{{ route('member.cart.destroy', $item) }}" x-show="manage" x-cloak>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded border border-gray-200 px-2 py-0.5 text-xs text-gray-500">{{ __('member.cart.remove') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('member.cart.update', $item) }}" class="ml-auto flex items-center overflow-hidden rounded-[4px]">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" name="quantity" value="{{ max(1, $item->quantity - 1) }}" class="grid h-7 w-9 place-items-center bg-[#f9f9f9] text-gray-400">−</button>
                                            <span class="grid h-7 min-w-9 place-items-center border-y border-[#f2f2f2] bg-white px-1 text-center text-[13px] text-[#fa3f3f]">{{ $item->quantity }}</span>
                                            <button type="submit" name="quantity" value="{{ $item->quantity + 1 }}" class="grid h-7 w-9 place-items-center bg-[#f9f9f9] text-gray-600">+</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endforeach
            </div>
        @endif
    </div>

    @if ($groups->isNotEmpty())
        {{-- Reference grace-footer: select all + total + red pill pay button --}}
        <div class="fixed inset-x-0 bottom-[calc(50px+env(safe-area-inset-bottom))] z-40 flex h-[50px] items-center bg-white pl-3.5 pr-2.5 md:left-1/2 md:max-w-[420px] md:-translate-x-1/2">
            <form method="POST" action="{{ route('member.cart.select-all') }}" class="flex items-center gap-2">
                @csrf
                <input type="hidden" name="selected" value="1">
                <button type="submit" class="flex items-center gap-2 text-[15px] text-gray-900">
                    <span class="size-[18px] rounded-full border border-gray-200"></span>
                    {{ __('member.cart.select_all') }}
                </button>
            </form>
            <p class="ml-auto mr-3 text-[15px] text-gray-900">
                {{ __('member.orders.grand_total') }}
                <span class="text-[#fa3f3f]"><span class="text-[13px]">$</span><span class="text-[20px]">{{ number_format($total, 2) }}</span></span>
            </p>
            <form method="POST" action="{{ route('member.cart.checkout') }}">
                @csrf
                <button type="submit" class="h-11 rounded-full bg-[#fa3534] px-7 text-[17px] text-white">{{ __('member.cart.checkout') }}</button>
            </form>
        </div>
    @endif
@endsection
