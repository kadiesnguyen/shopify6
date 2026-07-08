<?php

namespace App\Services\Import;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDistribution;
use App\Models\Shop;
use App\Models\User;
use App\Services\Member\ProductDistributionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class SieummoProductImporter
{
    public function __construct(
        private readonly SieummoCatalogParser $parser,
        private readonly SieummoProductDetailParser $detailParser,
    ) {}

    /**
     * @return array{created: int, updated: int, skipped: int, shops: int, categories: int}
     */
    public function import(
        string $sourceUrl,
        bool $dryRun = false,
        ?int $limit = null,
        bool $skipImages = false,
        bool $withDetails = false,
        bool $deactivateMissing = false,
    ): array {
        set_time_limit(0);

        $html = Http::timeout(120)->get(rtrim($sourceUrl, '/').'/products')->throw()->body();
        $items = $this->parser->parse($html);

        if ($limit !== null) {
            $items = array_slice($items, 0, $limit);
        }

        if ($items === []) {
            throw new RuntimeException('No products parsed from sieummo catalog.');
        }

        $admin = User::role('admin')->first() ?? User::query()->first();

        if (! $admin) {
            throw new RuntimeException('No admin user found. Run db:seed first.');
        }

        Role::findOrCreate('member');
        Role::findOrCreate('shop');

        $stats = [
            'parsed' => count($items),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'shops' => 0,
            'categories' => 0,
        ];

        $importedSlugs = [];
        $shopCache = [];
        $categoryCache = [];

        foreach ($items as $index => $item) {
            $slug = $this->productSlug($item['sieummo_id']);
            $importedSlugs[] = $slug;

            if ($dryRun) {
                continue;
            }

            [$shop, $shopCreated] = $this->resolveShop($item, $sourceUrl, $skipImages, $shopCache);
            [$category, $categoryCreated] = $this->resolveCategory($item['category_name'], $categoryCache);

            if ($shopCreated) {
                $stats['shops']++;
            }

            if ($categoryCreated) {
                $stats['categories']++;
            }

            $pricing = $this->pricingFor($item['selling_price']);
            $description = $withDetails
                ? $this->fetchDescription($sourceUrl, $item['sieummo_id'], $item['sieummo_shop_id'])
                : $item['name'];

            $imagePath = $skipImages
                ? null
                : $this->downloadAsset($sourceUrl, $item['image_url'], 'products/sieummo');

            $payload = [
                'category_id' => $category->id,
                'shop_id' => $shop->id,
                'user_id' => $admin->id,
                'name' => $item['name'],
                'description' => $description ?: $item['name'],
                'selling_price' => $item['selling_price'],
                'purchase_price' => $pricing['purchase_price'],
                'commission' => $pricing['commission'],
                'commission_type' => 'fixed',
                'stock' => $item['stock'],
                'status' => Product::STATUS_ACTIVE,
            ];

            if ($imagePath) {
                $payload['image'] = $imagePath;
            }

            $product = Product::query()->where('slug', $slug)->first();

            if ($product) {
                $product->update($payload);
                $stats['updated']++;
            } else {
                $product = Product::query()->create(array_merge($payload, ['slug' => $slug]));
                $stats['created']++;
            }

            $this->ensureDistribution($shop, $product);

            if ($withDetails) {
                $this->syncGalleryForProduct(
                    $product,
                    $sourceUrl,
                    $item['sieummo_id'],
                    $item['sieummo_shop_id'],
                    $skipImages,
                );
            }

            if (($index + 1) % 25 === 0) {
                gc_collect_cycles();
            }
        }

        if ($deactivateMissing && ! $dryRun && $importedSlugs !== []) {
            Product::query()
                ->where('slug', 'like', 'sm-%')
                ->whereNotIn('slug', $importedSlugs)
                ->update(['status' => Product::STATUS_INACTIVE]);
        }

        return $stats;
    }

    public function productSlug(int $sieummoId): string
    {
        return 'sm-'.$sieummoId;
    }

    public function syncGalleryForProduct(
        Product $product,
        string $sourceUrl,
        int $sieummoId,
        int $shopId,
        bool $skipImages = false,
    ): bool {
        try {
            $html = Http::timeout(30)
                ->get(rtrim($sourceUrl, '/').'/product', ['id' => $sieummoId, 'shop' => $shopId])
                ->body();
        } catch (\Throwable) {
            return false;
        }

        $urls = $this->detailParser->parseGalleryUrls($html);

        if ($urls === []) {
            return false;
        }

        $target = app(DemoProductImporter::class)->targetGalleryCount((float) $product->selling_price);
        $urls = array_slice($urls, 0, $target);
        $imagePaths = [];

        if (! $skipImages) {
            foreach ($urls as $index => $url) {
                $path = $this->downloadAsset($sourceUrl, $url, 'products/sieummo');

                if ($path === null) {
                    continue;
                }

                $imagePaths[] = ['path' => $path, 'sort' => $index];
            }
        }

        if ($imagePaths === [] && ! $skipImages) {
            return false;
        }

        $payload = [];

        if ($imagePaths !== []) {
            $payload['image'] = $imagePaths[0]['path'];
        }

        if ($payload !== []) {
            $product->update($payload);
        }

        $this->syncImages($product, $imagePaths);

        return true;
    }

    /** @param  array<string, Shop>  $cache */
    /** @return array{0: Shop, 1: bool} */
    private function resolveShop(array $item, string $sourceUrl, bool $skipImages, array &$cache): array
    {
        $cacheKey = (string) $item['sieummo_shop_id'];

        if (isset($cache[$cacheKey])) {
            return [$cache[$cacheKey], false];
        }

        $slug = Str::slug($item['shop_name']).'-'.$item['sieummo_shop_id'];
        $email = 'shop-'.$item['sieummo_shop_id'].'@import.shopefy.local';

        $owner = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'username' => Str::slug($item['shop_name']).'-'.$item['sieummo_shop_id'],
                'user_code' => 'SM'.str_pad((string) $item['sieummo_shop_id'], 6, '0', STR_PAD_LEFT),
                'name' => $item['shop_name'],
                'phone' => null,
                'password' => 'password',
                'status' => 'active',
            ],
        );

        $owner->syncRoles([Role::findOrCreate('member'), Role::findOrCreate('shop')]);

        $logoPath = null;

        if (! $skipImages && ! empty($item['shop_logo_url'])) {
            $logoPath = $this->downloadAsset($sourceUrl, $item['shop_logo_url'], 'shops/sieummo');
        }

        $shop = Shop::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'user_id' => $owner->id,
                'name' => $item['shop_name'],
                'description' => 'Imported from sieummo.vn',
                'logo' => $logoPath,
                'status' => Shop::STATUS_ACTIVE,
            ],
        );

        $cache[$cacheKey] = $shop;

        return [$shop, $shop->wasRecentlyCreated];
    }

    /** @param  array<string, Category>  $cache */
    /** @return array{0: Category, 1: bool} */
    private function resolveCategory(string $name, array &$cache): array
    {
        $slug = Str::slug($name) ?: 'khac';

        if (isset($cache[$slug])) {
            return [$cache[$slug], false];
        }

        $sortOrder = Category::query()->max('sort_order') + 1;

        $category = Category::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'status' => Category::STATUS_ACTIVE,
                'sort_order' => $sortOrder,
            ],
        );

        $cache[$slug] = $category;

        return [$category, $category->wasRecentlyCreated];
    }

    /** @return array{purchase_price: float, commission: float} */
    private function pricingFor(float $sellingPrice): array
    {
        $purchasePrice = ProductDistributionService::costPriceForPrice($sellingPrice);

        return [
            'purchase_price' => $purchasePrice,
            'commission' => round($sellingPrice - $purchasePrice, 2),
        ];
    }

    private function ensureDistribution(Shop $shop, Product $product): void
    {
        $owner = $shop->user;

        if (! $owner) {
            return;
        }

        $distribution = ProductDistribution::query()->firstOrCreate(
            [
                'user_id' => $owner->id,
                'product_id' => $product->id,
            ],
            [
                'selling_price' => $product->selling_price,
                'purchase_price' => $product->purchase_price,
                'commission' => $product->commission,
                'commission_type' => ProductDistribution::COMMISSION_FIXED,
                'status' => ProductDistribution::STATUS_AVAILABLE,
            ],
        );

        if (! $distribution->wasRecentlyCreated) {
            $distribution->update([
                'selling_price' => $product->selling_price,
                'purchase_price' => $product->purchase_price,
                'commission' => $product->commission,
                'status' => ProductDistribution::STATUS_AVAILABLE,
            ]);
        }
    }

    private function fetchDescription(string $sourceUrl, int $productId, int $shopId): ?string
    {
        $html = Http::timeout(30)
            ->get(rtrim($sourceUrl, '/').'/product', ['id' => $productId, 'shop' => $shopId])
            ->body();

        return $this->detailParser->parseDescriptionHtml($html)
            ?? $this->detailParser->parseDescription($html);
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

    private function downloadAsset(string $sourceUrl, string $path, string $folder): ?string
    {
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        $url = str_starts_with($path, 'http')
            ? $path
            : rtrim($sourceUrl, '/').'/'.ltrim($path, '/');

        $filename = basename(parse_url($url, PHP_URL_PATH) ?: 'asset.bin');
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
}
