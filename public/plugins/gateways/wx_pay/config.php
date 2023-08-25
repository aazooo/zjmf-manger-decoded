<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
return[
    'module_name'          => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title' => '名称', // 表单的label标题
        'type'  => 'text', // 表单的类型：text,password,textarea,checkbox,radio,select等
        'value' => '微信支付', // 表单的默认值
        'tip'   => '', //表单的帮助提示
    ],
    'AppId'=>[
        'title'=>'AppId',
        'type'=>'text',
        'value'=>'',
        'tip'=>'配置信息'
    ],
    'MerchantId'=>[
        'title'=>'MerchantId',
        'type'=>'text',
        'value'=>'',
        'tip'=>'配置信息'
    ],
    'Key'=>[
        'title'=>'Key',
        'type'=>'text',
        'value'=>'',
        'tip'=>'商户配置信息'
    ],
    'AppSecret'=>[
        'title'=>'AppSecret',
        'type'=>'text',
        'value'=>'',
        'tip'=>'配置信息'
    ],
//    'currency'      => [
//        'title' => '支持货币单位',
//        'type'  => 'text',
//        'value' => '',
//        'tip'   => '',
//    ],

];