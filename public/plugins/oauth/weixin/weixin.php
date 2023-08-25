<?php
namespace oauth\weixin;


class weixin
{

	function __construct(){
		if(!session_id()) session_start();
    }
	//插件信息
	public function meta(){
		return [
			'name'        => '微信登录',
			'description' => '微信登录',
			'author'      => '智简魔方',
			'logo_url'=> 'weixin.svg',//接口图片地址
			'version'=> '1.0.0',//版本号
		];
	}
	//插件接口配置信息
	public function config(){
		return [
			'App Key'=> [
				'type' => 'text',
				'name' => 'appid',
				'desc' => '应用唯一标识，在微信开放平台提交应用审核通过后获得'
			],
			'App Secret'=> [
				'type' => 'text',
				'name' => 'appSecret',
				'desc' => '应用密钥AppSecret，在微信开放平台提交应用审核通过后获得'
			],

		];
	}
	//生成请求地址
	public function url($params){
			
        //-------生成唯一随机串防CSRF攻击
        $state = md5(uniqid(rand(), TRUE));
		$_SESSION['oauth_weixin_state']=$state;

        //-------构造请求参数列表
        $keysArr = array(
            "response_type" => "code",
            "appid" => $params['appid'],
            "redirect_uri" => $params['callback'],
            "state" => $state,
            "scope" => "snsapi_login"
        );

        $login_url =  $this->combineURL("https://open.weixin.qq.com/connect/qrconnect",$keysArr);        
		return $login_url;

	}
	//回调地址
	public function callback($params){
		//判断state
		if($_SESSION['oauth_weixin_state']!=$params['state'] || empty($params['code'])){
			return 'error';
		}
		//获取 access_token 
        $keysArr = array(
            "code" => $params['code'],
            "appid" => $params['appid'],
            "secret" => $params['appSecret'],
            "grant_type" => 'authorization_code'
        );
		$access_token =  $this->get("https://api.weixin.qq.com/sns/oauth2/access_token",$keysArr); 
		//echo "<pre>";var_dump($access_token);exit;
		$access_token=json_decode($access_token,true);
		if(empty($access_token['access_token'])) return $access_token['errmsg'];
		
		//获取用户信息 
		$keysArr = array(
            "access_token" => $access_token['access_token'],
            "openid" => $access_token['openid'],
        );
		$userinfo =  $this->get("https://api.weixin.qq.com/sns/userinfo",$keysArr); 
		$userinfo=json_decode($userinfo,true);
		if(empty($userinfo['openid'])) return $userinfo['errmsg'];
		
		
		
		$callback=[
			'openid'=>$userinfo['openid'],
			'data'=>[
				'username'=>$userinfo['nickname'],
				'sex'=>$userinfo['sex'],
				'province'=>$userinfo['province'],
				'city'=>$userinfo['city'],
				'avatar'=>$userinfo['headimgurl'],
			],
			'callbackBind'=>"all",//授权成功后跳转到新页面。0邮箱和手机号任选绑定，1输入手机号绑定，2输入邮箱绑定，没有该参数则为默认0
		];
		/*callbackBind参数，
返回的值是空或all，邮箱和手机号任选绑定。
返回的值是bind_mobile，输入手机号绑定，
返回的值是bind_email，输入邮箱绑定
返回的值是login，直接登录，无需绑定*/
		unset($_SESSION['oauth_weixin_state']);
		return  $callback;	
	}	
	    /**
     * combineURL
     * 拼接url
     * @param string $baseURL   基于的url
     * @param array  $keysArr   参数列表数组
     * @return string           返回拼接的url
     */
    public function combineURL($baseURL,$keysArr){
        $combined = $baseURL."?";
        $valueArr = array();

        foreach($keysArr as $key => $val){
            $valueArr[] = "$key=$val";
        }

        $keyStr = implode("&",$valueArr);
        $combined .= ($keyStr);
        
        return $combined;
    }

    /**
     * get_contents
     * 服务器通过get请求获得内容
     * @param string $url       请求的url,拼接后的
     * @return string           请求返回的内容
     */
    public function get_contents($url){
        if (ini_get("allow_url_fopen") == "1") {
            $response = file_get_contents($url);
        }else{
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_URL, $url);
            $response =  curl_exec($ch);
            curl_close($ch);
        }
        return $response;
    }

    /**
     * get
     * get方式请求资源
     * @param string $url     基于的baseUrl
     * @param array $keysArr  参数列表数组      
     * @return string         返回的资源内容
     */
    public function get($url, $keysArr){
        $combined = $this->combineURL($url, $keysArr);
        return $this->get_contents($combined);
    }	
}