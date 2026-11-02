<?php

namespace Tests\Unit;

use App\Services\Member\ProductDistributionService;
use Tests\TestCase;

class CommissionPricingTest extends TestCase
{
    public function test_cost_price_leaves_a_thirty_percent_commission_at_every_price(): void
    {
        $this->assertSame(46.2, ProductDistributionService::costPriceForPrice(66));
        $this->assertSame(350.0, ProductDistributionService::costPriceForPrice(500));
        $this->assertSame(1050.0, ProductDistributionService::costPriceForPrice(1500));
        $this->assertSame(4410.0, ProductDistributionService::costPriceForPrice(6300));
    }

    public function test_zero_price_has_zero_cost(): void
    {
        $this->assertSame(0.0, ProductDistributionService::costPriceForPrice(0));
    }
}
