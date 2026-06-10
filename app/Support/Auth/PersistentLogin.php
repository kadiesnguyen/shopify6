<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PersistentLogin
{
    public static function rememberMinutes(): int
    {
        return (int) config('auth.remember_minutes', 1440);
    }

    public static function configureRememberDuration(): void
    {
        Auth::guard()->setRememberDuration(self::rememberMinutes());
    }

    public static function finalize(Request $request, User $user): void
    {
        self::logoutOtherSessions($request, $user);
    }

    public static function logoutOtherSessions(Request $request, User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
