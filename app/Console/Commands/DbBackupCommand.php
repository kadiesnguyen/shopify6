<?php

namespace App\Console\Commands;

use App\Support\Database\DatabaseBackup;
use Illuminate\Console\Command;

class DbBackupCommand extends Command
{
    protected $signature = 'db:backup
                {--path= : Directory for the .sql file (default: storage/app/backups/database)}';

    protected $description = 'Export the MySQL database to a timestamped .sql file';

    public function handle(DatabaseBackup $backup): int
    {
        try {
            $file = $backup->export($this->option('path') ?: null);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $size = round(filesize($file) / 1024 / 1024, 2);
        $this->components->info("Backup saved: {$file} ({$size} MB)");

        return self::SUCCESS;
    }
}
