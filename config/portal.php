<?php

return [
    'brand_name' => 'Shopify',
    'logo' => 'images/portal/logo.jpg',
    'max_width' => '420px',

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
            'icon' => 'store',
            'color' => 'bg-violet-600',
        ],
        [
            'label_key' => 'member.actions.promotions',
            'route' => 'member.promotions.index',
            'icon' => 'ticket-percent',
            'color' => 'bg-rose-500',
        ],
        [
            'label_key' => 'member.actions.register',
            'route' => 'auth.register',
            'icon' => 'gem',
            'color' => 'bg-amber-400',
        ],
        [
            'label_key' => 'member.actions.loyalty',
            'route' => 'member.my.index',
            'icon' => 'crown',
            'color' => 'bg-amber-500',
        ],
        [
            'label_key' => 'member.actions.support',
            'route' => 'member.chat.index',
            'icon' => 'headset',
            'color' => 'bg-red-500',
        ],
    ],
];
