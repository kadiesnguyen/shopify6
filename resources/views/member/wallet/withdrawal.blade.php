@extends('layouts.member')

@section('title', __('member.wallet.withdraw_title'))
@section('back_url', route('member.my.index'))

@section('content')
    @if ($wallet)
        <p class="mb-4 rounded-xl bg-white p-4 text-center shadow-sm">
            <span class="text-sm text-slate-500">{{ __('member.my.balance') }}</span>
            <span class="block text-2xl font-bold text-brand">${{ number_format($wallet->balance, 2) }}</span>
        </p>
    @endif

    <form method="POST" action="{{ route('member.wallet.withdrawal.store') }}" class="rounded-xl bg-white p-4 shadow-sm">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('member.wallet.method') }}</label>
                <select name="withdrawal_method_id" required class="w-full rounded-lg border-slate-300">
                    @foreach ($methods as $method)
                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('member.wallet.amount') }}</label>
                <input type="number" name="amount" min="1" step="0.01" required class="w-full rounded-lg border-slate-300">
                @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('member.wallet.payout_details') }}</label>
                <textarea name="payout_details" rows="3" required class="w-full rounded-lg border-slate-300" placeholder="Bank account / wallet address"></textarea>
            </div>
            <button type="submit" class="w-full rounded-lg bg-brand py-2.5 font-semibold text-white">{{ __('member.wallet.submit_withdraw') }}</button>
        </div>
    </form>
@endsection
