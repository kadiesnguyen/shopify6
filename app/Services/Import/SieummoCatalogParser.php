<?php

namespace App\Services\Import;

use DOMDocument;
use DOMElement;
use DOMXPath;

class SieummoCatalogParser
{
    /**
     * @return list<array{
     *     sieummo_id: int,
     *     sieummo_shop_id: int,
     *     name: string,
     *     category_name: string,
     *     shop_name: string,
     *     shop_logo_url: ?string,
     *     image_url: string,
     *     selling_price: float,
     *     stock: int
     * }>
     */
    public function parse(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $items = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' pitem ') or contains(concat(' ', normalize-space(@class), ' '), ' hcard ')]");

        if ($items === false) {
            return [];
        }

        $products = [];
        $seen = [];

        /** @var DOMElement $node */
        foreach ($items as $node) {
            $parsed = $this->parseItem($node);

            if ($parsed === null) {
                continue;
            }

            $key = $parsed['sieummo_id'].'-'.$parsed['sieummo_shop_id'];

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $products[] = $parsed;
        }

        return $products;
    }

    /** @return array<string, mixed>|null */
    private function parseItem(DOMElement $node): ?array
    {
        $href = $this->firstMatch($node->ownerDocument->saveHTML($node), "/\\/product\\?id=(\\d+)(?:&|&amp;)shop=(\\d+)/");

        if ($href === null) {
            return null;
        }

        [$sieummoId, $sieummoShopId] = $href;

        $name = '';
        $shopName = html_entity_decode(trim($node->getAttribute('data-shop') ?: ''), ENT_QUOTES | ENT_HTML5);

        $xpath = new DOMXPath($node->ownerDocument);
        $titleNode = $xpath->query(".//p[contains(concat(' ', normalize-space(@class), ' '), ' font-medium ')]", $node)->item(0);

        if ($titleNode instanceof DOMElement) {
            $name = trim(html_entity_decode($titleNode->textContent ?? '', ENT_QUOTES | ENT_HTML5));
        }

        if ($name === '') {
            $name = trim(html_entity_decode($node->getAttribute('data-name') ?: '', ENT_QUOTES | ENT_HTML5));
        }

        $img = $node->getElementsByTagName('img')->item(0);
        $categoryName = 'Khác';
        $imageUrl = '';

        if ($img instanceof DOMElement) {
            $categoryName = trim(html_entity_decode($img->getAttribute('alt') ?: '', ENT_QUOTES | ENT_HTML5)) ?: 'Khác';
            $imageUrl = trim($img->getAttribute('src') ?: '');
        }

        $shopLogoUrl = null;
        $images = $node->getElementsByTagName('img');

        for ($i = 0; $i < $images->length; $i++) {
            $candidate = $images->item($i);

            if (! $candidate instanceof DOMElement) {
                continue;
            }

            $src = trim($candidate->getAttribute('src') ?: '');

            if ($src !== '' && $src !== $imageUrl && str_contains($src, '/uploads/')) {
                $shopLogoUrl = $src;
                break;
            }
        }

        $text = html_entity_decode($node->textContent ?? '', ENT_QUOTES | ENT_HTML5);

        if ($name === '') {
            if (preg_match('/\n([^\n]+)\n/', "\n".$text, $match)) {
                $name = trim($match[1]);
            }
        }

        $price = 0.0;

        if (preg_match('/\$([\d,]+(?:\.\d{2})?)/', $text, $match)) {
            $price = (float) str_replace(',', '', $match[1]);
        }

        $stock = 0;

        if (preg_match('/Kho:\s*([\d,]+)/u', $text, $match)) {
            $stock = (int) str_replace(',', '', $match[1]);
        }

        if ($name === '' || $price <= 0) {
            return null;
        }

        return [
            'sieummo_id' => (int) $sieummoId,
            'sieummo_shop_id' => (int) $sieummoShopId,
            'name' => $name,
            'category_name' => $categoryName,
            'shop_name' => $shopName !== '' ? $shopName : 'Shop '.$sieummoShopId,
            'shop_logo_url' => $shopLogoUrl,
            'image_url' => $imageUrl,
            'selling_price' => $price,
            'stock' => $stock,
        ];
    }

    /** @return array{0: string, 1: string}|null */
    private function firstMatch(string $haystack, string $pattern): ?array
    {
        if (! preg_match($pattern, $haystack, $matches)) {
            return null;
        }

        return [$matches[1], $matches[2]];
    }
}
