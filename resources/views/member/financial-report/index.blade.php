@extends('layouts.member')

@section('title', __('member.financial_report.title'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    <div class="min-h-screen bg-gray-50 pb-24">
        <header class="sticky top-0 z-10 flex items-center justify-between bg-black px-4 py-3 text-white">
            <a href="{{ route('member.my.index') }}" class="flex items-center gap-1 text-white/90 no-underline">
                <x-member.icon name="chevron-left" class="size-5" />
                <span class="text-sm">{{ __('member.back') }}</span>
            </a>
            <h1 class="absolute left-1/2 -translate-x-1/2 text-base font-semibold">{{ __('member.financial_report.title') }}</h1>
            <span class="w-16" aria-hidden="true"></span>
        </header>

        <div class="space-y-4 p-4">
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-black p-4 text-white">
                    <p class="text-sm text-white/80">{{ __('member.my.pending_payment_amount') }}</p>
                    <p class="text-xl font-bold text-red-400">${{ number_format($report['pending_payment'], 2) }}</p>
                </div>
                <div class="rounded-xl bg-black p-4 text-white">
                    <p class="text-sm text-white/80">{{ __('member.financial_report.total_profit') }}</p>
                    <p class="text-xl font-bold text-green-400">${{ number_format($report['total_profit'], 2) }}</p>
                </div>
                <div class="col-span-2 rounded-xl bg-black p-4 text-white">
                    <p class="text-sm text-white/80">{{ __('member.my.total_income') }}</p>
                    <p class="text-xl font-bold text-amber-300">${{ number_format($report['total_income'], 2) }}</p>
                </div>
            </div>

            <div class="flex items-center justify-between rounded-xl bg-white p-4 shadow-sm">
                <span class="text-gray-600">{{ $report['period_label'] }}</span>
                <span class="text-sm text-gray-500">{{ __('member.financial_report.total_orders') }}: {{ number_format($report['period_order_count']) }}</span>
                <span class="font-medium">{{ __('member.financial_report.profit') }} ${{ number_format($report['period_profit'], 2) }}</span>
            </div>

            <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="flex border-b border-gray-100">
                    @foreach ([
                        'day' => __('member.financial_report.period_day'),
                        'week' => __('member.financial_report.period_week'),
                        'month' => __('member.financial_report.period_month'),
                        'year' => __('member.financial_report.period_year'),
                    ] as $key => $label)
                        <a
                            href="{{ route('member.financial-report.index', ['period' => $key, 'date' => $date]) }}"
                            @class([
                                'relative flex-1 py-3 text-center text-sm font-medium no-underline',
                                'text-black' => $period === $key,
                                'text-gray-400' => $period !== $key,
                            ])
                        >
                            {{ $label }}
                            @if ($period === $key)
                                <span class="absolute bottom-0 left-1/2 h-0.5 w-10 -translate-x-1/2 rounded-full bg-black"></span>
                            @endif
                        </a>
                    @endforeach
                    <button
                        type="button"
                        id="toggleCustomRange"
                        @class([
                            'relative px-3 py-3 text-sm font-medium',
                            'text-black' => $period === 'custom',
                            'text-gray-400' => $period !== 'custom',
                        ])
                    >
                        <x-member.icon name="calendar" class="size-4" />
                    </button>
                </div>

                <div id="customRange" @class(['px-4 pt-3', 'hidden' => $period !== 'custom'])>
                    <p class="mb-2 text-sm text-gray-700">{{ __('member.financial_report.pick_range') }}</p>
                    <form method="GET" action="{{ route('member.financial-report.index') }}" class="space-y-2">
                        <input type="hidden" name="period" value="custom">
                        <div class="flex items-center gap-2">
                            <input
                                type="date"
                                name="from"
                                value="{{ $from ?? $date }}"
                                required
                                class="flex-1 rounded-md border border-gray-300 px-2.5 py-1.5 text-sm outline-none"
                            >
                            <span class="text-gray-400">—</span>
                            <input
                                type="date"
                                name="to"
                                value="{{ $to ?? $date }}"
                                required
                                class="flex-1 rounded-md border border-gray-300 px-2.5 py-1.5 text-sm outline-none"
                            >
                        </div>
                        <button type="submit" class="mt-2 w-full rounded-md bg-black py-2 text-sm font-medium text-white">
                            {{ __('member.financial_report.apply_range') }}
                        </button>
                    </form>
                </div>

                <div class="p-4">
                    <div class="h-64">
                        <canvas id="financialReportChart"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 px-4 pb-4">
                    <div class="rounded-lg bg-blue-50 p-3 text-center">
                        <p class="text-xs text-blue-600">{{ __('member.financial_report.stock_import') }}</p>
                        <p class="text-sm font-bold text-blue-700">${{ number_format($report['summary_purchases'], 2) }}</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-3 text-center">
                        <p class="text-xs text-emerald-600">{{ __('member.financial_report.profit') }}</p>
                        <p class="text-sm font-bold text-emerald-700">${{ number_format($report['summary_profit'], 2) }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-100 p-3 text-center">
                        <p class="text-xs text-gray-500">{{ __('member.financial_report.orders') }}</p>
                        <p class="text-sm font-bold text-gray-700">{{ number_format($report['summary_orders']) }}</p>
                    </div>
                </div>
            </section>

            <p class="text-center text-sm text-gray-400">{{ __('member.orders.empty_alt') }}</p>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('toggleCustomRange');
    const customRange = document.getElementById('customRange');

    toggle?.addEventListener('click', () => {
        customRange?.classList.toggle('hidden');
    });

    const canvas = document.getElementById('financialReportChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const purchases = @json($report['chart_purchases']);
    const profits = @json($report['chart_profits']);
    const maxValue = Math.max(0, ...purchases, ...profits);

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: @json($report['chart_labels']),
            datasets: [
                {
                    label: @js(__('member.financial_report.stock_import')),
                    data: purchases,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    pointBackgroundColor: '#3b82f6',
                    pointRadius: 3,
                    tension: 0.35,
                    fill: true,
                },
                {
                    label: @js(__('member.financial_report.profit')),
                    data: profits,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    pointBackgroundColor: '#10b981',
                    pointRadius: 3,
                    tension: 0.35,
                    fill: true,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        padding: 16,
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 0,
                        autoSkip: true,
                        font: { size: 10 },
                    },
                    grid: { color: 'rgba(148, 163, 184, 0.25)' },
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: maxValue <= 0 ? 1 : undefined,
                    ticks: {
                        callback: (value) => '$' + Number(value).toFixed(2),
                        stepSize: maxValue <= 0 ? 0.1 : undefined,
                    },
                    grid: { color: 'rgba(148, 163, 184, 0.25)' },
                },
            },
        },
    });
});
</script>
@endpush
