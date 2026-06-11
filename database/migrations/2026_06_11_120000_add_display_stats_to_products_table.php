<?php

use App\Models\Product;
use App\Support\ProductDisplayStats;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('display_click_count')->nullable()->after('stock');
            $table->unsignedInteger('display_sales_count')->nullable()->after('display_click_count');
        });

        Product::query()->eachById(function (Product $product): void {
                $stats = ProductDisplayStats::randomPair();

                $product->update([
                    'display_click_count' => $stats['clicks'],
                    'display_sales_count' => $stats['sales'],
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['display_click_count', 'display_sales_count']);
        });
    }
};
