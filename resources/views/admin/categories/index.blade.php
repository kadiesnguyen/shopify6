@extends('layouts.admin')

@section('title', __('admin.categories.title'))

@section('content')
    <x-admin.page-header :title="__('admin.categories.title')" :action-url="route('admin.categories.create')" />

    <div class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <x-ui.responsive-table>
        <table class="w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3">{{ $category->name }}</td>
                        <td class="px-4 py-3">{{ $category->status }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-brand">{{ __('admin.actions.edit') }}</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="ml-2 text-red-600">{{ __('admin.actions.delete') }}</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">{{ __('admin.categories.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </x-ui.responsive-table>
        <div class="px-4 py-3">{{ $categories->links() }}</div>
    </div>
@endsection
