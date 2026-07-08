<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Import\DemoProductImporter;
use App\Services\Import\SieummoProductImporter;
use App\Services\Member\ProductDetailService;
use Illuminate\Console\Command;

class BackfillProductGalleryCommand extends Command
{
    protected $signature = 'products:backfill-gallery
                {--limit= : Max products to update}
                {--sleep=100 : Milliseconds between remote fetches}
                {--skip-images : Update descriptions only, do not download images}
                {--local-only : Skip demo API; use on-disk product images only}
                {--source=https://sieummo.vn : Sieummo base URL for sm-* gallery sync}';

    protected $description = 'Backfill product gallery/description images (demo API + local fallback)';

    public function handle(
        DemoProductImporter $importer,
        SieummoProductImporter $sieummo,
        ProductDetailService $details,
    ): int {
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $sleepMs = max(0, (int) $this->option('sleep'));
        $skipImages = (bool) $this->option('skip-images');
        $localOnly = (bool) $this->option('local-only');
        $sourceUrl = (string) $this->option('source');
        $updated = 0;
        $skipped = 0;

        if (! $localOnly && ! \Illuminate\Support\Facades\Cache::has('demo:goods-name-index')) {
            $this->warn('No demo name index yet — sm-* products will use local image fallback.');
        }

        $products = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->withCount('images')
            ->orderBy('id')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get()
            ->filter(function (Product $product) use ($importer): bool {
                $target = $importer->targetGalleryCount((float) $product->selling_price);

                if ((int) $product->images_count < $target) {
                    return true;
                }

                return $importer->countDescriptionImages((string) $product->description) < $target;
            })
            ->values();

        foreach ($products as $product) {
            $synced = false;

            if (! $localOnly) {
                if (preg_match('/^sm-(\d+)$/', (string) $product->slug, $match)) {
                    $product->loadMissing('shop');
                    $shopId = $this->sieummoShopId($product);

                    if ($shopId !== null && $sieummo->syncGalleryForProduct(
                        $product->fresh(),
                        $sourceUrl,
                        (int) $match[1],
                        $shopId,
                        $skipImages,
                    )) {
                        $updated++;
                        $this->line("Updated #{$product->id} {$product->slug} from sieummo:{$match[1]}");
                        $synced = true;
                    }
                }

                if (! $synced) {
                    $goodsId = null;

                    if (preg_match('/^demo-(\d+)$/', (string) $product->slug, $match)) {
                        $goodsId = (int) $match[1];
                    } else {
                        $goodsId = $details->findDemoGoodsIdByName($product->name);
                    }

                    if ($goodsId !== null && $importer->syncGalleryForProduct($product->fresh(), $goodsId, $skipImages)) {
                        $updated++;
                        $this->line("Updated #{$product->id} {$product->slug} from demo:{$goodsId}");
                        $synced = true;
                    }
                }

                if ($synced && $sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }

            if ($synced) {
                continue;
            }

            if ($importer->syncLocalGalleryForProduct($product->fresh(['images', 'category']))) {
                $updated++;
                $this->line("Updated #{$product->id} {$product->slug} from local images");

                continue;
            }

            $skipped++;
            $this->line("Skip #{$product->id} {$product->slug} — no usable images");
        }

        $this->info("Done. updated={$updated} skipped={$skipped}");

        return self::SUCCESS;
    }

    private function sieummoShopId(Product $product): ?int
    {
        $shopSlug = $product->shop?->slug;

        if ($shopSlug === null || ! preg_match('/-(\d+)$/', $shopSlug, $match)) {
            return null;
        }

        return (int) $match[1];
    }
}
