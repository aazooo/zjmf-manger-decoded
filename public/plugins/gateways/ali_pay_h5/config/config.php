<?php
$domain = configuration('domain');
return array (
		//应用ID,您的APPID。
		'app_id' => '',
		//商户私钥
		'merchant_private_key' => '',
        //异步通知地址
		'notify_url' => "{$domain}/gateway/ali_pay_h5/index/notify_handle",
		//同步跳转
		'return_url' => "{$domain}/gateway/ali_pay/index/return_handle",
		//编码格式
		'charset' => "UTF-8",
		//签名方式
		'sign_type'=>"RSA2",
		//支付宝网关
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
);