<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\WalletResource;
use App\Models\RechargeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
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
}
