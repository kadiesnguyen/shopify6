@extends('layouts.admin')

@section('title', __('admin.requests.password_title'))

@section('content')
    <x-admin.page-header :title="__('admin.requests.password_title')" />

    <div class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <x-ui.responsive-table>
        <table class="w-full text-sm">
            <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">User</th><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($requests as $item)
                    <tr>
                        <td class="px-4 py-3">{{ $item->user?->email }}</td>
                        <td class="px-4 py-3">{{ $item->type }}</td>
                        <td class="px-4 py-3">{{ $item->status }}</td>
                        <td class="px-4 py-3">
                            @if ($item->status === 'pending')
                                <form method="POST" action="{{ route('admin.password-change-requests.approve', $item) }}" class="inline">@csrf<button class="text-brand">{{ __('admin.actions.approve') }}</button></form>
                                <form method="POST" action="{{ route('admin.password-change-requests.reject', $item) }}" class="inline ml-2">@csrf<button class="text-red-600">{{ __('admin.actions.reject') }}</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('admin.requests.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </x-ui.responsive-table>
        <div class="px-4 py-3">{{ $requests->links() }}</div>
    </div>
@endsection
