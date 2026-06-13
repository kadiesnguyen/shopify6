@extends('layouts.member')

@section('title', __('member.shop_application.status_title'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')

@section('content')
    <div class="min-h-screen bg-gray-50">
        <header class="sticky top-0 z-10 flex items-center bg-black px-4 py-3 text-white">
            <a href="{{ route('member.home') }}" class="inline-flex items-center" aria-label="{{ __('member.back') }}">
                <x-member.icon name="chevron-left" class="size-5" />
            </a>
            <h1 class="absolute left-1/2 -translate-x-1/2 text-base font-semibold">{{ __('member.shop_application.status_title') }}</h1>
        </header>

        <div class="mx-4 mt-6 rounded-xl bg-white p-5 shadow-sm">
            <div class="mb-4 flex size-14 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <x-member.icon name="check-circle-2" class="size-7" />
            </div>
            <h2 class="text-base font-semibold text-gray-900">{{ __('member.shop_application.pending_title') }}</h2>
            <p class="mt-2 text-sm text-gray-600">{{ __('member.shop_application.pending_body') }}</p>

            <dl class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">{{ __('member.shop_application.shop_name') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $application->shop_name }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">{{ __('member.shop_application.real_name') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $application->real_name }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">{{ __('member.profile.phone') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $application->phone }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">{{ __('member.shop_application.submitted_at') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $application->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        <div class="p-4">
            <a href="{{ route('member.home') }}" class="block w-full rounded-lg bg-emerald-600 py-3 text-center text-sm font-semibold text-white">
                {{ __('member.shop_application.back_home') }}
            </a>
        </div>
    </div>
@endsection
