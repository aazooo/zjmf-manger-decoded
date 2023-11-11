<?php

namespace gateways\wx_pay\lib;

class BaseService
{
    //SDK版本号
    static $VERSION = "3.0.10";

    //应用APPID
    protected $appId;

    //商户号
    protected $mchId;

    //商户API密钥
    protected $apiKey;

    //子商户号
    protected $subMchId;

    //子商户公众账号ID
    protected $subAppId;

    //商户证书路径
    protected $sslCertPath;

    //商户证书私钥路径
    protected $sslKeyPath;

    //公共请求参数
    protected $publicParams = [];

    /**
     * @param $config 微信支付配置信息
     */
    public function __construct($config)
    {
        if (empty($config['appid'])) {
            throw new \InvalidArgumentException('应用APPID不能为空');
        }
        if (empty($config['mchid'])) {
            throw new \InvalidArgumentException("商户号不能为空");
        }
        if (empty($config['apikey'])) {
            throw new \InvalidArgumentException("商户API密钥不能为空");
        }
        $this->appId = $config['appid'];
        $this->mchId = $config['mchid'];
        $this->apiKey = $config['apikey'];
        $this->sslCertPath = $config['sslcert_path'];
        $this->sslKeyPath = $config['sslkey_path'];
        if (isset($config['sub_mchid'])) {
            $this->subMchId = $config['sub_mchid'];
        }
        if (isset($config['sub_appid'])) {
            $this->subAppId = $config['sub_appid'];
        }
    }


    /**
     * 请求接口并解析返回数据
     * @param $url url
     * @param $params 请求参数
     * @param $cert 是否需要证书
     * @return mixed
     */
    public function execute($url, $params, $cert = false)
    {
        $params = array_merge($this->publicParams, $params);
        $params['sign'] = $this->makeSign($params);
        $xml = $this->array2Xml($params);
        $response = $this->curl($url, $xml, $cert);
        $result = $this->xml2array($response);
        if (isset($result['return_code']) && $result['return_code'] == 'SUCCESS') {
            if (isset($result['result_code']) && $result['result_code'] == 'SUCCESS') {
                if (isset($result['sign']) && !$this->checkSign($result)) {
                    throw new \Exception('返回数据验签失败');
                }
                return $result;
            }
        }
        throw new WeChatPayException($result);
    }

    /**
     * 验签
     * @param $data
     * @return bool
     */
    protected function checkSign($data)
    {
        if (!isset($data['sign'])) return false;

        $sign = $this->makeSign($data);

        return $sign === $data['sign'];
    }

    /**
     * 生成签名
     * @param $data
     * @return string
     */
    protected function makeSign($data)
    {
        ksort($data);
        $signStr = '';
        foreach ($data as $k => $v) {
            if($k != 'sign' && !is_array($v) && !$this->isEmpty($v)){
                $signStr .= $k . '=' . $v . '&';
            }
        }
        $signStr = trim($signStr, '&') . '&key=' . $this->apiKey;
        if (isset($data['sign_type']) && $data['sign_type'] == 'HMAC-SHA256') {
            $sign = hash_hmac("sha256", $signStr, $this->apiKey);
        } else {
            $sign = md5($signStr);
        }
        return strtoupper($sign);
    }

    /**
     * 校验某字符串或可被转换为字符串的数据，是否为 NULL 或均为空白字符.
     *
     * @param string|null $value
     *
     * @return bool
     */
    protected function isEmpty($value)
    {
        return $value === null || $value === '';
    }

    /**
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    protected function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 转为XML数据
     * @param array $data 源数据
     * @return string
     */
    protected function array2Xml($data)
    {
        if (!is_array($data)) {
            return false;
        }
        $xml = '<xml>';
        foreach ($data as $key => $val) {
            $xml .= (is_numeric($val) ? "<{$key}>{$val}</{$key}>" : "<{$key}><![CDATA[{$val}]]></{$key}>");
        }
        return $xml . '</xml>';
    }

    /**
     * 解析XML数据
     * @param string $xml 源数据
     * @return mixed
     */
    protected function xml2array($xml)
    {
        if (!$xml) {
            return false;
        }
		LIBXML_VERSION < 20900 && libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 以post方式提交xml到对应的接口url
     * @param string $url  url
     * @param string $xml  需要post的xml数据
     * @param bool $useCert 是否需要证书
     * @param int $second   url执行超时时间
     * @return string
     */
    protected function curl($url, $xml, $useCert = false, $second = 10)
    {
        $ch = curl_init();
        $curlVersion = curl_version();
        $ua = "WXPaySDK/" . self::$VERSION . " (" . PHP_OS . ") PHP/" . PHP_VERSION . " CURL/" . $curlVersion['version'] . " ". $this->mchId;

        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($useCert) {
            if (!file_exists($this->sslCertPath) || !file_exists($this->sslKeyPath)) {
                throw new \Exception('商户证书文件不存在');
            }
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $this->sslCertPath);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $this->sslKeyPath);
        }
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            $errmsg = curl_error($ch);
            curl_close($ch);
            throw new \Exception($errmsg, 0);
        }
        curl_close($ch);
        return $data;
    }
}
