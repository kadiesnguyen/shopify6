<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingsRequest;
use App\Http\Requests\Admin\UploadCmsImageRequest;
use App\Models\Page;
use App\Support\RichTextSanitizer;
use App\Support\SiteSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $aboutPage = Page::query()->where('slug', 'gioi-thieu')->first();
        $contactPage = Page::query()->where('slug', 'lien-he')->first();

        return view('admin.settings.edit', [
            'activeTab' => request('tab', 'general'),
            'settings' => [
                'portal_home_marquee_text' => SiteSettings::get(SiteSettings::KEY_PORTAL_HOME_MARQUEE),
                'profile_marquee_text' => SiteSettings::get(SiteSettings::KEY_PROFILE_MARQUEE),
                'website_title' => SiteSettings::get(SiteSettings::KEY_WEBSITE_TITLE),
                'seo_description' => SiteSettings::get(SiteSettings::KEY_SEO_DESCRIPTION),
                'logo_path' => SiteSettings::get(SiteSettings::KEY_LOGO),
                'favicon_path' => SiteSettings::get(SiteSettings::KEY_FAVICON),
                'seo_og_image_path' => SiteSettings::get(SiteSettings::KEY_SEO_OG_IMAGE),
            ],
            'pageContents' => [
                'about_vi' => $aboutPage?->translate('content', 'vi') ?? '',
                'about_en' => $aboutPage?->translate('content', 'en') ?? '',
                'contact_vi' => $contactPage?->translate('content', 'vi') ?? '',
                'contact_en' => $contactPage?->translate('content', 'en') ?? '',
            ],
            'logoUrl' => SiteSettings::logoUrl(),
            'faviconUrl' => SiteSettings::faviconUrl(),
            'ogImageUrl' => SiteSettings::seoOgImageUrl(),
        ]);
    }

    public function update(UpdateSiteSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        SiteSettings::set(SiteSettings::KEY_PORTAL_HOME_MARQUEE, $data['portal_home_marquee_text'] ?? null);
        SiteSettings::set(SiteSettings::KEY_PROFILE_MARQUEE, $data['profile_marquee_text'] ?? null);
        SiteSettings::set(SiteSettings::KEY_WEBSITE_TITLE, $data['website_title'] ?? null);
        SiteSettings::set(SiteSettings::KEY_SEO_DESCRIPTION, $data['seo_description'] ?? null);

        $this->storeUpload($request, 'logo', SiteSettings::KEY_LOGO);
        $this->storeUpload($request, 'favicon', SiteSettings::KEY_FAVICON);
        $this->storeUpload($request, 'seo_og_image', SiteSettings::KEY_SEO_OG_IMAGE);

        $sanitizer = app(RichTextSanitizer::class);

        $this->syncPageContent(
            slug: 'gioi-thieu',
            type: Page::TYPE_ABOUT,
            defaultTitle: ['vi' => 'Giới thiệu', 'en' => 'About us'],
            contents: [
                'vi' => $sanitizer->clean($data['about_content_vi'] ?? null),
                'en' => $sanitizer->clean($data['about_content_en'] ?? null),
            ],
        );

        $this->syncPageContent(
            slug: 'lien-he',
            type: Page::TYPE_CONTACT,
            defaultTitle: ['vi' => 'Liên hệ', 'en' => 'Contact'],
            contents: [
                'vi' => $sanitizer->clean($data['contact_content_vi'] ?? null),
                'en' => $sanitizer->clean($data['contact_content_en'] ?? null),
            ],
        );

        $tab = $request->string('active_tab')->toString() ?: 'general';

        return redirect()
            ->route('admin.settings.edit', ['tab' => $tab])
            ->with('status', __('admin.settings.saved'));
    }

    public function uploadCmsImage(UploadCmsImageRequest $request): JsonResponse
    {
        $path = $request->file('image')->store('cms/pages', 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
        ]);
    }

    private function syncPageContent(string $slug, string $type, array $defaultTitle, array $contents): void
    {
        $page = Page::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'type' => $type,
                'title' => $defaultTitle,
                'content' => ['vi' => '', 'en' => ''],
                'meta_title' => $defaultTitle,
                'meta_description' => ['vi' => '', 'en' => ''],
                'status' => Page::STATUS_PUBLISHED,
            ],
        );

        $page->update([
            'content' => [
                'vi' => $contents['vi'] ?? '',
                'en' => $contents['en'] ?? '',
            ],
            'status' => Page::STATUS_PUBLISHED,
        ]);
    }

    private function storeUpload(UpdateSiteSettingsRequest $request, string $field, string $settingKey): void
    {
        if (! $request->hasFile($field)) {
            return;
        }

        $previous = SiteSettings::get($settingKey);
        $path = $request->file($field)->store('site-settings', 'public');

        SiteSettings::set($settingKey, $path);
        SiteSettings::deleteStoredFile($previous);
    }
}
