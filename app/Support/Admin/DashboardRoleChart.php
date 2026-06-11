<?php

namespace App\Support\Admin;

use Illuminate\Support\Collection;

class DashboardRoleChart
{
    /** @var array<string, string> */
    private const COLORS = [
        'shop' => '#3b82f6',
        'member' => '#a78bfa',
        'admin' => '#f59e0b',
    ];

    /**
     * @param  iterable<string, int>  $data
     * @return Collection<int, array{role: string, label: string, count: int, color: string, path: string}>
     */
    public static function segments(iterable $data): Collection
    {
        $items = collect($data)
            ->filter(fn (int $count): bool => $count > 0)
            ->sortKeys();

        $total = (int) $items->sum();

        if ($total === 0) {
            return collect();
        }

        $cx = 60.0;
        $cy = 60.0;
        $radius = 56.0;
        $startAngle = -90.0;

        return $items->map(function (int $count, string $role) use (&$startAngle, $cx, $cy, $radius, $total) {
            $sweep = ($count / $total) * 360;
            $endAngle = $startAngle + $sweep;
            $path = self::describeSlice($cx, $cy, $radius, $startAngle, $endAngle);
            $startAngle = $endAngle;

            return [
                'role' => $role,
                'label' => __('admin.roles.'.$role),
                'count' => $count,
                'color' => self::COLORS[$role] ?? '#94a3b8',
                'path' => $path,
            ];
        })->values();
    }

    private static function describeSlice(float $cx, float $cy, float $radius, float $startAngle, float $endAngle): string
    {
        if ($endAngle - $startAngle >= 359.999) {
            return sprintf(
                'M %.2f %.2f m -%.2f 0 a %.2f %.2f 0 1 0 %.4f 0 a %.2f %.2f 0 1 0 -%.4f 0',
                $cx,
                $cy,
                $radius,
                $radius,
                $radius,
                $radius * 2,
                $radius,
                $radius,
                $radius * 2,
            );
        }

        $start = self::polarToCartesian($cx, $cy, $radius, $endAngle);
        $end = self::polarToCartesian($cx, $cy, $radius, $startAngle);
        $largeArc = ($endAngle - $startAngle) > 180 ? 1 : 0;

        return sprintf(
            'M %.2f %.2f L %.2f %.2f A %.2f %.2f 0 %d 0 %.2f %.2f Z',
            $cx,
            $cy,
            $start['x'],
            $start['y'],
            $radius,
            $radius,
            $largeArc,
            $end['x'],
            $end['y'],
        );
    }

    /** @return array{x: float, y: float} */
    private static function polarToCartesian(float $cx, float $cy, float $radius, float $angleDegrees): array
    {
        $angleRadians = deg2rad($angleDegrees);

        return [
            'x' => round($cx + ($radius * cos($angleRadians)), 2),
            'y' => round($cy + ($radius * sin($angleRadians)), 2),
        ];
    }
}
