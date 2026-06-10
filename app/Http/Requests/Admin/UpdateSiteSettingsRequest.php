<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'portal_home_marquee_text' => ['nullable', 'string', 'max:2000'],
            'profile_marquee_text' => ['nullable', 'string', 'max:2000'],
            'website_title' => ['nullable', 'string', 'max:120'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,svg,webp', 'max:1024'],
            'seo_og_image' => ['nullable', 'image', 'max:4096'],
            'about_content_vi' => ['nullable', 'string', 'max:50000'],
            'about_content_en' => ['nullable', 'string', 'max:50000'],
            'contact_content_vi' => ['nullable', 'string', 'max:50000'],
            'contact_content_en' => ['nullable', 'string', 'max:50000'],
            'active_tab' => ['nullable', 'string', 'in:general,pages'],
        ];
    }
}
