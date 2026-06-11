<?php

namespace App\Console\Commands;

use App\Services\Import\SieummoProductImporter;
use Illuminate\Console\Command;

class ImportSieummoProductsCommand extends Command
{
    protected $signature = 'sieummo:import-products
                {--source=https://sieummo.vn : Sieummo base URL}
                {--dry-run : Parse only, do not write to database}
                {--limit= : Import only the first N products}
                {--skip-images : Skip downloading product and shop images}
                {--with-details : Fetch product detail pages for descriptions (slower)}
                {--deactivate-missing : Deactivate sm-* products not in the import}
                {--allow-data-loss : Required with --deactivate-missing}';

    protected $description = 'Import product catalog from sieummo.vn into Shopefy';

    public function handle(SieummoProductImporter $importer): int
    {
        if ($this->option('deactivate-missing') && ! $this->option('allow-data-loss')) {
            $this->components->error('Blocked --deactivate-missing: can hide catalog products.');
            $this->line('  • Import without deactivating:  php artisan sieummo:import-products');
            $this->line('  • Force deactivate missing:     php artisan sieummo:import-products --deactivate-missing --allow-data-loss');

            return self::FAILURE;
        }

        $source = (string) $this->option('source');
        $limit = $this->option('limit');
        $limit = $limit !== null ? max(1, (int) $limit) : null;

        $this->components->info("Importing catalog from {$source}/products");

        try {
            $stats = $importer->import(
                sourceUrl: $source,
                dryRun: (bool) $this->option('dry-run'),
                limit: $limit,
                skipImages: (bool) $this->option('skip-images'),
                withDetails: (bool) $this->option('with-details'),
                deactivateMissing: (bool) $this->option('deactivate-missing'),
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
        $this->components->twoColumnDetail('Shops (new)', (string) $stats['shops']);
        $this->components->twoColumnDetail('Categories (new)', (string) $stats['categories']);

        $this->components->success('Sieummo product import finished.');

        return self::SUCCESS;
    }
}
