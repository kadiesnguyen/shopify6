<?php

namespace App\Support;

use App\Models\Page;

final class CmsPages
{
    public static function about(): ?Page
    {
        return Page::query()->where('slug', 'gioi-thieu')->first();
    }

    public static function syncAbout(?string $contentVi, ?string $contentEn): Page
    {
        return self::sync(
            slug: 'gioi-thieu',
            type: Page::TYPE_ABOUT,
            defaultTitle: ['vi' => 'Giới thiệu', 'en' => 'About us'],
            contents: [
                'vi' => $contentVi ?? '',
                'en' => $contentEn ?? '',
            ],
        );
    }

    public static function syncContact(?string $contentVi, ?string $contentEn): Page
    {
        return self::sync(
            slug: 'lien-he',
            type: Page::TYPE_CONTACT,
            defaultTitle: ['vi' => 'Liên hệ', 'en' => 'Contact'],
            contents: [
                'vi' => $contentVi ?? '',
                'en' => $contentEn ?? '',
            ],
        );
    }

    /** @param  array{vi: string|null, en: string|null}  $contents */
    private static function sync(string $slug, string $type, array $defaultTitle, array $contents): Page
    {
        $page = Page::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'type' => $type,
                'title' => $defaultTitle,
                'content' => ['vi' => '', 'en' => ''],
                'meta_title' => $defaultTitle,
                'meta_description' => ['vi' => '', 'en' => ''],
                'status' => Page::STATUS_PUBLISHED,
            ],
        );

        $page->update([
            'content' => [
                'vi' => $contents['vi'] ?? '',
                'en' => $contents['en'] ?? '',
            ],
            'status' => Page::STATUS_PUBLISHED,
        ]);

        return $page->fresh();
    }
}
