<?php

namespace Tests\Unit;

use App\Services\Member\ProductDetailService;
use Tests\TestCase;

class ProductDetailImageStripTest extends TestCase
{
    public function test_detail_description_drops_gallery_and_inline_images(): void
    {
        $service = app(ProductDetailService::class);

        $html = '<p>Great product.</p>'
            .'<p><img src="a.jpg"></p>'
            .'<ul><li>Durable</li></ul>'
            .'<div class="product-desc-gallery"><p><img src="b.jpg"></p><p><img src="c.jpg"></p></div>';

        $stripped = $service->stripDescriptionImages($html);

        $this->assertStringNotContainsString('<img', $stripped);
        $this->assertStringNotContainsString('product-desc-gallery', $stripped);
        $this->assertStringContainsString('Great product.', $stripped);
        $this->assertStringContainsString('<ul>', $stripped);
    }
}
