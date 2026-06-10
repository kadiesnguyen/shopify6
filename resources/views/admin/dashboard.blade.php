@extends('layouts.admin')

@section('title', __('admin.menu.overview'))

@section('content')
    <x-admin.page-header :title="__('admin.menu.overview')" />

    <p class="mb-4 text-sm text-slate-600">{{ __('admin.dashboard.welcome', ['email' => auth()->user()->email]) }}</p>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="text-xs text-slate-500">{{ __('admin.dashboard.period') }}</label>
            <select name="period" class="rounded-lg border-slate-300 text-sm">
                @foreach (['day' => __('admin.dashboard.period_day'), 'week' => __('admin.dashboard.period_week'), 'month' => __('admin.dashboard.period_month'), 'year' => __('admin.dashboard.period_year')] as $value => $label)
                    <option value="{{ $value }}" @selected($periodKey === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.dashboard.refresh') }}</button>
    </form>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-card :label="__('admin.dashboard.total_deposit')" :value="'$'.number_format($stats['total_deposit'], 2)" />
        <x-admin.stat-card :label="__('admin.dashboard.total_withdrawal')" :value="'$'.number_format($stats['total_withdrawal'], 2)" />
        <x-admin.stat-card :label="__('admin.dashboard.total_users')" :value="$stats['total_users']" />
        <x-admin.stat-card :label="__('admin.dashboard.new_users')" :value="$stats['new_users']" />
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-4 font-semibold">{{ __('admin.dashboard.deposit_vs_withdrawal') }}</h2>
            @if ($chartDeposits->isEmpty() && $chartWithdrawals->isEmpty())
                <x-ui.empty-state :title="__('admin.dashboard.no_chart_data')" />
            @else
                <canvas id="depositChart" height="180"></canvas>
            @endif
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-4 font-semibold">{{ __('admin.dashboard.users_by_role') }}</h2>
            <ul class="space-y-2 text-sm">
                @forelse ($usersByRole as $role => $count)
                    <li class="flex justify-between rounded-lg bg-slate-50 px-3 py-2">
                        <span class="capitalize">{{ $role }}</span>
                        <span class="font-semibold">{{ $count }}</span>
                    </li>
                @empty
                    <x-ui.empty-state :title="__('admin.dashboard.no_chart_data')" />
                @endforelse
            </ul>
        </div>
    </div>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="mb-4 font-semibold">{{ __('admin.dashboard.recent_activity') }}</h2>
        @if ($recentRequests->isEmpty())
            <x-ui.empty-state :title="__('admin.requests.empty')" />
        @else
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($recentRequests as $item)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <span>{{ $item->user?->email }} · ${{ number_format($item->amount, 2) }} · {{ $item->status }}</span>
                        <span class="text-xs text-slate-500">{{ $item->created_at->format('d/m/Y H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">{{ __('admin.dashboard.quick_links') }}</h2>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('admin.users.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 hover:bg-slate-200">{{ __('admin.menu.users') }} →</a>
            <a href="{{ route('admin.invite-codes.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 hover:bg-slate-200">{{ __('admin.menu.invite_codes') }} →</a>
            <a href="{{ route('admin.products.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 hover:bg-slate-200">{{ __('admin.menu.products') }} →</a>
            <a href="{{ route('admin.promotions.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 hover:bg-slate-200">{{ __('admin.menu.promotions') }} →</a>
            <a href="{{ route('admin.recharge-requests.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 hover:bg-slate-200">{{ __('admin.menu.recharge_requests') }} →</a>
            <a href="{{ route('admin.withdrawal-requests.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 hover:bg-slate-200">{{ __('admin.menu.withdrawal_requests') }} →</a>
            <a href="{{ route('admin.shop-applications.index', ['status' => 'pending']) }}" class="rounded-lg bg-slate-100 px-3 py-2 hover:bg-slate-200">{{ __('admin.menu.shop_applications') }} →</a>
        </div>
    </section>
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
                { label: 'Deposit', data: labels.map(l => depositData[l] ?? 0), backgroundColor: '#00a651' },
                { label: 'Withdrawal', data: labels.map(l => withdrawalData[l] ?? 0), backgroundColor: '#004d2e' },
            ],
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } },
    });
</script>
@endif
@endpush
