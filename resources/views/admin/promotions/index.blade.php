@extends('layouts.admin')

@section('title', __('admin.promotions.title'))

@section('content')
    <x-admin.page-header :title="__('admin.promotions.title')" :action-url="route('admin.promotions.create')" />
    <x-admin.search :placeholder="__('admin.promotions.search')" />

    <div class="mb-4 flex gap-2">
        @foreach ([null, 30, 7, 3] as $days)
            <a href="{{ route('admin.promotions.index', array_filter(['days' => $days, 'q' => request('q')])) }}" @class(['rounded-full px-3 py-1 text-xs', 'bg-brand text-white' => request('days') == $days, 'bg-white ring-1 ring-slate-200' => request('days') != $days])>
                {{ $days ? $days.' days' : 'All' }}
            </a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Title</th><th class="px-4 py-3 text-left">Shop</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($promotions as $promotion)
                    <tr>
                        <td class="px-4 py-3">{{ $promotion->title }}</td>
                        <td class="px-4 py-3">{{ $promotion->shop?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $promotion->status }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.promotions.edit', $promotion) }}" class="text-brand">{{ __('admin.actions.edit') }}</a>
                            <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="ml-2 text-red-600">{{ __('admin.actions.delete') }}</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('admin.promotions.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">{{ $promotions->links() }}</div>
    </div>
@endsection
