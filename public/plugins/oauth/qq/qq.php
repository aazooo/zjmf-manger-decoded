<?php
namespace oauth\qq;

class qq
{
    public function __construct()
    {
        if(!session_id()) session_start();
    }
    public function meta()
    {
        return [
            'name' => 'QQ登录',
            'description' => 'QQ登录',
            'author' => '智简魔方',
            'logo_url' => 'qq.svg'
        ];
    }
    public function config()
    {
        return [
            'App Key' => [
                'type' => 'text',
                'name' => 'client_id',
                'desc' => '申请QQ登录成功后，分配给应用的appid。'
            ],
            'App Secret' => [
                'type' => 'text',
                'name' => 'client_secret',
                'desc' => '申请QQ登录成功后，分配给网站的appkey。'
            ]
        ];
    }
    public function url($params)
    {
        $state = md5(uniqid(rand(), true));
        $_SESSION['oauth_qq_state'] = $state;
        $keysArr = ['response_type' => 'code', 'client_id' => $params['client_id'], 'redirect_uri' => $params['callback'], 'state' => $state, 'scope' => 'snsapi_login'];
        $login_url = $this->combineURL('https://graph.qq.com/oauth2.0/authorize', $keysArr);
        return $login_url;
    }
    public function callback($params)
    {
        if ($_SESSION['oauth_qq_state'] != $params['state'] || empty($params['code'])) {
            return 'error';
        }
        $keysArr = ['code' => $params['code'], 'client_id' => $params['client_id'], 'client_secret' => $params['client_secret'], 'grant_type' => 'authorization_code', 'redirect_uri' => $params['callback']];
        $access_token = $this->get('https://graph.qq.com/oauth2.0/token', $keysArr);
        if (strpos($access_token, 'callback(') !== false) {
            $access_token = $this->strCallbackToArray($access_token);
        }
        if (isset($access_token['error'])) {
            return $access_token['error_description'];
        }
        parse_str($access_token, $access_token);
        $keysArr = ['access_token' => $access_token['access_token']];
        $openid = $this->get('https://graph.qq.com/oauth2.0/me', $keysArr);
        if (strpos($access_token, 'callback(') !== false) {
            $openid = $this->strCallbackToArray($openid);
        }
        if (empty($openid['openid'])) {
            return $openid;
        }
        $keysArr = ['access_token' => $access_token['access_token'], 'openid' => $openid['openid'], 'oauth_consumer_key' => $params['client_id']];
        $userinfo = $this->get('https://graph.qq.com/user/get_user_info', $keysArr);
        $userinfo = json_decode($userinfo, true);
        if ($userinfo['ret'] != 0) {
            return $userinfo['msg'];
        }
        $callback = ['openid' => $openid['openid'], 'data' => ['username' => $userinfo['nickname'], 'sex' => $userinfo['gender_type'], 'province' => $userinfo['province'], 'city' => $userinfo['city'], 'avatar' => $userinfo['figureurl']]];
        unset($_SESSION['oauth_qq_state']);
        return $callback;
    }
    public function combineURL($baseURL, $keysArr)
    {
        $combined = $baseURL . '?';
        $valueArr = [];
        foreach ($keysArr as $key => $val) {
            $valueArr[] = $key . '=' . $val;
        }
        $keyStr = implode('&', $valueArr);
        $combined .= $keyStr;
        return $combined;
    }
    public function get_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    public function get($url, $keysArr)
    {
        $combined = $this->combineURL($url, $keysArr);
        return $this->get_contents($combined);
    }
    public function strCallbackToArray($string)
    {
        $string = str_replace('callback(', '', $string);
        $string = trim(str_replace(');', '', $string));
        $string = json_decode($string, true);
        return $string;
    }
}

?>