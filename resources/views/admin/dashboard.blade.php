@extends('layouts.admin')

@section('title', __('admin.menu.overview'))

@section('content')
    <div class="space-y-6">
        <p class="text-gray-600">
            {!! __('admin.dashboard.welcome_html', ['email' => e(auth()->user()->email)]) !!}
        </p>

        <div class="rounded-lg bg-white ring-1 ring-gray-200">
            <form method="GET" class="p-4 sm:px-6">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="font-medium text-gray-900">{{ __('admin.dashboard.filter_stats') }}</span>
                    <select
                        name="period"
                        onchange="this.form.submit()"
                        class="w-40 rounded-md bg-white px-2.5 py-1.5 text-sm ring-1 ring-inset ring-gray-300 outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                        @foreach (['day' => __('admin.dashboard.period_day'), 'week' => __('admin.dashboard.period_week'), 'month' => __('admin.dashboard.period_month'), 'year' => __('admin.dashboard.period_year')] as $value => $label)
                            <option value="{{ $value }}" @selected($periodKey === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs text-emerald-600 ring-1 ring-inset ring-emerald-500/50 transition-colors hover:bg-emerald-50">
                        {{ __('admin.dashboard.refresh') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-admin.stat-card
                :label="__('admin.dashboard.total_deposit')"
                :value="'$'.number_format($stats['total_deposit'], 2)"
                icon="arrow-down-circle"
                icon-bg="bg-emerald-100"
                icon-color="text-emerald-600"
            />
            <x-admin.stat-card
                :label="__('admin.dashboard.total_withdrawal')"
                :value="'$'.number_format($stats['total_withdrawal'], 2)"
                icon="arrow-up-circle"
                icon-bg="bg-rose-100"
                icon-color="text-rose-600"
            />
            <x-admin.stat-card
                :label="__('admin.dashboard.total_users')"
                :value="number_format($stats['total_users'])"
                icon="users"
                icon-bg="bg-cyan-100"
                icon-color="text-cyan-600"
            />
            <x-admin.stat-card
                :label="__('admin.dashboard.new_users')"
                :value="number_format($stats['new_users'])"
                icon="user-plus"
                icon-bg="bg-violet-100"
                icon-color="text-violet-600"
            />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="overflow-hidden rounded-lg bg-white ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-4 sm:px-6">
                    <span class="font-medium text-gray-900">{{ __('admin.dashboard.deposit_vs_withdrawal') }}</span>
                </div>
                <div class="p-4 sm:p-6">
                    @if ($chartDeposits->isEmpty() && $chartWithdrawals->isEmpty())
                        <div class="flex h-64 items-center justify-center text-sm text-gray-400">{{ __('admin.dashboard.no_chart_data') }}</div>
                    @else
                        <canvas id="depositChart" height="180"></canvas>
                    @endif
                </div>
            </div>
            <div class="overflow-hidden rounded-lg bg-white ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-4 sm:px-6">
                    <span class="font-medium text-gray-900">{{ __('admin.dashboard.users_by_role') }}</span>
                </div>
                <div class="p-4 sm:p-6">
                    <x-admin.role-pie-chart :data="$usersByRole" />
                </div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-1">
                <div class="overflow-hidden rounded-lg bg-white ring-1 ring-gray-200">
                    <div class="border-b border-gray-100 p-4 sm:px-6">
                        <span class="font-medium text-gray-900">{{ __('admin.dashboard.quick_links') }}</span>
                    </div>
                    <div class="p-4 sm:p-6">
                        <div class="space-y-2">
                            @foreach ([
                                ['route' => 'admin.users.index', 'label' => __('admin.menu.users'), 'icon' => 'users', 'color' => 'text-cyan-600'],
                                ['route' => 'admin.invite-codes.index', 'label' => __('admin.menu.invite_codes'), 'icon' => 'ticket', 'color' => 'text-violet-600'],
                                ['route' => 'admin.products.index', 'label' => __('admin.menu.products'), 'icon' => 'package', 'color' => 'text-amber-600'],
                                ['route' => 'admin.recharge-requests.index', 'label' => __('admin.menu.recharge_requests'), 'icon' => 'circle-plus', 'color' => 'text-emerald-600'],
                                ['route' => 'admin.withdrawal-requests.index', 'label' => __('admin.menu.withdrawal_requests'), 'icon' => 'circle-arrow-out', 'color' => 'text-orange-600'],
                            ] as $link)
                                <a href="{{ route($link['route']) }}" class="flex items-center gap-2 rounded-lg p-2 text-sm text-gray-700 no-underline hover:bg-gray-50">
                                    <x-admin.dashboard-icon :name="$link['icon']" @class(['size-5', $link['color']]) />
                                    <span>{{ $link['label'] }} →</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-lg bg-white ring-1 ring-gray-200">
                    <div class="border-b border-gray-100 p-4 sm:px-6">
                        <span class="font-medium text-gray-900">{{ __('admin.dashboard.recent_activity') }}</span>
                    </div>
                    <div class="p-4 sm:p-6">
                        @if ($recentRequests->isEmpty())
                            <x-ui.empty-state :title="__('admin.requests.empty')" />
                        @else
                            <div class="max-h-96 space-y-2 overflow-y-auto">
                                @foreach ($recentRequests as $item)
                                    <div class="flex flex-col gap-1 rounded-lg border border-gray-200 p-3 text-sm">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-2 py-1 text-xs text-white">
                                                {{ __('admin.dashboard.activity_recharge') }}
                                            </span>
                                            <span class="font-medium">${{ number_format($item->amount, 2) }}</span>
                                            <span @class([
                                                'rounded-md px-2 py-0.5 text-xs font-medium',
                                                'bg-amber-50 text-amber-700' => $item->status === 'pending',
                                                'bg-emerald-50 text-emerald-700' => $item->status === 'approved',
                                                'bg-rose-50 text-rose-700' => $item->status === 'rejected',
                                            ])>{{ $item->status }}</span>
                                        </div>
                                        <p class="text-xs text-gray-400">
                                            {{ $item->user?->email ?? '—' }} · {{ $item->created_at->format('Y-m-d H:i:s') }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@if (! $chartDeposits->isEmpty() || ! $chartWithdrawals->isEmpty())
<script type="module">
    import Chart from 'chart.js/auto';
    const depositData = @json($chartDeposits);
    const withdrawalData = @json($chartWithdrawals);
    const labels = [...new Set([...Object.keys(depositData), ...Object.keys(withdrawalData)])].sort();
    new Chart(document.getElementById('depositChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: @js(__('admin.dashboard.total_deposit')), data: labels.map(l => depositData[l] ?? 0), backgroundColor: '#10b981' },
                { label: @js(__('admin.dashboard.total_withdrawal')), data: labels.map(l => withdrawalData[l] ?? 0), backgroundColor: '#f43f5e' },
            ],
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } },
    });
</script>
@endif
@endpush
