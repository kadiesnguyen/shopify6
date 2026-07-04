@extends('layouts.member')

@section('title', __('member.shop_hub.refunds'))
@section('back_url', route('member.shop-hub.menu'))

@section('content')
    <h1 class="mb-4 text-lg font-bold text-gray-900">{{ __('member.shop_hub.refunds') }}</h1>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <div class="space-y-3">
        @forelse ($refunds as $refund)
            <article class="rounded-xl bg-white px-4 py-3 shadow-sm ring-1 ring-gray-100">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-medium text-gray-900">{{ $refund->order?->order_no }}</p>
                    <span class="text-xs uppercase text-gray-500">{{ $refund->status }}</span>
                </div>
                <p class="mt-1 text-sm text-gray-600">${{ number_format($refund->amount, 2) }} · {{ $refund->buyer?->name }}</p>
                @if ($refund->reason)
                    <p class="mt-2 text-sm text-gray-700">{{ $refund->reason }}</p>
                @endif
            </article>
        @empty
            <p class="rounded-xl bg-white px-4 py-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-100">{{ __('member.shop_hub.refunds_empty') }}</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $refunds->links() }}</div>
@endsection
