<?php

return [
    'index' => ['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D', '5' => 'E', '6' => 'F', '7' => 'G', '8' => 'H', '9' => 'I', '10' => 'J', '11' => 'K', '12' => 'L', '13' => 'M', '14' => 'N', '15' => 'O', '16' => 'P', '17' => 'Q', '18' => 'R', '19' => 'S', '20' => 'T', '21' => 'U', '22' => 'V', '23' => 'W', '24' => 'X', '25' => 'Y', '26' => 'Z'],
    'list' => [
        'achievement' => [
            'name' => '我的业绩',
            'getDataFun' => 'achievementFun',
            'achievement' => ['croom' => '主机名称', 'cip' => '主ip', 'pname' => '业务经理名称', 'paytype' => '付款方式', 'bill_num' => '账单编号', 'pd_name' => '产品名称', 'g_name' => '客户名称', 'amount' => '账单金额(在线支付+余额)', 'stream' => '在线支付金额', 'balance' => '余额', 'mount_at' => '收款时间']], 'billPay' => ['name' => '账单列表（已支付）', 'getDataFun' => 'billPay', 'billPay' => ['croom' => '主机名称', 'cip' => '主ip', 'pname' => '业务经理名称', 'paytype' => '付款方式', 'bill_num' => '账单编号', 'pd_name' => '产品名称', 'g_name' => '客户名称', 'amount' => '账单金额(在线支付+余额)', 'stream' => '在线支付金额', 'balance' => '余额', 'mount_at' => '收款时间']
        ]
    ]
];
