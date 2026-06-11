@extends('layouts.admin')

@section('title', __('admin.methods.recharge_title'))

@section('content')
    <x-admin.page-header :title="__('admin.methods.recharge_title')">
        <x-slot:actions>
            <a href="{{ route('admin.recharge-methods.index', ['show_create' => 1]) }}" class="inline-flex items-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                {{ __('admin.methods.add_method') }}
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <p class="mb-4 text-sm text-slate-600">{{ __('admin.methods.recharge_subtitle') }}</p>

    @include('admin.partials.methods-table', [
        'methods' => $methods,
        'indexRoute' => 'admin.recharge-methods.index',
        'destroyRoute' => 'admin.recharge-methods.destroy',
        'toggleRoute' => 'admin.recharge-methods.toggle-status',
    ])

    @if ($showMethodModal ?? false)
        @include('admin.partials.recharge-method-modal', [
            'method' => $modalMethod ?? new \App\Models\RechargeMethod(['status' => 'active', 'type' => 'bank', 'sort_order' => 0]),
        ])
    @endif
@endsection
