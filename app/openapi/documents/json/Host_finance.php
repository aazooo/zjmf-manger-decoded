<?php

return ["title" => "财务类", "item" => ["renewPage" => ["title" => "获取续费", "desc" => "获取续费", "url" => "/v1/hosts/:id/renew", "method" => "GET", "auth" => "开发者", "version" => "v1", "param" => [["name" => "id", "type" => "int", "require" => "require", "max" => "", "desc" => "产品ID", "example" => "1", "child" => []], ["name" => "billingcycle", "type" => "string", "require" => "", "max" => "-", "desc" => "周期", "example" => "monthly", "child" => []]], "return" => [["name" => "currency", "type" => "array[]", "require" => "", "max" => "-", "desc" => "货币信息", "example" => "", "child" => [["name" => "id", "type" => "int", "require" => "", "max" => "-", "desc" => "货币ID", "example" => "1", "child" => []], ["name" => "code", "type" => "string", "require" => "", "max" => "-", "desc" => "货币代码", "example" => "CNY", "child" => []], ["name" => "prefix", "type" => "string", "require" => "", "max" => "-", "desc" => "货币前缀", "example" => "￥", "child" => []], ["name" => "suffix", "type" => "string", "require" => "", "max" => "-", "desc" => "货币后缀", "example" => "元", "child" => []]]], ["name" => "cycle", "type" => "array[]", "require" => "", "max" => "-", "desc" => "商品可续费周期", "example" => "{
                \"setup_fee\": \"0.00\",
                \"price\": \"1000.00\",
                \"billingcycle\": \"monthly\",
                \"billingcycle_zh\": \"月付\",
                \"amount\": \"1005.00\",
                \"saleproducts\": \"0.00\"
            }", "child" => [["name" => "billingcycle", "type" => "string", "require" => "", "max" => "-", "desc" => "周期", "example" => "monthly", "child" => []], ["name" => "amount", "type" => "price", "require" => "", "max" => "-", "desc" => "续费金额", "example" => "1005.00", "child" => []], ["name" => "saleproducts", "type" => "price", "require" => "", "max" => "-", "desc" => "折扣后续费金额", "example" => "0.00", "child" => []], ["name" => "price", "type" => "price", "require" => "", "max" => "-", "desc" => "商品价格", "example" => "1000.00", "child" => []], ["name" => "setup_fee", "type" => "price", "require" => "", "max" => "-", "desc" => "初装费价格", "example" => "0.00", "child" => []]]], ["name" => "pay_type", "type" => "array[]", "require" => "", "max" => "-", "desc" => "其它周期付款信息", "example" => "{
            \"pay_type\": \"recurring\",
            \"pay_hour_cycle\": \"720\",
            \"pay_day_cycle\": \"30\",
            \"pay_ontrial_status\": 1,
            \"pay_ontrial_cycle\": \"10\",
            \"pay_ontrial_num\": \"1\",
            \"pay_ontrial_condition\": [
                \"phone\",
                \"realname\"
            ],
            \"pay_ontrial_cycle_type\": \"day\",
            \"pay_ontrial_num_rule\": \"0\",
            \"clientscount_rule\": \"0\"
        }", "child" => [["name" => "pay_type", "type" => "string", "require" => "", "max" => "-", "desc" => "付款周期", "example" => "recurring", "child" => []], ["name" => "pay_hour_cycle", "type" => "int", "require" => "", "max" => "-", "desc" => "小时付周期", "example" => "720", "child" => []], ["name" => "pay_day_cycle", "type" => "int", "require" => "", "max" => "-", "desc" => "天付周期", "example" => "30", "child" => []], ["name" => "pay_ontrial_status", "type" => "int", "require" => "", "max" => "-", "desc" => "是否支持试用:1是,0否", "example" => "1", "child" => []], ["name" => "pay_ontrial_cycle_type", "type" => "string", "require" => "", "max" => "-", "desc" => "试用周期类型:day天,hour小时", "example" => "1", "child" => []], ["name" => "pay_ontrial_cycle", "type" => "int", "require" => "", "max" => "-", "desc" => "试用周期周期", "example" => "10", "child" => []], ["name" => "pay_ontrial_condition", "type" => "array[]", "require" => "", "max" => "-", "desc" => "试用条件:phone需要绑定手机号,realname需要实名认证", "example" => "[
                \"phone\",
                \"realname\"
            ]", "child" => []], ["name" => "pay_ontrial_num", "type" => "int", "require" => "", "max" => "-", "desc" => "试用周期可购买数量:0表示无限制", "example" => "10", "child" => []], ["name" => "pay_ontrial_num_rule", "type" => "int", "require" => "", "max" => "-", "desc" => "试用规则", "example" => "1", "child" => []], ["name" => "clientscount_rule", "type" => "int", "require" => "", "max" => "-", "desc" => "试用数量计算规则:0任意状态产品,1激活状态产品", "example" => "1", "child" => []]]]]], "renew" => ["title" => "续费", "desc" => "续费", "url" => "/v1/hosts/:id/renew", "method" => "POST", "auth" => "开发者", "version" => "v1", "param" => [["name" => "id", "type" => "int", "require" => "require", "max" => "", "desc" => "产品ID", "example" => "1", "child" => []], ["name" => "billingcycle", "type" => "string", "require" => "require", "max" => "", "desc" => "周期", "example" => "monthly", "child" => []]], "return" => [["name" => "invoiceid", "type" => "int", "require" => "", "max" => "-", "desc" => "账单ID", "example" => "1", "child" => []], ["name" => "payment", "type" => "string", "require" => "", "max" => "-", "desc" => "支付方式", "example" => "WxPay", "child" => []]]], "renewAuto" => ["title" => "自动余额续费开关", "desc" => "自动余额续费开关", "url" => "/v1/hosts/:id/renew", "method" => "PUT", "auth" => "开发者", "version" => "v1", "param" => [["name" => "id", "type" => "int", "require" => "require", "max" => "", "desc" => "产品ID", "example" => "1", "child" => []], ["name" => "initiative_renew", "type" => "int", "require" => "require", "max" => "", "desc" => "是否自动余额续费:1是,0否", "example" => "1", "child" => []]]], "renewBatchPage" => ["title" => "获取批量续费", "desc" => "获取批量续费", "url" => "/v1/hosts/renew/batch", "method" => "GET", "auth" => "开发者", "version" => "v1", "param" => [["name" => "ids", "type" => "array[]", "require" => "是", "max" => "", "desc" => "产品ID数组", "example" => "[613,142]", "child" => []], ["name" => "billingcycles", "type" => "array[]", "require" => "否", "max" => "", "desc" => "产品对应周期", "example" => "{\"613\":\"monthly\",\"142\":\"monthly\"}", "child" => []]], "return" => [["name" => "total", "type" => "price", "require" => "是", "max" => "", "desc" => "续费总价", "example" => "1028.40", "child" => []], ["name" => "totalsale", "type" => "price", "require" => "是", "max" => "", "desc" => "折扣后续费总价", "example" => "0.00", "child" => []], ["name" => "currency", "type" => "array[]", "require" => "是", "max" => "-", "desc" => "货币信息", "example" => "", "child" => [["name" => "id", "type" => "int", "require" => "是", "max" => "-", "desc" => "货币ID", "example" => "1", "child" => []], ["name" => "code", "type" => "string", "require" => "是", "max" => "-", "desc" => "货币代码", "example" => "CNY", "child" => []], ["name" => "prefix", "type" => "string", "require" => "是", "max" => "-", "desc" => "货币前缀", "example" => "￥", "child" => []], ["name" => "suffix", "type" => "string", "require" => "是", "max" => "-", "desc" => "货币后缀", "example" => "元", "child" => []], ["name" => "default", "type" => "int", "require" => "是", "max" => "-", "desc" => "是否默认货币:1是,0否", "example" => "1", "child" => []]]], ["name" => "hosts", "type" => "array[]", "require" => "是", "max" => "", "desc" => "产品信息", "example" => "{
                \"productid\": 1,
                \"dedicatedip\": \"192.168.1.71\",
                \"uid\": 7,
                \"id\": 142,
                \"name\": \"魔方云主机\",
                \"nextduedate\": 1638786314,
                \"billingcycle\": \"monthly\",
                \"amount\": \"23.40\",
                \"flag\": 1,
                \"groupid\": 1,
                \"promoid\": 0,
                \"groupn\": {
                    \"id\": 1,
                    \"groupname\": \"云服务器\",
                    \"fa_icon\": \"el-icon-menu\",
                    \"order\": 0
                },
                \"saleproducts\": 0,
                \"nextduedate_renew\": 1641464714,
                \"allow_billingcycle\": [
                    {
                        \"setup_fee\": \"1.00\",
                        \"price\": \"60.00\",
                        \"billingcycle\": \"hour\",
                        \"billingcycle_zh\": \"小时\",
                        \"amount\": \"54.00\",
                        \"saleproducts\": \"36.00\",
                        \"flags\": 1
                    },
                    {
                        \"setup_fee\": \"10.00\",
                        \"price\": \"20.00\",
                        \"billingcycle\": \"day\",
                        \"billingcycle_zh\": \"天\",
                        \"amount\": \"12.00\",
                        \"saleproducts\": \"8.00\",
                        \"flags\": 1
                    },
                    {
                        \"setup_fee\": \"10.00\",
                        \"price\": \"20.00\",
                        \"billingcycle\": \"monthly\",
                        \"billingcycle_zh\": \"月付\",
                        \"amount\": \"23.40\",
                        \"saleproducts\": 0
                    },
                    {
                        \"setup_fee\": \"20.00\",
                        \"price\": \"40.00\",
                        \"billingcycle\": \"quarterly\",
                        \"billingcycle_zh\": \"季付\",
                        \"amount\": \"36.00\",
                        \"saleproducts\": \"24.00\"
                    },
                    {
                        \"setup_fee\": \"30.00\",
                        \"price\": \"60.00\",
                        \"billingcycle\": \"semiannually\",
                        \"billingcycle_zh\": \"半年付\",
                        \"amount\": \"36.00\",
                        \"saleproducts\": \"24.00\"
                    }
                ],
                \"flags\": 1
            }", "child" => [["name" => "productid", "type" => "int", "require" => "是", "max" => "", "desc" => "商品ID", "example" => "1", "child" => []], ["name" => "dedicatedip", "type" => "string", "require" => "是", "max" => "", "desc" => "独立IP", "example" => "192.168.1.71", "child" => []], ["name" => "name", "type" => "string", "require" => "是", "max" => "", "desc" => "商品名称", "example" => "魔方云主机", "child" => []], ["name" => "nextduedate", "type" => "int", "require" => "是", "max" => "", "desc" => "到期时间时间戳", "example" => "1638786314", "child" => []], ["name" => "billingcycle", "type" => "string", "require" => "是", "max" => "", "desc" => "周期", "example" => "monthly", "child" => []], ["name" => "nextduedate", "type" => "int", "require" => "是", "max" => "", "desc" => "到期时间时间戳", "example" => "1638786314", "child" => []], ["name" => "amount", "type" => "price", "require" => "是", "max" => "", "desc" => "续费金额", "example" => "23.40", "child" => []], ["name" => "groupn", "type" => "array[]", "require" => "是", "max" => "", "desc" => "产品菜单", "example" => "{
                    \"id\": 1,
                    \"groupname\": \"云服务器\",
                    \"fa_icon\": \"el-icon-menu\",
                    \"order\": 0
                },", "child" => [["name" => "id", "type" => "int", "require" => "是", "max" => "", "desc" => "菜单ID", "example" => "1", "child" => []], ["name" => "groupname", "type" => "string", "require" => "是", "max" => "", "desc" => "菜单名称", "example" => "云服务器", "child" => []], ["name" => "fa_icon", "type" => "string", "require" => "是", "max" => "", "desc" => "图标", "example" => "1el-icon-menu", "child" => []], ["name" => "order", "type" => "int", "require" => "是", "max" => "", "desc" => "菜单排序", "example" => "1", "child" => []]]], ["name" => "promoid", "type" => "int", "require" => "是", "max" => "", "desc" => "优惠码ID", "example" => "1", "child" => []], ["name" => "flags", "type" => "int", "require" => "是", "max" => "", "desc" => "是否有折扣:1是,0否", "example" => "1", "child" => []], ["name" => "allow_billingcycle", "type" => "array[]", "require" => "是", "max" => "", "desc" => "允许的周期", "example" => "{
                        \"setup_fee\": \"20.00\",
                        \"price\": \"40.00\",
                        \"billingcycle\": \"quarterly\",
                        \"billingcycle_zh\": \"季付\",
                        \"amount\": \"36.00\",
                        \"saleproducts\": \"24.00\"
                    }", "child" => [["name" => "billingcycle", "type" => "string", "require" => "是", "max" => "", "desc" => "周期", "example" => "quarterly", "child" => []], ["name" => "billingcycle_zh", "type" => "string", "require" => "是", "max" => "", "desc" => "周期(中文)", "example" => "季付", "child" => []], ["name" => "amount", "type" => "price", "require" => "是", "max" => "", "desc" => "续费金额", "example" => "36.00", "child" => []], ["name" => "setup_fee", "type" => "price", "require" => "是", "max" => "", "desc" => "初装费", "example" => "20.00", "child" => []], ["name" => "price", "type" => "price", "require" => "是", "max" => "", "desc" => "商品价格", "example" => "40.00", "child" => []], ["name" => "saleproducts", "type" => "price", "require" => "是", "max" => "", "desc" => "折扣后价格", "example" => "24.00", "child" => []]]]]]]], "renewBatch" => ["title" => "批量续费", "desc" => "批量续费", "url" => "/v1/hosts/renew/batch", "method" => "POST", "auth" => "开发者", "version" => "v1", "param" => [["name" => "ids", "type" => "array[]", "require" => "require", "max" => "", "desc" => "产品ID数组", "example" => "[613,142]", "child" => []], ["name" => "billingcycles", "type" => "array[]", "require" => "", "max" => "", "desc" => "产品对应周期", "example" => "{\"613\":\"monthly\",\"142\":\"monthly\"}", "child" => []]], "return" => [["name" => "invoice_id", "type" => "int", "require" => "", "max" => "-", "desc" => "账单ID", "example" => "1", "child" => []], ["name" => "payment", "type" => "string", "require" => "", "max" => "-", "desc" => "支付方式", "example" => "WxPay", "child" => []]]], "getCancelPage" => ["title" => "获取停用信息", "desc" => "获取停用信息", "url" => "/v1/hosts/:id/cancel", "method" => "GET", "auth" => "开发者", "version" => "v1", "param" => [], "return" => [["name" => "cancel", "type" => "array[]", "require" => "", "max" => "-", "desc" => "停用信息", "example" => "[\"example\"]", "child" => [["name" => "type", "type" => "string", "require" => "", "max" => "-", "desc" => "停用类型:Immediate立即,Endofbilling等待账单周期结束", "example" => "Immediate", "child" => []], ["name" => "reason", "type" => "string", "require" => "", "max" => "-", "desc" => "停用原因", "example" => "example", "child" => []]]]]], "postCancel" => ["title" => "申请停用", "desc" => "申请停用", "url" => "/v1/hosts/:id/cancel", "method" => "POST", "auth" => "开发者", "version" => "v1", "param" => [["name" => "type", "type" => "string", "require" => "是", "max" => "", "desc" => "停用类型:Immediate立即,Endofbilling等待账单周期结束", "example" => "Immediate", "child" => []], ["name" => "reason", "type" => "string", "require" => "是", "max" => "", "desc" => "停用原因", "example" => "example", "child" => []]], "return" => []], "deleteCancel" => ["title" => "取消停用", "desc" => "取消停用", "url" => "/v1/hosts/:id/cancel", "method" => "DELETE", "auth" => "开发者", "version" => "v1", "param" => [], "return" => []], "upgradeConfigPage1" => ["title" => "获取可升降级产品配置项", "desc" => "获取可升降级产品配置项", "url" => "/v1/hosts/:id/actions/upgradeconfig", "method" => "GET", "auth" => "开发者", "version" => "v1", "param" => [["name" => "id", "type" => "int", "require" => "是", "max" => "", "desc" => "产品ID", "example" => "613", "child" => []]], "return" => [["name" => "pid", "type" => "int", "require" => "是", "max" => "", "desc" => "商品ID", "example" => "1", "child" => []], ["name" => "currency", "type" => "array[]", "require" => "是", "max" => "-", "desc" => "货币信息", "example" => "", "child" => [["name" => "id", "type" => "int", "require" => "是", "max" => "-", "desc" => "货币ID", "example" => "1", "child" => []], ["name" => "code", "type" => "string", "require" => "是", "max" => "-", "desc" => "货币代码", "example" => "CNY", "child" => []], ["name" => "prefix", "type" => "string", "require" => "是", "max" => "-", "desc" => "货币前缀", "example" => "￥", "child" => []], ["name" => "suffix", "type" => "string", "require" => "是", "max" => "-", "desc" => "货币后缀", "example" => "元", "child" => []]]], ["name" => "host", "type" => "array[]", "require" => "是", "max" => "", "desc" => "配置项信息(可升降级的)", "example" => "{
                \"oid\": 15305,
                \"id\": 15305,
                \"flag\": 1,
                \"option_name\": \"IP数量\",
                \"option_type\": 4,
                \"qty\": 2,
                \"suboption_name\": \"IP数量\",
                \"suboption_name_first\": \"IP数量\",
                \"subid\": 85333,
                \"fee\": \"0.00\",
                \"setupfee\": \"0.00\",
                \"qty_minimum\": 1,
                \"qty_maximum\": 20,
                \"qty_stage\": 0,
                \"unit\": \"\",
                \"linkage_pid\": 0,
                \"linkage_top_pid\": 0,
                \"sub\": [
                    {
                        \"id\": 85333,
                        \"config_id\": 15305,
                        \"qty_minimum\": 1,
                        \"qty_maximum\": 20,
                        \"option_name\": \"IP数量\",
                        \"option_name_first\": \"IP数量\",
                        \"pricing\": \"0.00\",
                        \"qty_stage\": 0,
                        \"show_pricing\": \"IP数量 ￥0.00元\"
                    }
                ]
            }", "child" => [["name" => "id", "type" => "int", "require" => "是", "max" => "", "desc" => "配置项ID", "example" => "15305", "child" => []], ["name" => "option_name", "type" => "string", "require" => "是", "max" => "", "desc" => "配置项名称", "example" => "CPU", "child" => []], ["name" => "option_type", "type" => "int", "require" => "是", "max" => "", "desc" => "配置项类型", "example" => "6", "child" => []], ["name" => "suboption_name", "type" => "string", "require" => "是", "max" => "", "desc" => "子项名称,|分隔符之后", "example" => "1核", "child" => []], ["name" => "suboption_name_first", "type" => "string", "require" => "是", "max" => "", "desc" => "子项名称,|分隔符之前", "example" => "1", "child" => []], ["name" => "subid", "type" => "int", "require" => "是", "max" => "", "desc" => "子项ID", "example" => "85310", "child" => []], ["name" => "qty_stage", "type" => "int", "require" => "是", "max" => "", "desc" => "是否开启数量阶梯:1是,0否", "example" => "85310", "child" => []], ["name" => "unit", "type" => "string", "require" => "是", "max" => "", "desc" => "单位", "example" => "GB", "child" => []], ["name" => "sub", "type" => "array[]", "require" => "是", "max" => "", "desc" => "配置项", "example" => "{
                        \"id\": 85310,
                        \"config_id\": 15299,
                        \"qty_minimum\": 0,
                        \"qty_maximum\": 0,
                        \"option_name\": \"1核\",
                        \"option_name_first\": \"1\",
                        \"pricing\": \"0.00\",
                        \"qty_stage\": 0,
                        \"show_pricing\": \"1核 ￥0.00元\"
                    }", "child" => [["name" => "id", "type" => "int", "require" => "是", "max" => "", "desc" => "子项ID", "example" => "85310", "child" => []], ["name" => "config_id", "type" => "int", "require" => "是", "max" => "", "desc" => "配置项ID", "example" => "15299", "child" => []], ["name" => "option_name", "type" => "string", "require" => "是", "max" => "", "desc" => "配置项名称,|分隔符之后", "example" => "1核", "child" => []], ["name" => "option_name_first", "type" => "string", "require" => "是", "max" => "", "desc" => "配置项名称,,|分隔符之前", "example" => "1核", "child" => []], ["name" => "qty_minimum", "type" => "string", "require" => "是", "max" => "", "desc" => "子项最小值(配置项为数量类型时)", "example" => "1", "child" => []], ["name" => "qty_maximum", "type" => "string", "require" => "是", "max" => "", "desc" => "子项最大值(配置项为数量类型时)", "example" => "100", "child" => []], ["name" => "pricing", "type" => "price", "require" => "是", "max" => "", "desc" => "价格", "example" => "100.00", "child" => []], ["name" => "qty_stage", "type" => "string", "require" => "是", "max" => "", "desc" => "数量阶梯:1开启,0否", "example" => "1", "child" => []], ["name" => "show_pricing", "type" => "string", "require" => "是", "max" => "", "desc" => "价格显示", "example" => "1核 ￥0.00元", "child" => []]]]]]]], "upgradeConfig1" => ["title" => "升降级配置项", "desc" => "升降级配置项", "url" => "/v1/hosts/:id/actions/upgradeconfig", "method" => "POST", "auth" => "开发者", "version" => "v1", "param" => [["name" => "id", "type" => "int", "require" => "是", "max" => "", "desc" => "产品ID", "example" => "1", "child" => []], ["name" => "configoption", "type" => "array[]", "require" => "是", "max" => "", "desc" => "选择的配置项、子项的数组(配置项为数量时,传数量;配置项为单选框时,不勾选不传)", "example" => "{\"1\":\"2\",\"5\":\"6\"}", "child" => []]], "return" => [["name" => "currency", "type" => "array[]", "require" => "是", "max" => "-", "desc" => "货币信息", "example" => "", "child" => [["name" => "id", "type" => "int", "require" => "是", "max" => "-", "desc" => "货币ID", "example" => "1", "child" => []], ["name" => "code", "type" => "string", "require" => "是", "max" => "-", "desc" => "货币代码", "example" => "CNY", "child" => []], ["name" => "prefix", "type" => "string", "require" => "是", "max" => "-", "desc" => "货币前缀", "example" => "￥", "child" => []], ["name" => "suffix", "type" => "string", "require" => "是", "max" => "-", "desc" => "货币后缀", "example" => "元", "child" => []]]], ["name" => "name", "type" => "string", "require" => "", "max" => "", "desc" => "商品分组-商品名称(主机名)", "example" => "上下游同步问题分组1-产品升降级002(ser698889845905)", "child" => []], ["name" => "payment", "type" => "string", "require" => "", "max" => "", "desc" => "默认支付方式", "example" => "WxPay", "child" => []], ["name" => "payment", "type" => "string", "require" => "", "max" => "", "desc" => "默认支付方式", "example" => "WxPay", "child" => []], ["name" => "saleproducts", "type" => "price", "require" => "", "max" => "", "desc" => "折扣后价格", "example" => "80.00", "child" => []], ["name" => "subtotal", "type" => "price", "require" => "", "max" => "", "desc" => "小计", "example" => "100.00", "child" => []], ["name" => "total", "type" => "price", "require" => "", "max" => "", "desc" => "总计", "example" => "100.00", "child" => []], ["name" => "promo_code", "type" => "string", "require" => "", "max" => "", "desc" => "优惠码", "example" => "1YMLbk64", "child" => []], ["name" => "billingcycle", "type" => "string", "require" => "", "max" => "", "desc" => "周期", "example" => "monthly", "child" => []], ["name" => "configoptions", "type" => "json", "require" => "", "max" => "", "desc" => "选择的配置子项", "example" => "{
            \"15383\": \"86039\",
            \"15384\": \"86041\"
        }", "child" => []], ["name" => "alloption", "type" => "array[]", "require" => "", "max" => "", "desc" => "配置项信息", "example" => "", "child" => [["name" => "oid", "type" => "int", "require" => "", "max" => "", "desc" => "配置项ID", "example" => "1620", "child" => []], ["name" => "option_name", "type" => "string", "require" => "", "max" => "", "desc" => "配置项名称", "example" => "CPU", "child" => []], ["name" => "option_type", "type" => "int", "require" => "", "max" => "", "desc" => "配置项类型", "example" => "2", "child" => []], ["name" => "suboption_name", "type" => "string", "require" => "", "max" => "", "desc" => "新配置子项名称", "example" => "4核", "child" => []], ["name" => "old_suboption_name", "type" => "string", "require" => "", "max" => "", "desc" => "旧配置子项名称", "example" => "2核", "child" => []], ["name" => "old_qty", "type" => "int", "require" => "", "max" => "", "desc" => "配置项类型为数量时,旧配置项的数量(仅option_type=4/7/9/11/14/15/16/17/18/19时,返回此字段);附：配置项类型:1Dropdown(默认)下拉,2radio单选,3Yes/No是/否,4quantity数量(应用价格),
                            5OperationSystem操作系统,6CpuDropdowncpu核心单选,7CpuQuantitycpu核心范围(应用价格),
                            8MemDropdown内存单选,9MemQuantity内存范围(应用价格),10BwDropdown带宽单选,11BwQuantity带宽范围(应用价格),
                            12LocationDropdown数据中心,13SystemDiskSizeDropdown系统盘容量单选,14SystemDiskSizeQuantity系统盘容量范围(应用价格),
                            15QuantityStage数量(阶段计费),16CpuQuantityStagecpu核心范围(阶段计费),17MemQuantityStage内存范围(阶段计费),
                            18BwQuantityStage带宽范围(阶段计费),19SystemDiskSizeQuantityStage系统盘容量范围(阶段计费),20RadioLevelLinkAge单选框-层级联动(专业版可用)", "example" => "1", "child" => []], ["name" => "qty", "type" => "int", "require" => "", "max" => "", "desc" => "配置项类型为数量时,新配置项的数量(仅option_type=4/7/9/11/14/15/16/17/18/19时,返回此字段);附：配置项类型:1Dropdown(默认)下拉,2radio单选,3Yes/No是/否,4quantity数量(应用价格),
                            5OperationSystem操作系统,6CpuDropdowncpu核心单选,7CpuQuantitycpu核心范围(应用价格),
                            8MemDropdown内存单选,9MemQuantity内存范围(应用价格),10BwDropdown带宽单选,11BwQuantity带宽范围(应用价格),
                            12LocationDropdown数据中心,13SystemDiskSizeDropdown系统盘容量单选,14SystemDiskSizeQuantity系统盘容量范围(应用价格),
                            15QuantityStage数量(阶段计费),16CpuQuantityStagecpu核心范围(阶段计费),17MemQuantityStage内存范围(阶段计费),
                            18BwQuantityStage带宽范围(阶段计费),19SystemDiskSizeQuantityStage系统盘容量范围(阶段计费),20RadioLevelLinkAge单选框-层级联动(专业版可用)", "example" => "5", "child" => []]]]]], "upgradeConfigPromo" => ["title" => "配置项升降级应用优惠码", "desc" => "配置项升降级应用优惠码", "url" => "/v1/hosts/:id/actions/upgradeconfig/promo", "method" => "PUT", "auth" => "开发者", "version" => "v1", "param" => [["name" => "id", "type" => "int", "require" => "是", "max" => "", "desc" => "产品ID", "example" => "1", "child" => []], ["name" => "pormo_code", "type" => "string", "require" => "是", "max" => "", "desc" => "优惠码", "example" => "rlA6e5F0", "child" => []]], "return" => []], "upgradeConfigPromoRemove" => ["title" => "配置项升降级移除优惠码", "desc" => "配置项升降级移除优惠码", "url" => "/v1/hosts/:id/actions/upgradeconfig/promo", "method" => "DELETE", "auth" => "开发者", "version" => "v1", "param" => [["name" => "id", "type" => "int", "require" => "是", "max" => "", "desc" => "产品ID", "example" => "1", "child" => []]], "return" => []], "upgradeConfigCheckout" => ["title" => "配置项升降级结算", "desc" => "配置项升降级结算", "url" => "/v1/hosts/:id/actions/upgradeconfig/checkout", "method" => "POST", "auth" => "开发者", "version" => "v1", "param" => [["name" => "id", "type" => "int", "require" => "是", "max" => "", "desc" => "产品ID", "example" => "1", "child" => []]], "return" => []], "upgradeHostPage" => ["title" => "获取产品升降级", "desc" => "获取产品升降级", "url" => "/v1/hosts/:id/actions/upgrade", "method" => "GET", "auth" => "开发者", "version" => "v1", "param" => [["name" => "id", "type" => "int", "require" => "是", "max" => "", "desc" => "产品ID", "example" => "1", "child" => []]], "return" => [["name" => "currency", "type" => "array[]", "require" => "是", "max" => "-", "desc" => "货币信息", "example" => "", "child" => [["name" => "id", "type" => "int", "require" => "是", "max" => "-", "desc" => "货币ID", "example" => "1", "child" => []], ["name" => "code", "type" => "string", "require" => "是", "max" => "-", "desc" => "货币代码", "example" => "CNY", "child" => []], ["name" => "prefix", "type" => "string", "require" => "是", "max" => "-", "desc" => "货币前缀", "example" => "￥", "child" => []], ["name" => "suffix", "type" => "string", "require" => "是", "max" => "-", "desc" => "货币后缀", "example" => "元", "child" => []]]], ["name" => "old_host", "type" => "array[]", "require" => "是", "max" => "", "desc" => "原产品信息", "example" => "{
            \"host\": \"升降级8001\",
            \"domain\": \"ser557409352685\",
            \"description\": \"CPU:2*e5-2450L\\n内存:16G\\n硬盘:250G SSD\\nIP数量:3\\n1:1\",
            \"pid\": 379,
            \"uid\": 7,
            \"flag\": 1
        }", "child" => [["name" => "host", "type" => "string", "require" => "是", "max" => "", "desc" => "产品名称", "example" => "魔方云", "child" => []], ["name" => "domain", "type" => "string", "require" => "是", "max" => "", "desc" => "主机名", "example" => "ser557409352685", "child" => []], ["name" => "description", "type" => "string", "require" => "是", "max" => "", "desc" => "描述", "example" => "CPU:2*e5-2450L\\n内存:16G\\n硬盘:250G SSD\\nIP数量:3\\n1:1", "child" => []], ["name" => "pid", "type" => "int", "require" => "是", "max" => "", "desc" => "商品ID", "example" => "379", "child" => []]]], ["name" => "host", "type" => "array[]", "require" => "是", "max" => "", "desc" => "新产品信息", "example" => "{
                \"pid\": 1,
                \"host\": \"魔方云主机\",
                \"description\": \"a撒旦法撒旦法哈开始大复活卡a收待发送联动jafdasdf\",
                \"cycle\": [
                    {
                        \"setup_fee\": \"1.00\",
                        \"price\": 42.6,
                        \"billingcycle\": \"hour\",
                        \"billingcycle_zh\": \"小时\",
                        \"amount\": \"0.00\",
                        \"saleproducts\": 0
                    },
                    {
                        \"setup_fee\": \"10.00\",
                        \"price\": 18,
                        \"billingcycle\": \"day\",
                        \"billingcycle_zh\": \"天\",
                        \"amount\": \"0.00\",
                        \"saleproducts\": 0
                    },
                    {
                        \"setup_fee\": \"20.00\",
                        \"price\": 33.6,
                        \"billingcycle\": \"monthly\",
                        \"billingcycle_zh\": \"月付\",
                        \"amount\": \"0.00\",
                        \"saleproducts\": 0
                    },
                    {
                        \"setup_fee\": \"40.00\",
                        \"price\": 60,
                        \"billingcycle\": \"quarterly\",
                        \"billingcycle_zh\": \"季付\",
                        \"amount\": \"0.00\",
                        \"saleproducts\": 0
                    },
                    {
                        \"setup_fee\": \"30.00\",
                        \"price\": 54,
                        \"billingcycle\": \"semiannually\",
                        \"billingcycle_zh\": \"半年付\",
                        \"amount\": \"0.00\",
                        \"saleproducts\": 0
                    }
                ]
            }", "child" => [["name" => "pid", "type" => "int", "require" => "是", "max" => "", "desc" => "商品ID", "example" => "1", "child" => []], ["name" => "host", "type" => "string", "require" => "是", "max" => "", "desc" => "商品名称", "example" => "魔方云主机", "child" => []], ["name" => "description", "type" => "string", "require" => "是", "max" => "", "desc" => "商品描述", "example" => "CPU:2*e5-2450L\\n内存:16G\\n硬盘:250G SSD\\nIP数量:3\\n1:1", "child" => []], ["name" => "cycle", "type" => "array[]", "require" => "是", "max" => "", "desc" => "商品可用周期", "example" => "{
                        \"setup_fee\": \"1.00\",
                        \"price\": 42.6,
                        \"billingcycle\": \"hour\",
                        \"billingcycle_zh\": \"小时\",
                        \"amount\": \"0.00\",
                        \"saleproducts\": 0
                    }", "child" => [["name" => "setup_fee", "type" => "price", "require" => "是", "max" => "", "desc" => "商品当前周期初装费", "example" => "20.00", "child" => []], ["name" => "price", "type" => "price", "require" => "是", "max" => "", "desc" => "商品当前周期价格", "example" => "33.60", "child" => []], ["name" => "billingcycle", "type" => "string", "require" => "是", "max" => "", "desc" => "商品周期", "example" => "monthly", "child" => []], ["name" => "saleproducts", "type" => "price", "require" => "是", "max" => "", "desc" => "折扣价格", "example" => "0.00", "child" => []]]]]]]], "upgradeHost" => ["title" => "产品升降级", "desc" => "产品升降级", "url" => "/v1/hosts/:id/actions/upgrade", "method" => "POST", "auth" => "开发者", "version" => "v1", "param" => [["name" => "id", "type" => "int", "require" => "是", "max" => "", "desc" => "产品ID", "example" => "1", "child" => []], ["name" => "product_id", "type" => "int", "require" => "是", "max" => "", "desc" => "新商品ID", "example" => "1", "child" => []], ["name" => "billingcycle", "type" => "string", "require" => "是", "max" => "", "desc" => "周期", "example" => "monthly", "child" => []]], "return" => [["name" => "currency", "type" => "array[]", "require" => "", "max" => "-", "desc" => "货币信息", "example" => "", "child" => [["name" => "id", "type" => "int", "require" => "", "max" => "-", "desc" => "货币ID", "example" => "1", "child" => []], ["name" => "code", "type" => "string", "require" => "", "max" => "-", "desc" => "货币代码", "example" => "CNY", "child" => []], ["name" => "prefix", "type" => "string", "require" => "", "max" => "-", "desc" => "货币前缀", "example" => "￥", "child" => []], ["name" => "suffix", "type" => "string", "require" => "", "max" => "-", "desc" => "货币后缀", "example" => "元", "child" => []]]], ["name" => "name", "type" => "string", "require" => "", "max" => "", "desc" => "新商品名称", "example" => "魔方云主机", "child" => []], ["name" => "saleproducts", "type" => "price", "require" => "", "max" => "", "desc" => "折扣金额", "example" => "14.40", "child" => []], ["name" => "amount_total", "type" => "price", "require" => "", "max" => "", "desc" => "总价", "example" => "-700.79", "child" => []], ["name" => "promo_code", "type" => "string", "require" => "", "max" => "", "desc" => "优惠码", "example" => "rlA6e5F0", "child" => []], ["name" => "billingcycle", "type" => "string", "require" => "", "max" => "", "desc" => "周期", "example" => "monthly", "child" => []], ["name" => "old_host", "type" => "array[]", "require" => "", "max" => "", "desc" => "旧产品信息", "example" => "{
            \"id\": 647,
            \"host\": \"上下游同步问题分组1-产品升降级001\",
            \"domain\": \"ser280455935849\",
            \"flag\": 0
        }", "child" => [["name" => "id", "type" => "int", "require" => "", "max" => "", "desc" => "产品ID", "example" => "657", "child" => []], ["name" => "host", "type" => "string", "require" => "", "max" => "", "desc" => "旧产品信息", "example" => "上下游同步问题分组1-产品升降级001", "child" => []], ["name" => "domain", "type" => "string", "require" => "", "max" => "", "desc" => "旧产品主机名", "example" => "ser280455935849", "child" => []]]]]], "upgradeProductAddPromo" => ["title" => "产品升降级应用优惠码", "desc" => "产品升降级应用优惠码", "url" => "/v1/hosts/:id/actions/upgrade/promo", "method" => "PUT", "auth" => "开发者", "version" => "v1", "param" => [["name" => "id", "type" => "int", "require" => "是", "max" => "", "desc" => "产品ID", "example" => "1", "child" => []], ["name" => "promo_code", "type" => "string", "require" => "是", "max" => "", "desc" => "优惠码", "example" => "1YMLbk64", "child" => []]], "return" => []], "upgradeProductRemovePromo" => ["title" => "产品升降级删除优惠码", "desc" => "产品升降级删除优惠码", "url" => "/v1/hosts/:id/actions/upgrade/promo", "method" => "DELETE", "auth" => "开发者", "version" => "v1", "param" => [["name" => "id", "type" => "int", "require" => "是", "max" => "", "desc" => "产品ID", "example" => "1", "child" => []]], "return" => []], "upgradeProductCheckout" => ["title" => "产品升降级结算", "desc" => "产品升降级结算", "url" => "/v1/hosts/:id/actions/upgrade/checkout", "method" => "POST", "auth" => "开发者", "version" => "v1", "param" => [["name" => "id", "type" => "int", "require" => "是", "max" => "", "desc" => "产品ID", "example" => "1", "child" => []]], "return" => [["name" => "invoiceid", "type" => "int", "require" => "", "max" => "", "desc" => "账单ID", "example" => "551332", "child" => []], ["name" => "orderid", "type" => "int", "require" => "", "max" => "", "desc" => "订单ID", "example" => "123", "child" => []]]]]];