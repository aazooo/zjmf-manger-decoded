<?php

namespace gateways\wx_pay\lib;

/**
 * 微信支付响应内容异常
 */
class WeChatPayException extends \Exception
{
    private $res = [];

    /**
     * @param array $res
     */
    public function __construct($res)
    {
        $this->res = $res;
        if (isset($res['err_code'])) {
            $message = '['.$res['err_code'].']'.$res['err_code_des'];
        } elseif (isset($res['return_code'])) {
            $message = '['.$res['return_code'].']'.$res['return_msg'];
        } else {
            $message = '返回数据解析失败';
        }
        parent::__construct($message);
    }

    public function getResponse()
    {
        return $this->res;
    }
}