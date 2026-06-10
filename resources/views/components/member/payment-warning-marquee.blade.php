@php
    $message = \App\Support\SiteSettings::profileMarqueeText();
@endphp

@if ($message !== '')
<div class="flex items-center gap-2 overflow-hidden rounded-xl bg-amber-50 px-4 py-2.5 text-amber-800">
    <x-member.icon name="volume-2" class="size-5 shrink-0" />
    <div class="relative min-w-0 flex-1 overflow-hidden">
        <div class="relative flex">
            <div class="animate-marquee flex shrink-0 items-center gap-8 whitespace-nowrap text-sm">
                <span>{{ $message }}</span>
                <span aria-hidden="true">{{ $message }}</span>
            </div>
            <div class="animate-marquee flex shrink-0 items-center gap-8 whitespace-nowrap text-sm" aria-hidden="true">
                <span>{{ $message }}</span>
                <span>{{ $message }}</span>
            </div>
        </div>
    </div>
</div>
@endif
