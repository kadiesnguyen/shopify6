@extends('layouts.admin')

@section('title', __('admin.requests.withdrawal_title'))

@section('content')
    <x-admin.page-header :title="__('admin.requests.withdrawal_title')" />

    <p class="mb-4 text-sm text-slate-600">{{ trans_choice('admin.requests.withdrawal_count', $requests->total(), ['count' => $requests->total()]) }}</p>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="status" class="rounded-lg border-slate-300 text-sm" onchange="this.form.submit()">
            <option value="">{{ __('admin.requests.all_status') }}</option>
            @foreach (['pending', 'approved', 'rejected'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('admin.requests.status_'.$status) }}</option>
            @endforeach
        </select>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.user') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.method') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.amount') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.status') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.created_at') }}</th>
                    <th class="px-4 py-3">{{ __('admin.columns.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($requests as $item)
                    <tr x-data="{ open: false }">
                        <td class="px-4 py-3">
                            <div>{{ $item->user?->email }}</div>
                            <div class="text-xs text-slate-500">{{ $item->user?->phone }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $item->withdrawalMethod?->name ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium">${{ number_format($item->amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded px-2 py-0.5 text-xs font-medium',
                                'bg-amber-100 text-amber-800' => $item->status === 'pending',
                                'bg-emerald-100 text-emerald-800' => $item->status === 'approved',
                                'bg-red-100 text-red-800' => $item->status === 'rejected',
                            ])>{{ __('admin.requests.status_'.$item->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <button type="button" @click="open = true" class="text-brand hover:underline">{{ __('admin.actions.view') }}</button>
                            @if ($item->status === 'pending')
                                <form method="POST" action="{{ route('admin.withdrawal-requests.approve', $item) }}" class="ml-2 inline">
                                    @csrf
                                    <button class="text-emerald-600 hover:underline">{{ __('admin.actions.approve') }}</button>
                                </form>
                            @endif

                            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center" @keydown.escape.window="open = false">
                                <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-5 shadow-xl" @click.outside="open = false">
                                    <div class="mb-4 flex items-center justify-between">
                                        <h3 class="text-lg font-semibold">{{ __('admin.requests.detail_title') }}</h3>
                                        <button type="button" @click="open = false" class="text-slate-400">×</button>
                                    </div>
                                    <dl class="space-y-2 text-sm">
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('admin.columns.user') }}</dt><dd>{{ $item->user?->email }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('admin.columns.method') }}</dt><dd>{{ $item->withdrawalMethod?->name }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('admin.columns.amount') }}</dt><dd class="font-semibold">${{ number_format($item->amount, 2) }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('admin.requests.payout_details') }}</dt><dd class="text-right">{{ $item->payout_details['details'] ?? json_encode($item->payout_details) }}</dd></div>
                                        @if ($item->admin_note)
                                            <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('admin.requests.admin_note') }}</dt><dd>{{ $item->admin_note }}</dd></div>
                                        @endif
                                    </dl>
                                    @if ($item->status === 'pending')
                                        <form method="POST" action="{{ route('admin.withdrawal-requests.reject', $item) }}" class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                                            @csrf
                                            <textarea name="admin_note" rows="2" placeholder="{{ __('admin.requests.reject_note_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
                                            <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">{{ __('admin.actions.reject') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('admin.requests.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">{{ $requests->links() }}</div>
    </div>
@endsection
