<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\MemberRechargeRequest;
use App\Http\Requests\Member\MemberWithdrawalRequest;
use App\Models\RechargeMethod;
use App\Models\RechargeRequest;
use App\Models\Transaction;
use App\Models\WithdrawalMethod;
use App\Models\WithdrawalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function recharge(): View
    {
        $methods = RechargeMethod::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        return view('member.wallet.recharge', compact('methods'));
    }

    public function storeRecharge(MemberRechargeRequest $request): RedirectResponse
    {
        RechargeRequest::query()->create([
            'user_id' => auth()->id(),
            'recharge_method_id' => $request->validated('recharge_method_id'),
            'amount' => $request->validated('amount'),
            'status' => RechargeRequest::STATUS_PENDING,
            'reference' => 'RC-'.strtoupper(substr(uniqid(), -8)),
        ]);

        return redirect()
            ->route('member.wallet.recharge')
            ->with('status', __('member.wallet.recharge_success'));
    }

    public function withdrawal(): View
    {
        $methods = WithdrawalMethod::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $wallet = auth()->user()->wallet;

        return view('member.wallet.withdrawal', compact('methods', 'wallet'));
    }

    public function storeWithdrawal(MemberWithdrawalRequest $request): RedirectResponse
    {
        $wallet = auth()->user()->wallet;
        $amount = (float) $request->validated('amount');

        if (! $wallet || $wallet->balance < $amount) {
            return back()
                ->withInput()
                ->withErrors(['amount' => __('member.wallet.insufficient_balance')]);
        }

        WithdrawalRequest::query()->create([
            'user_id' => auth()->id(),
            'withdrawal_method_id' => $request->validated('withdrawal_method_id'),
            'amount' => $amount,
            'status' => WithdrawalRequest::STATUS_PENDING,
            'payout_details' => ['details' => $request->validated('payout_details')],
        ]);

        return redirect()
            ->route('member.wallet.withdrawal')
            ->with('status', __('member.wallet.withdraw_success'));
    }

    public function fundRecords(Request $request): View
    {
        $transactions = Transaction::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('member.wallet.fund-records', compact('transactions'));
    }
}
