<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HandlesBulkActions
{
    protected function bulkDelete(string $modelClass, Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        /** @var class-string<Model> $modelClass */
        $deleted = $modelClass::query()->whereIn('id', $data['ids'])->delete();

        return response()->json([
            'message' => 'Bulk delete completed.',
            'deleted' => $deleted,
        ]);
    }
}
