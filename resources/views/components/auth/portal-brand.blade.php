@php
    use App\Support\SiteSettings;

    $logo = SiteSettings::logoUrl();
    $brandName = SiteSettings::websiteTitle();
@endphp

<a href="{{ route('landing.home') }}" class="mx-auto mb-6 block w-fit sm:mb-8">
    <img
        src="{{ $logo }}"
        alt="{{ $brandName }}"
        class="h-9 w-auto object-contain sm:h-11"
        width="200"
        height="40"
    >
</a>
