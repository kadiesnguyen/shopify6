<?php

namespace App\Console\Commands;

use App\Support\Database\DatabaseGuard;
use Illuminate\Database\Console\Migrations\FreshCommand;

class MigrateFreshCommand extends FreshCommand
{
    /**
     * @var string
     */
    protected $signature = 'migrate:fresh
                {--database= : The database connection to use}
                {--drop-views : Drop all tables and views}
                {--drop-types : Drop all types (Postgres only)}
                {--force : Force the operation to run when in production}
                {--path=* : The path(s) to the migrations files to be executed}
                {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                {--schema-path= : The path to a schema dump file}
                {--seed : Indicates if the seed task should be re-run}
                {--seeder= : The class name of the root seeder}
                {--step : Force the migrations to be run so they can be rolled back individually}
                {--allow-user-loss : Allow wiping existing users (destructive)}
                {--allow-data-loss : Alias for --allow-user-loss}';

    public function handle(): int
    {
        if ($this->shouldBlockDestructiveFresh()) {
            $this->components->error('Blocked migrate:fresh: database already has live data.');
            foreach (DatabaseGuard::summaryLines() as $line) {
                $this->line('  '.$line);
            }
            $this->line('  • Run migrations only:  php artisan migrate');
            $this->line('  • Safe baseline seed:   php artisan db:seed --class=Database\\\\Seeders\\\\BaselineDatabaseSeeder');
            $this->line('  • Backup first:         php artisan db:backup');
            $this->line('  • Force full wipe:      php artisan migrate:fresh --allow-data-loss [--seed]');

            return self::FAILURE;
        }

        return parent::handle();
    }

    private function shouldBlockDestructiveFresh(): bool
    {
        if ($this->option('allow-user-loss') || $this->option('allow-data-loss')) {
            return false;
        }

        if ($this->laravel->runningUnitTests()
            && config('database.default') !== 'mysql') {
            return false;
        }

        return DatabaseGuard::hasPreservedData();
    }
}
