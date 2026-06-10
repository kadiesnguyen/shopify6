<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->paginateQuery(
            Category::query(),
            $request,
            searchColumns: ['name', 'slug'],
            filterable: ['status'],
            sortable: ['sort_order', 'name', 'created_at'],
            defaultSort: 'sort_order',
        );

        return CategoryResource::collection($items);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $data['slug'] = Str::slug($data['name']);

        $category = Category::query()->create($data);

        return response()->json(['data' => new CategoryResource($category)], 201);
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $category->update($data);

        return response()->json(['data' => new CategoryResource($category->fresh())]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function bulk(Request $request): JsonResponse
    {
        return $this->bulkDelete(Category::class, $request);
    }
}
