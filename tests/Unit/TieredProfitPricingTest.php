<?php

namespace Tests\Unit;

use App\Services\Member\ProductDistributionService;
use Tests\TestCase;

class TieredProfitPricingTest extends TestCase
{
    public function test_profit_rate_tiers_by_price(): void
    {
        $this->assertSame(0.10, ProductDistributionService::profitRateForPrice(500));
        $this->assertSame(0.10, ProductDistributionService::profitRateForPrice(999.99));
        $this->assertSame(0.25, ProductDistributionService::profitRateForPrice(1000));
        $this->assertSame(0.25, ProductDistributionService::profitRateForPrice(1999.99));
        $this->assertSame(0.30, ProductDistributionService::profitRateForPrice(2000));
        $this->assertSame(0.30, ProductDistributionService::profitRateForPrice(6300));
    }

    public function test_cost_price_gives_expected_profit_per_tier(): void
    {
        // Cheap: 10% profit over cost.
        $cost = ProductDistributionService::costPriceForPrice(500);
        $this->assertSame(454.55, $cost);
        $this->assertEqualsWithDelta(0.10, (500 - $cost) / $cost, 0.001);

        // Mid: 25%.
        $cost = ProductDistributionService::costPriceForPrice(1500);
        $this->assertEqualsWithDelta(0.25, (1500 - $cost) / $cost, 0.001);

        // Expensive: 30%.
        $cost = ProductDistributionService::costPriceForPrice(6300);
        $this->assertSame(4846.15, $cost);
        $this->assertEqualsWithDelta(0.30, (6300 - $cost) / $cost, 0.001);
    }

    public function test_zero_price_has_zero_cost(): void
    {
        $this->assertSame(0.0, ProductDistributionService::costPriceForPrice(0));
    }
}
