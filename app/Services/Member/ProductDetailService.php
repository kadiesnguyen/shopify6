<?php

namespace App\Services\Member;

use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\Shop;
use App\Services\Import\SieummoProductDetailParser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ProductDetailService
{
    public function __construct(private readonly SieummoProductDetailParser $parser) {}

    /** @return array<string, mixed> */
    public function resolve(
        Product $product,
        string $sourceUrl = 'https://sieummo.vn',
        ?int $displayShopId = null,
        ?int $shopOwnerUserId = null,
    ): array {
        $product->loadMissing(['category', 'shop', 'images'])->loadCount('orderItems');

        $distribution = $this->resolveDisplayDistribution($product, $displayShopId, $shopOwnerUserId);
        $purchasePrice = (float) ($distribution?->purchase_price ?? $product->purchase_price);
        $sellingPrice = (float) ($distribution?->selling_price ?? $product->selling_price);
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

    private function resolveDisplayDistribution(
        Product $product,
        ?int $displayShopId = null,
        ?int $shopOwnerUserId = null,
    ): ?ProductDistribution {
        if ($shopOwnerUserId) {
            return ProductDistribution::query()
                ->available()
                ->where('product_id', $product->id)
                ->where('user_id', $shopOwnerUserId)
                ->first();
        }

        if ($displayShopId > 0) {
            return ProductDistribution::query()
                ->available()
                ->where('product_id', $product->id)
                ->whereHas('user.shop', fn ($query) => $query->whereKey($displayShopId))
                ->orderByDesc('created_at')
                ->first();
        }

        return null;
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

        $sieummo = $this->fetchDescriptionFromSieummo($product, $sourceUrl);

        if ($sieummo !== null) {
            return $sieummo;
        }

        $demoId = $this->demoGoodsIdForName($product->name);

        return $demoId ? $this->fetchDescriptionFromDemo($demoId) : null;
    }

    public function warmDemoNameIndex(): void
    {
        Cache::forget('demo:goods-name-index');
        $this->demoNameIndex();
    }

    private function demoGoodsIdForName(string $name): ?int
    {
        if (! Cache::has('demo:goods-name-index')) {
            return null;
        }

        $key = mb_strtolower(trim(html_entity_decode($name, ENT_QUOTES | ENT_HTML5)));
        $id = $this->demoNameIndex()[$key] ?? null;

        return $id ? (int) $id : null;
    }

    /** @return array<string, int> */
    private function demoNameIndex(): array
    {
        return Cache::remember('demo:goods-name-index', 3600, function (): array {
            $index = [];
            $categories = $this->demoApiCall('api/Category/index')['data']['cateres'] ?? [];

            foreach ($categories as $category) {
                $page = 1;

                while (true) {
                    $response = $this->demoApiCall('api/Goods/getCategoryGoodsList', [
                        'cate_id' => $category['id'],
                        'page' => $page,
                    ]);
                    $items = $response['data']['goodres'] ?? [];

                    if (! is_array($items) || $items === []) {
                        break;
                    }

                    foreach ($items as $item) {
                        $label = html_entity_decode((string) ($item['goods_name'] ?? ''), ENT_QUOTES | ENT_HTML5);
                        $lookup = mb_strtolower(trim($label));

                        if ($lookup !== '') {
                            $index[$lookup] = (int) $item['id'];
                        }
                    }

                    if (count($items) < 10) {
                        break;
                    }

                    $page++;
                }
            }

            return $index;
        });
    }

    /** @param  array<string, mixed>  $params */
    private function demoApiCall(string $endpoint, array $params = []): array
    {
        $secretKey = (string) config('services.demo_api.secret_key');
        $baseUrl = rtrim((string) config('services.demo_api.base_url'), '/');

        $response = Http::asForm()->timeout(60)->post(
            $baseUrl.'/'.$endpoint.'?lang='.config('services.demo_api.locale').'&t='.now()->getTimestampMs(),
            array_merge([
                'api_token' => md5($endpoint.$secretKey),
                'client_id' => 1,
            ], $params),
        );

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private function fetchDescriptionFromDemo(int $goodsId): ?string
    {
        try {
            $json = $this->demoApiCall('api/Goods/goodsInfo', ['goods_id' => $goodsId]);
            $html = $json['data']['goodsinfo']['goods_desc'] ?? null;
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
