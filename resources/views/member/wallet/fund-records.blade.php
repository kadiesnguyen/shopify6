@extends('layouts.member')

@section('title', __('member.wallet.fund_records'))
@section('back_url', route('member.my.index'))

@section('content')
    @if ($transactions->isEmpty())
        <x-ui.empty-state :title="__('member.wallet.empty_transactions')" class="bg-white" />
    @else
        <div class="space-y-3">
            @foreach ($transactions as $transaction)
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $transaction->description ?? $transaction->type }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</p>
                            @if ($transaction->reference)
                                <p class="text-xs text-slate-400">{{ __('member.wallet.reference') }}: {{ $transaction->reference }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p @class(['font-bold', 'text-brand' => $transaction->amount >= 0, 'text-red-600' => $transaction->amount < 0])>
                                ${{ number_format(abs($transaction->amount), 2) }}
                            </p>
                            <p class="text-xs capitalize text-slate-500">{{ $transaction->status }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-4">{{ $transactions->links() }}</div>
    @endif
@endsection
