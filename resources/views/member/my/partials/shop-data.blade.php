@props(['stats'])

<div class="mt-4 px-4">
    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <h2 class="text-left text-sm font-semibold text-gray-900">{{ __('member.shop_dashboard.store_data') }}</h2>

        <div class="mt-4 grid grid-cols-2 gap-3">
            @foreach ([
                ['label' => __('member.shop_dashboard.total_sales'), 'value' => '$'.number_format($stats['total_sales'], 2)],
                ['label' => __('member.shop_dashboard.total_profit'), 'value' => '$'.number_format($stats['total_profit'], 2)],
            ] as $item)
                <div class="rounded-xl bg-gray-50 px-3 py-4 text-center">
                    <p class="text-xs text-gray-500">{{ $item['label'] }}</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">{{ $item['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-4 grid grid-cols-3 gap-2">
            @foreach ([
                ['label' => __('member.shop_dashboard.orders_today'), 'value' => number_format($stats['orders_today'])],
                ['label' => __('member.shop_dashboard.sales_today'), 'value' => '$'.number_format($stats['sales_today'], 2)],
                ['label' => __('member.shop_dashboard.profit_today'), 'value' => '$'.number_format($stats['profit_today'], 2)],
            ] as $item)
                <div class="rounded-lg bg-emerald-600 px-2 py-3 text-center text-white">
                    <p class="text-[10px] leading-tight">{{ $item['label'] }}</p>
                    <p class="mt-1 text-sm font-bold">{{ $item['value'] }}</p>
                </div>
            @endforeach
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
                <p class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($stats['followers']) }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 px-2 py-3">
                <p class="text-gray-500">{{ __('member.shop_dashboard.shop_stars') }}</p>
                <div class="mt-1 flex justify-center gap-0.5">
                    @for ($i = 1; $i <= 5; $i++)
                        <span class="{{ $i <= round($stats['star_rating']) ? 'text-amber-400' : 'text-gray-300' }}">★</span>
                    @endfor
                </div>
            </div>
            <div class="rounded-lg border border-gray-100 px-2 py-3">
                <p class="text-gray-500">{{ __('member.shop_dashboard.credit_score') }}</p>
                <p class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($stats['credit_score']) }}</p>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 px-4">
    <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <h2 class="text-left text-sm font-semibold text-emerald-700">{{ __('member.shop_dashboard.sales_chart') }}</h2>
        <div class="mt-4">
            <canvas id="myShopSalesChart" height="200"></canvas>
        </div>
    </section>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('myShopSalesChart');
    if (!canvas || typeof Chart === 'undefined') return;

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: @json($stats['chart_labels']),
            datasets: [{
                label: @js(__('member.shop_dashboard.chart_label')),
                data: @json($stats['chart_sales']),
                borderColor: '#059669',
                backgroundColor: 'rgba(5, 150, 105, 0.08)',
                pointBackgroundColor: '#059669',
                pointRadius: 4,
                fill: false,
                tension: 0.2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 10 },
                    },
                    grid: { color: 'rgba(148, 163, 184, 0.25)' },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => '$' + Number(value).toFixed(2),
                        stepSize: 0.2,
                    },
                    grid: { color: 'rgba(148, 163, 184, 0.25)' },
                },
            },
        },
    });
});
</script>
@endpush
