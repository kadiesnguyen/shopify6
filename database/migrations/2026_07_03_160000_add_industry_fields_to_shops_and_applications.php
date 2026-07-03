<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->string('industry_id', 40)->nullable()->after('seller_type');
            $table->json('business_category_ids')->nullable()->after('industry_id');
        });

        Schema::table('shop_applications', function (Blueprint $table): void {
            $table->string('industry_id', 40)->nullable()->after('seller_type');
            $table->json('business_category_ids')->nullable()->after('industry_id');
            $table->text('shop_description')->nullable()->after('shop_name');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->dropColumn(['industry_id', 'business_category_ids']);
        });

        Schema::table('shop_applications', function (Blueprint $table): void {
            $table->dropColumn(['industry_id', 'business_category_ids', 'shop_description']);
        });
    }
};
