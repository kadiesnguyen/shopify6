<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()->orderBy('sort_order')->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::query()->create([
            ...$request->validated(),
            'slug' => Str::slug($request->validated('name')),
        ]);

        return redirect()->route('admin.categories.index')->with('status', __('admin.categories.created'));
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')->with('status', __('admin.categories.updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return back()->with('status', __('admin.categories.deleted'));
    }
}
