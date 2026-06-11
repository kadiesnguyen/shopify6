<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\Member\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    public function __construct(private readonly FinancialReportService $financialReport) {}

    public function index(Request $request): View
    {
        $period = $request->string('period', 'day')->toString();

        if (! in_array($period, ['day', 'week', 'month', 'year', 'custom'], true)) {
            $period = 'day';
        }

        $date = $request->date('date')?->toDateString() ?? now()->toDateString();
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        if ($period === 'custom' && (! $from || ! $to)) {
            $period = 'day';
        }

        $user = auth()->user()->load('shop');
        $report = $this->financialReport->reportFor($user, $period, $date, $from, $to);

        return view('member.financial-report.index', compact('report', 'period', 'date', 'from', 'to', 'user'));
    }
}
