<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Support\Str;

class MemberCredentials
{
    public static function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @return array{email: string, phone: ?string, username: string, name: string}
     */
    public static function fromLogin(string $login): array
    {
        $login = trim($login);

        if (self::isEmail($login)) {
            $local = Str::before($login, '@');
            $username = self::uniqueUsername($local);

            return [
                'email' => $login,
                'phone' => null,
                'username' => $username,
                'name' => $username,
            ];
        }

        $digits = preg_replace('/\D+/', '', $login) ?: $login;
        $username = self::uniqueUsername('u'.$digits);

        return [
            'email' => $digits.'@member.shopefy.local',
            'phone' => $login,
            'username' => $username,
            'name' => $username,
        ];
    }

    public static function loginField(string $login): string
    {
        return self::isEmail($login) ? 'email' : 'phone';
    }

    public static function loginExists(string $login): bool
    {
        if (self::isEmail($login)) {
            return User::query()->where('email', $login)->exists();
        }

        return User::query()->where('phone', $login)->exists();
    }

    private static function uniqueUsername(string $base): string
    {
        $username = Str::lower(Str::slug($base, ''));
        $username = $username !== '' ? $username : 'member';
        $candidate = $username;
        $suffix = 1;

        while (User::query()->where('username', $candidate)->exists()) {
            $candidate = $username.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
