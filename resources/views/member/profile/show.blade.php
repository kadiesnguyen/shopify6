@extends('layouts.member')

@section('title', __('member.profile.info_title'))
@section('full_bleed', '1')
@section('portal_gray_bg', '1')

@section('content')
    @php
        $avatarUrl = $user->avatarUrl();
    @endphp

    <div class="min-h-[var(--app-height,100dvh)] bg-gray-50 pb-24">
        <header class="sticky top-14 z-10 flex items-center justify-center border-b border-gray-100 bg-white px-4 py-3">
            <a href="{{ route('member.settings.index') }}" class="absolute left-2 flex size-10 items-center justify-center text-gray-700 no-underline" aria-label="{{ __('member.back') }}">
                <x-member.icon name="chevron-left" class="size-6" />
            </a>
            <h1 class="text-base font-semibold text-gray-900">{{ __('member.profile.info_title') }}</h1>
        </header>

        <form method="POST" action="{{ route('member.profile.avatar.update') }}" enctype="multipart/form-data" id="avatar-form" class="mt-2 bg-white">
            @csrf
            <label class="flex cursor-pointer items-center justify-between px-4 py-4">
                <span class="text-sm font-medium text-gray-900">{{ __('member.profile.avatar') }}</span>
                <span class="flex items-center gap-2">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="" class="size-14 rounded-full border border-gray-200 object-cover">
                    @else
                        <span class="flex size-14 items-center justify-center rounded-full border border-gray-200 bg-gray-100 text-gray-400">
                            <x-member.icon name="user" class="size-7" />
                        </span>
                    @endif
                    <x-member.icon name="chevron-right" class="size-5 text-gray-300" />
                </span>
                <input
                    id="avatar-input"
                    type="file"
                    name="avatar"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    class="hidden"
                    onchange="if (this.files.length) document.getElementById('avatar-form').submit()"
                >
            </label>
            @error('avatar')<p class="px-4 pb-3 text-xs text-red-600">{{ $message }}</p>@enderror
        </form>

        <form method="POST" action="{{ route('member.profile.update') }}" class="portal-wallet-form mt-2">
            @csrf
            @method('PUT')

            <div class="divide-y divide-gray-100 bg-white">
                <div class="px-4 py-4">
                    <label for="name" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.profile.username') }}</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $user->name) }}"
                        placeholder="{{ __('member.profile.full_name_placeholder') }}"
                        class="portal-plain-input"
                        required
                    >
                    @error('name')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="px-4 py-4">
                    <label for="gender" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.profile.gender') }}</label>
                    <select id="gender" name="gender" class="portal-plain-input">
                        <option value="" @selected(old('gender', $user->gender ?? '') === '')>{{ __('member.profile.gender_placeholder') }}</option>
                        <option value="male" @selected(old('gender', $user->gender ?? '') === 'male')>{{ __('member.profile.gender_male') }}</option>
                        <option value="female" @selected(old('gender', $user->gender ?? '') === 'female')>{{ __('member.profile.gender_female') }}</option>
                        <option value="other" @selected(old('gender', $user->gender ?? '') === 'other')>{{ __('member.profile.gender_other') }}</option>
                    </select>
                    @error('gender')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="px-4 py-4">
                    <label for="birthday" class="mb-2 block text-sm font-medium text-gray-900">{{ __('member.profile.birthday') }}</label>
                    <input
                        id="birthday"
                        name="birthday"
                        type="date"
                        value="{{ old('birthday', $user->birthday?->format('Y-m-d') ?? '') }}"
                        class="portal-plain-input"
                    >
                    @error('birthday')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="p-4">
                <button type="submit" class="w-full rounded-lg bg-emerald-600 py-3 font-medium text-white hover:bg-emerald-700 active:opacity-90">
                    {{ __('member.profile.confirm_changes') }}
                </button>
            </div>
        </form>
    </div>
@endsection
