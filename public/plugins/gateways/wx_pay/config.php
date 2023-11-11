<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
return[
    'AppId'=>[
        'title'=>'APPID',
        'type'=>'text',
        'value'=>'',
        'tip'=>'需绑定至微信支付商户'
    ],
    'MerchantId'=>[
        'title'=>'商户号',
        'type'=>'text',
        'value'=>'',
        'tip'=>''
    ],
    'Key'=>[
        'title'=>'商户APIv2密钥',
        'type'=>'text',
        'value'=>'',
        'tip'=>''
    ],
    'AppSecret'=>[
        'title'=>'APPSECRET',
        'type'=>'text',
        'value'=>'',
        'tip'=>'仅JSAPI支付需要配置'
    ],
    'ProductNative' => [
        'title' => 'NATIVE支付',
        'type' => 'select',
        'options' => [
            '1' => '开启',
            '0' => '关闭',
        ],
        'value' => '1',
        'tip' => '',
    ],
    'ProductJsapi' => [
        'title' => 'JSAPI支付',
        'type' => 'select',
        'options' => [
            '1' => '开启',
            '0' => '关闭',
        ],
        'value' => '0',
        'tip' => '',
    ],
    'ProductWap' => [
        'title' => 'H5支付',
        'type' => 'select',
        'options' => [
            '1' => '开启',
            '0' => '关闭',
        ],
        'value' => '0',
        'tip' => '',
    ],
];