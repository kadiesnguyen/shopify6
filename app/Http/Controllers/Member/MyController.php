<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\View\View;

class MyController extends Controller
{
    public function index(): View
    {
        $user = auth()->user()->load('wallet');

        $statusCounts = Order::query()
            ->where('user_id', $user->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingPaymentTotal = Order::query()
            ->where('user_id', $user->id)
            ->where('status', Order::STATUS_PENDING_PAYMENT)
            ->sum('total');

        $totalIncome = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', Transaction::TYPE_COMMISSION)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->sum('amount');

        return view('member.my.index', compact(
            'user',
            'statusCounts',
            'pendingPaymentTotal',
            'totalIncome',
        ));
    }
}
