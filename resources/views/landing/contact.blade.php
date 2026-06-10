@extends('layouts.landing')

@section('title', __('landing.contact.title').' — '.config('landing.brand_name', 'Shopify'))

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-10 md:py-14">
        <div class="grid gap-10 lg:grid-cols-2">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">
                    {{ $page?->translate('title') ?? __('landing.contact.title') }}
                </h1>
                <p class="mt-3 text-slate-600">{{ __('landing.contact.subtitle') }}</p>

                @if ($page?->translate('content'))
                    <div class="prose prose-slate mt-6 max-w-none">
                        {!! $page->translate('content') !!}
                    </div>
                @endif

                <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                    <div class="flex aspect-video items-center justify-center text-sm text-slate-500">
                        Google Map placeholder
                    </div>
                </div>
            </div>

            <form action="{{ route('landing.contact.store') }}" method="POST" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">{{ __('landing.contact.name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required class="w-full rounded-lg border-slate-300 focus:border-brand focus:ring-brand">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">{{ __('landing.contact.email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-lg border-slate-300 focus:border-brand focus:ring-brand">
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">{{ __('landing.contact.phone') }}</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="w-full rounded-lg border-slate-300 focus:border-brand focus:ring-brand">
                        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="message" class="mb-1 block text-sm font-medium text-slate-700">{{ __('landing.contact.message') }}</label>
                        <textarea id="message" name="message" rows="5" required class="w-full rounded-lg border-slate-300 focus:border-brand focus:ring-brand">{{ old('message') }}</textarea>
                        @error('message')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">
                        {{ __('landing.contact.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
