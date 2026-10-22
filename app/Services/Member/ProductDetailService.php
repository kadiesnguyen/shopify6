<?php

namespace App\Services\Member;

use App\Jobs\FetchProductDescriptionJob;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\Shop;
use App\Services\Import\SieummoProductDetailParser;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
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

        $distributions = $product->distributions()
            ->available()
            ->with(['user.shop'])
            ->get();
        $distribution = $this->pickDisplayDistribution($distributions, $displayShopId, $shopOwnerUserId);
        $marketPrice = (float) $product->selling_price;
        $purchasePrice = (float) ($distribution?->purchase_price ?? $product->purchase_price);
        $sellingPrice = (float) ($distribution?->selling_price ?? $marketPrice);
        $profit = max(0, $sellingPrice - $purchasePrice);
        $description = $this->stripDescriptionImages($this->resolveDescription($product, $sourceUrl));

        return [
            'product' => $product,
            'name' => $product->name,
            'image_url' => $product->imageUrl(),
            'images' => $this->imageUrls($product),
            'description' => $description,
            'description_html' => $this->isHtmlDescription($description),
            'purchase_price' => $purchasePrice,
            'selling_price' => $sellingPrice,
            'market_price' => $marketPrice,
            'show_market_price' => $distribution !== null && $sellingPrice + 0.001 < $marketPrice,
            'profit' => $profit,
            'stock' => (int) $product->stock,
            'is_recommended' => $distributions->isNotEmpty(),
            'sales_count' => (int) $product->order_items_count,
            'category' => $product->category?->name,
            'shop' => $this->resolveDisplayShop($product, $distributions, $displayShopId),
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
        $urls = [];

        foreach ($product->images as $image) {
            $url = $image->imageUrl();

            if ($url) {
                $urls[] = $url;
            }
        }

        if ($urls !== []) {
            return $urls;
        }

        $main = $product->imageUrl();

        return $main ? [$main] : [];
    }

    /**
     * @param  Collection<int, ProductDistribution>  $distributions
     * @return array{id: int, user_id: int, name: string, description: ?string, logo_url: ?string, products_url: string}|null
     */
    private function resolveDisplayShop(Product $product, Collection $distributions, ?int $preferredShopId = null): ?array
    {
        $ordered = $distributions->sortByDesc(fn (ProductDistribution $distribution): int => $distribution->created_at?->getTimestamp() ?? 0);

        if ($preferredShopId > 0) {
            foreach ($ordered as $distribution) {
                $shop = $distribution->user?->shop;

                if ($shop && (int) $shop->id === $preferredShopId) {
                    return $this->shopPayload($shop);
                }
            }
        }

        $shop = $ordered->first()?->user?->shop ?? $product->shop;

        return $shop ? $this->shopPayload($shop) : null;
    }

    /**
     * @param  Collection<int, ProductDistribution>  $distributions
     */
    private function pickDisplayDistribution(
        Collection $distributions,
        ?int $displayShopId = null,
        ?int $shopOwnerUserId = null,
    ): ?ProductDistribution {
        if ($shopOwnerUserId) {
            return $distributions->first(
                fn (ProductDistribution $distribution): bool => (int) $distribution->user_id === $shopOwnerUserId,
            );
        }

        if ($displayShopId > 0) {
            return $distributions
                ->filter(fn (ProductDistribution $distribution): bool => (int) ($distribution->user?->shop?->id) === $displayShopId)
                ->sortByDesc(fn (ProductDistribution $distribution): int => $distribution->created_at?->getTimestamp() ?? 0)
                ->first();
        }

        return $distributions
            ->sort(function (ProductDistribution $left, ProductDistribution $right): int {
                return $this->distributionRank($right) <=> $this->distributionRank($left);
            })
            ->first();
    }

    /** @return array{0: int, 1: int, 2: int} */
    private function distributionRank(ProductDistribution $distribution): array
    {
        return [
            $distribution->is_featured ? 1 : 0,
            $distribution->featured_at?->getTimestamp() ?? 0,
            $distribution->created_at?->getTimestamp() ?? 0,
        ];
    }

    /** @return array{id: int, user_id: int, name: string, description: ?string, logo_url: ?string, products_url: string} */
    private function shopPayload(Shop $shop): array
    {
        $description = trim((string) $shop->description);

        return [
            'id' => $shop->id,
            'user_id' => $shop->user_id,
            'name' => $shop->name,
            'description' => $description !== '' ? $description : null,
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
        $this->queueRemoteDescription($product, $current, $sourceUrl);

        return $current !== '' ? $current : $product->name;
    }

    public function fetchAndStoreRemoteDescription(Product $product, string $sourceUrl = 'https://sieummo.vn'): ?string
    {
        $fetched = $this->fetchRemoteDescription($product, $sourceUrl);

        if ($fetched === null || $fetched === '' || ! $this->isRichDescription($fetched, $product->name)) {
            return null;
        }

        $product->update(['description' => $fetched]);

        return $fetched;
    }

    private function queueRemoteDescription(Product $product, string $current, string $sourceUrl): void
    {
        if (config('queue.default') === 'sync') {
            return;
        }

        $needsFetch = ! $this->isRichDescription($current, $product->name)
            || $this->needsHtmlUpgrade($product, $current);

        if (! $needsFetch) {
            return;
        }

        $cacheKey = 'product:desc-fetch:'.$product->id;

        if (! Cache::add($cacheKey, 1, now()->addDay())) {
            return;
        }

        try {
            FetchProductDescriptionJob::dispatch($product->id, $sourceUrl);
        } catch (\Throwable) {
            Cache::forget($cacheKey);
        }
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

    public function findDemoGoodsIdByName(string $name): ?int
    {
        return $this->demoGoodsIdForName($name);
    }

    public function rememberDemoGoodsMapping(string $name, int $goodsId): void
    {
        $key = mb_strtolower(trim(html_entity_decode($name, ENT_QUOTES | ENT_HTML5)));

        if ($key === '') {
            return;
        }

        $index = Cache::get('demo:goods-name-index', []);
        $index[$key] = $goodsId;
        Cache::put('demo:goods-name-index', $index, now()->addDays(30));
    }

    private function demoGoodsIdForName(string $name): ?int
    {
        $key = mb_strtolower(trim(html_entity_decode($name, ENT_QUOTES | ENT_HTML5)));
        $index = Cache::get('demo:goods-name-index', []);
        $id = $index[$key] ?? null;

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

    private function remoteHttp(): PendingRequest
    {
        return Http::connectTimeout(3)->timeout(8);
    }

    /** @param  array<string, mixed>  $params */
    private function demoApiCall(string $endpoint, array $params = []): array
    {
        $secretKey = (string) config('services.demo_api.secret_key');
        $baseUrl = rtrim((string) config('services.demo_api.base_url'), '/');

        $response = $this->remoteHttp()->asForm()->post(
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
            $html = $this->remoteHttp()
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

    /**
     * Product photos belong to the gallery slide, not the specs body. Drop the
     * appended gallery block and any inline images so the detail tab is text-only.
     */
    public function stripDescriptionImages(string $html): string
    {
        $html = preg_replace('/<div class="product-desc-gallery">.*?<\/div>/is', '', $html) ?? $html;
        $html = preg_replace('/<img\b[^>]*>/i', '', $html) ?? $html;
        $html = preg_replace('/<p>\s*<\/p>/i', '', $html) ?? $html;

        return trim($html);
    }
}
