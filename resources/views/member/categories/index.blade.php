@extends('layouts.member')

@section('title', __('member.nav.categories'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    <div class="flex min-h-[calc(100vh-50px)] flex-col">
        <div class="px-4 py-2.5">
            <h1 class="text-center text-[15px] font-normal text-gray-600">{{ __('member.nav.categories') }}</h1>
        </div>

        <div class="flex min-h-0 flex-1">
            {{-- Reference: gray sidebar, active item enlarged in orange, no border marker --}}
            <aside class="w-28 shrink-0 overflow-y-auto bg-[#f8f8f8]">
                @foreach ($categories as $category)
                    <a
                        href="{{ route('member.categories.index', ['category' => $category->id]) }}"
                        @class([
                            'flex h-14 items-center justify-center px-1 text-center leading-tight no-underline',
                            'bg-white text-[16px] font-semibold text-[#ff4c15]' => $activeCategory?->id === $category->id,
                            'text-[14px] text-[#555]' => $activeCategory?->id !== $category->id,
                        ])
                    >
                        <span class="line-clamp-2">{{ $category->name }}</span>
                    </a>
                @endforeach
            </aside>

            <div class="min-w-0 flex-1 overflow-y-auto bg-white">
                @if ($banners->isNotEmpty())
                    <div class="px-3.5 pt-3.5">
                        <x-member.banner-carousel :banners="$banners" :rounded="true" />
                    </div>
                @endif

                @if ($products->isEmpty())
                    <x-ui.empty-state :title="__('member.no_products')" class="m-3.5 rounded-xl bg-gray-50 py-8" />
                @else
                    {{-- Reference: 3-col grid of image + name inside a white panel --}}
                    <div class="flex flex-wrap p-3.5">
                        @foreach ($products as $product)
                            <a
                                href="{{ route('member.products.show', ['product' => $product, 'from' => 'category']) }}"
                                class="mb-4 flex w-1/3 flex-col items-center px-1 no-underline"
                            >
                                <span class="block size-14 overflow-hidden rounded-[5px] bg-gray-50">
                                    @if ($product->imageUrl())
                                        <img src="{{ $product->imageUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                                    @endif
                                </span>
                                <span class="mt-1.5 w-full truncate text-center text-[13px] text-gray-900">{{ $product->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
