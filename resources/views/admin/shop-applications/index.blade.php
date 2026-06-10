@extends('layouts.admin')

@section('title', __('admin.shop_applications.title'))

@section('content')
    <div x-data="{ reviewId: null }">
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
            <div class="overflow-x-auto">
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
                            <th class="px-4 py-3 text-right">{{ __('admin.shop_applications.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($applications as $item)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $item->user?->email }}</div>
                                    <div class="text-xs text-slate-500">{{ $item->user?->phone }}</div>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $item->shop_name }}</td>
                                <td class="px-4 py-3">{{ $item->real_name }}</td>
                                <td class="px-4 py-3">{{ $item->phone }}</td>
                                <td class="px-4 py-3">{{ __('admin.shop_applications.type_'.$item->seller_type) }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        'bg-amber-100 text-amber-800' => $item->status === 'pending',
                                        'bg-emerald-100 text-emerald-800' => $item->status === 'approved',
                                        'bg-red-100 text-red-800' => $item->status === 'rejected',
                                    ])>{{ __('admin.shop_applications.status_'.$item->status) }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="reviewId = {{ $item->id }}"
                                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-medium text-brand transition hover:bg-brand/5"
                                    >
                                        {{ __('admin.actions.view') }}
                                    </button>
                                    @if ($item->status === 'pending')
                                        <form method="POST" action="{{ route('admin.shop-applications.approve', $item) }}" class="ml-1 inline">
                                            @csrf
                                            <button class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-sm font-medium text-emerald-600 transition hover:bg-emerald-50">{{ __('admin.actions.approve') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-slate-500">{{ __('admin.shop_applications.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $applications->links() }}</div>
        </div>

        @foreach ($applications as $item)
            @include('admin.shop-applications.partials.review-modal', ['item' => $item])
        @endforeach
    </div>
@endsection
