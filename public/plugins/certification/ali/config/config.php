<?php
/*
 * 手机三要素实名认证配置
 */
return [
    # API版本
    'api_version'           => "1.0",
    # 提交字符
    'post_charset'          => "UTF-8",
    # 返回类型
    'format'                => "json",
    # 字符编码
    'charset'               => "UTF-8",
    # 支付宝网关
    'gateway_url'           => "https://openapi.alipay.com/gateway.do",
    # 认证完成后用户的跳转链接
    'return_url'            => "/login.php",
    # 应用ID,您的APPID
    // 'app_id'                => "",
    # 商户私玥(一行)
    // 'merchant_private_key'  => "",
    # 签名方式(RSA/RSA2)
    'sign_type'             => "RSA2",
    # 认证场景码。入参支持的认证场景码和商户签约的认证场景相关，取值如下:
    #   FACE：多因子人脸认证
    #   CERT_PHOTO：多因子证照认证
    #   CERT_PHOTO_FACE ：多因子证照和人脸认证
    #   SMART_FACE：多因子快捷认证
    'biz_code'              => "SMART_FACE",
    # 支付宝公玥(一行)
    // 'alipay_public_key'     => "",
    # 调试模式
    'debug'                 => false,
];