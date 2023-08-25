<?php


namespace gateways\ali_pay\validate;

use think\Validate;

class AliPayValidate extends Validate{

    protected $rule = [
//        'product_name' => 'chsDash|length:2,50',
        'out_trade_no|{%INVOICE_ID}' => 'alphaDash|length:2,20',
        'total_fee' => 'float|length:1,11',
    ];
    protected $message = [
            'total_fee.integer' =>  '{%OVERFLOW_PAYMENT}',
//            'product_name' =>  '{%PRODUCT_NAME}',
    ];



}
