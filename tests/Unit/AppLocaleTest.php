<?php

namespace Tests\Unit;

use App\Support\AppLocale;
use Tests\TestCase;

class AppLocaleTest extends TestCase
{
    public function test_display_locales_map_to_vietnamese_content(): void
    {
        foreach (['es', 'fr-CA', 'th', 'ms', 'zh-CN', 'zh-TW'] as $code) {
            session(['locale' => $code]);

            $this->assertSame($code, AppLocale::display());
            $this->assertSame('vi', AppLocale::content());
        }
    }

    public function test_english_maps_to_english_content(): void
    {
        session(['locale' => 'en']);

        $this->assertSame('en', AppLocale::display());
        $this->assertSame('en', AppLocale::content());
    }
}
