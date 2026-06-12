@extends('layouts.admin')

@section('title', __('admin.invite_codes.title'))

@section('content')
    <x-admin.page-header :title="__('admin.invite_codes.title')">
        <x-slot:actions>
            <a href="{{ route('admin.invite-codes.index', ['show_add' => 1, 'filter' => request('filter')]) }}" class="inline-flex items-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                {{ __('admin.invite_codes.add_code') }}
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['' => 'tab_all', 'unused' => 'tab_unused', 'used' => 'tab_used'] as $value => $labelKey)
            <a href="{{ route('admin.invite-codes.index', array_filter(['filter' => $value ?: null])) }}" @class([
                'rounded-full px-3 py-1 text-xs font-medium',
                'bg-brand text-white' => ($filter ?? '') === $value,
                'bg-white ring-1 ring-slate-200 text-slate-700' => ($filter ?? '') !== $value,
            ])>{{ __('admin.invite_codes.'.$labelKey) }}</a>
        @endforeach
    </div>

    <div class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <x-ui.responsive-table>
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.code') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.status') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.invite_codes.used_by') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.created_at') }}</th>
                    <th class="px-4 py-3">{{ __('admin.columns.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($codes as $code)
                    <tr>
                        <td class="px-4 py-3 font-mono">{{ $code->code }}</td>
                        <td class="px-4 py-3">{{ $code->used_by ? __('admin.invite_codes.tab_used') : __('admin.invite_codes.tab_unused') }}</td>
                        <td class="px-4 py-3">{{ $code->usedByUser?->email ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $code->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <button type="button" class="mr-2 text-brand hover:underline" onclick="navigator.clipboard.writeText(@js($code->code))">{{ __('admin.invite_codes.copy') }}</button>
                            <form method="POST" action="{{ route('admin.invite-codes.destroy', $code) }}" onsubmit="return confirm('Delete?')" class="inline">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">{{ __('admin.actions.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('admin.invite_codes.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </x-ui.responsive-table>
        <div class="px-4 py-3">{{ $codes->links() }}</div>
    </div>

    @if ($showAddModal ?? false)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center">
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('admin.invite_codes.add_modal_title') }}</h3>
                    <a href="{{ route('admin.invite-codes.index', array_filter(['filter' => request('filter')])) }}" class="text-2xl text-slate-400">×</a>
                </div>
                <form method="POST" action="{{ route('admin.invite-codes.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium">{{ __('admin.columns.code') }}</label>
                        <input name="code" placeholder="{{ __('admin.invite_codes.code_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div class="flex justify-end gap-2">
                        <a href="{{ route('admin.invite-codes.index', array_filter(['filter' => request('filter')])) }}" class="rounded-lg border px-4 py-2 text-sm">{{ __('admin.actions.cancel') }}</a>
                        <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.actions.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
