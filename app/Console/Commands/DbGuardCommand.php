<?php

namespace App\Console\Commands;

use App\Support\Database\DatabaseGuard;
use Illuminate\Console\Command;

class DbGuardCommand extends Command
{
    protected $signature = 'db:guard';

    protected $description = 'Show live database record counts (data preservation checkpoint)';

    public function handle(): int
    {
        foreach (DatabaseGuard::summaryLines() as $line) {
            $this->line('  '.$line);
        }

        if (DatabaseGuard::hasPreservedData()) {
            $this->components->info('Database has preserved data — do not run migrate:fresh or db:seed without backup.');
        } else {
            $this->components->warn('Database is empty — safe to seed once.');
        }

        return self::SUCCESS;
    }
}
