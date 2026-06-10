@extends('layouts.member')

@section('title', __('member.wallet.recharge_title'))
@section('back_url', route('member.my.index'))

@section('content')
    <form method="POST" action="{{ route('member.wallet.recharge.store') }}" class="rounded-xl bg-white p-4 shadow-sm">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('member.wallet.method') }}</label>
                <select name="recharge_method_id" required class="w-full rounded-lg border-slate-300">
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
            <button type="submit" class="w-full rounded-lg bg-brand py-2.5 font-semibold text-white">{{ __('member.wallet.submit_recharge') }}</button>
        </div>
    </form>

    <p class="mt-4 text-center">
        <a href="{{ route('member.wallet.fund-records') }}" class="text-sm font-medium text-brand">{{ __('member.wallet.history') }} →</a>
    </p>
@endsection
