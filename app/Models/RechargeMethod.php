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

    public static function hasActive(): bool
    {
        return static::query()->where('status', self::STATUS_ACTIVE)->exists();
    }

    public static function supportChatPrefill(User $user): string
    {
        $username = $user->user_code
            ?: $user->username
            ?: $user->name
            ?: ($user->loginIdentifier() ?? (string) $user->id);

        return __('member.wallet.recharge_support_prefill', ['username' => $username]);
    }

    public static function memberEntryUrl(?User $user = null): string
    {
        if (static::hasActive()) {
            return route('member.wallet.recharge');
        }

        $user ??= auth()->user();

        return route('member.chat.index', [
            'prefill' => static::supportChatPrefill($user),
        ]);
    }
}
