<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('purchase_cost', 15, 2)->default(0)->after('commission');
            $table->timestamp('stock_restored_at')->nullable()->after('completed_at');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('purchase_price', 15, 2)->default(0)->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['purchase_cost', 'stock_restored_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('purchase_price');
        });
    }
};
