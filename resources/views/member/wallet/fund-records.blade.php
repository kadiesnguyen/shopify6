@extends('layouts.member')

@section('title', __('member.wallet.fund_records'))
@section('portal_gray_bg', '1')
@section('full_bleed', '1')

@section('content')
    @php
        $backUrl = $type === 'withdrawal'
            ? route('member.wallet.withdrawal')
            : ($rechargeEntryUrl ?? route('member.wallet.recharge'));
    @endphp

    <div class="min-h-[var(--app-height,100dvh)] bg-gray-50 pb-[calc(4.5rem+env(safe-area-inset-bottom))]">
        <header class="sticky top-[3.75rem] z-10 flex items-center justify-center border-b border-gray-100 bg-white px-4 py-3">
            <a href="{{ $backUrl }}" class="absolute left-2 flex size-10 items-center justify-center text-gray-700 no-underline" aria-label="{{ __('member.back') }}">
                <x-member.icon name="chevron-left" class="size-6" />
            </a>
            <h1 class="text-base font-semibold text-gray-900">{{ __('member.wallet.fund_records') }}</h1>
        </header>

        <div class="sticky top-[calc(3.75rem+3.25rem)] z-10 flex border-b border-gray-100 bg-white">
            <a
                href="{{ route('member.wallet.fund-records', ['type' => 'recharge']) }}"
                @class([
                    'flex-1 py-3 text-center text-sm font-medium no-underline border-b-2',
                    'border-black text-black' => $type === 'recharge',
                    'border-transparent text-gray-500' => $type !== 'recharge',
                ])
            >{{ __('member.wallet.tab_recharge') }}</a>
            <a
                href="{{ route('member.wallet.fund-records', ['type' => 'withdrawal']) }}"
                @class([
                    'flex-1 py-3 text-center text-sm font-medium no-underline border-b-2',
                    'border-black text-black' => $type === 'withdrawal',
                    'border-transparent text-gray-500' => $type !== 'withdrawal',
                ])
            >{{ __('member.wallet.tab_withdrawal') }}</a>
        </div>

        @if ($records->isEmpty())
            <p class="py-12 text-center text-sm text-gray-400">{{ __('member.wallet.empty_fund_records') }}</p>
        @else
            <div class="divide-y divide-gray-100">
                @foreach ($records as $record)
                    @php
                        $isRecharge = $type === 'recharge';
                        $kindLabel = $isRecharge
                            ? __('member.wallet.kind_recharge')
                            : __('member.wallet.kind_withdrawal');
                    @endphp
                    <article class="flex items-center justify-between bg-white px-4 py-3">
                        <div class="min-w-0 pr-3">
                            <p class="font-medium text-gray-900">{{ $kindLabel }}</p>
                            <p class="text-xs text-gray-400">{{ $record->created_at->format('d/m/Y H:i') }}</p>
                            @if ($record->status === 'rejected' && filled($record->admin_note))
                                <p class="mt-1 text-xs text-red-500">{{ $record->admin_note }}</p>
                            @endif
                        </div>
                        <div class="shrink-0 text-right">
                            <p @class([
                                'font-medium',
                                'text-emerald-600' => $isRecharge,
                                'text-red-600' => ! $isRecharge,
                            ])>${{ number_format($record->amount, 2) }}</p>
                            <p @class([
                                'text-xs font-medium',
                                'text-amber-600' => $record->status === 'pending',
                                'text-emerald-600' => $record->status === 'approved',
                                'text-red-600' => $record->status === 'rejected',
                            ])>{{ __('member.wallet.status_'.$record->status) }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="px-4 py-4">{{ $records->links() }}</div>
        @endif
    </div>
@endsection
