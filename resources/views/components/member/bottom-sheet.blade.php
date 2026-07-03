@props([
    'show',
    'title',
])

@php($navOffset = 'calc(50px + env(safe-area-inset-bottom))')

<div
    x-show="{{ $show }}"
    x-cloak
    class="fixed inset-x-0 top-0 z-[200] md:left-1/2 md:w-full md:max-w-[420px] md:-translate-x-1/2"
    style="bottom: 0"
>
    <div
        class="absolute inset-x-0 top-0 bg-black/40"
        style="bottom: {{ $navOffset }}"
        @click="{{ $show }} = false"
    ></div>

    <div
        class="absolute inset-x-0 z-10"
        style="bottom: {{ $navOffset }}"
        @click.stop
    >
        <div class="rounded-t-2xl bg-white shadow-[0_-4px_24px_rgba(15,23,42,0.12)]">
            <div class="flex justify-center pt-3 pb-2">
                <div class="h-1 w-10 rounded-full bg-gray-300"></div>
            </div>
            <div class="px-4 pb-4">
                <h3 class="mb-3 text-center text-base font-semibold text-gray-900">{{ $title }}</h3>
                <div class="space-y-1">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
