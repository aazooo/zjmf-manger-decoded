<?php
/*
 *  前台自定义菜单
 */
return [
    [
        'name' => '插件样式Demo', # 菜单名称 默认为一级菜单
        'url'  => '', # 菜单路由 (若有子菜单,此值留空)
        'fa_icon' => 'bx bxs-grid-alt', # 菜单图标 支持bootstrap
        'lang' => [ # 菜单多语言
            'chinese' => '插件样式Demo', # 中文
            'chinese_tw' => '插件样式Demo', # 台湾
            'english' => 'Style Demo', # 英文
        ],
        'child' => [  # 子菜单 没有定义为空数组
            [
                'name' => '样式1', # 链接名称
                'url'  => 'DemoStyle://Index/addhelp', # 链接格式   插件名://控制器名/方法   菜单路由 (若有子菜单,此值留空)
                'fa_icon' => '',
                'lang' => [ # 菜单多语言
                    'chinese' => '样式1', # 中文
                    'chinese_tw' => '样式1', # 台湾
                    'english' => 'Style1', # 英文
                ],
                'child' => []
            ],
            [
                'name' => '样式2', # 链接名称
                'url'  => 'DemoStyle://Index/customerdetail1', # 链接格式   插件名://控制器名/方法
                'fa_icon' => '',
                'lang' => [ # 菜单多语言
                    'chinese' => '样式2', # 中文
                    'chinese_tw' => '样式2', # 台湾
                    'english' => 'Style2', # 英文
                ],
                'child' => []
            ],
            [
                'name' => '样式3', # 链接名称
                'url'  => 'DemoStyle://Index/customerdetail2', # 链接格式   插件名://控制器名/方法
                'fa_icon' => '',
                'lang' => [ # 菜单多语言
                    'chinese' => '样式3', # 中文
                    'chinese_tw' => '样式3', # 台湾
                    'english' => 'Style3', # 英文
                ],
                'child' => []
            ],
            [
                'name' => '样式4', # 链接名称
                'url'  => 'DemoStyle://Index/customerdetail3', # 链接格式   插件名://控制器名/方法
                'fa_icon' => '',
                'lang' => [ # 菜单多语言
                    'chinese' => '样式4', # 中文
                    'chinese_tw' => '样式4', # 台湾
                    'english' => 'Style4', # 英文
                ],
                'child' => []
            ],
            [
                'name' => '样式5', # 链接名称
                'url'  => 'DemoStyle://Index/customerdetail4', # 链接格式   插件名://控制器名/方法
                'fa_icon' => '',
                'lang' => [ # 菜单多语言
                    'chinese' => '样式5', # 中文
                    'chinese_tw' => '样式5', # 台湾
                    'english' => 'Style5', # 英文
                ],
                'child' => []
            ],
            [
                'name' => '样式6', # 链接名称
                'url'  => 'DemoStyle://Index/helplist', # 链接格式   插件名://控制器名/方法
                'fa_icon' => '',
                'lang' => [ # 菜单多语言
                    'chinese' => '样式6', # 中文
                    'chinese_tw' => '样式6', # 台湾
                    'english' => 'Style6', # 英文
                ],
                'child' => []
            ]
        ]
    ]
];