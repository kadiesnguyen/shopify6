<?php

return [
    /*
    | ponytail: industries are config-driven; category slugs resolve to DB ids at runtime.
    | comprehensive => all active categories.
    */
    'comprehensive' => [
        'name' => 'Ngành toàn diện',
        'rate' => 3,
        'categories' => '*',
    ],
    'fashion' => [
        'name' => 'Ngành thời trang',
        'rate' => 5,
        'categories' => ['thoi-trang', 'demo-cate-2', 'giay-dep', 'tui-sach-hang-hieu', 'trang-suc'],
    ],
    'beauty' => [
        'name' => 'Ngành mỹ phẩm',
        'rate' => 5,
        'categories' => ['my-pham', 'nuoc-hoa', 'san-pham-ve-sinh', 'do-dung-phong-tam'],
    ],
    'electronics' => [
        'name' => 'Ngành điện tử',
        'rate' => 5,
        'categories' => ['dien-thoai', 'do-dien-tu', 'dong-ho'],
    ],
    'mother_baby' => [
        'name' => 'Ngành mẹ và bé',
        'rate' => 5,
        'categories' => ['me-va-be', 'do-choi-tre-em'],
    ],
    'food_health' => [
        'name' => 'Ngành thực phẩm',
        'rate' => 5,
        'categories' => ['thuc-pham-chuc-nang', 'thuc-an-thu-cung'],
    ],
    'home_living' => [
        'name' => 'Ngành gia dụng',
        'rate' => 5,
        'categories' => ['do-gia-dung', 'sofa', 'dung-cu-nha-bep', 'do-trang-tri', 'ghe-massage', 'may-chay-bo', 'van-phong', 'do-phong-thuy'],
    ],
    'other' => [
        'name' => 'Ngành khác',
        'rate' => 5,
        'categories' => ['khac', 'do-choi-tinh-duc'],
    ],
];
