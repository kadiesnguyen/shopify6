<?php

namespace App\Support\Database\Concerns;

use App\Support\Database\DatabaseGuard;

trait SkipsWhenDataExists
{
    protected function skipWhenPreservedDataExists(string $seederName): bool
    {
        if (! DatabaseGuard::hasPreservedData()) {
            return false;
        }

        if ($this->command) {
            $this->command->warn("Skipped {$seederName}: database already has live data.");
            foreach (DatabaseGuard::summaryLines() as $line) {
                $this->command->line('  '.$line);
            }
        }

        return true;
    }
}
