<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RechargeRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Admin\DashboardPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $periodKey = $request->input('period', 'month');
        if (! in_array($periodKey, ['day', 'week', 'month', 'year'], true)) {
            $periodKey = 'month';
        }

        $period = DashboardPeriod::fromRequest($periodKey);

        $stats = [
            'total_deposit' => Transaction::query()
                ->where('type', Transaction::TYPE_DEPOSIT)
                ->where('status', Transaction::STATUS_COMPLETED)
                ->sum('amount'),
            'total_withdrawal' => Transaction::query()
                ->where('type', Transaction::TYPE_WITHDRAWAL)
                ->where('status', Transaction::STATUS_COMPLETED)
                ->sum('amount'),
            'total_users' => User::query()->count(),
            'new_users' => $period->applyToQuery(User::query())->count(),
        ];

        $roles = Role::query()->pluck('name');
        $usersByRole = $roles->mapWithKeys(fn (string $role) => [
            $role => User::role($role)->count(),
        ]);

        $recentRequests = RechargeRequest::query()
            ->with('user')
            ->latest()
            ->limit(20)
            ->get();

        $chartDeposits = $period->applyToQuery(
            Transaction::query()
                ->where('type', Transaction::TYPE_DEPOSIT)
                ->where('status', Transaction::STATUS_COMPLETED)
        )
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $chartWithdrawals = $period->applyToQuery(
            Transaction::query()
                ->where('type', Transaction::TYPE_WITHDRAWAL)
                ->where('status', Transaction::STATUS_COMPLETED)
        )
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return view('admin.dashboard', compact(
            'stats',
            'usersByRole',
            'recentRequests',
            'chartDeposits',
            'chartWithdrawals',
            'periodKey',
        ));
    }
}
