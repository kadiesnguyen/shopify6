@php($isEdit = $product->exists)
@php($closeUrl = route('admin.products.index', $listQuery))
@php($galleryImages = $isEdit ? $product->images : collect())

<div class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-2 sm:items-center sm:p-4">
    <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-xl">
        <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-4 py-3 sm:px-5">
            <h3 class="text-lg font-semibold">{{ $isEdit ? __('admin.products.edit_product') : __('admin.products.add_product') }}</h3>
            <a href="{{ $closeUrl }}" class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="{{ __('admin.actions.cancel') }}">×</a>
        </div>

        <form
            method="POST"
            action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}"
            enctype="multipart/form-data"
            class="flex min-h-0 flex-1 flex-col"
            data-rich-editor-form
            data-cms-image-upload-url="{{ route('admin.settings.cms-images') }}"
        >
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-4 sm:px-5">
                <div class="grid gap-4 md:grid-cols-2">
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
                </div>

                @include('admin.settings.partials.rich-editor', [
                    'name' => 'description',
                    'label' => __('admin.columns.description'),
                    'value' => old('description', $product->description),
                    'tall' => true,
                ])

                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.selling_price') }} *</label>
                        <input type="number" step="0.01" min="0" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" required class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.purchase_price') }}</label>
                        <input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" required class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
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

                <div class="max-w-xs">
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.stock') }}</label>
                    <input type="number" min="0" name="stock" value="{{ old('stock', $product->stock) }}" required class="w-full rounded-lg border-slate-300 text-sm">
                </div>

                <div class="space-y-4 rounded-lg border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">{{ __('admin.products.main_image') }}</label>
                        @if ($product->imageUrl())
                            <img src="{{ $product->imageUrl() }}" alt="" class="mb-2 size-20 rounded-lg border border-slate-200 object-cover">
                        @endif
                        <input type="file" name="image_file" accept="image/*" class="w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-brand file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                    </div>

                    @if ($galleryImages->isNotEmpty())
                        <div>
                            <p class="mb-2 text-sm font-medium text-slate-700">{{ __('admin.products.gallery_images') }}</p>
                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5">
                                @foreach ($galleryImages as $galleryImage)
                                    <label class="group relative overflow-hidden rounded-lg border border-slate-200 bg-white">
                                        <img src="{{ $galleryImage->imageUrl() }}" alt="" class="aspect-square w-full object-cover">
                                        <span class="absolute inset-x-0 bottom-0 flex items-center gap-1 bg-black/55 px-2 py-1 text-[11px] text-white">
                                            <input type="checkbox" name="remove_gallery_ids[]" value="{{ $galleryImage->id }}" class="rounded border-slate-300">
                                            <span>{{ __('admin.products.remove_image') }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-medium">{{ __('admin.products.add_gallery_images') }}</label>
                        <input
                            type="file"
                            name="gallery_files[]"
                            accept="image/*"
                            multiple
                            class="w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-700 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white"
                        >
                        <p class="mt-1 text-xs text-slate-500">{{ __('admin.products.gallery_hint') }}</p>
                    </div>
                </div>
            </div>

            <input type="hidden" name="status" value="{{ old('status', $product->status ?? 'active') }}">

            <div class="flex shrink-0 justify-end gap-2 border-t border-slate-100 px-4 py-3 sm:px-5">
                <a href="{{ $closeUrl }}" class="rounded-lg border px-4 py-2 text-sm">{{ __('admin.actions.cancel') }}</a>
                <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.actions.save') }}</button>
            </div>
        </form>
    </div>
</div>
