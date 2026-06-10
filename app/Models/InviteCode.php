<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InviteCode extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_USED = 'used';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'code',
        'created_by',
        'used_by',
        'used_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
