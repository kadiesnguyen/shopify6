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
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4" data-product-price-fields>
            <div>
                <label class="mb-1 block text-sm font-medium">selling_price</label>
                <input type="number" step="0.01" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" required data-selling-price class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">purchase_price</label>
                <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" required readonly tabindex="-1" data-purchase-price class="w-full rounded-lg border-slate-300 bg-slate-50">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">commission</label>
                <input type="hidden" name="commission_type" value="fixed">
                <input type="number" step="0.01" name="commission" value="{{ old('commission', $product->commission) }}" required readonly tabindex="-1" data-commission class="w-full rounded-lg border-slate-300 bg-slate-50">
            </div>
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

    <script>
        (function () {
            const root = document.querySelector('[data-product-price-fields]');
            if (!root) {
                return;
            }

            const selling = root.querySelector('[data-selling-price]');
            const purchase = root.querySelector('[data-purchase-price]');
            const commission = root.querySelector('[data-commission]');

            const sync = function () {
                if (selling.value === '') {
                    return;
                }

                const price = Number(selling.value);
                if (!Number.isFinite(price) || price < 0) {
                    return;
                }

                const commissionAmount = Math.round(price * 30) / 100;
                purchase.value = (Math.round((price - commissionAmount) * 100) / 100).toFixed(2);
                commission.value = commissionAmount.toFixed(2);
            };

            selling.addEventListener('input', sync);
            sync();
        })();
    </script>
@endsection
