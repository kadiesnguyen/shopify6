<?php

namespace App\Console\Commands;

use App\Services\Import\DemoProductImporter;
use Illuminate\Console\Command;

class ImportDemoProductsCommand extends Command
{
    protected $signature = 'demo:import-products
        {--source= : Demo API base URL (default from config)}
        {--dry-run : Parse only, do not write to database}
        {--limit= : Import only the first N products}
        {--skip-images : Skip downloading product and shop images}
        {--sleep=100 : Milliseconds to sleep between detail requests}';

    protected $description = 'Import products and shops from Shopify demo site into Shopefy';

    public function handle(DemoProductImporter $importer): int
    {
        $source = (string) $this->option('source');

        if ($source === '') {
            $source = (string) config('services.demo_api.base_url');
        }

        $limit = $this->option('limit');
        $limit = $limit !== null ? max(1, (int) $limit) : null;
        $sleep = (int) $this->option('sleep');
        $sleep = max(0, $sleep);

        $this->components->info("Importing products from demo API {$source}");

        $progressLog = storage_path('logs/demo-import-progress.log');
        @file_put_contents($progressLog, now()->toDateTimeString()." START\n");

        try {
            $stats = $importer->import(
                sourceUrl: $source,
                dryRun: (bool) $this->option('dry-run'),
                limit: $limit,
                skipImages: (bool) $this->option('skip-images'),
                sleepMs: $sleep,
                onProgress: function (array $progress) use ($progressLog): void {
                    $line = sprintf(
                        '%s goods:%s cat:%s parsed:%d saved:%d skipped:%d',
                        now()->toDateTimeString(),
                        $progress['goods_id'],
                        $progress['category'],
                        $progress['parsed'],
                        $progress['created'] + $progress['updated'],
                        $progress['skipped'] ?? 0,
                    );

                    @file_put_contents($progressLog, $line."\n", FILE_APPEND);
                },
            );
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->components->twoColumnDetail('Parsed', (string) $stats['parsed']);
            $this->components->warn('Dry run complete — no database changes were made.');

            return self::SUCCESS;
        }

        $this->components->twoColumnDetail('Parsed', (string) $stats['parsed']);
        $this->components->twoColumnDetail('Created', (string) $stats['created']);
        $this->components->twoColumnDetail('Updated', (string) $stats['updated']);
        $this->components->twoColumnDetail('Skipped', (string) ($stats['skipped'] ?? 0));
        $this->components->twoColumnDetail('Shops (new)', (string) $stats['shops']);
        $this->components->twoColumnDetail('Categories (new)', (string) $stats['categories']);

        $this->components->success('Demo product import finished.');

        return self::SUCCESS;
    }
}
