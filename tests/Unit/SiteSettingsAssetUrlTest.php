<?php

namespace Tests\Unit;

use App\Support\SiteSettings;
use Tests\TestCase;

class SiteSettingsAssetUrlTest extends TestCase
{
    public function test_uploaded_logo_uses_relative_storage_url(): void
    {
        SiteSettings::set(SiteSettings::KEY_LOGO, 'site-settings/test-logo.png');

        $this->assertSame('/storage/site-settings/test-logo.png', SiteSettings::logoUrl());
    }

    public function test_missing_logo_falls_back_to_public_asset(): void
    {
        SiteSettings::set(SiteSettings::KEY_LOGO, null);

        $this->assertStringContainsString('images/portal/logo.jpg', SiteSettings::logoUrl());
    }
}
