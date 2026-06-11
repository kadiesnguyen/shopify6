<?php

namespace Tests\Unit;

use App\Support\ProductDisplayStats;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductDisplayStatsTest extends TestCase
{
    #[Test]
    public function test_random_pair_stays_within_bounds_with_clicks_above_sales(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $stats = ProductDisplayStats::randomPair();

            $this->assertGreaterThanOrEqual(ProductDisplayStats::MIN, $stats['sales']);
            $this->assertLessThanOrEqual(ProductDisplayStats::MAX, $stats['sales']);
            $this->assertGreaterThanOrEqual(ProductDisplayStats::MIN, $stats['clicks']);
            $this->assertLessThanOrEqual(ProductDisplayStats::MAX, $stats['clicks']);
            $this->assertGreaterThan($stats['sales'], $stats['clicks']);
        }
    }
}
