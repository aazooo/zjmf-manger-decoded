<?php
$domain = configuration('domain');
return array (
		'mode' => 'live', # live生产环境,sandbox沙箱环境
        //ID
		'clientId' => '',
		//密码
		'clientSecret' => '',
        //异步通知地址
		'notify_url' => "{$domain}/gateway/paypal/index/notify_handle",
		//同步跳转
		'return_url' => "{$domain}/gateway/paypal/index/return_handle",
        //取消支付地址
		'cancel_url' => "{$domain}/gateway/paypal/index/cancel_handle",
);