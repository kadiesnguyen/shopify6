<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Faq;
use App\Models\Page;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $aboutContent = require database_path('data/about-page-content.php');

        Banner::query()->upsert([
            [
                'id' => 1,
                'image' => 'banners/banner-1.jpg',
                'link_url' => '/home/products',
                'sort_order' => 1,
                'status' => 'active',
                'title' => json_encode(['vi' => 'Khởi nghiệp cùng Shopefy', 'en' => 'Start selling with Shopefy']),
                'subtitle' => json_encode(['vi' => 'Nền tảng thương mại điện tử', 'en' => 'E-commerce platform']),
            ],
            [
                'id' => 2,
                'image' => 'banners/banner-2.jpg',
                'link_url' => '/home/promotions',
                'sort_order' => 2,
                'status' => 'active',
                'title' => json_encode(['vi' => 'Khuyến mãi hấp dẫn', 'en' => 'Hot promotions']),
                'subtitle' => json_encode(['vi' => 'Ưu đãi dành cho thành viên', 'en' => 'Member exclusive deals']),
            ],
        ], ['id'], ['image', 'link_url', 'sort_order', 'status', 'title', 'subtitle']);

        Page::query()->upsert([
            [
                'slug' => 'gioi-thieu',
                'type' => 'about',
                'title' => json_encode(['vi' => 'Giới thiệu', 'en' => 'About us']),
                'content' => json_encode($aboutContent),
                'meta_title' => json_encode(['vi' => 'Giới thiệu Shopefy', 'en' => 'About Shopefy']),
                'meta_description' => json_encode([
                    'vi' => 'Giá trị, quyền riêng tư và cam kết bảo vệ thông tin trên nền tảng Shopefy',
                    'en' => 'Our values, privacy commitments, and how Shopefy protects your information',
                ]),
                'status' => 'published',
            ],
            [
                'slug' => 'lien-he',
                'type' => 'contact',
                'title' => json_encode(['vi' => 'Liên hệ', 'en' => 'Contact']),
                'content' => json_encode([
                    'vi' => '<p>Shopify Inc. 151 O\'Connor Street, Ground floor Ottawa, ON K2P 2L8 Canada</p>',
                    'en' => '<p>Shopify Inc. 151 O\'Connor Street, Ground floor Ottawa, ON K2P 2L8 Canada</p>',
                ]),
                'meta_title' => json_encode(['vi' => 'Liên hệ Shopefy', 'en' => 'Contact Shopefy']),
                'meta_description' => json_encode(['vi' => 'Liên hệ hỗ trợ', 'en' => 'Contact support']),
                'status' => 'published',
            ],
            [
                'slug' => 'chinh-sach',
                'type' => 'policy',
                'title' => json_encode(['vi' => 'Chính sách', 'en' => 'Policy']),
                'content' => json_encode([
                    'vi' => '<p>Chính sách bảo mật và điều khoản sử dụng.</p>',
                    'en' => '<p>Privacy policy and terms of service.</p>',
                ]),
                'meta_title' => json_encode(['vi' => 'Chính sách Shopefy', 'en' => 'Shopefy Policy']),
                'meta_description' => json_encode(['vi' => 'Chính sách bảo mật', 'en' => 'Privacy policy']),
                'status' => 'published',
            ],
        ], ['slug'], ['type', 'title', 'content', 'meta_title', 'meta_description', 'status']);

        Faq::query()->insert([
            [
                'question' => json_encode(['vi' => 'Làm sao để nạp tiền?', 'en' => 'How to recharge?']),
                'answer' => json_encode(['vi' => 'Vào mục Nạp tiền trong tài khoản.', 'en' => 'Go to Recharge in your account.']),
                'sort_order' => 1,
                'status' => 'active',
            ],
            [
                'question' => json_encode(['vi' => 'Thời hạn thanh toán đơn hàng?', 'en' => 'Order payment deadline?']),
                'answer' => json_encode(['vi' => 'Bạn có 24 giờ để thanh toán.', 'en' => 'You have 24 hours to pay.']),
                'sort_order' => 2,
                'status' => 'active',
            ],
        ]);
    }
}
