<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Support\SiteSettings;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            SiteSettings::KEY_PORTAL_HOME_MARQUEE => __('landing.marquee'),
            SiteSettings::KEY_PROFILE_MARQUEE => __('member.my.payment_warning_long'),
            SiteSettings::KEY_WEBSITE_TITLE => config('portal.brand_name', 'Shopify'),
            SiteSettings::KEY_SEO_DESCRIPTION => __('landing.meta_description'),
        ];

        foreach ($defaults as $key => $value) {
            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        SiteSettings::clearCache();
    }
}
