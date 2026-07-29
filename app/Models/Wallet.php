<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'balance_pending',
        'balance_frozen',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'balance_pending' => 'decimal:2',
            'balance_frozen' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Spendable funds only — frozen balance is a vault and never debited by business flows. */
    public function spendableBalance(): float
    {
        return (float) $this->balance;
    }

    public function canSpend(float $amount): bool
    {
        return $amount > 0 && $this->spendableBalance() >= $amount;
    }
}
