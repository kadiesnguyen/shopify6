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
            <div
                id="portal-home-products"
                class="grid grid-cols-2 gap-2 px-2"
                data-next-page="{{ $products->currentPage() + 1 }}"
                data-has-more="{{ $products->hasMorePages() ? '1' : '0' }}"
            >
                @include('member.home.partials.product-cards', ['products' => $products, 'imageOffset' => 0])
            </div>
            <div id="portal-home-loader" class="mt-3 hidden px-2 text-center text-xs text-gray-500">{{ __('ui.loading') }}</div>
            <div id="portal-home-trigger" class="h-1"></div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const list = document.getElementById('portal-home-products');
            const trigger = document.getElementById('portal-home-trigger');
            const loader = document.getElementById('portal-home-loader');

            if (!list || !trigger || !loader) {
                return;
            }

            let nextPage = Number(list.dataset.nextPage || '2');
            let hasMore = list.dataset.hasMore === '1';
            let loading = false;

            const observer = new IntersectionObserver((entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) {
                    return;
                }

                loadMore();
            }, { rootMargin: '260px 0px' });

            if (hasMore) {
                observer.observe(trigger);
            }

            async function loadMore() {
                if (loading || !hasMore) {
                    return;
                }

                loading = true;
                loader.classList.remove('hidden');

                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', String(nextPage));

                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('load failed');
                    }

                    const payload = await response.json();

                    if (payload?.html) {
                        list.insertAdjacentHTML('beforeend', payload.html);
                    }

                    hasMore = Boolean(payload?.has_more);
                    nextPage = Number(payload?.next_page || (nextPage + 1));

                    if (!hasMore) {
                        observer.disconnect();
                    }
                } catch (_) {
                    observer.disconnect();
                } finally {
                    loading = false;
                    loader.classList.add('hidden');
                }
            }
        })();
    </script>
@endpush
