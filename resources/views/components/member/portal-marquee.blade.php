@php
    $message = \App\Support\SiteSettings::portalHomeMarqueeText();
@endphp

@if ($message !== '')
    <section class="overflow-hidden bg-brand-marquee py-2.5 text-white">
        <div class="relative flex">
            <div class="animate-marquee flex shrink-0 items-center gap-8 whitespace-nowrap px-4 text-sm font-medium">
                <span>{{ $message }}</span>
                <span aria-hidden="true">{{ $message }}</span>
            </div>
            <div class="animate-marquee flex shrink-0 items-center gap-8 whitespace-nowrap px-4 text-sm font-medium" aria-hidden="true">
                <span>{{ $message }}</span>
                <span>{{ $message }}</span>
            </div>
        </div>
    </section>
@endif
