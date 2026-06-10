@extends('layouts.admin')

@section('title', __('admin.methods.payment_title'))

@section('content')
    <x-admin.page-header :title="__('admin.methods.payment_title')">
        <x-slot:actions>
            <a href="{{ route('admin.payment-methods.index', ['show_create' => 1]) }}" class="inline-flex items-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                {{ __('admin.methods.add_method') }}
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <p class="mb-4 text-sm text-slate-600">{{ __('admin.methods.payment_subtitle') }}</p>

    @include('admin.partials.methods-table', [
        'methods' => $methods,
        'indexRoute' => 'admin.payment-methods.index',
        'destroyRoute' => 'admin.payment-methods.destroy',
    ])

    @if ($showMethodModal ?? false)
        @include('admin.partials.payment-method-modal', [
            'method' => $modalMethod ?? new \App\Models\PaymentMethod(['status' => 'active', 'sort_order' => 0]),
        ])
    @endif
@endsection
