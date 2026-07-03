@extends('layouts.member')

@section('title', __('member.settings.title'))
@section('back_url', route('member.my.index'))

@section('content')
    <h1 class="mb-4 text-lg font-bold text-gray-900">{{ __('member.settings.title') }}</h1>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <x-member.menu-link :href="route('member.profile.show')" icon="user" icon-color="text-blue-600" icon-bg="bg-blue-50" :label="__('member.my.personal')" />
        <x-member.menu-link :href="route('member.payment-password.edit')" icon="lock" icon-color="text-violet-600" icon-bg="bg-violet-50" :label="__('member.profile.payment_password')" />
        <x-member.menu-link :href="route('member.notifications.index')" icon="bell" icon-color="text-amber-600" icon-bg="bg-amber-50" :label="__('member.notifications.title')" />
        <form method="POST" action="{{ route('auth.logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center justify-between px-4 py-3 text-left transition hover:bg-gray-50">
                <span class="flex items-center gap-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-full bg-red-50 text-red-600">
                        <x-member.icon name="log-out" class="size-5" />
                    </span>
                    <span class="text-gray-800">{{ __('messages.logout') }}</span>
                </span>
                <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
            </button>
        </form>
    </div>
@endsection
