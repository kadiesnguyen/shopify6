<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPayoutAccount extends Model
{
    public const TYPE_BANK = 'bank';

    public const TYPE_CRYPTO = 'crypto';

    protected $fillable = [
        'user_id',
        'type',
        'label',
        'bank_name',
        'account_name',
        'account_number',
        'crypto_currency',
        'crypto_network',
        'crypto_address',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function bankFor(User $user): ?self
    {
        return $user->payoutAccounts()
            ->where('type', self::TYPE_BANK)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    public static function rememberBank(User $user, ?string $bankName, ?string $accountName, ?string $accountNumber): ?self
    {
        $existing = self::bankFor($user);

        if ($existing) {
            return $existing;
        }

        $bankName = trim((string) $bankName);
        $accountName = trim((string) $accountName);
        $accountNumber = trim((string) $accountNumber);

        if ($bankName === '' || $accountName === '' || $accountNumber === '') {
            return null;
        }

        return $user->payoutAccounts()->create([
            'type' => self::TYPE_BANK,
            'bank_name' => $bankName,
            'account_name' => $accountName,
            'account_number' => $accountNumber,
            'is_default' => true,
        ]);
    }

    public static function replaceBank(User $user, string $bankName, string $accountName, string $accountNumber): self
    {
        $existing = self::bankFor($user);

        $user->payoutAccounts()
            ->where('type', self::TYPE_BANK)
            ->when($existing, fn ($query, self $account) => $query->whereKeyNot($account->id))
            ->delete();

        if ($existing) {
            $existing->update([
                'bank_name' => trim($bankName),
                'account_name' => trim($accountName),
                'account_number' => trim($accountNumber),
                'is_default' => true,
            ]);

            return $existing->refresh();
        }

        return $user->payoutAccounts()->create([
            'type' => self::TYPE_BANK,
            'bank_name' => trim($bankName),
            'account_name' => trim($accountName),
            'account_number' => trim($accountNumber),
            'is_default' => true,
        ]);
    }

    public function bankAddress(): string
    {
        return implode(' · ', array_filter([
            $this->account_name,
            $this->bank_name,
            $this->account_number,
        ]));
    }

    /** @return array<string, mixed> */
    public function toPayoutDetails(): array
    {
        if ($this->type === self::TYPE_BANK) {
            return [
                'type' => self::TYPE_BANK,
                'bank_name' => $this->bank_name,
                'account_name' => $this->account_name,
                'account_number' => $this->account_number,
                'details' => trim(implode(' / ', array_filter([
                    $this->bank_name,
                    $this->account_name,
                    $this->account_number,
                ]))),
            ];
        }

        return [
            'type' => self::TYPE_CRYPTO,
            'currency' => $this->crypto_currency,
            'network' => $this->crypto_network,
            'address' => $this->crypto_address,
            'details' => trim(implode(' / ', array_filter([
                $this->crypto_currency,
                $this->crypto_network,
                $this->crypto_address,
            ]))),
        ];
    }
}
