<?php

namespace App\Console\Commands;

use App\Services\Import\DemoProductImporter;
use Illuminate\Console\Command;

class DemoCatalogStatsCommand extends Command
{
    protected $signature = 'demo:catalog-stats
        {--source= : Demo API base URL (default from config)}';

    protected $description = 'Count demo API catalog size with per-category progress';

    public function handle(DemoProductImporter $importer): int
    {
        $source = (string) $this->option('source');

        if ($source === '') {
            $source = (string) config('services.demo_api.base_url');
        }

        $this->components->info("Counting demo catalog at {$source}");

        try {
            $stats = $importer->catalogStats(function (array $progress): void {
                $this->line(sprintf(
                    '[%s] page %d → category %d, running total %d products (%d pages)',
                    $progress['category'],
                    $progress['page'],
                    $progress['category_total'],
                    $progress['products'],
                    $progress['pages'],
                ));
            });
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Categories', (string) $stats['categories']);
        $this->components->twoColumnDetail('Products', (string) $stats['products']);
        $this->components->twoColumnDetail('List pages', (string) $stats['pages']);
        $this->components->success('Demo catalog stats complete.');

        return self::SUCCESS;
    }
}
