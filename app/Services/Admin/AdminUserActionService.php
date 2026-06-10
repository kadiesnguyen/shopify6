<?php

namespace App\Services\Admin;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserActionService
{
    public function updateBalance(User $user, array $data): Wallet
    {
        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'balance_pending' => 0, 'balance_frozen' => 0],
        );

        $wallet->update([
            'balance_pending' => $data['balance_pending'],
            'balance' => $data['balance'],
            'balance_frozen' => $data['balance_frozen'],
        ]);

        return $wallet->fresh();
    }

    public function deposit(User $user, float $amount, ?string $note = null): Wallet
    {
        return DB::transaction(function () use ($user, $amount, $note): Wallet {
            $wallet = Wallet::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'balance_pending' => 0, 'balance_frozen' => 0],
            );

            $wallet->increment('balance', $amount);

            Transaction::query()->create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => Transaction::TYPE_DEPOSIT,
                'status' => Transaction::STATUS_COMPLETED,
                'reference' => 'ADM-DEP-'.strtoupper(Str::random(8)),
                'description' => $note ?: 'Admin deposit',
                'processed_at' => now(),
                'metadata' => ['source' => 'admin_users'],
            ]);

            return $wallet->fresh();
        });
    }

    public function changePassword(User $user, string $password): void
    {
        $user->update(['password' => Hash::make($password)]);
    }

    public function changePaymentPassword(User $user, string $password): void
    {
        $user->update(['payment_password' => Hash::make($password)]);
    }

    public function toggleAccountLock(User $user): User
    {
        $user->update([
            'status' => $user->status === User::STATUS_BANNED
                ? User::STATUS_ACTIVE
                : User::STATUS_BANNED,
        ]);

        return $user->fresh();
    }

    public function toggleDistributionLock(User $user): User
    {
        $user->update(['distribution_locked' => ! $user->distribution_locked]);

        return $user->fresh();
    }
}
