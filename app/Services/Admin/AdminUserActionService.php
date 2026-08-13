<?php

namespace App\Services\Admin;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminUserActionService
{
    public function updateBalance(User $user, array $data): Wallet
    {
        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'balance_pending' => 0, 'balance_frozen' => 0],
        );

        [$available, $frozen] = $this->applyFrozenTransfer($wallet, $data);

        $wallet->update([
            'balance_pending' => $data['balance_pending'],
            'balance' => $available,
            'balance_frozen' => $frozen,
        ]);

        return $wallet->fresh();
    }

    /** @param  array{balance: mixed, balance_frozen: mixed}  $data */
    private function applyFrozenTransfer(Wallet $wallet, array $data): array
    {
        $oldAvailable = round((float) $wallet->balance, 2);
        $oldFrozen = round((float) $wallet->balance_frozen, 2);
        $available = round((float) $data['balance'], 2);
        $frozen = round((float) $data['balance_frozen'], 2);
        $deltaFrozen = round($frozen - $oldFrozen, 2);
        $deltaAvailable = round($available - $oldAvailable, 2);

        // ponytail: freeze is a vault transfer. If admin only raises/lowers frozen
        // and leaves available as-is, move the delta out of / back into available.
        if (abs($deltaFrozen) >= 0.01 && abs($deltaAvailable) < 0.01) {
            $available = round($oldAvailable - $deltaFrozen, 2);
        }

        if ($available < 0) {
            throw ValidationException::withMessages([
                'balance_frozen' => __('admin.users.actions.balance_frozen_exceeds_available'),
            ]);
        }

        return [$available, $frozen];
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
