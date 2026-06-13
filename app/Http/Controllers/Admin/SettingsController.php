<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingsRequest;
use App\Http\Requests\Admin\UploadCmsImageRequest;
use App\Support\CmsPages;
use App\Support\RichTextSanitizer;
use App\Support\SiteSettings;
use App\Support\Storage\PublicUploadStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $aboutPage = CmsPages::about();

        return view('admin.settings.edit', [
            'activeTab' => request('tab', 'general'),
            'settings' => [
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
            ],
            'logoUrl' => SiteSettings::logoUrl(),
            'faviconUrl' => SiteSettings::faviconUrl(),
            'ogImageUrl' => SiteSettings::seoOgImageUrl(),
        ]);
    }

    public function update(UpdateSiteSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        SiteSettings::set(SiteSettings::KEY_PROFILE_MARQUEE, $data['profile_marquee_text'] ?? null);
        SiteSettings::set(SiteSettings::KEY_WEBSITE_TITLE, $data['website_title'] ?? null);
        SiteSettings::set(SiteSettings::KEY_SEO_DESCRIPTION, $data['seo_description'] ?? null);

        $this->storeUpload($request, 'logo', SiteSettings::KEY_LOGO);
        $this->storeUpload($request, 'favicon', SiteSettings::KEY_FAVICON);
        $this->storeUpload($request, 'seo_og_image', SiteSettings::KEY_SEO_OG_IMAGE);

        $sanitizer = app(RichTextSanitizer::class);

        if ($request->hasAny(['about_content_vi', 'about_content_en'])) {
            CmsPages::syncAbout(
                $sanitizer->clean($data['about_content_vi'] ?? null),
                $sanitizer->clean($data['about_content_en'] ?? null),
            );
        }

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

    private function storeUpload(UpdateSiteSettingsRequest $request, string $field, string $settingKey): void
    {
        if (! $request->hasFile($field)) {
            return;
        }

        $previous = SiteSettings::get($settingKey);
        $path = PublicUploadStorage::store($request->file($field), 'site-settings');

        if (! Storage::disk('public')->exists($path)) {
            throw ValidationException::withMessages([
                $field => [__('admin.settings.upload_failed')],
            ]);
        }

        SiteSettings::set($settingKey, $path);
        SiteSettings::deleteStoredFile($previous);
    }
}
