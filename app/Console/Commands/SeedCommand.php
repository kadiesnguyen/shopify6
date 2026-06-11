<?php

namespace App\Console\Commands;

use App\Support\Database\DatabaseGuard;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Console\Seeds\SeedCommand as BaseSeedCommand;
use Symfony\Component\Console\Input\InputOption;

class SeedCommand extends BaseSeedCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->getDefinition()->addOption(
            new InputOption(
                'allow-data-loss',
                null,
                InputOption::VALUE_NONE,
                'Allow full DatabaseSeeder on a populated database',
            ),
        );
    }

    public function handle(): int
    {
        if ($this->shouldBlockFullSeed()) {
            $this->components->error('Blocked db:seed: database already has live data.');
            foreach (DatabaseGuard::summaryLines() as $line) {
                $this->line('  '.$line);
            }
            $this->line('  • Safe baseline only:  php artisan db:seed --class=Database\\\\Seeders\\\\BaselineDatabaseSeeder');
            $this->line('  • Migrate only:        php artisan migrate');
            $this->line('  • Backup first:        php artisan db:backup');
            $this->line('  • Force full reseed:   php artisan db:seed --allow-data-loss');

            return self::FAILURE;
        }

        return parent::handle();
    }

    private function shouldBlockFullSeed(): bool
    {
        if ($this->option('allow-data-loss')) {
            return false;
        }

        $class = ltrim((string) ($this->option('class') ?: DatabaseSeeder::class), '\\');

        if ($class !== DatabaseSeeder::class) {
            return false;
        }

        return DatabaseGuard::hasPreservedData();
    }
}
