<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'withdrawal_method_id',
        'amount',
        'status',
        'payout_details',
        'admin_note',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payout_details' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawalMethod(): BelongsTo
    {
        return $this->belongsTo(WithdrawalMethod::class);
    }

    public function formattedPayoutDetails(): string
    {
        $payout = $this->payout_details ?? [];

        if ($payout === []) {
            return '—';
        }

        if (filled($payout['details'] ?? null) && blank($payout['address'] ?? null)) {
            return (string) $payout['details'];
        }

        $lines = [];

        if (filled($payout['address'] ?? null)) {
            $lines[] = (string) $payout['address'];
        }

        if (($payout['type'] ?? null) === WithdrawalMethod::TYPE_CRYPTO) {
            if (filled($payout['network'] ?? null)) {
                $lines[] = (string) $payout['network'];
            }

            if (filled($payout['currency'] ?? null)) {
                $lines[] = (string) $payout['currency'];
            }
        }

        return $lines !== [] ? implode("\n", $lines) : '—';
    }
}
