<?php

namespace App\Models;

use App\Services\Member\MemberNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'type',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    /** @return list<string> */
    public static function bellTypes(): array
    {
        return [
            MemberNotificationService::TYPE_ORDER_PENDING_PAYMENT,
            MemberNotificationService::TYPE_ORDER_COMPLETED,
        ];
    }

    public function scopeBellVisible(Builder $query): Builder
    {
        return $query->whereIn('type', self::bellTypes());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
