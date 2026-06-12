@extends('layouts.member')

@section('title', __('member.contract.title'))
@section('back_url', route('member.my.index'))

@section('content')
    <section class="rounded-xl bg-white p-4 shadow-sm">
        <h1 class="text-lg font-semibold text-slate-900">
            {{ $page?->translate('title') ?? __('member.contract.title') }}
        </h1>
        <div class="rich-content prose prose-sm mt-4 max-w-none text-slate-700">
            @if ($page)
                {!! $page->translate('content') !!}
            @else
                <p>{{ __('landing.features.intro') }}</p>
            @endif
        </div>
    </section>
@endsection
