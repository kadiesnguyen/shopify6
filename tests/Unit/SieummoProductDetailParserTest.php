<?php

namespace Tests\Unit;

use App\Services\Import\SieummoProductDetailParser;
use Tests\TestCase;

class SieummoProductDetailParserTest extends TestCase
{
    public function test_parses_description_html_from_list_markup(): void
    {
        $html = <<<'HTML'
            <h2 class="font-bold text-gray-900 mb-2">Mô tả sản phẩm</h2>
            <ul class="list-disc pl-5 space-y-2 text-sm text-gray-700"><li>Full product description here.</li></ul>
        HTML;

        $this->assertSame(
            '<li>Full product description here.</li>',
            (new SieummoProductDetailParser)->parseDescriptionHtml($html),
        );
    }

    public function test_parses_description_from_list_markup(): void
    {
        $html = <<<'HTML'
            <h2 class="font-bold text-gray-900 mb-2">Mô tả sản phẩm</h2>
            <ul class="list-disc pl-5 space-y-2 text-sm text-gray-700"><li>Full product description here.</li></ul>
        HTML;

        $this->assertSame(
            'Full product description here.',
            (new SieummoProductDetailParser)->parseDescription($html),
        );
    }

    public function test_parses_description_from_paragraph_markup(): void
    {
        $html = <<<'HTML'
            <p class="text-sm font-semibold text-gray-900 mb-2">Mô tả sản phẩm</p>
            <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">Paragraph description body.</p>
        HTML;

        $this->assertSame(
            'Paragraph description body.',
            (new SieummoProductDetailParser)->parseDescription($html),
        );
    }
}
