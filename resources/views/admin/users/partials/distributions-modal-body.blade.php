@php
    $distQuery = array_merge(
        request()->only(['q', 'user_id', 'role', 'shop_application', 'dist_q', 'dist_commission_type', 'dist_price_from', 'dist_price_to', 'dist_sort']),
        ['show_distributions' => $modalUser->id],
    );
@endphp

<p class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
    {{ __('admin.users.distributions.featured_hint') }}
</p>

<form method="GET" class="mb-4">
    <input type="hidden" name="show_distributions" value="{{ $modalUser->id }}">
    @foreach (['q', 'role', 'shop_application'] as $field)
        @if (request($field))
            <input type="hidden" name="{{ $field }}" value="{{ request($field) }}">
        @endif
    @endforeach
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,0.95fr)_5.25rem_5.25rem_minmax(0,0.85fr)_auto_auto] lg:items-end lg:gap-x-2 lg:gap-y-0">
        <div class="min-w-0 sm:col-span-2 lg:col-span-1">
            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('admin.actions.search') }}</label>
            <input type="search" name="dist_q" value="{{ request('dist_q') }}" placeholder="{{ __('admin.users.distributions.search') }}" class="w-full rounded-lg border-slate-300 text-sm">
        </div>
        <div class="min-w-0">
            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('admin.users.distributions.commission_type') }}</label>
            <select name="dist_commission_type" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">{{ __('admin.users.distributions.commission_type_all') }}</option>
                <option value="fixed" @selected(request('dist_commission_type') === 'fixed')>{{ __('admin.users.distributions.commission_type_fixed') }}</option>
                <option value="percent" @selected(request('dist_commission_type') === 'percent')>{{ __('admin.users.distributions.commission_type_percent') }}</option>
            </select>
        </div>
        <div class="min-w-0">
            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('admin.users.distributions.price_from') }}</label>
            <input type="number" step="0.01" min="0" name="dist_price_from" value="{{ request('dist_price_from') }}" placeholder="0" class="w-full rounded-lg border-slate-300 text-sm">
        </div>
        <div class="min-w-0">
            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('admin.users.distributions.price_to') }}</label>
            <input type="number" step="0.01" min="0" name="dist_price_to" value="{{ request('dist_price_to') }}" placeholder="∞" class="w-full rounded-lg border-slate-300 text-sm">
        </div>
        <div class="min-w-0">
            <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('admin.users.distributions.sort') }}</label>
            <select name="dist_sort" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="newest" @selected(request('dist_sort', 'newest') === 'newest')>{{ __('admin.users.distributions.sort_newest') }}</option>
                <option value="oldest" @selected(request('dist_sort') === 'oldest')>{{ __('admin.users.distributions.sort_oldest') }}</option>
            </select>
        </div>
        <button type="submit" class="inline-flex shrink-0 items-center justify-center self-end whitespace-nowrap rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:bg-brand/90">{{ __('admin.actions.search') }}</button>
        <a href="{{ route('admin.users.index', array_merge(request()->only(['q', 'user_id', 'role', 'shop_application']), ['show_distributions' => $modalUser->id])) }}" class="inline-flex shrink-0 items-center justify-center self-end whitespace-nowrap rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">{{ __('admin.users.distributions.reset') }}</a>
    </div>
</form>

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
                <th class="px-3 py-2 text-left">{{ __('admin.columns.featured') }}</th>
                <th class="px-3 py-2 text-left">{{ __('admin.columns.status') }}</th>
                <th class="px-3 py-2 text-left">{{ __('admin.columns.created_at') }}</th>
                <th class="px-3 py-2 text-left">{{ __('admin.columns.actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($distributions as $index => $distribution)
                @php
                    $product = $distribution->product;
                    $featuredToggleUrl = route('admin.users.distributions.toggle-featured', [$modalUser, $distribution]).'?'.http_build_query(array_filter($distQuery));
                @endphp
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
                    <td class="px-3 py-2">
                        <x-admin.power-toggle
                            :enabled="$distribution->is_featured"
                            :action="$featuredToggleUrl"
                            :on-label="__('admin.methods.yes')"
                            :off-label="__('admin.methods.no')"
                        />
                        @if ($distribution->is_featured && ! $distribution->isAvailable())
                            <p class="mt-1 max-w-[12rem] text-xs text-amber-700">{{ __('admin.users.distributions.featured_hidden_reserved') }}</p>
                        @endif
                    </td>
                    <td class="px-3 py-2">
                        @if ($distribution->isAvailable())
                            <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('admin.users.distributions.status_available') }}</span>
                        @else
                            <span class="inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">{{ __('admin.users.distributions.status_reserved') }}</span>
                        @endif
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
                    <td colspan="10" class="px-3 py-8 text-center text-slate-500">{{ __('admin.users.distributions.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($distributions instanceof \Illuminate\Contracts\Pagination\Paginator && $distributions->hasPages())
    <div class="mt-3">{{ $distributions->appends($distQuery)->links() }}</div>
@endif

<div class="mt-4 text-right">
    <a href="{{ route('admin.users.index', request()->only(['q', 'user_id', 'role', 'shop_application'])) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">{{ __('admin.users.distributions.close') }}</a>
</div>
