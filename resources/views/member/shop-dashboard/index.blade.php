@extends('layouts.member')

@section('title', __('member.shop_dashboard.title'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    <div class="min-h-screen bg-gray-50 pb-8">
        <header class="sticky top-0 z-10 flex items-center bg-black px-4 py-3 text-white">
            <a href="{{ route('member.my.index') }}" class="flex items-center gap-1.5 no-underline text-white">
                <x-member.icon name="chevron-left" class="size-5" />
                <span class="text-sm">{{ __('member.back') }}</span>
            </a>
            <h1 class="absolute left-1/2 -translate-x-1/2 text-base font-semibold">{{ __('member.shop_dashboard.title') }}</h1>
        </header>

        <div class="mx-4 mt-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
            <h2 class="text-center text-sm font-semibold text-gray-900">{{ __('member.shop_dashboard.store_data') }}</h2>

            <div class="mt-4 grid grid-cols-2 gap-3 text-center">
                <div>
                    <p class="text-xs text-gray-500">{{ __('member.shop_dashboard.total_sales') }}</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">${{ number_format($stats['total_sales'], 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">{{ __('member.shop_dashboard.total_profit') }}</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">${{ number_format($stats['total_profit'], 2) }}</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-2">
                <div class="rounded-lg bg-emerald-600 px-2 py-3 text-center text-white">
                    <p class="text-[10px] leading-tight">{{ __('member.shop_dashboard.orders_today') }}</p>
                    <p class="mt-1 text-lg font-bold">{{ $stats['orders_today'] }}</p>
                </div>
                <div class="rounded-lg bg-emerald-600 px-2 py-3 text-center text-white">
                    <p class="text-[10px] leading-tight">{{ __('member.shop_dashboard.sales_today') }}</p>
                    <p class="mt-1 text-sm font-bold">${{ number_format($stats['sales_today'], 2) }}</p>
                </div>
                <div class="rounded-lg bg-emerald-600 px-2 py-3 text-center text-white">
                    <p class="text-[10px] leading-tight">{{ __('member.shop_dashboard.profit_today') }}</p>
                    <p class="mt-1 text-sm font-bold">${{ number_format($stats['profit_today'], 2) }}</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                @foreach ([
                    ['label' => __('member.shop_dashboard.visitors_today'), 'value' => $stats['visitors_today']],
                    ['label' => __('member.shop_dashboard.visitors_7d'), 'value' => $stats['visitors_7d']],
                    ['label' => __('member.shop_dashboard.visitors_30d'), 'value' => $stats['visitors_30d']],
                ] as $item)
                    <div class="rounded-lg border border-gray-100 px-2 py-3">
                        <p class="text-[10px] leading-tight text-gray-500">{{ $item['label'] }}</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $item['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
                <div class="rounded-lg border border-gray-100 px-2 py-3">
                    <p class="text-gray-500">{{ __('member.shop_dashboard.followers') }}</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $stats['followers'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-100 px-2 py-3">
                    <p class="text-gray-500">{{ __('member.shop_dashboard.shop_stars') }}</p>
                    <div class="mt-1 flex justify-center gap-0.5 text-amber-400">
                        @for ($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= round($stats['star_rating']) ? 'text-amber-400' : 'text-gray-300' }}">★</span>
                        @endfor
                    </div>
                </div>
                <div class="rounded-lg border border-gray-100 px-2 py-3">
                    <p class="text-gray-500">{{ __('member.shop_dashboard.credit_score') }}</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $stats['credit_score'] }}</p>
                </div>
            </div>
        </div>

        <div class="mx-4 mt-4 grid grid-cols-3 gap-3">
            @foreach ([
                ['route' => 'member.wallet.recharge', 'icon' => 'wallet', 'label' => __('member.my.recharge')],
                ['route' => 'member.wallet.withdrawal', 'icon' => 'banknote', 'label' => __('member.my.withdraw')],
                ['route' => 'member.promotions.index', 'icon' => 'zap', 'label' => __('member.shop_dashboard.shop_boost')],
                ['route' => 'member.contract.show', 'icon' => 'headset', 'label' => __('member.shop_dashboard.seller_services')],
                ['route' => 'member.profile.show', 'icon' => 'settings', 'label' => __('member.shop_dashboard.shop_settings')],
                ['route' => 'member.chat.index', 'icon' => 'headset', 'label' => __('member.actions.support')],
            ] as $action)
                <a
                    href="{{ route($action['route']) }}"
                    class="flex aspect-square flex-col items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white p-2 text-center no-underline shadow-sm transition hover:bg-gray-50"
                >
                    <x-member.icon :name="$action['icon']" class="size-6 text-emerald-600" />
                    <span class="text-[11px] leading-tight text-gray-800">{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>

        <section class="mx-4 mt-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
            <h2 class="text-center text-sm font-semibold text-emerald-700">{{ __('member.shop_dashboard.sales_chart') }}</h2>
            <div class="mt-4">
                <canvas id="shopSalesChart" height="180"></canvas>
            </div>
        </section>

        <section class="mx-4 mt-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
            <h2 class="text-center text-sm font-semibold text-gray-900">{{ __('member.shop_dashboard.faq') }}</h2>
            <ul class="mt-3 divide-y divide-gray-100">
                @foreach ([
                    ['label' => __('member.shop_dashboard.faq_rating'), 'route' => 'member.contract.show'],
                    ['label' => __('member.shop_dashboard.faq_products'), 'route' => 'member.products.manage.index'],
                    ['label' => __('member.shop_dashboard.faq_promotion'), 'route' => 'member.promotions.index'],
                    ['label' => __('member.shop_dashboard.faq_shipping'), 'route' => 'member.contract.show'],
                    ['label' => __('member.shop_dashboard.faq_security'), 'route' => 'member.profile.show'],
                    ['label' => __('member.shop_dashboard.faq_refund'), 'route' => 'member.contract.show'],
                    ['label' => __('member.shop_dashboard.faq_manage'), 'route' => 'member.shop-dashboard.index'],
                ] as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" class="flex items-center justify-between py-3 text-sm text-gray-800 no-underline hover:text-emerald-600">
                            <span>{{ $item['label'] }}</span>
                            <x-member.icon name="chevron-right" class="size-4 text-gray-300" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('shopSalesChart');
    if (!canvas || typeof Chart === 'undefined') return;

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: @json($stats['chart_labels']),
            datasets: [{
                label: @js(__('member.shop_dashboard.chart_label')),
                data: @json($stats['chart_sales']),
                borderColor: '#059669',
                backgroundColor: 'rgba(5, 150, 105, 0.12)',
                fill: true,
                tension: 0.35,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: (v) => '$' + v } },
            },
        },
    });
});
</script>
@endpush
