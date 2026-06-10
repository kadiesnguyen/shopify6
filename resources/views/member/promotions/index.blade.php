@extends('layouts.member')

@section('title', __('member.promotions.title'))
@section('back_url', route('member.home'))

@section('content')
    @if ($promotions->isEmpty())
        <x-ui.empty-state :title="__('member.promotions.empty')" class="bg-white" />
    @else
        <div class="space-y-3">
            @foreach ($promotions as $promotion)
                <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-28 bg-gradient-to-r from-brand/20 to-brand-dark/30"></div>
                    <div class="p-4">
                        <h2 class="font-semibold text-slate-900">{{ $promotion->title }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ $promotion->description }}</p>
                        @if ($promotion->start_date)
                            <p class="mt-2 text-xs text-slate-500">{{ $promotion->start_date->format('d/m/Y') }} — {{ optional($promotion->end_date)->format('d/m/Y') }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-4">{{ $promotions->links() }}</div>
    @endif
@endsection
