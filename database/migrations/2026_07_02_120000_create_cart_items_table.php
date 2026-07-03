<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ponytail: table may exist if a prior migrate was killed mid-flight
        if (Schema::hasTable('cart_items')) {
            return;
        }

        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_distribution_id')->constrained('product_distributions')->cascadeOnDelete();
            $table->foreignId('shop_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('selected')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'product_distribution_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfTable('cart_items');
    }
};
