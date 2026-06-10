@php($isEdit = $method->exists)
@php($config = $method->config ?? [])
@php($closeUrl = route('admin.withdrawal-methods.index'))

<div class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center">
    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-5 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">{{ $isEdit ? __('admin.methods.edit_method') : __('admin.methods.add_method') }}</h3>
            <a href="{{ $closeUrl }}" class="text-2xl leading-none text-slate-400 hover:text-slate-600">×</a>
        </div>

        <form method="POST" action="{{ $isEdit ? route('admin.withdrawal-methods.update', $method) : route('admin.withdrawal-methods.store') }}" class="space-y-4">
            @csrf
            @if ($isEdit)
                @method('PATCH')
            @endif

            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.type') }}</label>
                <select name="type" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="bank" @selected(old('type', $method->type ?? 'bank') === 'bank')>bank</option>
                    <option value="crypto" @selected(old('type', $method->type ?? 'bank') === 'crypto')>crypto</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.name') }} *</label>
                <input name="name" value="{{ old('name', $method->name) }}" required class="w-full rounded-lg border-slate-300 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.methods.network_or_bank') }}</label>
                <input name="network_or_bank" value="{{ old('network_or_bank', $config['network_or_bank'] ?? '') }}" class="w-full rounded-lg border-slate-300 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.methods.withdrawal_fee_percent') }}</label>
                <input type="number" step="0.01" min="0" max="100" name="fee_percent" value="{{ old('fee_percent', $config['fee_percent'] ?? '') }}" class="w-full rounded-lg border-slate-300 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.methods.sort_order') }}</label>
                    <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $method->sort_order ?? 0) }}" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.methods.enabled') }}</label>
                    <select name="status" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="active" @selected(old('status', $method->status ?? 'active') === 'active')>{{ __('admin.methods.yes') }}</option>
                        <option value="inactive" @selected(old('status', $method->status ?? 'active') === 'inactive')>{{ __('admin.methods.no') }}</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.methods.internal_note') }}</label>
                <textarea name="internal_note" rows="3" class="w-full rounded-lg border-slate-300 text-sm">{{ old('internal_note', $config['internal_note'] ?? '') }}</textarea>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <a href="{{ $closeUrl }}" class="rounded-lg border px-4 py-2 text-sm">{{ __('admin.actions.cancel') }}</a>
                <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.actions.save') }}</button>
            </div>
        </form>
    </div>
</div>
