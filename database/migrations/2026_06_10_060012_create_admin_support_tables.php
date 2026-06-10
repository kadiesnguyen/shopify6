<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invite_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->json('config')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('recharge_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 30);
            $table->json('config')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('withdrawal_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 30);
            $table->json('config')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('recharge_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recharge_method_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('pending');
            $table->string('reference', 64)->nullable();
            $table->string('proof_image')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('withdrawal_method_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('pending');
            $table->json('payout_details')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('password_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_change_requests');
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('recharge_requests');
        Schema::dropIfExists('withdrawal_methods');
        Schema::dropIfExists('recharge_methods');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('invite_codes');
    }
};
