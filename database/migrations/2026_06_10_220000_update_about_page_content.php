<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $content = require database_path('data/about-page-content.php');

        Page::query()
            ->where('slug', 'gioi-thieu')
            ->update([
                'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                'meta_description' => json_encode([
                    'vi' => 'Giá trị, quyền riêng tư và cam kết bảo vệ thông tin trên nền tảng Shopefy',
                    'en' => 'Our values, privacy commitments, and how Shopefy protects your information',
                ], JSON_UNESCAPED_UNICODE),
            ]);
    }

    public function down(): void
    {
        Page::query()
            ->where('slug', 'gioi-thieu')
            ->update([
                'content' => json_encode([
                    'vi' => '<p>Shopefy là nền tảng thương mại điện tử hỗ trợ bán hàng trực tuyến.</p>',
                    'en' => '<p>Shopefy is an e-commerce platform for online selling.</p>',
                ], JSON_UNESCAPED_UNICODE),
                'meta_description' => json_encode([
                    'vi' => 'Tìm hiểu về Shopefy',
                    'en' => 'Learn about Shopefy',
                ], JSON_UNESCAPED_UNICODE),
            ]);
    }
};
