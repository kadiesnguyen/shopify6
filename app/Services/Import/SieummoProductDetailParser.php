<?php

namespace App\Services\Import;

class SieummoProductDetailParser
{
    public function parseDescription(string $html): ?string
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

            $text = trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5));

            if ($text !== '') {
                return $text;
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
}
