<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait HandlesApiListing
{
    protected function paginateQuery(
        Builder $query,
        Request $request,
        array $searchColumns = [],
        array $filterable = [],
        array $sortable = ['created_at'],
        string $defaultSort = 'created_at',
    ): LengthAwarePaginator {
        if ($search = $request->string('search')->toString()) {
            $query->where(function (Builder $builder) use ($searchColumns, $search): void {
                foreach ($searchColumns as $column) {
                    $builder->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        foreach ($filterable as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        $sort = $request->string('sort')->toString() ?: $defaultSort;
        $direction = $request->string('direction')->lower()->toString() === 'asc' ? 'asc' : 'desc';

        if (in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy($defaultSort, 'desc');
        }

        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        return $query->paginate($perPage)->withQueryString();
    }
}
