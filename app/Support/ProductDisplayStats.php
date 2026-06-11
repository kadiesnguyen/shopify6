<?php

namespace App\Support;

class ProductDisplayStats
{
    public const MIN = 1000;

    public const MAX = 500_000;

    /** @return array{clicks: int, sales: int} */
    public static function randomPair(): array
    {
        $sales = random_int(self::MIN, self::MAX - 1);
        $clicks = random_int($sales + 1, self::MAX);

        return [
            'clicks' => $clicks,
            'sales' => $sales,
        ];
    }
}
