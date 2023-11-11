<?php
namespace oauth\weibo;

class weibo
{
    public function __construct()
    {
        if(!session_id()) session_start();
    }
    public function meta()
    {
        return [
            'name' => '微博登录',
            'description' => '微博登录',
            'author' => '智简魔方',
            'logo_url' => 'weibo.svg'
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
        $_SESSION['oauth_weibo_state'] = $state;
        $keysArr = ['client_id' => $params['client_id'], 'redirect_uri' => $params['callback'], 'state' => $state, 'scope' => 'all'];
        $login_url = $this->combineURL('https://api.weibo.com/oauth2/authorize', $keysArr);
        return $login_url;
    }
    public function callback($params)
    {
        if ($_SESSION['oauth_weibo_state'] != $params['state'] || empty($params['code'])) {
            return 'error';
        }
        $keysArr = ['code' => $params['code'], 'client_id' => $params['client_id'], 'client_secret' => $params['client_secret'], 'grant_type' => 'authorization_code', 'redirect_uri' => $params['callback']];
        $access_token = $this->post('https://api.weibo.com/oauth2/access_token', $keysArr);
        $access_token = json_decode($access_token, true);
        if (isset($access_token['error'])) {
            return $access_token['error_description'];
        }
        $keysArr = ['access_token' => $access_token['access_token'], 'uid' => $access_token['uid']];
        $userinfo = $this->get('https://api.weibo.com/2/users/show.json', $keysArr);
        $userinfo = json_decode($userinfo, true);
        if (isset($userinfo['error'])) {
            return $userinfo['error_description'];
        }
        $location = explode(' ', $userinfo['location']);
        $callback = ['openid' => $access_token['access_token'], 'data' => ['username' => $userinfo['screen_name'], 'sex' => $userinfo['gender_type'], 'province' => $location[0], 'city' => $location[1], 'avatar' => $userinfo['profile_image_url']]];
        unset($_SESSION['oauth_weibo_state']);
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
    public function post($url, $keysArr, $flag = 0)
    {
        if ($keysArr) {
            $keysArr = http_build_query($keysArr);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $keysArr);
        curl_setopt($ch, CURLOPT_URL, $url);
        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }
}

?>