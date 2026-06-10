<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_distributions', function (Blueprint $table) {
            $table->string('status', 20)->default('available')->after('commission_type');
            $table->index(['user_id', 'status']);
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('product_distributions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['product_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
