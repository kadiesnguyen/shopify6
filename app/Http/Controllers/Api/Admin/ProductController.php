<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\Api\CsvExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->paginateQuery(
            Product::query()->with(['category', 'shop']),
            $request,
            searchColumns: ['name', 'slug'],
            filterable: ['status', 'category_id'],
            sortable: ['created_at', 'name', 'selling_price', 'stock'],
        );

        return ProductResource::collection($items);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'name' => ['required', 'string'],
            'selling_price' => ['required', 'numeric'],
            'purchase_price' => ['required', 'numeric'],
            'commission' => ['required', 'numeric'],
            'stock' => ['required', 'integer'],
            'status' => ['required', 'in:active,inactive,draft'],
            'description' => ['nullable', 'string'],
        ]);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));

        $product = Product::query()->create($data);

        return response()->json(['data' => new ProductResource($product->load('category', 'shop'))], 201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category', 'shop'));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['sometimes', 'exists:categories,id'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'name' => ['sometimes', 'string'],
            'selling_price' => ['sometimes', 'numeric'],
            'purchase_price' => ['sometimes', 'numeric'],
            'commission' => ['sometimes', 'numeric'],
            'stock' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'in:active,inactive,draft'],
            'description' => ['nullable', 'string'],
        ]);

        $product->update($data);

        return response()->json(['data' => new ProductResource($product->fresh()->load('category', 'shop'))]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function bulk(Request $request): JsonResponse
    {
        return $this->bulkDelete(Product::class, $request);
    }

    public function export(Request $request, CsvExportService $export)
    {
        $query = Product::query();

        if ($search = $request->string('search')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $export->stream(
            $query,
            ['ID', 'Name', 'Price', 'Stock', 'Status'],
            fn (Product $p) => [$p->id, $p->name, $p->selling_price, $p->stock, $p->status],
            'products-'.now()->format('Ymd-His').'.csv',
        );
    }
}
