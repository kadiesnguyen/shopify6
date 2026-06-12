<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('seller_type', 20)->default('personal')->after('user_id');
        });

        Schema::table('shop_applications', function (Blueprint $table) {
            $table->string('application_kind', 20)->default('registration')->after('seller_type');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('seller_type');
        });

        Schema::table('shop_applications', function (Blueprint $table) {
            $table->dropColumn('application_kind');
        });
    }
};
