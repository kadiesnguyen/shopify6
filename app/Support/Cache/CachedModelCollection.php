<?php

namespace App\Support\Cache;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class CachedModelCollection
{
    /**
     * Cache query results without serializing Eloquent Collection (breaks on unserialize in production).
     *
     * @param  callable(): EloquentCollection  $query
     */
    public static function remember(string $key, int $seconds, callable $query): Collection
    {
        $items = Cache::remember($key, $seconds, fn () => $query()->all());

        return collect($items);
    }
}
