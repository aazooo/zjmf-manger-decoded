<?php

namespace gateways\wx_pay\lib;

/**
 * JSAPI支付工具类
 * 实现了从微信公众平台获取code、通过code获取openid和access_token
 */
class JsApiTool
{
    const GET_AUTH_CODE_URL = "https://open.weixin.qq.com/connect/oauth2/authorize";
    const GET_ACCESS_TOKEN_URL = "https://api.weixin.qq.com/sns/oauth2/access_token";
    const GET_MINIAPP_TOKEN_URL = "https://api.weixin.qq.com/sns/jscode2session";

    private $appid;
    private $appsecret;

    /**
     * 网页授权接口微信服务器返回的数据，返回样例如下
     * {
     *  "access_token":"ACCESS_TOKEN",
     *  "expires_in":7200,
     *  "refresh_token":"REFRESH_TOKEN",
     *  "openid":"OPENID",
     *  "scope":"SCOPE",
     *  "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
     * }
     * openid是微信支付jsapi支付接口必须的参数
     * @var array
     */
    public $data = null;

    public function __construct($appid, $appsecret)
    {
        $this->appid = $appid;
        $this->appsecret = $appsecret;
    }

    /**
     * 通过跳转获取用户的openid，跳转流程如下：
     * 1、设置自己需要调回的url及其其他参数，跳转到微信服务器
     * 2、微信服务处理完成之后会跳转回用户redirect_uri地址，此时会带上一些参数，如：code
     * 
     * @return 用户的openid
     */
    public function GetOpenid()
    {
        if (!isset($_GET['code'])) {
            $this->login();
        } else {
            $code = $_GET['code'];
            $openid = $this->GetOpenidFromMp($code);
            return $openid;
        }
    }

    /**
     * 跳转到微信公众平台登录
     */
    public function login()
    {
        if (function_exists('is_https')) {
            $redirect_uri = (is_https() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            $redirect_uri = ($_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
        $param = [
            "appid" => $this->appid,
            "redirect_uri" => $redirect_uri,
            "response_type" => "code",
            "scope" => "snsapi_base",
            "state" => "STATE"
        ];
        $url = self::GET_AUTH_CODE_URL . '?' . http_build_query($param) . "#wechat_redirect";
        Header("Location: $url");
        exit;
    }

    /**
     * 从公众平台获取openid
     * @param string $code 微信跳转回来带上的code
     * 
     * @return string openid
     */
    public function GetOpenidFromMp($code)
    {
        $param = [
            "appid" => $this->appid,
            "secret" => $this->appsecret,
            "code" => $code,
            "grant_type" => "authorization_code"
        ];
        $url = self::GET_ACCESS_TOKEN_URL . '?' . http_build_query($param);
        $res = $this->curl($url);
        $data = json_decode($res, true);
        if (isset($data['access_token']) && isset($data['openid'])) {
            $this->data = $data;
            return $data['openid'];
        } elseif (isset($data['errcode'])) {
            throw new \Exception('Openid获取失败 [' . $data['errcode'] . ']' . $data['errmsg']);
        } else {
            throw new \Exception('Openid获取失败，原因未知');
        }
    }

    /**
     * 微信小程序获取Openid
     * @param string $code 登录时获取的code
     * 
     * @return string openid
     */
    public function AppGetOpenid($code)
    {
        $param = [
            "appid" => $this->appid,
            "secret" => $this->appsecret,
            "js_code" => $code,
            "grant_type" => "authorization_code"
        ];
        $url = self::GET_MINIAPP_TOKEN_URL . '?' . http_build_query($param);
        $res = $this->curl($url);
        $data = json_decode($res, true);
        if (isset($data['session_key']) && isset($data['openid'])) {
            $this->data = $data;
            return $data['openid'];
        } elseif (isset($data['errcode'])) {
            throw new \Exception('获取openid失败 [' . $data['errcode'] . ']' . $data['errmsg']);
        } else {
            throw new \Exception('获取openid失败，原因未知');
        }
    }

    /**
     * 发起GET请求
     * @param $url 请求url
     * @return string
     */
    private function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.0.4; es-mx; HTC_One_X Build/IMM76D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0");
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}
