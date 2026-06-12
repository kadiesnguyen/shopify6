@extends('layouts.admin')

@section('title', __('admin.products.title'))

@section('content')
    @php($listQuery = request()->only(['q']))

    <x-admin.page-header :title="__('admin.products.title')">
        <x-slot:actions>
            <a href="{{ route('admin.products.index', array_merge($listQuery, ['show_create' => 1])) }}" class="inline-flex items-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                {{ __('admin.products.add_product') }}
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <p class="mb-4 text-sm text-slate-600">{{ __('admin.products.count', ['count' => $productTotal]) }}</p>

    <x-admin.search :placeholder="__('admin.products.search')" />

    <div class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <x-ui.responsive-table>
            <table class="w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        @foreach (['image', 'name', 'category', 'selling_price', 'purchase_price', 'commission', 'stock', 'actions'] as $col)
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('admin.columns.'.$col) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                @if ($product->imageUrl())
                                    <img src="{{ $product->imageUrl() }}" alt="" class="size-10 rounded object-cover">
                                @else
                                    <div class="size-10 rounded bg-slate-100"></div>
                                @endif
                            </td>
                            <td class="px-4 py-3"><span class="cell-clamp-2" title="{{ $product->name }}">{{ $product->name }}</span></td>
                            <td class="px-4 py-3"><span class="cell-truncate">{{ $product->category?->name ?? '—' }}</span></td>
                            <td class="px-4 py-3">${{ number_format($product->selling_price, 2) }}</td>
                            <td class="px-4 py-3">${{ number_format($product->purchase_price, 2) }}</td>
                            <td class="px-4 py-3">
                                @if (($product->commission_type ?? 'fixed') === 'percent')
                                    {{ number_format($product->commission, 2) }}%
                                @else
                                    ${{ number_format($product->commission, 2) }}
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $product->stock }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.products.index', array_merge($listQuery, ['edit' => $product->id])) }}" class="inline-flex rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        {{ __('admin.actions.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('{{ __('admin.actions.confirm_delete_item') }}')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex rounded-lg border border-red-200 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                                            {{ __('admin.actions.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">{{ __('admin.products.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.responsive-table>
        <div class="px-4 py-3">{{ $products->links() }}</div>
    </div>

    @if ($showProductModal ?? false)
        @include('admin.products.partials.modal', [
            'product' => $modalProduct ?? new \App\Models\Product(['status' => 'active', 'commission_type' => 'fixed']),
            'categories' => $categories,
            'shops' => $shops,
            'listQuery' => $listQuery,
        ])
    @endif
@endsection
