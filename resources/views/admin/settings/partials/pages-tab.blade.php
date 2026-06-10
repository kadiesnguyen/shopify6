<section class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div>
        <h2 class="text-base font-semibold text-slate-900">{{ __('admin.settings.sections.landing_pages') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('admin.settings.landing_pages_hint') }}</p>
    </div>

    <div class="space-y-8">
        <div class="space-y-4 border-b border-slate-100 pb-8">
            <h3 class="text-sm font-semibold text-slate-800">{{ __('admin.settings.about_page') }}</h3>

            @include('admin.settings.partials.rich-editor', [
                'name' => 'about_content_vi',
                'label' => __('admin.settings.content_vi'),
                'value' => $pageContents['about_vi'],
            ])

            @include('admin.settings.partials.rich-editor', [
                'name' => 'about_content_en',
                'label' => __('admin.settings.content_en'),
                'value' => $pageContents['about_en'],
            ])
        </div>

        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-slate-800">{{ __('admin.settings.contact_page') }}</h3>
            <p class="text-xs text-slate-500">{{ __('admin.settings.contact_page_hint') }}</p>

            @include('admin.settings.partials.rich-editor', [
                'name' => 'contact_content_vi',
                'label' => __('admin.settings.content_vi'),
                'value' => $pageContents['contact_vi'],
            ])

            @include('admin.settings.partials.rich-editor', [
                'name' => 'contact_content_en',
                'label' => __('admin.settings.content_en'),
                'value' => $pageContents['contact_en'],
            ])
        </div>
    </div>
</section>
