<?php

namespace Tests\Unit;

use App\Services\Import\SieummoCatalogParser;
use Tests\TestCase;

class SieummoCatalogParserTest extends TestCase
{
    public function test_parser_extracts_product_from_sieummo_list_markup(): void
    {
        $html = <<<'HTML'
        <div class="pitem flex gap-3 p-3 rounded-xl bg-white shadow-sm"
             data-name="vertu điện thoại"
             data-shop="0356674298">
          <div onclick="location.href='/product?id=768&shop=30'">
            <img src="/uploads/20260610201340-dd23992e.webp" alt="Điện Thoại">
          </div>
          <div>
            <p class="font-medium text-gray-900 truncate">VERTU</p>
            <p class="text-emerald-600 font-semibold text-base mt-1">$2,250.00</p>
            <p class="text-xs text-gray-400 mt-0.5">Kho: 612</p>
          </div>
        </div>
        HTML;

        $items = app(SieummoCatalogParser::class)->parse($html);

        $this->assertCount(1, $items);
        $this->assertSame(768, $items[0]['sieummo_id']);
        $this->assertSame(30, $items[0]['sieummo_shop_id']);
        $this->assertSame('VERTU', $items[0]['name']);
        $this->assertSame('Điện Thoại', $items[0]['category_name']);
        $this->assertSame('0356674298', $items[0]['shop_name']);
        $this->assertSame(2250.0, $items[0]['selling_price']);
        $this->assertSame(612, $items[0]['stock']);
    }
}
