<?php

return [
    'brand_name' => 'Shopify',
    'logo' => 'images/portal/logo.jpg',
    'max_width' => '420px',

    // ponytail: shipped → completed after N hours; override via ORDER_AUTO_COMPLETE_HOURS
    'order_auto_complete_hours' => (int) env('ORDER_AUTO_COMPLETE_HOURS', 1),

    'banners' => [
        'images/portal/banners/banner1.jpg',
        'images/portal/banners/banner2.jpg',
        'images/portal/banners/banner3.jpg',
        'images/portal/banners/banner4.jpg',
    ],

    'quick_actions' => [
        [
            'label_key' => 'member.actions.start_selling',
            'route' => 'member.shop-application.create',
            'image' => 'images/portal/quick/start-selling.jpg',
        ],
        [
            'label_key' => 'member.actions.promotions',
            'route' => 'member.promotions.index',
            'image' => 'images/portal/quick/promotions.png',
        ],
        [
            'label_key' => 'member.actions.register_goods',
            'route' => 'auth.register',
            'image' => 'images/portal/quick/register.png',
        ],
        [
            'label_key' => 'member.actions.loyalty',
            'route' => 'member.my.index',
            'image' => 'images/portal/quick/loyalty.png',
        ],
        [
            'label_key' => 'member.actions.support',
            'route' => 'member.chat.index',
            'image' => 'images/portal/quick/cskh.png',
        ],
    ],
];
