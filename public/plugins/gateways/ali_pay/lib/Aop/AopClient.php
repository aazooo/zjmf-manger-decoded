<?php

namespace gateways\ali_pay\lib\Aop;

class AopClient
{
    //应用ID
    public $appId;

    //网关
    public $gatewayUrl = 'https://openapi.alipay.com/gateway.do';

    //API版本
    public $apiVersion = '1.0';

    //编码
    public $charset = 'UTF-8';

    //返回数据格式
    public $format = 'json';

    //应用私钥
    public $rsaPrivateKey;

    //应用私钥文件路径
    public $rsaPrivateKeyFilePath;

    //支付宝公钥
    public $rsaPublicKey;

    //支付宝公钥文件路径
    public $rsaPublicKeyFilePath;

    //AES加密密钥
    public $encryptKey;

    //签名方式
    public $signType = 'RSA2'; 

    //应用公钥证书编号
    public $appCertSN;

    //支付宝根证书编号
    public $alipayRootCertSN;

    //SDK版本
    protected $sdkVersion = 'alipay-sdk-PHP-4.11.14.ALL';

    /**
     * 创建客户端.
     *
     */
    public function __construct() {
    }

    /**
     * AES解密数据.
     *
     * @param string $content 已加密的数据，如手机号
     * @param string $aesKey  AES密钥
     *
     * @return string
     *
     * @see https://docs.alipay.com/mini/introduce/aes
     * @see https://docs.alipay.com/mini/introduce/getphonenumber
     */
    public static function aesDecrypt($content, $aesKey)
    {
        return openssl_decrypt($content, 'aes-128-cbc', base64_decode($aesKey));
    }

    /**
     * AES加密数据.
     *
     * @param string $content 要加密的数据
     * @param string $aesKey  AES密钥
     *
     * @return string
     */
    public static function aesEncrypt($content, $aesKey)
    {
        $result = openssl_encrypt($content, 'aes-128-cbc', base64_decode($aesKey));
        return base64_encode($result);
    }

    /**
     * 发起请求并解析结果
     *
     * @param  AlipayRequest  $request
     * 
     * @return AlipayResponse
     */
    public function execute(AlipayRequest $request)
    {
        $params = $this->build($request);

        $url = $this->gatewayUrl.'?charset='.$this->charset;
        $raw = $this->curl($url, $params);

        $response = new AlipayResponse($raw, $request->getApiMethodName());

        $this->verifyResponse($response);

        return $response;
    }

    /**
     * 生成用于调用收银台SDK的字符串
     *
     * @param  AlipayRequest  $request
     * @return string
     */
    public function sdkExecute(AlipayRequest $request)
    {
        $params = $this->build($request);

        return http_build_query($params);
    }

    /**
     * 页面提交执行方法
     *
     * @param  AlipayRequest  $request
     * @param  $httpmethod
     * @return string
     */
    public function pageExecute(AlipayRequest $request, $httpmethod = 'POST')
    {
        $params = $this->build($request);

        if (strtoupper($httpmethod) == 'REDIRECT') {
            $requestUrl = $this->gatewayUrl.'?'.http_build_query($params);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $requestUrl);
            curl_setopt($ch, CURLOPT_FAILONERROR, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            if (curl_errno($ch) > 0) {
                $errmsg = curl_error($ch);
                curl_close($ch);
                throw new \Exception($errmsg, 0);
            }
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpStatusCode == 301 || $httpStatusCode == 302) {
                $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
                return $redirect_url;
            } elseif ($httpStatusCode == 200) {
                $response = mb_convert_encoding($response, 'UTF-8', 'GB2312');
                if(preg_match('/<div\s+class="Todo">([^<]+)<\/div>/i', $response, $matchers)) {
                    throw new \Exception($matchers[1]);
                }
            }
            throw new \Exception('返回数据解析失败', $httpStatusCode);

        } elseif (strtoupper($httpmethod) == 'GET') {
            $requestUrl = $this->gatewayUrl.'?'.http_build_query($params);
            return $requestUrl;
        } else {
            $url = $this->gatewayUrl.'?charset='.$this->charset;

            $html = "<form id='alipaysubmit' name='alipaysubmit' action='{$url}' method='POST'>";
            foreach ($params as $key => $value) {
                if ($this->isEmpty($value)) {
                    continue;
                }
                $value = htmlentities($value, ENT_QUOTES | ENT_HTML5);
                $html .= "<input type='hidden' name='{$key}' value='{$value}'/>";
            }
            $html .= "<input type='submit' value='ok' style='display:none;'></form>";
            $html .= "<script>document.forms['alipaysubmit'].submit();</script>";
    
            return $html;
        }
    }
    
    /**
     * 拼接请求参数并签名.
     *
     * @param  AlipayRequest  $request
     * 
     * @return array
     */
    protected function build(AlipayRequest $request)
    {
        // 组装系统参数
        $sysParams = [];
        $sysParams['app_id'] = $this->appId;
        $sysParams['version'] = $this->apiVersion;
        $sysParams['alipay_sdk'] = $this->sdkVersion;

        $sysParams['charset'] = $this->charset;
        $sysParams['format'] = $this->format;
        $sysParams['sign_type'] = $this->signType;

        $sysParams['method'] = $request->getApiMethodName();
        $sysParams['timestamp'] = $request->getTimestamp();
        $sysParams['notify_url'] = $request->getNotifyUrl();
        $sysParams['return_url'] = $request->getReturnUrl();

        $sysParams['terminal_type'] = $request->getTerminalType();
        $sysParams['terminal_info'] = $request->getTerminalInfo();
        $sysParams['prod_code'] = $request->getProdCode();

        $sysParams['auth_token'] = $request->getAuthToken();
        $sysParams['app_auth_token'] = $request->getAppAuthToken();
        if (!$this->isEmpty($this->appCertSN) && !$this->isEmpty($this->alipayRootCertSN)) {
            $sysParams["app_cert_sn"] = $this->appCertSN;
            $sysParams["alipay_root_cert_sn"] = $this->alipayRootCertSN;
        }
        $sysParams['biz_content'] = $request->getBizContent();
        $sysParams = array_merge($sysParams, get_object_vars($request));
        // 转换可能是数组的参数
        foreach ($sysParams as $key => &$param) {
            if (is_array($param) || is_object($param) && !$param instanceof \CURLFile) {
                $param = json_encode($param, JSON_UNESCAPED_UNICODE);
            }
            if (is_null($param)) {
                unset($sysParams[$key]);
            }
        }

        // 签名
        $sysParams['sign'] = $this->generateSign($sysParams, $this->signType);

        return $sysParams;
    }

    /**
     * 验证返回内容签名
     *
     * @param $response
     */
    protected function verifyResponse(AlipayResponse $response)
    {
        $signData = $response->getSignData();
        $sign = $response->getSign();
        if ($this->isEmpty($signData) || $this->isEmpty($sign)) {
            throw new AlipayResponseException($response->getData());
        }
        $checkResult = $this->rsaPubilcVerify($signData, $sign, $this->signType);
        if (!$checkResult) {
            if (strpos($signData, '\/') > 0) {
                $signData = str_replace('\/', '/', $signData);
                $checkResult = $this->rsaPubilcVerify($signData, $sign, $this->signType);
            }
            if (!$checkResult) {
                throw new \Exception('对返回数据使用支付宝公钥验签失败');
            }
        }
    }

    /**
     * 异步通知回调验签
     *
     * @param $params
     * 
     * @return bool
     */
    public function verify($params)
    {
        if (!$params || !isset($params['sign'])) {
            return false;
        }
        $sign = $params['sign'];
        unset($params['sign']);
        unset($params['sign_type']);
        $data = $this->getSignContent($params);
        try {
            return $this->rsaPubilcVerify($data, $sign, $this->signType);
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * 异步通知回调验签V2
     *
     * @param $params
     * 
     * @return bool
     */
    public function verifyV2($params)
    {
        if (!$params || !isset($params['sign'])) {
            return false;
        }
        $sign = $params['sign'];
        unset($params['sign']);
        $data = $this->getSignContent($params);
        try {
            return $this->rsaPubilcVerify($data, $sign, $this->signType);
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * 将参数数组签名（计算 Sign 值）.
     *
     * @param $params
     * @param $signType
     *
     * @return string
     */
    protected function generateSign($params, $signType = 'RSA2')
    {
        $data = $this->getSignContent($params);

        return $this->rsaPrivateSign($data, $signType);
    }

    /**
     * 将数组转换为待签名数据.
     *
     * @param array $params
     *
     * @return string
     */
    protected function getSignContent($params)
    {
        ksort($params);
        unset($params['sign']);

        $stringToBeSigned = "";
        foreach ($params as $k => $v) {
            if($v instanceof \CURLFile || $this->isEmpty($v) || substr($v, 0, 1) == '@') continue;
            $stringToBeSigned .= "&{$k}={$v}";
        }
        $stringToBeSigned = substr($stringToBeSigned, 1);

        return $stringToBeSigned;
    }

    /**
     * 使用应用私钥签名
     *
     * @param $data
     * @param $signType
     *
     * @return string
     *
     * @see https://docs.open.alipay.com/291/106118
     */
    protected function rsaPrivateSign($data, $signType = 'RSA2')
    {
        if ($this->isEmpty($this->rsaPrivateKeyFilePath)) {
            $priKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($this->rsaPrivateKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        } else {
            $priKey = file_get_contents($this->rsaPrivateKeyFilePath);
        }
        $res = openssl_get_privatekey($priKey);
        if(!$res){
            throw new \Exception('签名失败，应用私钥不正确');
        }

        if($signType == 'RSA2'){
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        }else{
            openssl_sign($data, $sign, $res);
        }
        if(is_resource($res)){
            openssl_free_key($res);
        }
        return base64_encode($sign);
    }

    /**
     * 使用支付宝公钥验签
     *
     * @param $data
     * 
     * @return bool
     */
    protected function rsaPubilcVerify($data, $sign, $signType = 'RSA2')
    {
        if ($this->isEmpty($this->rsaPublicKeyFilePath)) {
            $pubKey = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($this->rsaPublicKey, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        } else {
            $pubKey = file_get_contents($this->rsaPublicKeyFilePath);
        }
        $res = openssl_get_publickey($pubKey);
        if(!$res){
            throw new \Exception('验签失败，支付宝公钥不正确');
        }

        if($signType == 'RSA2'){
            $result = openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        }else{
            $result = openssl_verify($data, base64_decode($sign), $res);
        }
        if(is_resource($res)){
            openssl_free_key($res);
        }
        return $result === 1;
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
        return $value === null || trim($value) === '';
    }

    /**
     * 发起 GET/POST 请求.
     *
     * @param $url
     * @param $params
     *
     * @return bool|string
     */
    protected function curl($url, $postFields = null)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (is_array($postFields) && 0 < count($postFields)) {
            $postMultipart = false;
            foreach ($postFields as &$value) {
                if ($value instanceof \CURLFile) {
                    $postMultipart = true;
                } elseif(substr($value, 0, 1) == '@' && class_exists('CURLFile')) {
                    $postMultipart = true;
                    $file = substr($value, 1);
                    if(file_exists($file)){
                        $value = new \CURLFile($file);
                    }
                }
            }
            curl_setopt($ch, CURLOPT_POST, true);
            if($postMultipart){
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            }else{
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
            }
        }

        $response = curl_exec($ch);

        if (curl_errno($ch) > 0) {
            $errmsg = curl_error($ch);
            curl_close($ch);
            throw new \Exception($errmsg, 0);
        }

        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpStatusCode != 200) {
            curl_close($ch);
            throw new \Exception($response, $httpStatusCode);
        }

        curl_close($ch);

        return $response;
    }


}
