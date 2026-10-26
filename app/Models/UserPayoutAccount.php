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
