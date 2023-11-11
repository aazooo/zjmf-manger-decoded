<?php

namespace gateways\ali_pay\lib\Aop;

class AlipayResponse
{
    /**
     * 响应签名节点名
     */
    const SIGN_NODE = 'sign';

    /**
     * 响应数据节点后缀
     */
    const RESPONSE_SUFFIX = '_response';

    /**
     * 响应错误节点名
     */
    const ERROR_NODE = 'error_response';

    /**
     * 支付宝公钥证书节点名
     */
    const ALIPAY_CERT_SN = 'alipay_cert_sn';

    /**
     * 原始响应
     *
     * @var string
     */
    protected $raw;

    /**
     * 已解析的响应
     *
     * @var mixed
     */
    protected $parsed;

    /**
     * 数据节点名称
     */
    protected $nodeName;

    /**
     * 待验签数据
     */
    protected $signData;


    /**
     * @param $raw     原始数据
     * @param $apiName 接口名称
     */
    public function __construct($raw, $apiName)
    {
        $this->raw = $raw;
        $this->parsed = json_decode($raw, true);
        if (!$this->parsed) {
            $error = function_exists('json_last_error_msg') ? json_last_error_msg() : json_last_error();
            throw new \Exception('返回数据解析失败:'.$error);
        }
        $this->parseResponseData($apiName);
    }

    /**
     * 获取原始响应的被签名数据，用于验证签名.
     *
     * @param $apiName
     */
    protected function parseResponseData($apiName)
    {
        $nodeName = str_replace(".", "_", $apiName) . self::RESPONSE_SUFFIX;
        $nodeIndex = strpos($this->raw, $nodeName);
        if (!$nodeIndex) {
            $nodeName = self::ERROR_NODE;
            $nodeIndex = strpos($this->raw, $nodeName);
            if(!$nodeIndex){
                throw new \Exception('Response data not found');
            }
        }
        $this->nodeName = $nodeName;

        $signDataStartIndex = $nodeIndex + strlen($nodeName) + 2;
        $signIndex = strrpos($this->raw, '"'.static::ALIPAY_CERT_SN.'"');
        if(!$signIndex) {
            $signIndex = strrpos($this->raw, '"'.static::SIGN_NODE.'"');
        }

        $signDataEndIndex = $signIndex - 1;
        $indexLen = $signDataEndIndex - $signDataStartIndex;
        if ($indexLen < 0) {
            return;
        }

        $this->signData = substr($this->raw, $signDataStartIndex, $indexLen);
    }
    
    /**
     * 获取待验签数据
     *
     * @return string
     */
    public function getSignData()
    {
        return $this->signData;
    }

    /**
     * 获取响应内的签名.
     *
     * @return string
     */
    public function getSign()
    {
        if (isset($this->parsed[static::SIGN_NODE])) {
            return $this->parsed[static::SIGN_NODE];
        }
        return null;
    }

    /**
     * 获取响应内的数据.
     *
     * @param bool $assoc
     *
     * @return mixed|object
     */
    public function getData($assoc = true)
    {
        if (!isset($this->parsed[$this->nodeName])){
            return null;
        }
        $result = $this->parsed[$this->nodeName];
        if ($assoc == false) {
            $result = (object) ($result);
        }
        return $result;
    }

    /**
     * 判断响应是否成功.
     *
     * @return bool
     */
    public function isSuccess()
    {
        if (isset($this->parsed[static::ERROR_NODE])) {
            return false;
        }
        if (!isset($this->parsed[$this->nodeName])){
            return false;
        }
        $data = $this->parsed[$this->nodeName];
        return isset($data['code']) && $data['code'] == '10000';
    }

    /**
     * 获取原始响应.
     *
     * @return string
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * 获取支付宝公钥证书序列号
     *
     * @return bool|string
     */
    public function getAlipayCertSN()
    {
        if (isset($this->parsed[static::ALIPAY_CERT_SN])) {
            return $this->parsed[static::ALIPAY_CERT_SN];
        }
        return false;
    }
}
