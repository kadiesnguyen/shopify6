<?php

namespace App\Support\Member;

use App\Models\Notification;
use Illuminate\Support\Facades\Cache;

class BellNotificationCache
{
    public static function unreadCount(int $userId): int
    {
        return (int) Cache::remember(
            self::cacheKey($userId),
            now()->addSeconds(30),
            fn (): int => Notification::query()
                ->where('user_id', $userId)
                ->bellVisible()
                ->whereNull('read_at')
                ->count(),
        );
    }

    public static function forget(int $userId): void
    {
        Cache::forget(self::cacheKey($userId));
    }

    private static function cacheKey(int $userId): string
    {
        return "member.bell_unread.{$userId}";
    }
}
