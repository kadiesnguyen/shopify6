@extends('layouts.member')

@section('title', __('member.products.distribution_center'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    <div class="min-h-[var(--app-height,100dvh)] bg-gray-50 pb-[calc(6rem+env(safe-area-inset-bottom))]">
        <header class="sticky top-0 z-10 bg-black text-white">
            <div class="relative flex items-center justify-between px-4 py-3">
                <a href="{{ route('member.products.index') }}" class="relative z-10 flex shrink-0 items-center gap-1.5 text-white/90 no-underline">
                    <x-member.icon name="chevron-left" class="size-5" />
                    <span class="text-sm font-medium">{{ __('member.back') }}</span>
                </a>
                <span class="pointer-events-none absolute left-1/2 max-w-[55%] -translate-x-1/2 truncate text-center text-base font-semibold">{{ __('member.products.distribution_center') }}</span>
                <a href="{{ route('member.products.manage.index') }}" class="relative z-10 shrink-0 text-sm font-medium text-white/90 no-underline">
                    {{ __('member.products.goods') }}
                </a>
            </div>
        </header>

        <div class="space-y-3 p-4">
            @if ($errors->any())
                <div class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-800">{{ $errors->first() }}</div>
            @endif

            <section class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-sm ring-1 ring-gray-100">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                    <x-member.icon name="wallet" class="size-5" />
                </span>
                <span class="text-sm text-gray-900">{{ __('member.wallet.current_balance') }}:</span>
                <span class="ml-auto text-base font-bold text-red-600">${{ number_format($balance, 2) }}</span>
            </section>

            <x-member.filter-toolbar
                :search-value="request('q')"
                :search-placeholder="__('member.products.distribution_search')"
                :search-autocomplete="true"
                search-suggest-target="product"
                search-suggest-context="distribution"
                :sort-value="$sort"
                :sort-options="[
                    'best' => __('member.products.sort_best'),
                    'new' => __('member.orders.sort_newest'),
                    'old' => __('member.orders.sort_oldest'),
                ]"
            />

            @if ($products->isEmpty())
                <x-ui.empty-state :title="__('member.no_products')" class="rounded-xl bg-white" />
            @else
                <section
                    id="distribution-grid"
                    class="grid grid-cols-2 gap-3"
                    data-next-page="{{ $products->currentPage() + 1 }}"
                    data-has-more="{{ $products->hasMorePages() ? '1' : '0' }}"
                >
                    @include('member.products.partials.distribution-cards', ['products' => $products, 'distributedIds' => $distributedIds])
                </section>
                <div id="distribution-loader" class="hidden py-2 text-center text-xs text-gray-500">{{ __('ui.loading') }}</div>
                <div id="distribution-trigger" class="h-1"></div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const distributeLabels = {
                alreadyDistributed: @js(__('member.products.already_distributed')),
                error: @js(__('member.products.distribute_failed')),
            };

            document.addEventListener('submit', async (event) => {
                const form = event.target.closest('.distribution-form');

                if (!form) {
                    return;
                }

                event.preventDefault();

                const button = form.querySelector('button[type="submit"]');

                if (!button || button.disabled) {
                    return;
                }

                button.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': form.querySelector('[name=_token]')?.value ?? '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(form),
                    });

                    if (response.ok) {
                        const payload = await response.json().catch(() => ({}));

                        if (payload?.redirect) {
                            window.location.href = payload.redirect;

                            return;
                        }

                        const slot = form.parentElement;

                        if (slot) {
                            slot.innerHTML = `<span class="inline-flex w-full items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1.5 text-xs font-semibold text-emerald-700">${distributeLabels.alreadyDistributed}</span>`;
                        }

                        return;
                    }

                    button.disabled = false;
                    const payload = await response.json().catch(() => null);
                    const message = payload?.message
                        ?? Object.values(payload?.errors ?? {})?.flat()?.[0]
                        ?? distributeLabels.error;

                    window.alert(message);
                } catch (_) {
                    button.disabled = false;
                    form.submit();
                }
            });

            const grid = document.getElementById('distribution-grid');
            const trigger = document.getElementById('distribution-trigger');
            const loader = document.getElementById('distribution-loader');

            if (!grid || !trigger || !loader) {
                return;
            }

            let nextPage = Number(grid.dataset.nextPage || '2');
            let hasMore = grid.dataset.hasMore === '1';
            let loading = false;

            const observer = new IntersectionObserver((entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) {
                    return;
                }

                loadMore();
            }, { rootMargin: '280px 0px' });

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
                        grid.insertAdjacentHTML('beforeend', payload.html);
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
