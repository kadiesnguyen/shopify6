<section class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div>
        <h2 class="text-base font-semibold text-slate-900">{{ __('admin.settings.sections.about') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('admin.settings.about_hint') }}</p>
    </div>

    @include('admin.settings.partials.rich-editor', [
        'name' => 'about_content_vi',
        'label' => __('admin.settings.content_vi'),
        'value' => $pageContents['about_vi'],
        'hint' => __('admin.settings.about_editor_hint'),
        'tall' => true,
    ])

    @include('admin.settings.partials.rich-editor', [
        'name' => 'about_content_en',
        'label' => __('admin.settings.content_en'),
        'value' => $pageContents['about_en'],
        'tall' => true,
    ])

    <div class="flex justify-end border-t border-slate-100 pt-4">
        <a
            href="{{ route('member.contract.show') }}"
            target="_blank"
            rel="noopener"
            class="text-sm font-medium text-brand hover:underline"
        >
            {{ __('admin.settings.about_preview') }}
        </a>
    </div>
</section>
