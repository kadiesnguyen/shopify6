<?php

namespace App\Services\Member;

use App\Models\Product;
use App\Models\Shop;
use App\Services\Import\SieummoProductDetailParser;
use Illuminate\Support\Facades\Http;

class ProductDetailService
{
    public function __construct(private readonly SieummoProductDetailParser $parser) {}

    /** @return array<string, mixed> */
    public function resolve(Product $product, string $sourceUrl = 'https://sieummo.vn', ?int $displayShopId = null): array
    {
        $product->loadMissing(['category', 'shop', 'images'])->loadCount('orderItems');

        $purchasePrice = (float) $product->purchase_price;
        $sellingPrice = (float) $product->selling_price;
        $profit = max(0, $sellingPrice - $purchasePrice);
        $description = $this->resolveDescription($product, $sourceUrl);
        $isRecommended = $product->distributions()->available()->exists();

        return [
            'product' => $product->fresh(['category', 'shop', 'images']),
            'name' => $product->name,
            'image_url' => $product->imageUrl(),
            'images' => $this->imageUrls($product),
            'description' => $description,
            'description_html' => $this->isHtmlDescription($description),
            'purchase_price' => $purchasePrice,
            'selling_price' => $sellingPrice,
            'profit' => $profit,
            'stock' => (int) $product->stock,
            'is_recommended' => $isRecommended,
            'sales_count' => (int) $product->order_items_count,
            'category' => $product->category?->name,
            'shop' => $this->resolveDisplayShop($product, $displayShopId),
        ];
    }

    public function needsHtmlUpgrade(Product $product, string $description): bool
    {
        return (bool) preg_match('/^demo-(\d+)$/', (string) $product->slug)
            && ! $this->isHtmlDescription($description);
    }

    public function isRichDescription(string $description, string $productName): bool
    {
        $description = trim($description);

        if ($description === '' || $description === $productName) {
            return false;
        }

        if ($this->isHtmlDescription($description)) {
            return strlen(trim(strip_tags($description))) > 40;
        }

        return strlen($description) > 80;
    }

    public function isHtmlDescription(string $description): bool
    {
        return (bool) preg_match('/<\s*(p|ul|ol|li|div|img|table|h[1-6]|br|strong|span)\b/i', $description);
    }

    /** @return list<string|null> */
    private function imageUrls(Product $product): array
    {
        $images = [];

        $main = $product->imageUrl();
        if ($main) {
            $images[] = $main;
        }

        foreach ($product->images as $image) {
            $url = $image->imageUrl();
            if ($url && $url !== $main) {
                $images[] = $url;
            }
        }

        return $images ?: [$main];
    }

    /** @return array{id: int, user_id: int, name: string, logo_url: ?string, products_url: string}|null */
    private function resolveDisplayShop(Product $product, ?int $preferredShopId = null): ?array
    {
        $distributions = $product->distributions()
            ->available()
            ->with(['user.shop'])
            ->orderByDesc('created_at')
            ->get();

        if ($preferredShopId > 0) {
            foreach ($distributions as $distribution) {
                $shop = $distribution->user?->shop;

                if ($shop && (int) $shop->id === $preferredShopId) {
                    return $this->shopPayload($shop);
                }
            }
        }

        $shop = $distributions->first()?->user?->shop ?? $product->shop;

        return $shop ? $this->shopPayload($shop) : null;
    }

    /** @return array{id: int, user_id: int, name: string, logo_url: ?string, products_url: string} */
    private function shopPayload(Shop $shop): array
    {
        return [
            'id' => $shop->id,
            'user_id' => $shop->user_id,
            'name' => $shop->name,
            'logo_url' => $shop->displayLogoUrl(),
            'products_url' => route('member.products.index', [
                'shop_id' => $shop->id,
                'shop' => $shop->name,
            ]),
        ];
    }

    private function resolveDescription(Product $product, string $sourceUrl): string
    {
        $current = trim((string) $product->description);

        if ($this->isRichDescription($current, $product->name) && ! $this->needsHtmlUpgrade($product, $current)) {
            return $current;
        }

        $fetched = $this->fetchRemoteDescription($product, $sourceUrl);

        if ($fetched !== null && $fetched !== '' && $this->isRichDescription($fetched, $product->name)) {
            $product->update(['description' => $fetched]);

            return $fetched;
        }

        return $current !== '' ? $current : $product->name;
    }

    private function fetchRemoteDescription(Product $product, string $sourceUrl): ?string
    {
        if (preg_match('/^demo-(\d+)$/', (string) $product->slug, $match)) {
            return $this->fetchDescriptionFromDemo((int) $match[1]);
        }

        return $this->fetchDescriptionFromSieummo($product, $sourceUrl);
    }

    private function fetchDescriptionFromDemo(int $goodsId): ?string
    {
        $endpoint = 'api/Goods/goodsInfo';
        $secretKey = (string) config('services.demo_api.secret_key');
        $baseUrl = rtrim((string) config('services.demo_api.base_url'), '/');

        try {
            $response = Http::asForm()->timeout(30)->post(
                $baseUrl.'/'.$endpoint.'?lang='.config('services.demo_api.locale').'&t='.now()->getTimestampMs(),
                [
                    'api_token' => md5($endpoint.$secretKey),
                    'client_id' => 1,
                    'goods_id' => $goodsId,
                ],
            );

            $html = $response->json('data.goodsinfo.goods_desc');
        } catch (\Throwable) {
            return null;
        }

        return is_string($html) ? $this->sanitizeDescriptionHtml($html) : null;
    }

    private function fetchDescriptionFromSieummo(Product $product, string $sourceUrl): ?string
    {
        $ids = $this->sieummoIdsFor($product);

        if ($ids === null) {
            return null;
        }

        [$sieummoProductId, $sieummoShopId] = $ids;

        try {
            $html = Http::timeout(20)
                ->get(rtrim($sourceUrl, '/').'/product', [
                    'id' => $sieummoProductId,
                    'shop' => $sieummoShopId,
                ])
                ->body();
        } catch (\Throwable) {
            return null;
        }

        $richHtml = $this->parser->parseDescriptionHtml($html);

        if ($richHtml !== null && $richHtml !== '') {
            return $this->sanitizeDescriptionHtml($richHtml);
        }

        $plain = $this->parser->parseDescription($html);

        return $plain !== null && $plain !== '' ? $plain : null;
    }

    /** @return array{0: int, 1: int}|null */
    private function sieummoIdsFor(Product $product): ?array
    {
        if (! preg_match('/^sm-(\d+)$/', (string) $product->slug, $productMatch)) {
            return null;
        }

        $shopSlug = $product->shop?->slug;

        if ($shopSlug === null || ! preg_match('/-(\d+)$/', $shopSlug, $shopMatch)) {
            return null;
        }

        return [(int) $productMatch[1], (int) $shopMatch[1]];
    }

    private function sanitizeDescriptionHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;

        return trim($html);
    }
}
