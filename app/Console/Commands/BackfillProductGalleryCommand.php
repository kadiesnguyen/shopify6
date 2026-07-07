<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Import\DemoProductImporter;
use App\Services\Member\ProductDetailService;
use Illuminate\Console\Command;

class BackfillProductGalleryCommand extends Command
{
    protected $signature = 'products:backfill-gallery
                {--limit= : Max products to update}
                {--sleep=100 : Milliseconds between remote fetches}
                {--skip-images : Update descriptions only, do not download images}';

    protected $description = 'Backfill product gallery/description images from demo API (shopify.lljcj.com source)';

    public function handle(DemoProductImporter $importer, ProductDetailService $details): int
    {
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $sleepMs = max(0, (int) $this->option('sleep'));
        $skipImages = (bool) $this->option('skip-images');
        $updated = 0;
        $skipped = 0;

        if (! \Illuminate\Support\Facades\Cache::has('demo:goods-name-index')) {
            $this->warn('No demo name index yet — sm-* products need demo:import-products first for name matching.');
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
            $goodsId = null;

            if (preg_match('/^demo-(\d+)$/', (string) $product->slug, $match)) {
                $goodsId = (int) $match[1];
            } else {
                $goodsId = $details->findDemoGoodsIdByName($product->name);
            }

            if ($goodsId === null) {
                $skipped++;
                $this->line("Skip #{$product->id} {$product->slug} — no demo match");

                continue;
            }

            if ($importer->syncGalleryForProduct($product->fresh(), $goodsId, $skipImages)) {
                $updated++;
                $this->line("Updated #{$product->id} {$product->slug} from demo:{$goodsId}");
            } else {
                $skipped++;
                $this->line("Skip #{$product->id} {$product->slug} — insufficient gallery");
            }

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $this->info("Done. updated={$updated} skipped={$skipped}");

        return self::SUCCESS;
    }
}
