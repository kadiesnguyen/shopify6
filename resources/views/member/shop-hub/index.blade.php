@extends('layouts.member')

@section('title', __('member.shop_hub.title'))
@section('portal_gray_bg', '1')
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@push('vite')
    @vite(['resources/js/member-charts.js'])
@endpush

@section('content')
    @php
        $shop = $user->shop;
        $logoUrl = $shop?->displayLogoUrl() ?: $user->avatarUrl();
    @endphp

    <div class="bg-[#f4f4f4] pb-4">
        <div class="pointer-events-none fixed inset-y-0 left-0 right-0 z-50 md:left-1/2 md:right-auto md:w-full md:max-w-[420px] md:-translate-x-1/2">
            <a
                href="{{ route('member.home') }}"
                x-data="{
                    top: 72,
                    dragging: false,
                    moved: false,
                    startY: 0,
                    startTop: 0,
                    init() {
                        const saved = localStorage.getItem('shopHubHomeBtnTop');
                        if (saved) this.top = parseInt(saved, 10);
                    },
                    onPointerDown(event) {
                        if (event.button !== 0) return;
                        this.dragging = true;
                        this.moved = false;
                        this.startY = event.clientY;
                        this.startTop = this.top;
                        this.$el.setPointerCapture(event.pointerId);
                    },
                    onPointerMove(event) {
                        if (! this.dragging) return;
                        const delta = event.clientY - this.startY;
                        if (Math.abs(delta) > 4) this.moved = true;
                        const min = 12;
                        const max = window.innerHeight - this.$el.offsetHeight - 58;
                        this.top = Math.min(max, Math.max(min, this.startTop + delta));
                    },
                    onPointerUp(event) {
                        if (! this.dragging) return;
                        this.dragging = false;
                        this.$el.releasePointerCapture(event.pointerId);
                        if (this.moved) {
                            localStorage.setItem('shopHubHomeBtnTop', String(this.top));
                        }
                    },
                    onClick(event) {
                        if (this.moved) {
                            event.preventDefault();
                            this.moved = false;
                        }
                    },
                }"
                :style="'top:' + top + 'px'"
                @pointerdown="onPointerDown($event)"
                @pointermove="onPointerMove($event)"
                @pointerup="onPointerUp($event)"
                @pointercancel="onPointerUp($event)"
                @click="onClick($event)"
                class="pointer-events-auto absolute right-3 flex size-14 touch-none select-none flex-col items-center justify-center gap-0.5 rounded-full bg-blue-600 text-white shadow-lg ring-2 ring-white/70 no-underline active:opacity-90"
                :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
                aria-label="{{ __('member.shop_application.back_home') }}"
            >
                <x-member.icon name="home" class="size-5 shrink-0 pointer-events-none text-white" />
                <span class="max-w-[48px] truncate text-center text-[10px] font-semibold leading-tight pointer-events-none">{{ __('member.nav.home') }}</span>
            </a>
        </div>

        <div
            class="relative h-[140px] bg-[#333] bg-cover bg-center px-4 pt-8"
            style="background-image: url('{{ asset('images/portal/shop-hub-header-bg.png') }}');"
        >
            <div class="flex items-center gap-3 pt-6">
                <img
                    src="{{ $logoUrl ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed='.urlencode((string) $user->id) }}"
                    alt=""
                    class="size-12 rounded-full border-2 border-white/40 bg-white object-cover"
                >
                <div class="min-w-0">
                    <p class="truncate text-lg font-bold text-white">{{ $shop?->name ?? $user->name }}</p>
                    <p class="truncate text-sm text-white/80">{{ __('member.shop_hub.title') }}</p>
                </div>
            </div>
        </div>

        <div class="relative z-10 -mt-5 space-y-3.5 px-3.5">
            <section class="rounded-[11px] bg-white p-4">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-[15px] font-bold text-gray-900">{{ __('member.shop_hub.overview') }}</h2>
                    <button
                        type="button"
                        onclick="location.reload()"
                        class="shrink-0 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] text-gray-500"
                    >
                        {{ __('member.shop_hub.refresh_page') }}
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-[1fr_auto] items-start gap-x-4 gap-y-4">
                    <div>
                        <p class="text-[13px] text-gray-600">{{ __('member.shop_hub.order_count') }}</p>
                        <p class="mt-1 text-[28px] font-bold leading-none text-gray-900">{{ number_format($stats['total_orders']) }}</p>
                    </div>
                    <div class="min-w-0 text-right">
                        <p class="text-[13px] text-gray-600">{{ __('member.shop_hub.available_balance') }}</p>
                        <p class="mt-1 text-[22px] font-bold leading-none text-emerald-600">${{ number_format($stats['available_balance'], 2) }}</p>
                    </div>

                    @if ($stats['frozen_balance'] >= 0.01)
                        <div class="col-span-2 min-w-0 border-t border-gray-100 pt-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-gray-900">{{ __('member.shop_hub.spendable_balance') }}</p>
                                    <p class="text-xs leading-snug text-gray-500">{{ __('member.shop_hub.spendable_hint') }}</p>
                                </div>
                                <p class="shrink-0 whitespace-nowrap text-sm font-semibold text-gray-900">${{ number_format($stats['spendable_balance'], 2) }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="col-span-2">
                        <p class="text-[13px] text-gray-600">{{ __('member.shop_hub.total_revenue') }}</p>
                        <p class="mt-1 text-[22px] font-bold leading-none text-gray-900">${{ number_format($stats['total_sales'], 2) }}</p>
                    </div>

                    <div class="col-span-2">
                        <p class="text-[13px] text-gray-600">{{ __('member.shop_hub.reputation_score') }}</p>
                        <p class="mt-1 text-[28px] font-bold leading-none text-gray-900">{{ number_format($stats['credit_score']) }}</p>
                    </div>
                </div>

                <p class="mt-4 border-t border-gray-100 pt-3 text-[13px] text-gray-600">
                    {{ __('member.shop_hub.visitors_today') }}:
                    <span class="font-bold text-gray-900">{{ number_format($stats['visitors_today']) }}</span>
                </p>
            </section>

            <div class="rounded-[11px] bg-white">
                <x-member.order-status-icons
                    :status-counts="$statusCounts"
                    orders-route="member.seller.orders.index"
                    :merchant="true"
                    :title="__('member.shop_hub.order_management')"
                />
            </div>

            <section class="rounded-[11px] bg-white px-3 py-4">
                <div class="grid grid-cols-4 gap-2">
                    @foreach ([
                        ['href' => route('member.categories.index', ['mode' => 'distribute']), 'label' => __('member.shop_hub.distribute'), 'icon' => 'store', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-50'],
                        ['href' => route('member.products.manage.index'), 'label' => __('member.products.goods'), 'icon' => 'package', 'color' => 'text-violet-600', 'bg' => 'bg-violet-50'],
                        ['href' => route('member.wallet.recharge'), 'label' => __('member.shop_hub.recharge'), 'icon' => 'wallet', 'color' => 'text-orange-600', 'bg' => 'bg-orange-50'],
                        ['href' => route('member.chat.index'), 'label' => __('member.shop_hub.support'), 'icon' => 'chat-bubble', 'color' => 'text-sky-600', 'bg' => 'bg-sky-50'],
                        ['href' => route('member.shop-hub.menu'), 'label' => __('member.shop_hub.all_menu'), 'icon' => 'layout-grid', 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-50'],
                    ] as $link)
                        <a href="{{ $link['href'] }}" class="flex flex-col items-center gap-1.5 no-underline active:opacity-80">
                            <span @class(['inline-flex size-10 items-center justify-center rounded-full', $link['bg'], $link['color']])>
                                <x-member.icon :name="$link['icon']" class="size-5" />
                            </span>
                            <span class="w-full truncate text-center text-[12px] text-gray-700">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="rounded-[11px] bg-white p-4">
                <h2 class="text-[15px] font-bold text-emerald-700">{{ __('member.shop_hub.order_rate_month') }}</h2>
                <div class="mt-3">
                    <canvas id="shopHubMonthlyOrdersChart" height="180"></canvas>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const canvas = document.getElementById('shopHubMonthlyOrdersChart');
        if (!canvas || typeof Chart === 'undefined') return;

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: @json($stats['monthly_chart_labels']),
                datasets: [{
                    label: @js(__('member.shop_hub.order_count')),
                    data: @json($stats['monthly_chart_orders']),
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.08)',
                    pointBackgroundColor: '#059669',
                    pointRadius: 3,
                    fill: true,
                    tension: 0.3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        ticks: { maxRotation: 0, font: { size: 10 } },
                        grid: { color: 'rgba(148, 163, 184, 0.2)' },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 10 } },
                        grid: { color: 'rgba(148, 163, 184, 0.2)' },
                    },
                },
            },
        });
    });
    </script>
    @endpush
@endsection
