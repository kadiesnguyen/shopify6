<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_sub_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('username', 80);
            $table->string('phone', 20)->nullable();
            $table->string('password');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['shop_id', 'username']);
        });

        Schema::create('user_payout_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('label', 120)->nullable();
            $table->string('bank_name', 120)->nullable();
            $table->string('account_name', 120)->nullable();
            $table->string('account_number', 64)->nullable();
            $table->string('crypto_currency', 20)->nullable();
            $table->string('crypto_network', 120)->nullable();
            $table->string('crypto_address', 255)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('order_refund_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_refund_requests');
        Schema::dropIfExists('user_payout_accounts');
        Schema::dropIfExists('shop_sub_accounts');
    }
};
