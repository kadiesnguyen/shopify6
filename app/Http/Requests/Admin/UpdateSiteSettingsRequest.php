<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

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
            'profile_marquee_text' => ['nullable', 'string', 'max:2000'],
            'website_title' => ['nullable', 'string', 'max:120'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])->max(5120)],
            'favicon' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'])->max(2048)],
            'seo_og_image' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'gif', 'webp'])->max(4096)],
            'about_content_vi' => ['nullable', 'string', 'max:50000'],
            'about_content_en' => ['nullable', 'string', 'max:50000'],
            'active_tab' => ['nullable', 'string', 'in:general,about'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'logo' => __('admin.settings.logo'),
            'favicon' => __('admin.settings.favicon'),
            'seo_og_image' => __('admin.settings.seo_og_image'),
        ];
    }
}
