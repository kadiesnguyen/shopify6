@extends('layouts.member')

@section('title', __('member.settings.language_title'))
@section('full_bleed', '1')
@section('portal_gray_bg', '1')

@section('content')
    <div class="min-h-[var(--app-height,100dvh)] pb-24">
        <header class="sticky top-14 z-10 flex items-center justify-center border-b border-gray-100 bg-white px-4 py-3">
            <a href="{{ route('member.settings.index') }}" class="absolute left-2 flex size-10 items-center justify-center text-gray-700 no-underline" aria-label="{{ __('member.back') }}">
                <x-member.icon name="chevron-left" class="size-6" />
            </a>
            <h1 class="text-base font-semibold text-gray-900">{{ __('member.settings.language_title') }}</h1>
        </header>

        <div class="mt-2 bg-white">
            @foreach ($locales as $code => $locale)
                <a
                    href="{{ route('locale.switch', $code) }}"
                    @class([
                        'flex items-center justify-between border-b border-gray-100 px-4 py-3.5 text-sm no-underline last:border-b-0',
                        'font-semibold text-emerald-600' => $currentLocale === $code,
                        'text-gray-900' => $currentLocale !== $code,
                    ])
                >
                    <span class="flex items-center gap-2">
                        @if (! empty($locale['flag']))
                            <img src="{{ asset('images/landing/'.$locale['flag']) }}" alt="" class="h-4 w-5 shrink-0 object-cover" width="20" height="16">
                        @endif
                        <span>{{ $locale['label'] ?? $code }}</span>
                    </span>
                    @if ($currentLocale === $code)
                        <x-member.icon name="circle-check" class="size-5 shrink-0 text-emerald-600" />
                    @endif
                </a>
            @endforeach
        </div>
    </div>
@endsection
