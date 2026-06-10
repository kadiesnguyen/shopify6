<?php

namespace App\Support\Admin;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DashboardPeriod
{
    public function __construct(
        public readonly string $key,
        public readonly Carbon $start,
        public readonly Carbon $end,
    ) {}

    public static function fromRequest(string $period = 'month'): self
    {
        $end = now()->endOfDay();

        $start = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        return new self($period, $start, $end);
    }

    public function applyToQuery(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween($column, [$this->start, $this->end]);
    }
}
