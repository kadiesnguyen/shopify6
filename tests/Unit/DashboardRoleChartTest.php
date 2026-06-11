<?php

namespace Tests\Unit;

use App\Support\Admin\DashboardRoleChart;
use Tests\TestCase;

class DashboardRoleChartTest extends TestCase
{
    public function test_segments_returns_empty_collection_when_no_users(): void
    {
        $this->assertTrue(DashboardRoleChart::segments([])->isEmpty());
    }

    public function test_segments_builds_colored_slices(): void
    {
        $segments = DashboardRoleChart::segments([
            'shop' => 3,
            'member' => 7,
            'admin' => 1,
        ]);

        $this->assertCount(3, $segments);
        $this->assertSame('#3b82f6', $segments->firstWhere('role', 'shop')['color']);
        $this->assertStringStartsWith('M ', $segments->first()['path']);
    }
}
