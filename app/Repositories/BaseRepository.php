<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements RepositoryInterface
{
    public function __construct(protected Model $model) {}

    public function find(int|string $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    public function create(array $attributes): Model
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }
}
