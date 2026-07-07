@extends('layouts.member')

@section('title', __('member.shop_hub.title'))
@section('back_url', route('member.my.index'))
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
                <h2 class="text-[15px] font-bold text-gray-900">{{ __('member.shop_hub.overview') }}</h2>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    @foreach ([
                        ['label' => __('member.shop_hub.order_count'), 'value' => number_format($stats['total_orders'])],
                        ['label' => __('member.shop_hub.total_revenue'), 'value' => '$'.number_format($stats['total_sales'], 2)],
                        ['label' => __('member.shop_hub.available_balance'), 'value' => '$'.number_format($stats['available_balance'], 2)],
                        ['label' => __('member.shop_hub.reputation_score'), 'value' => number_format($stats['credit_score'])],
                    ] as $item)
                        <div class="rounded-lg bg-gray-50 px-3 py-3 text-center">
                            <p class="text-[11px] leading-tight text-gray-500">{{ $item['label'] }}</p>
                            <p class="mt-1 text-base font-bold text-gray-900">{{ $item['value'] }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-2 rounded-lg bg-emerald-50 px-3 py-3 text-center">
                    <p class="text-[11px] text-emerald-700">{{ __('member.shop_hub.visitors_today') }}</p>
                    <p class="mt-1 text-lg font-bold text-emerald-800">{{ number_format($stats['visitors_today']) }}</p>
                </div>
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
