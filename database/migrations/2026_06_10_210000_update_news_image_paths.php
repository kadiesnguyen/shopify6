<?php

use App\Models\News;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        News::query()
            ->where('slug', 'shopefy-launch')
            ->update(['image' => 'images/landing/news/launch.jpg']);

        News::query()
            ->where('slug', 'seller-tips')
            ->update(['image' => 'images/landing/news/seller-tips.jpg']);
    }

    public function down(): void
    {
        News::query()
            ->where('slug', 'shopefy-launch')
            ->update(['image' => 'news/launch.jpg']);

        News::query()
            ->where('slug', 'seller-tips')
            ->update(['image' => 'news/seller-tips.jpg']);
    }
};
