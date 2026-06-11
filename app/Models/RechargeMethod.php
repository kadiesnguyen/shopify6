<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RechargeMethod extends Model
{
    public const TYPE_BANK = 'bank';

    public const TYPE_CRYPTO = 'crypto';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'type',
        'config',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }

    public function rechargeRequests(): HasMany
    {
        return $this->hasMany(RechargeRequest::class);
    }
}
