<?php

use App\Services\Member\ProductDistributionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_distributions')
            ->join('products', 'products.id', '=', 'product_distributions.product_id')
            ->whereColumn('product_distributions.selling_price', 'products.selling_price')
            ->whereColumn('products.selling_price', '>', 'product_distributions.purchase_price')
            ->select([
                'product_distributions.id',
                'products.selling_price as market_price',
                'product_distributions.purchase_price',
            ])
            ->orderBy('product_distributions.id')
            ->lazy()
            ->each(function (object $row): void {
                $sellingPrice = ProductDistributionService::suggestedSellingPrice(
                    (float) $row->market_price,
                    (float) $row->purchase_price,
                );

                if ($sellingPrice >= (float) $row->market_price) {
                    return;
                }

                DB::table('product_distributions')
                    ->where('id', $row->id)
                    ->update([
                        'selling_price' => $sellingPrice,
                        'commission' => max(0, round($sellingPrice - (float) $row->purchase_price, 2)),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        // ponytail: one-way data normalization; re-run distribute manually if needed.
    }
};
