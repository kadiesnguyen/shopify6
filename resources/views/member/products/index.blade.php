@extends('layouts.member')

@section('title', __('member.nav.products'))

@section('content')
    <header class="mb-4">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('member.nav.products') }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">{{ __('member.products.category_subtitle') }}</p>
    </header>

    <div class="mb-4 space-y-3">
        @if (auth()->user()->isShop())
            <x-member.link-card
                :href="route('member.products.distributions.index')"
                icon="warehouse"
                :label="__('member.products.distribution_center')"
            />
            <x-member.link-card
                :href="route('member.products.manage.index')"
                icon="package"
                :label="__('member.products.goods')"
            />
        @endif
    </div>

    <form method="GET" class="mb-4 space-y-3">
        <x-member.search-field
            name="q"
            :value="request('q')"
            :placeholder="__('member.search.products_alt')"
            :autocomplete="true"
            suggest-target="product"
            suggest-context="portal"
            icon="search"
        />
        <x-member.search-field
            name="shop"
            :value="request('shop')"
            :placeholder="__('member.search.shops_alt')"
            :autocomplete="true"
            suggest-target="shop"
            suggest-context="portal"
            hidden-field-name="shop_id"
            :hidden-field-value="request('shop_id')"
            icon="store"
        />
    </form>

    @if ($products->isEmpty())
        <x-ui.empty-state :title="__('member.no_products')" class="rounded-xl bg-gray-50" />
    @else
        <section
            id="portal-products-list"
            class="space-y-3"
            data-next-page="{{ $products->currentPage() + 1 }}"
            data-has-more="{{ $products->hasMorePages() ? '1' : '0' }}"
        >
            @include('member.products.partials.portal-product-items', ['products' => $products])
        </section>
        <div id="portal-products-loader" class="mt-3 hidden text-center text-xs text-gray-500">{{ __('ui.loading') }}</div>
        <div id="portal-products-trigger" class="h-1"></div>
    @endif
@endsection

@push('scripts')
    <script>
        (() => {
            const list = document.getElementById('portal-products-list');
            const trigger = document.getElementById('portal-products-trigger');
            const loader = document.getElementById('portal-products-loader');

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
