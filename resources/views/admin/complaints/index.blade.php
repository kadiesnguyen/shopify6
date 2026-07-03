@extends('layouts.admin')

@section('title', __('admin.complaints.title'))

@section('content')
    <x-admin.page-header :title="__('admin.complaints.title')" />

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="status" class="rounded-lg border-slate-300 text-sm" onchange="this.form.submit()">
            <option value="">{{ __('admin.requests.all_status') }}</option>
            @foreach (['pending', 'resolved'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('admin.complaints.status_'.$status) }}</option>
            @endforeach
        </select>
    </form>

    <div class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <x-ui.responsive-table>
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.user') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.complaints.subject') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.status') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.created_at') }}</th>
                    <th class="px-4 py-3">{{ __('admin.columns.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($complaints as $item)
                    <tr x-data="{ open: false }">
                        <td class="px-3 py-2.5">
                            <span class="cell-truncate font-medium" title="{{ $item->user?->email }}">{{ $item->user?->email }}</span>
                            <span class="cell-truncate text-xs text-slate-500">{{ $item->user?->phone }}</span>
                        </td>
                        <td class="px-3 py-2.5"><span class="cell-truncate">{{ $item->subject }}</span></td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded px-2 py-0.5 text-xs font-medium',
                                'bg-amber-100 text-amber-800' => $item->status === 'pending',
                                'bg-emerald-100 text-emerald-800' => $item->status === 'resolved',
                            ])>{{ __('admin.complaints.status_'.$item->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <button type="button" @click="open = true" class="text-brand hover:underline">{{ __('admin.actions.view') }}</button>
                            @if ($item->status === 'pending')
                                <form method="POST" action="{{ route('admin.complaints.resolve', $item) }}" class="ml-2 inline">
                                    @csrf
                                    <button class="text-emerald-600 hover:underline">{{ __('admin.complaints.resolve') }}</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.complaints.destroy', $item) }}" class="ml-2 inline" onsubmit="return confirm('{{ __('admin.actions.confirm_delete_item') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">{{ __('admin.actions.delete') }}</button>
                            </form>

                            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center" @keydown.escape.window="open = false">
                                <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-5 text-left shadow-xl" @click.outside="open = false">
                                    <div class="mb-4 flex items-center justify-between">
                                        <h3 class="text-lg font-semibold">{{ $item->subject }}</h3>
                                        <button type="button" @click="open = false" class="text-slate-400">×</button>
                                    </div>
                                    <p class="mb-3 text-xs text-slate-500">{{ $item->user?->email }} — {{ $item->created_at->format('d/m/Y H:i') }}</p>
                                    <p class="whitespace-pre-line text-sm text-slate-700">{{ $item->body }}</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('admin.complaints.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </x-ui.responsive-table>
        <div class="px-4 py-3">{{ $complaints->links() }}</div>
    </div>
@endsection
