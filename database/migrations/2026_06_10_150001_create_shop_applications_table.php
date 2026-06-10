<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('seller_type', 20)->default('personal');
            $table->string('shop_name');
            $table->string('logo')->nullable();
            $table->string('address');
            $table->string('country', 100);
            $table->string('phone', 20);
            $table->string('real_name');
            $table->string('referral_code')->nullable();
            $table->string('id_number');
            $table->string('id_front')->nullable();
            $table->string('id_back')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_applications');
    }
};
