@php
    $user = auth()->user();
@endphp

<div class="relative" x-data="{ open: false }">
    <button
        type="button"
        @click="open = !open"
        @keydown.escape.window="open = false"
        class="inline-flex items-center gap-2 rounded-lg px-1 py-1 hover:bg-slate-50"
        aria-haspopup="true"
        :aria-expanded="open"
    >
        <span class="hidden max-w-[12rem] truncate text-sm text-slate-500 sm:inline">{{ $user->name }}</span>
        <span class="inline-flex size-8 items-center justify-center rounded-full bg-admin-sidebar text-sm font-semibold text-white">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </span>
    </button>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg"
    >
        <div class="border-b border-slate-100 px-4 py-3">
            <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
        </div>
        <a
            href="{{ route('admin.profile.show') }}"
            @click="open = false"
            class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50"
        >
            {{ __('admin.profile.title') }}
        </a>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="block w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50">
                {{ __('messages.logout') }}
            </button>
        </form>
    </div>
</div>
