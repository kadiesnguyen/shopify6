<?php

namespace App\Services\Admin;

use App\Models\RechargeRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletApprovalService
{
    public function approveRecharge(RechargeRequest $request): void
    {
        if ($request->status !== RechargeRequest::STATUS_PENDING) {
            return;
        }

        DB::transaction(function () use ($request): void {
            $wallet = Wallet::query()->firstOrCreate(
                ['user_id' => $request->user_id],
                ['balance' => 0, 'balance_pending' => 0, 'balance_frozen' => 0],
            );

            $wallet->increment('balance', $request->amount);

            Transaction::query()->create([
                'user_id' => $request->user_id,
                'wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'type' => Transaction::TYPE_DEPOSIT,
                'status' => Transaction::STATUS_COMPLETED,
                'reference' => $request->reference ?? 'RC-'.strtoupper(Str::random(8)),
                'description' => 'Recharge approved',
                'processed_at' => now(),
            ]);

            $request->update([
                'status' => RechargeRequest::STATUS_APPROVED,
                'processed_at' => now(),
            ]);
        });
    }

    public function rejectRecharge(RechargeRequest $request, ?string $note = null): void
    {
        if ($request->status !== RechargeRequest::STATUS_PENDING) {
            return;
        }

        $request->update([
            'status' => RechargeRequest::STATUS_REJECTED,
            'admin_note' => $note,
            'processed_at' => now(),
        ]);
    }

    public function approveWithdrawal(WithdrawalRequest $request): void
    {
        if ($request->status !== WithdrawalRequest::STATUS_PENDING) {
            return;
        }

        DB::transaction(function () use ($request): void {
            $wallet = Wallet::query()->where('user_id', $request->user_id)->lockForUpdate()->first();

            if (! $wallet || ! $wallet->canSpend($request->amount)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => __('admin.requests.insufficient_balance'),
                ]);
            }

            $wallet->decrement('balance', $request->amount);

            Transaction::query()->create([
                'user_id' => $request->user_id,
                'wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'type' => Transaction::TYPE_WITHDRAWAL,
                'status' => Transaction::STATUS_COMPLETED,
                'reference' => 'WD-'.strtoupper(Str::random(8)),
                'description' => 'Withdrawal approved',
                'processed_at' => now(),
            ]);

            $request->update([
                'status' => WithdrawalRequest::STATUS_APPROVED,
                'processed_at' => now(),
            ]);
        });
    }

    public function rejectWithdrawal(WithdrawalRequest $request, ?string $note = null): void
    {
        if ($request->status !== WithdrawalRequest::STATUS_PENDING) {
            return;
        }

        $request->update([
            'status' => WithdrawalRequest::STATUS_REJECTED,
            'admin_note' => $note,
            'processed_at' => now(),
        ]);
    }
}
