<?php

namespace App\Support;

class AppLocale
{
    /** @return array<string, array{label: string, flag?: string, content: string}> */
    public static function configured(): array
    {
        return config('landing.locales', []);
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::configured());
    }

    public static function isValid(string $locale): bool
    {
        return array_key_exists($locale, self::configured());
    }

    public static function display(): string
    {
        $locale = session('locale');

        if (is_string($locale) && self::isValid($locale)) {
            return $locale;
        }

        return config('app.locale', 'vi');
    }

    public static function content(): string
    {
        $display = self::display();

        return self::configured()[$display]['content'] ?? config('app.locale', 'vi');
    }

    /** @return array{label: string, flag?: string, content: string}|null */
    public static function currentMeta(): ?array
    {
        $display = self::display();

        return self::configured()[$display] ?? null;
    }
}
