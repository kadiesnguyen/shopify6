<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\Member\ProductDetailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchProductDescriptionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(
        public int $productId,
        public string $sourceUrl = 'https://sieummo.vn',
    ) {}

    public function handle(ProductDetailService $details): void
    {
        $product = Product::query()->find($this->productId);

        if ($product === null) {
            return;
        }

        $details->fetchAndStoreRemoteDescription($product, $this->sourceUrl);
    }
}
