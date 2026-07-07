<?php

namespace Tests\Unit;

use App\Services\Import\DemoProductImporter;
use Tests\TestCase;

class DemoProductGalleryTest extends TestCase
{
    public function test_target_gallery_count_uses_price_tier(): void
    {
        $importer = app(DemoProductImporter::class);

        $this->assertSame(3, $importer->targetGalleryCount(35.0));
        $this->assertSame(5, $importer->targetGalleryCount(1110.0));
        $this->assertSame(3, $importer->minimumGalleryCount(24.49));
        $this->assertSame(5, $importer->minimumGalleryCount(95.73));
    }

    public function test_collect_image_urls_caps_by_price_tier(): void
    {
        $importer = app(DemoProductImporter::class);

        $gpres = [
            ['img_url' => 'https://example.com/1.jpg'],
            ['img_url' => 'https://example.com/2.jpg'],
            ['img_url' => 'https://example.com/3.jpg'],
            ['img_url' => 'https://example.com/4.jpg'],
            ['img_url' => 'https://example.com/5.jpg'],
            ['img_url' => 'https://example.com/6.jpg'],
        ];

        $cheap = $importer->collectImageUrls('https://example.com/thumb.jpg', $gpres, 25.0);
        $expensive = $importer->collectImageUrls('https://example.com/thumb.jpg', $gpres, 250.0);

        $this->assertCount(3, $cheap);
        $this->assertCount(5, $expensive);
        $this->assertSame('https://example.com/thumb.jpg', $cheap[0]);
    }
}
