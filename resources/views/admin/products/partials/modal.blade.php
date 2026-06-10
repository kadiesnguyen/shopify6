@php($isEdit = $product->exists)
@php($closeUrl = route('admin.products.index', $listQuery))

<div class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center">
    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-5 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">{{ $isEdit ? __('admin.products.edit_product') : __('admin.products.add_product') }}</h3>
            <a href="{{ $closeUrl }}" class="text-2xl leading-none text-slate-400 hover:text-slate-600">×</a>
        </div>

        <form
            method="POST"
            action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}"
            enctype="multipart/form-data"
            class="space-y-4"
        >
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.name') }} *</label>
                <input name="name" value="{{ old('name', $product->name) }}" required class="w-full rounded-lg border-slate-300 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.category') }}</label>
                <select name="category_id" required class="w-full rounded-lg border-slate-300 text-sm">
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.description') }}</label>
                <textarea name="description" rows="3" class="w-full rounded-lg border-slate-300 text-sm">{{ old('description', $product->description) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.selling_price') }} *</label>
                    <input type="number" step="0.01" min="0" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" required class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.purchase_price') }}</label>
                    <input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" required class="w-full rounded-lg border-slate-300 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.products.commission_type') }}</label>
                    <select name="commission_type" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="fixed" @selected(old('commission_type', $product->commission_type ?? 'fixed') === 'fixed')>{{ __('admin.users.distributions.commission_type_fixed') }}</option>
                        <option value="percent" @selected(old('commission_type', $product->commission_type ?? 'fixed') === 'percent')>{{ __('admin.users.distributions.commission_type_percent') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.commission') }}</label>
                    <input type="number" step="0.01" min="0" name="commission" value="{{ old('commission', $product->commission) }}" required class="w-full rounded-lg border-slate-300 text-sm">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.stock') }}</label>
                <input type="number" min="0" name="stock" value="{{ old('stock', $product->stock) }}" required class="w-full rounded-lg border-slate-300 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.image') }}</label>
                @if ($product->imageUrl())
                    <img src="{{ $product->imageUrl() }}" alt="" class="mb-2 size-16 rounded object-cover">
                @endif
                <input type="file" name="image_file" accept="image/*" class="w-full text-sm">
            </div>

            <input type="hidden" name="status" value="{{ old('status', $product->status ?? 'active') }}">

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <a href="{{ $closeUrl }}" class="rounded-lg border px-4 py-2 text-sm">{{ __('admin.actions.cancel') }}</a>
                <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.actions.save') }}</button>
            </div>
        </form>
    </div>
</div>
