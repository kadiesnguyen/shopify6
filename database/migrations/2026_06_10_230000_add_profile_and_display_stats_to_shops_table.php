<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->string('id_number')->nullable()->after('logo');
            $table->string('id_front')->nullable()->after('id_number');
            $table->string('id_back')->nullable()->after('id_front');
            $table->string('address')->nullable()->after('id_back');
            $table->string('country')->nullable()->after('address');
            $table->unsignedInteger('followers')->default(0)->after('country');
            $table->unsignedInteger('credit_score')->default(0)->after('followers');
            $table->unsignedInteger('display_pending_orders')->nullable()->after('credit_score');
            $table->unsignedInteger('display_delivering_orders')->nullable()->after('display_pending_orders');
            $table->unsignedInteger('display_received_orders')->nullable()->after('display_delivering_orders');
            $table->unsignedInteger('display_completed_orders')->nullable()->after('display_received_orders');
            $table->decimal('display_total_income', 14, 2)->nullable()->after('display_completed_orders');
            $table->decimal('display_balance', 14, 2)->nullable()->after('display_total_income');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->dropColumn([
                'id_number',
                'id_front',
                'id_back',
                'address',
                'country',
                'followers',
                'credit_score',
                'display_pending_orders',
                'display_delivering_orders',
                'display_received_orders',
                'display_completed_orders',
                'display_total_income',
                'display_balance',
            ]);
        });
    }
};
