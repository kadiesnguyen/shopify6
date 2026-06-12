@extends('layouts.admin')

@section('title', __('admin.menu.settings'))

@push('scripts')
    @vite(['resources/js/admin-rich-editor.js'])
@endpush

@section('content')
    <x-admin.page-header :title="__('admin.menu.settings')" />

    <div
        class="max-w-4xl"
        x-data="{ tab: @js(old('active_tab', $activeTab)) }"
    >
        <div class="mb-6 flex gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
            <button
                type="button"
                @click="tab = 'general'"
                :class="tab === 'general' ? 'bg-brand text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
                class="flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold transition"
            >
                {{ __('admin.settings.tabs.general') }}
            </button>
            <button
                type="button"
                @click="tab = 'about'"
                :class="tab === 'about' ? 'bg-brand text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
                class="flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold transition"
            >
                {{ __('admin.settings.tabs.about') }}
            </button>
        </div>

        <form
            method="POST"
            action="{{ route('admin.settings.update') }}"
            enctype="multipart/form-data"
            class="space-y-8"
            data-settings-form
            data-cms-image-upload-url="{{ route('admin.settings.cms-images') }}"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="active_tab" :value="tab">

            <div x-show="tab === 'general'" class="space-y-8">
                @include('admin.settings.partials.general-tab')
            </div>

            <div x-show="tab === 'about'" x-cloak class="space-y-8">
                @include('admin.settings.partials.about-tab')
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand/90">
                    {{ __('admin.actions.save') }}
                </button>
            </div>
        </form>
    </div>
@endsection
