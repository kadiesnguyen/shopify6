<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->decimal('star_rating', 3, 1)->default(0)->after('credit_score');
            $table->decimal('display_total_sales', 14, 2)->nullable()->after('display_balance');
            $table->decimal('display_total_profit', 14, 2)->nullable()->after('display_total_sales');
            $table->unsignedInteger('display_orders_today')->nullable()->after('display_total_profit');
            $table->decimal('display_sales_today', 14, 2)->nullable()->after('display_orders_today');
            $table->decimal('display_profit_today', 14, 2)->nullable()->after('display_sales_today');
            $table->unsignedInteger('display_visitors_today')->nullable()->after('display_profit_today');
            $table->unsignedInteger('display_visitors_7d')->nullable()->after('display_visitors_today');
            $table->unsignedInteger('display_visitors_30d')->nullable()->after('display_visitors_7d');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->dropColumn([
                'star_rating',
                'display_total_sales',
                'display_total_profit',
                'display_orders_today',
                'display_sales_today',
                'display_profit_today',
                'display_visitors_today',
                'display_visitors_7d',
                'display_visitors_30d',
            ]);
        });
    }
};
