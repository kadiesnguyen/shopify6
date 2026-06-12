<section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-base font-semibold text-slate-900">{{ __('admin.settings.sections.notifications') }}</h2>

    <div>
        <label for="profile_marquee_text" class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ __('admin.settings.profile_marquee') }}
        </label>
        <textarea
            id="profile_marquee_text"
            name="profile_marquee_text"
            rows="3"
            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand"
            placeholder="{{ __('admin.settings.profile_marquee_hint') }}"
        >{{ old('profile_marquee_text', $settings['profile_marquee_text']) }}</textarea>
        @error('profile_marquee_text')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</section>

<section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-base font-semibold text-slate-900">{{ __('admin.settings.sections.branding') }}</h2>

    <div>
        <label for="website_title" class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ __('admin.settings.website_title') }}
        </label>
        <input
            id="website_title"
            name="website_title"
            type="text"
            value="{{ old('website_title', $settings['website_title']) }}"
            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand"
            placeholder="{{ config('portal.brand_name', 'Shopify') }}"
        >
        @error('website_title')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="logo" class="mb-1.5 block text-sm font-medium text-slate-700">
                {{ __('admin.settings.logo') }}
            </label>
            @if ($settings['logo_path'])
                <img src="{{ $logoUrl }}" alt="" class="mb-2 h-10 w-auto rounded border border-slate-200 object-contain">
            @endif
            <input
                id="logo"
                name="logo"
                type="file"
                accept="image/*"
                class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
            >
            @error('logo')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="favicon" class="mb-1.5 block text-sm font-medium text-slate-700">
                {{ __('admin.settings.favicon') }}
            </label>
            <img src="{{ $faviconUrl }}" alt="" class="mb-2 size-8 rounded border border-slate-200 object-contain">
            <input
                id="favicon"
                name="favicon"
                type="file"
                accept="image/*,.ico"
                class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
            >
            @error('favicon')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</section>

<section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-base font-semibold text-slate-900">{{ __('admin.settings.sections.seo') }}</h2>

    <div>
        <label for="seo_description" class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ __('admin.settings.seo_description') }}
        </label>
        <textarea
            id="seo_description"
            name="seo_description"
            rows="3"
            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand"
            placeholder="{{ __('landing.meta_description') }}"
        >{{ old('seo_description', $settings['seo_description']) }}</textarea>
        @error('seo_description')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="seo_og_image" class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ __('admin.settings.seo_og_image') }}
        </label>
        <p class="mb-2 text-xs text-slate-500">{{ __('admin.settings.seo_og_image_hint') }}</p>
        <img src="{{ $ogImageUrl }}" alt="" class="mb-2 max-h-32 w-auto rounded border border-slate-200 object-contain">
        <input
            id="seo_og_image"
            name="seo_og_image"
            type="file"
            accept="image/*"
            class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
        >
        @error('seo_og_image')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</section>
