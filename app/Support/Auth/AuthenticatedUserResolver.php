<?php

namespace App\Support\Auth;

use App\Models\User;
use App\Support\Auth\PersistentLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedUserResolver
{
    public static function fromSession(): ?User
    {
        $sessionUser = Auth::user();

        if (! $sessionUser instanceof User) {
            return null;
        }

        return User::query()->where('email', $sessionUser->email)->first();
    }

    public static function syncSession(Request $request): ?User
    {
        $sessionUser = $request->user();

        if (! $sessionUser instanceof User) {
            return null;
        }

        $dbUser = User::query()->where('email', $sessionUser->email)->first();

        if (! $dbUser) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return null;
        }

        if ($dbUser->id !== $sessionUser->getAuthIdentifier()) {
            PersistentLogin::configureRememberDuration();
            Auth::login($dbUser, true);
            $request->session()->regenerate();
        }

        return $dbUser;
    }
}
