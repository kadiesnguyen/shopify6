<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_distributions', function (Blueprint $table): void {
            $table->index(['product_id', 'status', 'created_at'], 'product_distributions_listing_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_distributions', function (Blueprint $table): void {
            $table->dropIndex('product_distributions_listing_idx');
        });
    }
};
