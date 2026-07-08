<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
            $modalProduct = Product::query()->with('images')->find($request->integer('edit'));
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

        unset($data['image_file'], $data['gallery_files'], $data['remove_gallery_ids']);

        $product = Product::query()->create($data);
        $this->syncGalleryImages($product, $request);

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

        unset($data['image_file'], $data['gallery_files'], $data['remove_gallery_ids']);

        $product->update($data);
        $this->syncGalleryImages($product, $request);

        return redirect()->route('admin.products.index')->with('status', __('admin.products.updated'));
    }

    private function syncGalleryImages(Product $product, Request $request): void
    {
        $removeIds = collect($request->input('remove_gallery_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        if ($removeIds !== []) {
            $product->images()->whereIn('id', $removeIds)->delete();
        }

        $galleryFiles = array_values(array_filter(
            $request->file('gallery_files', []) ?? [],
            fn ($file) => $file instanceof UploadedFile && $file->isValid(),
        ));

        if ($galleryFiles === []) {
            return;
        }

        $nextSort = ((int) $product->images()->max('sort_order')) + 1;

        foreach ($galleryFiles as $file) {
            $product->images()->create([
                'image' => $file->store('products/gallery', 'public'),
                'sort_order' => $nextSort++,
            ]);
        }
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('status', __('admin.products.deleted'));
    }
}
