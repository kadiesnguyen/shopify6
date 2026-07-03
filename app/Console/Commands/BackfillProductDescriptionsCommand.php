<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Member\ProductDetailService;
use Illuminate\Console\Command;

class BackfillProductDescriptionsCommand extends Command
{
    protected $signature = 'products:backfill-descriptions
                {--limit= : Max products to update}
                {--sleep=150 : Milliseconds between remote fetches}';

    protected $description = 'Fetch and store rich product descriptions from demo/sieummo sources';

    public function handle(ProductDetailService $details): int
    {
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $sleepMs = max(0, (int) $this->option('sleep'));
        $updated = 0;
        $skipped = 0;

        $query = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $products = $query->get();

        foreach ($products as $product) {
            $before = trim((string) $product->description);

        if ($details->isRichDescription($before, $product->name) && ! $details->needsHtmlUpgrade($product, $before)) {
                $skipped++;

                continue;
            }

            $payload = $details->resolve($product);
            $after = trim((string) ($payload['description'] ?? ''));

            if ($after !== $before && $details->isRichDescription($after, $product->name)) {
                $updated++;
                $this->line("Updated #{$product->id} {$product->slug}");
            } else {
                $skipped++;
            }

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $this->info("Done. updated={$updated} skipped={$skipped}");

        return self::SUCCESS;
    }
}
