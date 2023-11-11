<?php

namespace gateways\ali_pay\lib\Aop;

class AlipayResponseException extends \Exception
{
    private $res = [];

    /**
     * @param array $res
     */
    public function __construct($res)
    {
        $this->res = $res;
        if (isset($res['sub_msg'])) {
            $message = '['.$res['sub_code'].']'.$res['sub_msg'];
        } elseif (isset($res['msg'])) {
            $message = '['.$res['code'].']'.$res['msg'];
        } else {
            $message = '未知错误';
        }
        parent::__construct($message);
    }

    public function getResponse()
    {
        return $this->res;
    }
}