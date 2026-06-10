@extends('layouts.member')

@section('title', __('member.profile.title'))
@section('back_url', route('member.my.index'))

@section('content')
    <section class="rounded-xl bg-white p-4 shadow-sm">
        <dl class="space-y-4 text-sm">
            <div>
                <dt class="text-slate-500">{{ __('member.profile.avatar') }}</dt>
                <dd class="mt-1 font-medium">{{ $user->avatar ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">{{ __('member.profile.username') }}</dt>
                <dd class="mt-1 font-medium">{{ $user->username }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">{{ __('member.profile.email') }}</dt>
                <dd class="mt-1 font-medium">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">{{ __('member.profile.phone') }}</dt>
                <dd class="mt-1 font-medium">{{ $user->phone ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">{{ __('member.profile.user_code') }}</dt>
                <dd class="mt-1 font-medium">{{ $user->user_code ?? '—' }}</dd>
            </div>
        </dl>
    </section>

    <nav class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm">
        @if (! $user->hasPaymentPassword())
            <a href="{{ route('member.payment-password.create') }}" class="flex items-center justify-between border-b border-slate-100 px-4 py-3 text-sm">
                <span class="text-slate-700">{{ __('member.profile.payment_password') }}</span>
                <span class="text-xs font-medium text-brand">{{ __('member.shipping.add') }}</span>
            </a>
        @else
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 text-sm">
                <span class="text-slate-700">{{ __('member.profile.payment_password') }}</span>
                <span class="text-xs text-emerald-600">✓</span>
            </div>
        @endif
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 text-sm">
            <span class="text-slate-700">{{ __('member.profile.login_password') }}</span>
            <span class="text-xs text-slate-400">{{ __('member.profile.coming_soon') }}</span>
        </div>
        <div class="flex items-center justify-between px-4 py-3 text-sm">
            <span class="text-slate-700">{{ __('member.profile.funds_password') }}</span>
            <span class="text-xs text-slate-400">{{ __('member.profile.coming_soon') }}</span>
        </div>
    </nav>
@endsection
