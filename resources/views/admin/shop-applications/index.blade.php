@extends('layouts.admin')

@section('title', __('admin.shop_applications.title'))

@section('content')
    <div class="min-w-0 max-w-full" x-data="{ reviewId: null }">
        <x-admin.page-header :title="__('admin.shop_applications.title')" />

        <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
            <input
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="{{ __('admin.shop_applications.search') }}"
                class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm sm:max-w-xs"
            >
            <select name="status" class="rounded-lg border-slate-300 text-sm" onchange="this.form.submit()">
                <option value="">{{ __('admin.shop_applications.all_status') }}</option>
                @foreach (['pending', 'approved', 'rejected'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('admin.shop_applications.status_'.$status) }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-brand px-3 py-2 text-sm text-white">{{ __('admin.actions.search') }}</button>
        </form>

        <div class="hidden min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
            <x-ui.responsive-table>
                <table class="w-full text-sm">
                    <colgroup>
                        <col class="w-[17%]">
                        <col class="w-[18%]">
                        <col class="w-[11%]">
                        <col class="w-[16%]">
                        <col class="w-[11%]">
                        <col class="w-[13%]">
                        <col class="w-[14%]">
                    </colgroup>
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('admin.shop_applications.user') }}</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('admin.shop_applications.shop_name') }}</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('admin.shop_applications.real_name') }}</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('admin.shop_applications.request') }}</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('admin.shop_applications.status') }}</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-slate-600">{{ __('admin.shop_applications.submitted') }}</th>
                            <th class="px-3 py-2.5 text-right text-xs font-medium text-slate-600">{{ __('admin.shop_applications.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($applications as $item)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-3 py-2.5">
                                    <span class="cell-truncate font-medium text-slate-900" title="{{ $item->user?->email }}">{{ $item->user?->email }}</span>
                                    <span class="cell-truncate text-xs text-slate-500">{{ $item->phone }}</span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="cell-wrap font-medium text-slate-900">{{ $item->shop_name }}</span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="cell-truncate" title="{{ $item->real_name }}">{{ $item->real_name }}</span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <x-admin.shop-application-type-badges :application="$item" compact />
                                </td>
                                <td class="px-3 py-2.5">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-amber-100 text-amber-800' => $item->status === 'pending',
                                        'bg-emerald-100 text-emerald-800' => $item->status === 'approved',
                                        'bg-red-100 text-red-800' => $item->status === 'rejected',
                                    ])>{{ __('admin.shop_applications.status_'.$item->status) }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-xs text-slate-500">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-2.5 text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            @click="reviewId = {{ $item->id }}"
                                            class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-medium text-brand hover:bg-brand/5"
                                        >
                                            {{ __('admin.actions.view') }}
                                        </button>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.shop-applications.destroy', $item) }}"
                                            class="inline"
                                            onsubmit="return confirm(@js(__('admin.shop_applications.confirm_delete')))"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                                                {{ __('admin.actions.delete') }}
                                            </button>
                                        </form>
                                        @if ($item->status === 'pending')
                                            <form method="POST" action="{{ route('admin.shop-applications.approve', $item) }}" class="inline">
                                                @csrf
                                                <button class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-50">{{ __('admin.actions.approve') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500">{{ __('admin.shop_applications.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.responsive-table>
            <div class="border-t border-slate-100 px-4 py-3">{{ $applications->links() }}</div>
        </div>

        <div class="space-y-3 md:hidden">
            @forelse ($applications as $item)
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="break-words font-medium text-slate-900">{{ $item->shop_name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $item->user?->email }}</p>
                        </div>
                        <span @class([
                            'shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold',
                            'bg-amber-100 text-amber-800' => $item->status === 'pending',
                            'bg-emerald-100 text-emerald-800' => $item->status === 'approved',
                            'bg-red-100 text-red-800' => $item->status === 'rejected',
                        ])>{{ __('admin.shop_applications.status_'.$item->status) }}</span>
                    </div>
                    <div class="mt-3">
                        <x-admin.shop-application-type-badges :application="$item" compact />
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-2 text-xs text-slate-500">
                        <span>{{ $item->created_at->format('d/m/Y H:i') }}</span>
                        <div class="flex gap-2">
                            <button type="button" @click="reviewId = {{ $item->id }}" class="font-medium text-brand">{{ __('admin.actions.view') }}</button>
                            <form
                                method="POST"
                                action="{{ route('admin.shop-applications.destroy', $item) }}"
                                class="inline"
                                onsubmit="return confirm(@js(__('admin.shop_applications.confirm_delete')))"
                            >
                                @csrf
                                @method('DELETE')
                                <button class="font-medium text-red-600">{{ __('admin.actions.delete') }}</button>
                            </form>
                            @if ($item->status === 'pending')
                                <form method="POST" action="{{ route('admin.shop-applications.approve', $item) }}" class="inline">
                                    @csrf
                                    <button class="font-medium text-emerald-600">{{ __('admin.actions.approve') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <x-ui.empty-state :title="__('admin.shop_applications.empty')" class="rounded-xl bg-white" />
            @endforelse
            <div>{{ $applications->links() }}</div>
        </div>

        @foreach ($applications as $item)
            @include('admin.shop-applications.partials.review-modal', ['item' => $item])
        @endforeach
    </div>
@endsection
