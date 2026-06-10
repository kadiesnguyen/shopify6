<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category', 'shop'])
            ->when($request->string('q'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $modalProduct = null;
        $showProductModal = $request->boolean('show_create') || $request->filled('edit');

        if ($request->filled('edit')) {
            $modalProduct = Product::query()->find($request->integer('edit'));
        }

        $categories = Category::query()->orderBy('name')->get();
        $shops = Shop::query()->orderBy('name')->get();
        $productTotal = Product::query()->count();

        return view('admin.products.index', compact(
            'products',
            'modalProduct',
            'showProductModal',
            'categories',
            'shops',
            'productTotal',
        ));
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product,
            'categories' => Category::query()->orderBy('name')->get(),
            'shops' => Shop::query()->orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));

        if ($request->hasFile('image_file')) {
            $data['image'] = $request->file('image_file')->store('products', 'public');
        }

        unset($data['image_file']);

        Product::query()->create($data);

        return redirect()->route('admin.products.index')->with('status', __('admin.products.created'));
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
            'shops' => Shop::query()->orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image_file')) {
            $data['image'] = $request->file('image_file')->store('products', 'public');
        }

        unset($data['image_file']);

        $product->update($data);

        return redirect()->route('admin.products.index')->with('status', __('admin.products.updated'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('status', __('admin.products.deleted'));
    }
}
