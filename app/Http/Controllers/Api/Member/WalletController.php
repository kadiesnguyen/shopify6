<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Member\MemberWithdrawalRequest;
use App\Http\Resources\WalletResource;
use App\Models\RechargeRequest;
use App\Models\WithdrawalMethod;
use App\Models\WithdrawalRequest;
use App\Services\Member\WithdrawalQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WithdrawalQuoteService $withdrawalQuote) {}

    public function show(): WalletResource
    {
        $wallet = auth()->user()->wallet ?? auth()->user()->wallet()->create([
            'balance' => 0,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        return new WalletResource($wallet);
    }

    public function summary(): JsonResponse
    {
        $user = auth()->user()->load('wallet');
        $wallet = $user->wallet ?? $user->wallet()->create([
            'balance' => 0,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        return response()->json([
            'wallet' => new WalletResource($wallet),
            'links' => [
                'recharge' => route('member.wallet.recharge'),
                'withdrawal' => route('member.wallet.withdrawal'),
                'fund_records' => route('member.wallet.fund-records'),
                'withdrawal_records' => route('member.wallet.withdrawal-records'),
            ],
        ]);
    }

    public function recharge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recharge_method_id' => ['required', 'exists:recharge_methods,id'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $item = RechargeRequest::query()->create([
            'user_id' => auth()->id(),
            'recharge_method_id' => $data['recharge_method_id'],
            'amount' => $data['amount'],
            'status' => RechargeRequest::STATUS_PENDING,
            'reference' => 'RC-'.strtoupper(substr(uniqid(), -8)),
        ]);

        return response()->json(['message' => 'Recharge request submitted.', 'data' => $item], 201);
    }

    public function withdrawal(MemberWithdrawalRequest $request): JsonResponse
    {
        $wallet = auth()->user()->wallet;
        $amount = (float) $request->validated('amount');
        $method = WithdrawalMethod::query()->findOrFail($request->validated('withdrawal_method_id'));

        if (! $wallet || $wallet->balance < $amount) {
            return response()->json(['message' => __('member.wallet.insufficient_balance')], 422);
        }

        $network = $request->validated('network');
        $quote = $this->withdrawalQuote->quote($method, $amount, $network);

        if ($method->type === WithdrawalMethod::TYPE_BANK) {
            $currency = $method->config['currency'] ?? 'VND';
            $address = implode(' · ', array_filter([
                $request->validated('bank_account_name'),
                $request->validated('bank_name'),
                $request->validated('bank_account_number'),
            ]));
        } else {
            $currency = $request->validated('currency');
            $address = $request->validated('crypto_address');
        }

        $item = WithdrawalRequest::query()->create([
            'user_id' => auth()->id(),
            'withdrawal_method_id' => $method->id,
            'amount' => $amount,
            'status' => WithdrawalRequest::STATUS_PENDING,
            'payout_details' => [
                'method_name' => $method->name,
                'type' => $method->type,
                'currency' => $currency,
                'network' => $network,
                'address' => $address,
                'fee_percent' => $quote['fee_percent'],
                'fee_amount' => $quote['fee_amount'],
                'net_amount' => $quote['net_amount'],
            ],
        ]);

        return response()->json(['message' => __('member.wallet.withdraw_success'), 'data' => $item], 201);
    }
}
