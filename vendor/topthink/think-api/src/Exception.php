<?php

namespace think\api;

class Exception extends \Exception
{
    private $errorCode;

    public function __construct($code = "", $message = "")
    {
        parent::__construct($message, 0);
        $this->errorCode = $code;
    }

    /**
     * 返回错误码
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
