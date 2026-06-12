<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSettings
{
    public const CACHE_KEY = 'site_settings.all';

    public const KEY_PROFILE_MARQUEE = 'profile_marquee_text';

    public const KEY_WEBSITE_TITLE = 'website_title';

    public const KEY_LOGO = 'logo_path';

    public const KEY_FAVICON = 'favicon_path';

    public const KEY_SEO_DESCRIPTION = 'seo_description';

    public const KEY_SEO_OG_IMAGE = 'seo_og_image_path';

    public const KEY_CHAT_SUPPORT_TITLE = 'chat_support_title';

    public const KEY_CHAT_SUPPORT_AVATAR = 'chat_support_avatar_path';

    /** @return array<string, string|null> */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return SiteSetting::query()
                ->pluck('value', 'key')
                ->all();
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::all()[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    public static function set(string $key, ?string $value): void
    {
        SiteSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );

        self::clearCache();
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function websiteTitle(): string
    {
        return self::get(self::KEY_WEBSITE_TITLE)
            ?? config('portal.brand_name', config('landing.brand_name', config('app.name')));
    }

    public static function logoUrl(): string
    {
        return self::assetUrl(self::KEY_LOGO, config('portal.logo', config('landing.portal_logo', 'images/portal/logo.jpg')));
    }

    public static function faviconUrl(): string
    {
        return self::assetUrl(self::KEY_FAVICON, 'favicon.ico');
    }

    public static function seoDescription(): string
    {
        return self::get(self::KEY_SEO_DESCRIPTION) ?? __('landing.meta_description');
    }

    public static function seoOgImageUrl(): string
    {
        return self::assetUrl(self::KEY_SEO_OG_IMAGE, 'favicon.ico');
    }

    public static function profileMarqueeText(): string
    {
        return self::get(self::KEY_PROFILE_MARQUEE) ?? __('member.my.payment_warning_long');
    }

    public static function chatSupportTitle(): string
    {
        $custom = self::get(self::KEY_CHAT_SUPPORT_TITLE);

        if (filled($custom)) {
            return $custom;
        }

        return __('chat.support_title', ['brand' => self::websiteTitle()]);
    }

    public static function chatSupportAvatarUrl(): ?string
    {
        $path = self::get(self::KEY_CHAT_SUPPORT_AVATAR);

        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public static function assetUrl(string $key, string $fallback): string
    {
        $path = self::get($key);

        if ($path === null || $path === '') {
            return asset($fallback);
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }

    public static function deleteStoredFile(?string $path): void
    {
        if ($path === null || $path === '' || str_starts_with($path, 'images/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
