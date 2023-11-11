<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
return [

    'app_id' => [
        'title' => '应用ID',
        'type'  => 'text',
        'value' => '',
        'tip'   => '',
    ],
    'merchant_private_key' => [
        'title' => '应用私钥',
        'type'  => 'text',
        'value' => '',
        'tip'   => '',
    ],
    'alipay_public_key' => [
        'title' => '支付宝公钥',
        'type'  => 'text',
        'value' => '',
        'tip'   => '',
    ],
    'product_pc' => [
        'title' => '电脑网站支付',
        'type' => 'select',
        'options' => [
            '1' => '开启',
            '0' => '关闭',
        ],
        'value' => '1',
        'tip' => '',
    ],
    'product_wap' => [
        'title' => '手机网站支付',
        'type' => 'select',
        'options' => [
            '1' => '开启',
            '0' => '关闭',
        ],
        'value' => '1',
        'tip' => '',
    ],
    'product_qr' => [
        'title' => '当面付',
        'type' => 'select',
        'options' => [
            '1' => '开启',
            '0' => '关闭',
        ],
        'value' => '0',
        'tip' => '',
    ],
];
