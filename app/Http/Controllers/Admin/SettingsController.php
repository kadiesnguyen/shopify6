<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingsRequest;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => [
                'portal_home_marquee_text' => SiteSettings::get(SiteSettings::KEY_PORTAL_HOME_MARQUEE),
                'profile_marquee_text' => SiteSettings::get(SiteSettings::KEY_PROFILE_MARQUEE),
                'website_title' => SiteSettings::get(SiteSettings::KEY_WEBSITE_TITLE),
                'seo_description' => SiteSettings::get(SiteSettings::KEY_SEO_DESCRIPTION),
                'logo_path' => SiteSettings::get(SiteSettings::KEY_LOGO),
                'favicon_path' => SiteSettings::get(SiteSettings::KEY_FAVICON),
                'seo_og_image_path' => SiteSettings::get(SiteSettings::KEY_SEO_OG_IMAGE),
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

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', __('admin.settings.saved'));
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
