@extends('layouts.admin')

@section('title', __('admin.shop_applications.title'))

@section('content')
    <x-admin.page-header :title="__('admin.shop_applications.title')" />

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="{{ __('admin.shop_applications.search') }}"
            class="rounded-lg border-slate-300 text-sm"
        >
        <select name="status" class="rounded-lg border-slate-300 text-sm" onchange="this.form.submit()">
            <option value="">{{ __('admin.shop_applications.all_status') }}</option>
            @foreach (['pending', 'approved', 'rejected'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('admin.shop_applications.status_'.$status) }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-lg bg-brand px-3 py-2 text-sm text-white">{{ __('admin.actions.search') }}</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('admin.shop_applications.user') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.shop_applications.shop_name') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.shop_applications.real_name') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.shop_applications.phone') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.shop_applications.type') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.shop_applications.status') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.shop_applications.submitted') }}</th>
                    <th class="px-4 py-3">{{ __('admin.shop_applications.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($applications as $item)
                    <tr x-data="{ open: false }">
                        <td class="px-4 py-3">
                            <div>{{ $item->user?->email }}</div>
                            <div class="text-xs text-slate-500">{{ $item->user?->phone }}</div>
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $item->shop_name }}</td>
                        <td class="px-4 py-3">{{ $item->real_name }}</td>
                        <td class="px-4 py-3">{{ $item->phone }}</td>
                        <td class="px-4 py-3">{{ __('admin.shop_applications.type_'.$item->seller_type) }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded px-2 py-0.5 text-xs font-medium',
                                'bg-amber-100 text-amber-800' => $item->status === 'pending',
                                'bg-emerald-100 text-emerald-800' => $item->status === 'approved',
                                'bg-red-100 text-red-800' => $item->status === 'rejected',
                            ])>{{ __('admin.shop_applications.status_'.$item->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <button type="button" @click="open = true" class="text-brand hover:underline">{{ __('admin.actions.view') }}</button>
                            @if ($item->status === 'pending')
                                <form method="POST" action="{{ route('admin.shop-applications.approve', $item) }}" class="ml-2 inline">
                                    @csrf
                                    <button class="text-emerald-600 hover:underline">{{ __('admin.actions.approve') }}</button>
                                </form>
                            @endif

                            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center" @keydown.escape.window="open = false">
                                <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-5 shadow-xl" @click.outside="open = false">
                                    <div class="mb-4 flex items-center justify-between">
                                        <h3 class="text-lg font-semibold">{{ __('admin.shop_applications.detail_title') }}</h3>
                                        <button type="button" @click="open = false" class="text-slate-400">×</button>
                                    </div>
                                    <dl class="space-y-2 text-sm">
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('admin.shop_applications.shop_name') }}</dt><dd>{{ $item->shop_name }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('admin.shop_applications.real_name') }}</dt><dd>{{ $item->real_name }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('admin.shop_applications.phone') }}</dt><dd>{{ $item->phone }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('admin.shop_applications.address') }}</dt><dd class="text-right">{{ $item->address }}, {{ $item->country }}</dd></div>
                                        <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('admin.shop_applications.id_number') }}</dt><dd>{{ $item->id_number }}</dd></div>
                                        @if ($item->referral_code)
                                            <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('admin.shop_applications.referral') }}</dt><dd>{{ $item->referral_code }}</dd></div>
                                        @endif
                                    </dl>
                                    <div class="mt-4 grid grid-cols-2 gap-3">
                                        @if ($item->logo)
                                            <div><p class="mb-1 text-xs text-slate-500">{{ __('admin.shop_applications.logo') }}</p><img src="{{ $item->documentUrl($item->logo) }}" alt="" class="max-h-24 rounded border"></div>
                                        @endif
                                        @if ($item->id_front)
                                            <div><p class="mb-1 text-xs text-slate-500">{{ __('admin.shop_applications.id_front') }}</p><a href="{{ $item->documentUrl($item->id_front) }}" target="_blank"><img src="{{ $item->documentUrl($item->id_front) }}" alt="" class="max-h-24 rounded border"></a></div>
                                        @endif
                                        @if ($item->id_back)
                                            <div><p class="mb-1 text-xs text-slate-500">{{ __('admin.shop_applications.id_back') }}</p><a href="{{ $item->documentUrl($item->id_back) }}" target="_blank"><img src="{{ $item->documentUrl($item->id_back) }}" alt="" class="max-h-24 rounded border"></a></div>
                                        @endif
                                    </div>
                                    @if ($item->status === 'pending')
                                        <form method="POST" action="{{ route('admin.shop-applications.reject', $item) }}" class="mt-4 space-y-2 border-t border-slate-100 pt-4">
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
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">{{ __('admin.shop_applications.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">{{ $applications->links() }}</div>
    </div>
@endsection
