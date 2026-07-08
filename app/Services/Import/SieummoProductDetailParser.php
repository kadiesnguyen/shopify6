<?php

namespace App\Services\Import;

class SieummoProductDetailParser
{
    public function parseDescription(string $html): ?string
    {
        $rich = $this->parseDescriptionHtml($html);

        if ($rich === null) {
            return null;
        }

        $text = trim(html_entity_decode(strip_tags($rich), ENT_QUOTES | ENT_HTML5));

        return $text !== '' ? $text : null;
    }

    public function parseDescriptionHtml(string $html): ?string
    {
        $patterns = [
            '/<h2 class="font-bold text-gray-900 mb-2">Mô tả sản phẩm<\/h2>\s*<ul class="list-disc[^"]*">(.*?)<\/ul>/su',
            '/Mô tả sản phẩm<\/h2>\s*<ul class="list-disc[^"]*">(.*?)<\/ul>/su',
            '/Mô tả sản phẩm<\/p>\s*<p class="text-sm text-gray-600[^"]*">(.*?)<\/p>/su',
            '/<p class="text-sm font-semibold text-gray-900 mb-2">Mô tả sản phẩm<\/p>\s*<p class="[^"]*text-gray-600[^"]*">(.*?)<\/p>/su',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $html, $match)) {
                continue;
            }

            $inner = trim($match[1]);

            if ($inner !== '') {
                return $inner;
            }
        }

        return null;
    }

    public function parseRecommended(string $html): ?bool
    {
        if (preg_match('/Đề xuất<\/span><span class="text-sm font-medium text-gray-900">(Có|Không)<\/span>/u', $html, $match)) {
            return $match[1] === 'Có';
        }

        return null;
    }

    /** @return list<string> */
    public function parseGalleryUrls(string $html): array
    {
        $parts = preg_split('/Mô tả sản phẩm/u', $html, 2);
        $galleryHtml = $parts[0] ?? $html;

        return $this->extractUploadImageUrls($galleryHtml);
    }

    /** @return list<string> */
    private function extractUploadImageUrls(string $html): array
    {
        if (! preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
            return [];
        }

        $urls = [];

        foreach ($matches[1] as $src) {
            $src = trim(html_entity_decode((string) $src, ENT_QUOTES | ENT_HTML5));

            if ($src === '' || ! str_contains($src, '/uploads/')) {
                continue;
            }

            if (! in_array($src, $urls, true)) {
                $urls[] = $src;
            }
        }

        return $urls;
    }
}
