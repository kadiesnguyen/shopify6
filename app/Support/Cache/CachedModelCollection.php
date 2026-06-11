<?php

namespace App\Support\Cache;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class CachedModelCollection
{
    /**
     * Cache CMS rows as JSON and hydrate fresh models on read (PHP serialize breaks with view:cache).
     *
     * @param  class-string<Model>  $modelClass
     * @param  callable(): EloquentCollection  $query
     */
    public static function remember(string $key, int $seconds, string $modelClass, callable $query): Collection
    {
        $payload = Cache::remember($key, $seconds, function () use ($query): string {
            $rows = collect($query())
                ->map(static fn (Model $model) => $model->getAttributes())
                ->values()
                ->all();

            return json_encode($rows, JSON_THROW_ON_ERROR);
        });

        /** @var array<int, array<string, mixed>> $rows */
        $rows = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return $modelClass::hydrate($rows);
    }
}
