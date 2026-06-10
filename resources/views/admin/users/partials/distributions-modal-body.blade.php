@php
    $distQuery = array_merge(
        request()->only(['q', 'role', 'shop_application', 'dist_q', 'dist_commission_type', 'dist_price_from', 'dist_price_to', 'dist_sort']),
        ['show_distributions' => $modalUser->id],
    );
@endphp

<form method="GET" class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
    <input type="hidden" name="show_distributions" value="{{ $modalUser->id }}">
    @foreach (['q', 'role', 'shop_application'] as $field)
        @if (request($field))
            <input type="hidden" name="{{ $field }}" value="{{ request($field) }}">
        @endif
    @endforeach
    <div>
        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('admin.actions.search') }}</label>
        <input type="search" name="dist_q" value="{{ request('dist_q') }}" placeholder="{{ __('admin.users.distributions.search') }}" class="w-full rounded-lg border-slate-300 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('admin.users.distributions.commission_type') }}</label>
        <select name="dist_commission_type" class="w-full rounded-lg border-slate-300 text-sm">
            <option value="">{{ __('admin.users.distributions.commission_type_all') }}</option>
            <option value="fixed" @selected(request('dist_commission_type') === 'fixed')>{{ __('admin.users.distributions.commission_type_fixed') }}</option>
            <option value="percent" @selected(request('dist_commission_type') === 'percent')>{{ __('admin.users.distributions.commission_type_percent') }}</option>
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('admin.users.distributions.price_from') }}</label>
        <input type="number" step="0.01" min="0" name="dist_price_from" value="{{ request('dist_price_from') }}" placeholder="0" class="w-full rounded-lg border-slate-300 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('admin.users.distributions.price_to') }}</label>
        <input type="number" step="0.01" min="0" name="dist_price_to" value="{{ request('dist_price_to') }}" placeholder="∞" class="w-full rounded-lg border-slate-300 text-sm">
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('admin.users.distributions.sort') }}</label>
        <select name="dist_sort" class="w-full rounded-lg border-slate-300 text-sm">
            <option value="newest" @selected(request('dist_sort', 'newest') === 'newest')>{{ __('admin.users.distributions.sort_newest') }}</option>
            <option value="oldest" @selected(request('dist_sort') === 'oldest')>{{ __('admin.users.distributions.sort_oldest') }}</option>
        </select>
    </div>
    <div class="flex items-end gap-2">
        <button type="submit" class="rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:bg-brand/90">{{ __('admin.actions.search') }}</button>
        <a href="{{ route('admin.users.index', array_merge(request()->only(['q', 'role', 'shop_application']), ['show_distributions' => $modalUser->id])) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">{{ __('admin.users.distributions.reset') }}</a>
    </div>
</form>

@if ($catalogProducts->isNotEmpty())
    <form method="POST" action="{{ route('admin.users.distributions.store', $modalUser) }}" class="mb-4 flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
        @csrf
        <div class="min-w-[220px] flex-1">
            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('admin.users.distributions.assign') }}</label>
            <select name="product_id" required class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">{{ __('admin.users.distributions.select_product') }}</option>
                @foreach ($catalogProducts as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} (${{ number_format($product->selling_price, 2) }})</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand/90">{{ __('admin.users.distributions.assign') }}</button>
    </form>
@else
    <p class="mb-4 text-sm text-slate-500">{{ __('admin.users.distributions.no_catalog') }}</p>
@endif

<div class="overflow-x-auto rounded-lg border border-slate-200">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-3 py-2 text-left">#</th>
                <th class="px-3 py-2 text-left">{{ __('admin.columns.image') }}</th>
                <th class="px-3 py-2 text-left">{{ __('admin.columns.product') }}</th>
                <th class="px-3 py-2 text-left">{{ __('admin.columns.purchase_price') }}</th>
                <th class="px-3 py-2 text-left">{{ __('admin.columns.selling_price') }}</th>
                <th class="px-3 py-2 text-left">{{ __('admin.columns.commission') }}</th>
                <th class="px-3 py-2 text-left">{{ __('admin.columns.created_at') }}</th>
                <th class="px-3 py-2 text-left">{{ __('admin.columns.actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($distributions as $index => $distribution)
                @php($product = $distribution->product)
                <tr x-data="{ editing: false }">
                    <td class="px-3 py-2">{{ ($distributions->firstItem() ?? 1) + $index }}</td>
                    <td class="px-3 py-2">
                        @if ($product?->imageUrl())
                            <img src="{{ $product->imageUrl() }}" alt="" class="h-10 w-10 rounded object-cover">
                        @else
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded bg-slate-100 text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 font-medium">{{ $product?->name ?? '—' }}</td>
                    <td class="px-3 py-2">${{ number_format($distribution->purchase_price, 2) }}</td>
                    <td class="px-3 py-2">${{ number_format($distribution->selling_price, 2) }}</td>
                    <td class="px-3 py-2">
                        ${{ number_format($distribution->commission, 2) }}
                        <span class="text-xs text-slate-500">({{ $distribution->commission_type === 'percent' ? '%' : __('admin.users.distributions.commission_type_fixed') }})</span>
                    </td>
                    <td class="px-3 py-2 text-slate-500">{{ $distribution->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-3 py-2">
                        <div class="flex gap-2">
                            <button type="button" @click="editing = !editing" class="text-brand hover:underline">{{ __('admin.actions.edit') }}</button>
                            <form method="POST" action="{{ route('admin.users.distributions.destroy', [$modalUser, $distribution]) }}" onsubmit="return confirm(@js(__('admin.actions.delete').'?'))">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">{{ __('admin.actions.delete') }}</button>
                            </form>
                        </div>
                        <form x-show="editing" x-cloak method="POST" action="{{ route('admin.users.distributions.update', [$modalUser, $distribution]) }}" class="mt-2 space-y-2 rounded border border-slate-200 bg-slate-50 p-2">
                            @csrf @method('PATCH')
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" step="0.01" min="0" name="purchase_price" value="{{ $distribution->purchase_price }}" class="rounded border-slate-300 text-xs" placeholder="{{ __('admin.columns.purchase_price') }}">
                                <input type="number" step="0.01" min="0" name="selling_price" value="{{ $distribution->selling_price }}" class="rounded border-slate-300 text-xs" placeholder="{{ __('admin.columns.selling_price') }}">
                                <input type="number" step="0.01" min="0" name="commission" value="{{ $distribution->commission }}" class="rounded border-slate-300 text-xs" placeholder="{{ __('admin.columns.commission') }}">
                                <select name="commission_type" class="rounded border-slate-300 text-xs">
                                    <option value="fixed" @selected($distribution->commission_type === 'fixed')>{{ __('admin.users.distributions.commission_type_fixed') }}</option>
                                    <option value="percent" @selected($distribution->commission_type === 'percent')>{{ __('admin.users.distributions.commission_type_percent') }}</option>
                                </select>
                            </div>
                            <button type="submit" class="text-xs font-medium text-brand hover:underline">{{ __('admin.actions.save') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-3 py-8 text-center text-slate-500">{{ __('admin.users.distributions.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($distributions instanceof \Illuminate\Contracts\Pagination\Paginator && $distributions->hasPages())
    <div class="mt-3">{{ $distributions->appends($distQuery)->links() }}</div>
@endif

<div class="mt-4 text-right">
    <a href="{{ route('admin.users.index', request()->only(['q', 'role', 'shop_application'])) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">{{ __('admin.users.distributions.close') }}</a>
</div>
