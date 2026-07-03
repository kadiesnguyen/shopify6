@extends('layouts.admin')

@section('title', __('admin.reviews.title'))

@section('content')
    <x-admin.page-header :title="__('admin.reviews.title')" />

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="status" class="rounded-lg border-slate-300 text-sm" onchange="this.form.submit()">
            <option value="">{{ __('admin.requests.all_status') }}</option>
            @foreach (['published', 'hidden'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('admin.reviews.status_'.$status) }}</option>
            @endforeach
        </select>
    </form>

    <div class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <x-ui.responsive-table>
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.user') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.reviews.product') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.reviews.rating') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.reviews.body') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.status') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.created_at') }}</th>
                    <th class="px-4 py-3">{{ __('admin.columns.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($reviews as $item)
                    <tr>
                        <td class="px-3 py-2.5"><span class="cell-truncate font-medium" title="{{ $item->user?->email }}">{{ $item->user?->email }}</span></td>
                        <td class="px-3 py-2.5"><span class="cell-truncate">{{ $item->product?->name }}</span></td>
                        <td class="px-4 py-3 text-amber-500">{{ str_repeat('★', $item->rating) }}</td>
                        <td class="px-3 py-2.5 max-w-[16rem]"><span class="cell-truncate" title="{{ $item->body }}">{{ $item->body ?: '—' }}</span></td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded px-2 py-0.5 text-xs font-medium',
                                'bg-emerald-100 text-emerald-800' => $item->status === 'published',
                                'bg-slate-200 text-slate-700' => $item->status === 'hidden',
                            ])>{{ __('admin.reviews.status_'.$item->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <form method="POST" action="{{ route('admin.reviews.toggle-status', $item) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button class="text-brand hover:underline">
                                    {{ $item->status === 'published' ? __('admin.reviews.hide') : __('admin.reviews.publish') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.reviews.destroy', $item) }}" class="ml-2 inline" onsubmit="return confirm('{{ __('admin.actions.confirm_delete_item') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">{{ __('admin.actions.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ __('admin.reviews.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </x-ui.responsive-table>
        <div class="px-4 py-3">{{ $reviews->links() }}</div>
    </div>
@endsection
