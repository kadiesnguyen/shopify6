@extends('layouts.admin')

@section('title', __('admin.profile.title'))

@section('content')
    <x-admin.page-header :title="__('admin.profile.title')" />

    <div class="grid max-w-3xl gap-6">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-slate-900">{{ __('admin.profile.account_info') }}</h2>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-500">{{ __('admin.profile.name') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">{{ __('admin.profile.email') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">{{ __('admin.profile.username') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $user->username }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-slate-900">{{ __('admin.profile.change_password') }}</h2>
            <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="mb-1.5 block text-sm font-medium text-slate-700">{{ __('admin.profile.current_password') }}</label>
                    <input
                        id="current_password"
                        name="current_password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand"
                    >
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">{{ __('admin.profile.new_password') }}</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand"
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">{{ __('admin.profile.confirm_password') }}</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand"
                    >
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand/90">
                        {{ __('admin.profile.save_password') }}
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
