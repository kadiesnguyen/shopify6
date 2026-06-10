@extends('layouts.admin')

@section('title', $product->exists ? __('admin.actions.edit') : __('admin.actions.add'))

@section('content')
    <x-admin.page-header :title="$product->exists ? __('admin.actions.edit') : __('admin.actions.add')" />

    <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" class="max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf @if($product->exists) @method('PUT') @endif

        <div>
            <label class="mb-1 block text-sm font-medium">Name</label>
            <input name="name" value="{{ old('name', $product->name) }}" required class="w-full rounded-lg border-slate-300">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Category</label>
                <select name="category_id" required class="w-full rounded-lg border-slate-300">
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Shop</label>
                <select name="shop_id" class="w-full rounded-lg border-slate-300">
                    <option value="">—</option>
                    @foreach ($shops as $shop)
                        <option value="{{ $shop->id }}" @selected(old('shop_id', $product->shop_id) == $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach (['selling_price', 'purchase_price', 'commission'] as $field)
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ $field }}</label>
                    <input type="number" step="0.01" name="{{ $field }}" value="{{ old($field, $product->$field) }}" required class="w-full rounded-lg border-slate-300">
                </div>
            @endforeach
            <div>
                <label class="mb-1 block text-sm font-medium">stock</label>
                <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" required class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Status</label>
                <select name="status" class="w-full rounded-lg border-slate-300">
                    @foreach (['active', 'inactive', 'draft'] as $st)
                        <option value="{{ $st }}" @selected(old('status', $product->status ?? 'active') === $st)>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <textarea name="description" rows="3" class="w-full rounded-lg border-slate-300" placeholder="Description">{{ old('description', $product->description) }}</textarea>

        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.actions.save') }}</button>
            <a href="{{ route('admin.products.index') }}" class="rounded-lg border px-4 py-2 text-sm">{{ __('admin.actions.cancel') }}</a>
        </div>
    </form>
@endsection
