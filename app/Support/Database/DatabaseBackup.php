<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\Process;
use RuntimeException;

class DatabaseBackup
{
    public function export(?string $directory = null): string
    {
        $config = config('database.connections.'.config('database.default'));

        if (($config['driver'] ?? '') !== 'mysql') {
            throw new RuntimeException('db:backup only supports mysql.');
        }

        $directory = $directory ?? storage_path('app/backups/database');

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Cannot create backup directory: {$directory}");
        }

        $file = $directory.'/shopefy_'.now()->format('Ymd_His').'.sql';

        $command = [
            'mysqldump',
            '-h'.($config['host'] ?? '127.0.0.1'),
            '-P'.(string) ($config['port'] ?? 3306),
            '-u'.($config['username'] ?? 'root'),
            '--single-transaction',
            '--routines',
            '--triggers',
            (string) ($config['database'] ?? ''),
        ];

        $result = Process::env([
            'MYSQL_PWD' => (string) ($config['password'] ?? ''),
        ])->run($command);

        if (! $result->successful()) {
            throw new RuntimeException(trim($result->errorOutput()) ?: 'mysqldump failed.');
        }

        file_put_contents($file, $result->output());

        if (! is_file($file) || filesize($file) === 0) {
            throw new RuntimeException('Backup file is empty.');
        }

        return $file;
    }
}
