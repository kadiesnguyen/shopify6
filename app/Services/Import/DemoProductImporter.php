<?php

namespace App\Services\Import;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\Shop;
use App\Models\User;
use App\Services\Member\ProductDetailService;
use App\Services\Member\ProductDistributionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class DemoProductImporter
{
    private const DEFAULT_STOCK = 100;

    private const EXPENSIVE_PRICE_THRESHOLD = 80.0;

    private string $baseUrl;

    private string $secretKey;

    private string $locale;

    /** @var array<string, array{category: Category, created: bool}> */
    private array $categoryCache = [];

    /** @var array<string, array{shop: Shop, created: bool}> */
    private array $shopCache = [];

    public function __construct()
    {
        $this->secretKey = (string) config('services.demo_api.secret_key');
        $this->locale = (string) config('services.demo_api.locale');
    }

    /**
     * @param  (callable(array<string, int|string>): void)|null  $onProgress
     * @return array{parsed: int, created: int, updated: int, skipped: int, shops: int, categories: int}
     */
    public function import(
        string $sourceUrl,
        bool $dryRun = false,
        ?int $limit = null,
        bool $skipImages = false,
        int $sleepMs = 100,
        ?callable $onProgress = null,
    ): array {
        $this->baseUrl = rtrim($sourceUrl === '' ? (string) config('services.demo_api.base_url') : $sourceUrl, '/');
        set_time_limit(0);

        $admin = User::role('admin')->first() ?? User::query()->first();

        if (! $admin) {
            throw new RuntimeException('No admin user found. Run db:seed first.');
        }

        Role::findOrCreate('member');
        Role::findOrCreate('shop');

        $stats = [
            'parsed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'shops' => 0,
            'categories' => 0,
        ];

        $categories = $this->categories();

        if ($categories === []) {
            throw new RuntimeException('No categories returned from demo API.');
        }

        /** @var array<int, true> $seenIds */
        $seenIds = [];
        $imported = 0;

        foreach ($categories as $demoCategory) {
            $localCategory = $this->resolveCategory($demoCategory);

            if ($localCategory['created']) {
                $stats['categories']++;
            }

            $page = 1;

            while (true) {
                $response = $this->call('api/Goods/getCategoryGoodsList', [
                    'cate_id' => $demoCategory['id'],
                    'page' => $page,
                ]);

                $items = $response['data']['goodres'] ?? [];

                if (! is_array($items) || $items === []) {
                    break;
                }

                foreach ($items as $item) {
                    $id = (int) $item['id'];

                    if (isset($seenIds[$id])) {
                        continue;
                    }

                    $seenIds[$id] = true;
                    $stats['parsed']++;

                    if ($dryRun) {
                        if ($limit !== null && $stats['parsed'] >= $limit) {
                            break 3;
                        }

                        continue;
                    }

                    $meta = [
                        'demo' => $item,
                        'category_id' => $localCategory['category']->id,
                    ];

                    try {
                        $result = $this->importListedProduct($id, $meta, $admin, $skipImages, $sleepMs);

                        if ($result['shop_created']) {
                            $stats['shops']++;
                        }

                        if ($result['created']) {
                            $stats['created']++;
                        } else {
                            $stats['updated']++;
                        }

                        $imported++;
                    } catch (RuntimeException $exception) {
                        $stats['skipped']++;
                        $this->line("Skipping product {$id}: {$exception->getMessage()}");
                    }

                    if ($onProgress !== null) {
                        $onProgress([
                            'phase' => 'import',
                            'goods_id' => $id,
                            'category' => $demoCategory['cate_name'],
                            'page' => $page,
                            ...$stats,
                        ]);
                    }

                    if ($limit !== null && $imported >= $limit) {
                        break 3;
                    }
                }

                if (count($items) < 10) {
                    break;
                }

                $page++;
            }
        }

        if ($stats['parsed'] === 0) {
            throw new RuntimeException('No products parsed from demo API.');
        }

        return $stats;
    }

    /**
     * @param  array{demo: array<string, mixed>, category_id: int}  $meta
     * @return array{created: bool, shop_created: bool}
     */
    private function importListedProduct(
        int $goodsId,
        array $meta,
        User $admin,
        bool $skipImages,
        int $sleepMs,
    ): array {
        $detail = $this->call('api/Goods/goodsInfo', ['goods_id' => $goodsId]);
        $goodsInfo = $detail['data']['goodsinfo'] ?? null;
        $gpres = $detail['data']['gpres'] ?? [];

        if ($sleepMs > 0) {
            usleep($sleepMs * 1000);
        }

        $shop = $this->resolveShop((int) $meta['demo']['shop_id'], $skipImages);
        $result = $this->resolveProduct($meta, $goodsInfo, $gpres, $shop['shop'], $admin, $skipImages);

        app(ProductDetailService::class)->rememberDemoGoodsMapping(
            (string) $meta['demo']['goods_name'],
            $goodsId,
        );

        return [
            'created' => $result['created'],
            'shop_created' => $shop['created'],
        ];
    }

    /**
     * @param  (callable(array<string, int|string>): void)|null  $onProgress
     * @return array{categories: int, products: int, pages: int}
     */
    public function catalogStats(?callable $onProgress = null): array
    {
        $this->baseUrl = rtrim((string) config('services.demo_api.base_url'), '/');
        $categories = $this->categories();
        $total = 0;
        $pages = 0;

        foreach ($categories as $demoCategory) {
            $categoryTotal = 0;
            $page = 1;

            while (true) {
                $response = $this->call('api/Goods/getCategoryGoodsList', [
                    'cate_id' => $demoCategory['id'],
                    'page' => $page,
                ]);
                $items = $response['data']['goodres'] ?? [];

                if (! is_array($items) || $items === []) {
                    break;
                }

                $categoryTotal += count($items);
                $total += count($items);
                $pages++;

                if ($onProgress !== null) {
                    $onProgress([
                        'phase' => 'count',
                        'category' => $demoCategory['cate_name'],
                        'category_id' => $demoCategory['id'],
                        'page' => $page,
                        'category_total' => $categoryTotal,
                        'products' => $total,
                        'pages' => $pages,
                    ]);
                }

                if (count($items) < 10) {
                    break;
                }

                $page++;
            }
        }

        return [
            'categories' => count($categories),
            'products' => $total,
            'pages' => $pages,
        ];
    }

    /** @return list<array{id: int, cate_name: string}> */
    private function categories(): array
    {
        $response = $this->call('api/Category/index');

        return $response['data']['cateres'] ?? [];
    }

    public function targetGalleryCount(float $sellingPrice): int
    {
        return $sellingPrice >= self::EXPENSIVE_PRICE_THRESHOLD ? 5 : 3;
    }

    public function minimumGalleryCount(float $sellingPrice): int
    {
        return $this->targetGalleryCount($sellingPrice);
    }

    /**
     * @param  list<array<string, mixed>>  $gpres
     * @return list<string>
     */
    public function collectImageUrls(?string $thumbUrl, array $gpres, float $sellingPrice, ?string $goodsDesc = null): array
    {
        $urls = [];

        if ($thumbUrl) {
            $urls[] = $thumbUrl;
        }

        foreach ($gpres as $pic) {
            $url = $pic['img_url'] ?? null;

            if ($url && ! in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        if ($goodsDesc) {
            if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $goodsDesc, $matches)) {
                foreach ($matches[1] as $url) {
                    $url = trim(html_entity_decode((string) $url, ENT_QUOTES | ENT_HTML5));

                    if ($url !== '' && ! in_array($url, $urls, true)) {
                        $urls[] = $url;
                    }
                }
            }
        }

        return array_slice($urls, 0, $this->targetGalleryCount($sellingPrice));
    }

    public function countDescriptionImages(string $html): int
    {
        if (! preg_match_all('/<img\b/i', $html, $matches)) {
            return 0;
        }

        return count($matches[0]);
    }

    /** @return array<string, mixed>|null */
    public function fetchGoodsInfo(int $goodsId): ?array
    {
        try {
            return $this->call('api/Goods/goodsInfo', ['goods_id' => $goodsId]);
        } catch (RuntimeException) {
            return null;
        }
    }

    public function syncGalleryForProduct(Product $product, int $goodsId, bool $skipImages = false): bool
    {
        $detail = $this->fetchGoodsInfo($goodsId);

        if ($detail === null) {
            return false;
        }

        $goodsInfo = $detail['data']['goodsinfo'] ?? null;
        $gpres = $detail['data']['gpres'] ?? [];
        $thumb = is_array($goodsInfo) ? ($goodsInfo['thumb_url'] ?? null) : null;
        $goodsDesc = is_array($goodsInfo) ? ($goodsInfo['goods_desc'] ?? null) : null;
        $sellingPrice = (float) ($product->selling_price ?: ($goodsInfo['zs_shop_price'] ?? 0));
        $imageUrls = $this->collectImageUrls($thumb, $gpres, $sellingPrice, is_string($goodsDesc) ? $goodsDesc : null);

        if (count($imageUrls) < $this->minimumGalleryCount($sellingPrice)) {
            return false;
        }

        $imagePaths = [];

        if (! $skipImages) {
            foreach ($imageUrls as $index => $url) {
                $path = $this->downloadImage($url, 'products/demo', (string) $goodsId);

                if ($path === null) {
                    continue;
                }

                $imagePaths[] = ['path' => $path, 'sort' => $index];
            }
        }

        if ($imagePaths === [] && ! $skipImages) {
            return false;
        }

        $description = $this->htmlDescription(
            is_array($goodsInfo) ? ($goodsInfo['goods_desc'] ?? null) : null,
            $product->name,
        );
        $description = $this->appendGalleryToDescription(
            $description,
            array_column($imagePaths, 'path'),
            $this->minimumGalleryCount($sellingPrice),
        );

        $payload = ['description' => $description];

        if ($imagePaths !== []) {
            $payload['image'] = $imagePaths[0]['path'];
        }

        $product->update($payload);
        $this->syncImages($product, $imagePaths);

        return true;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function call(string $endpoint, array $params = []): array
    {
        $token = md5($endpoint.$this->secretKey);
        $data = array_merge(['api_token' => $token, 'client_id' => 1], $params);
        $t = now()->getTimestampMs();
        $url = $this->baseUrl.'/'.$endpoint.'?lang='.$this->locale.'&t='.$t;

        $response = Http::asForm()->timeout(60)->post($url, $data);

        if (! $response->successful()) {
            throw new RuntimeException("Demo API request failed: {$endpoint} (HTTP {$response->status()})");
        }

        $json = $response->json();

        if (! is_array($json) || ($json['status'] ?? 0) !== 200) {
            $message = $json['mess'] ?? 'Unknown error';
            throw new RuntimeException("Demo API error for {$endpoint}: {$message}");
        }

        return $json;
    }

    /**
     * @param  array{id: int, cate_name: string}  $demoCategory
     * @return array{category: Category, created: bool}
     */
    private function resolveCategory(array $demoCategory): array
    {
        $slug = 'demo-cate-'.$demoCategory['id'];

        if (isset($this->categoryCache[$slug])) {
            return $this->categoryCache[$slug];
        }

        $category = Category::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $demoCategory['cate_name'],
                'status' => Category::STATUS_ACTIVE,
                'sort_order' => Category::query()->max('sort_order') + 1,
            ],
        );

        $this->categoryCache[$slug] = [
            'category' => $category,
            'created' => $category->wasRecentlyCreated,
        ];

        return $this->categoryCache[$slug];
    }

    /**
     * @return array{shop: Shop, created: bool}
     */
    private function resolveShop(int $demoShopId, bool $skipImages): array
    {
        $key = (string) $demoShopId;

        if (isset($this->shopCache[$key])) {
            return $this->shopCache[$key];
        }

        $response = $this->call('api/Shops/getShopInfo', ['shop_id' => $demoShopId]);
        $shopInfo = $response['data']['shops'] ?? [];
        $shopName = $shopInfo['shop_name'] ?? 'Shop '.$demoShopId;

        $email = 'shop-'.$demoShopId.'@import.shopefy.local';

        $owner = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'username' => 'shop-'.$demoShopId,
                'user_code' => 'DP'.str_pad((string) $demoShopId, 6, '0', STR_PAD_LEFT),
                'name' => $shopName,
                'phone' => null,
                'password' => 'password',
                'status' => 'active',
            ],
        );

        $owner->syncRoles([Role::findOrCreate('member'), Role::findOrCreate('shop')]);

        $logoPath = null;

        if (! $skipImages && ! empty($shopInfo['logo'])) {
            $logoPath = $this->downloadImage($shopInfo['logo'], 'shops/demo');
        }

        $shop = Shop::query()->updateOrCreate(
            ['slug' => 'shop-'.$demoShopId],
            [
                'user_id' => $owner->id,
                'name' => $shopName,
                'description' => $shopInfo['shop_desc'] ?? 'Imported from demo shop',
                'logo' => $logoPath,
                'status' => Shop::STATUS_ACTIVE,
            ],
        );

        $this->shopCache[$key] = [
            'shop' => $shop,
            'created' => $shop->wasRecentlyCreated,
        ];

        return $this->shopCache[$key];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>|null  $goodsInfo
     * @param  list<array<string, mixed>>  $gpres
     * @return array{product: Product, created: bool}
     */
    private function resolveProduct(
        array $meta,
        ?array $goodsInfo,
        array $gpres,
        Shop $shop,
        User $admin,
        bool $skipImages,
    ): array {
        $demo = $meta['demo'];
        $slug = 'demo-'.$demo['id'];
        $name = html_entity_decode((string) $demo['goods_name'], ENT_QUOTES | ENT_HTML5);
        $name = Str::limit($name, 250, '');
        $sellingPrice = (float) ($demo['min_price'] ?? $goodsInfo['zs_shop_price'] ?? 0);
        $purchasePrice = round($sellingPrice * 0.595, 2);
        $commission = round($sellingPrice * 0.10, 2);
        $imageUrls = $this->collectImageUrls($demo['thumb_url'] ?? null, $gpres, $sellingPrice, $goodsInfo['goods_desc'] ?? null);
        $baseDescription = $this->htmlDescription($goodsInfo['goods_desc'] ?? null, $name);

        $mainImagePath = null;
        $imagePaths = [];

        if (! $skipImages && $imageUrls !== []) {
            foreach ($imageUrls as $index => $url) {
                $path = $this->downloadImage($url, 'products/demo', (string) $demo['id']);

                if ($path === null) {
                    continue;
                }

                if ($index === 0) {
                    $mainImagePath = $path;
                }

                $imagePaths[] = ['path' => $path, 'sort' => $index];
            }
        }

        $description = $this->appendGalleryToDescription(
            $baseDescription,
            array_column($imagePaths, 'path'),
            $this->minimumGalleryCount($sellingPrice),
        );

        $payload = [
            'category_id' => $meta['category_id'],
            'shop_id' => $shop->id,
            'user_id' => $admin->id,
            'name' => $name,
            'description' => $description,
            'selling_price' => $sellingPrice,
            'purchase_price' => $purchasePrice,
            'commission' => $commission,
            'commission_type' => 'fixed',
            'stock' => self::DEFAULT_STOCK,
            'status' => Product::STATUS_ACTIVE,
        ];

        if ($mainImagePath) {
            $payload['image'] = $mainImagePath;
        }

        $product = Product::query()->where('slug', $slug)->first();

        if ($product) {
            $product->update($payload);
            $created = false;
        } else {
            $product = Product::query()->create(array_merge($payload, ['slug' => $slug]));
            $created = true;
        }

        $this->syncImages($product, $imagePaths);
        $this->ensureDistribution($shop, $product, $sellingPrice, $purchasePrice, $commission);

        return ['product' => $product, 'created' => $created];
    }

    /** @param  list<array{path: string, sort: int}>  $imagePaths */
    private function syncImages(Product $product, array $imagePaths): void
    {
        $product->images()->delete();

        foreach ($imagePaths as $item) {
            $product->images()->create([
                'image' => $item['path'],
                'sort_order' => $item['sort'],
            ]);
        }
    }

    private function ensureDistribution(
        Shop $shop,
        Product $product,
        float $sellingPrice,
        float $purchasePrice,
        float $commission,
    ): void {
        $owner = $shop->user;

        if (! $owner) {
            return;
        }

        $displaySelling = ProductDistributionService::suggestedSellingPrice($sellingPrice, $purchasePrice);
        $displayCommission = max(0, $displaySelling - $purchasePrice);

        ProductDistribution::query()->updateOrCreate(
            [
                'user_id' => $owner->id,
                'product_id' => $product->id,
            ],
            [
                'selling_price' => $displaySelling,
                'purchase_price' => $purchasePrice,
                'commission' => $displayCommission,
                'commission_type' => ProductDistribution::COMMISSION_FIXED,
                'status' => ProductDistribution::STATUS_AVAILABLE,
                'is_featured' => true,
                'featured_at' => now(),
            ],
        );
    }

    /** @param  list<string>  $storagePaths */
    private function appendGalleryToDescription(string $html, array $storagePaths, int $minimumImages = 3): string
    {
        if ($storagePaths === [] || $this->countDescriptionImages($html) >= $minimumImages) {
            return $html;
        }

        $blocks = [];

        foreach ($storagePaths as $path) {
            $url = asset('storage/'.$path);
            $blocks[] = '<p><img src="'.$url.'" alt="" style="max-width:100%;height:auto;display:block;margin:0 auto;"></p>';
        }

        return $html.'<div class="product-desc-gallery">'.implode('', $blocks).'</div>';
    }

    private function descriptionHasImages(string $html): bool
    {
        return (bool) preg_match('/<img\b/i', $html);
    }

    private function downloadImage(string $url, string $folder, ?string $goodsId = null): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if ($goodsId !== null && $goodsId !== '') {
            $folder = rtrim($folder, '/').'/'.$goodsId;
        }

        $filename = basename(parse_url($url, PHP_URL_PATH) ?: 'asset.bin');

        if ($filename === '' || $filename === 'asset.bin') {
            $filename = md5($url).'.jpg';
        }

        $storagePath = $folder.'/'.$filename;

        if (Storage::disk('public')->exists($storagePath)) {
            return $storagePath;
        }

        $response = Http::timeout(60)->get($url);

        if (! $response->successful()) {
            return null;
        }

        Storage::disk('public')->put($storagePath, $response->body());

        return $storagePath;
    }

    private function htmlDescription(?string $html, string $fallback): string
    {
        if ($html === null || trim($html) === '') {
            return $fallback;
        }

        $clean = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $clean = trim($clean);

        return $clean !== '' ? $clean : $fallback;
    }

    private function plainDescription(?string $html, string $fallback): string
    {
        if ($html === null || $html === '') {
            return $fallback;
        }

        $text = preg_replace('/<\/(p|li|div|h[1-6])>/iu', "\n", $html);
        $text = strip_tags($text ?? '');
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $clean = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line !== '') {
                $clean[] = $line;
            }
        }

        return $clean !== [] ? implode("\n", $clean) : $fallback;
    }

    private function line(string $message): void
    {
        if (app()->runningInConsole()) {
            // Allow the command to decide whether to surface this; for now we use the logger.
            logger()->info($message);
        }
    }
}
