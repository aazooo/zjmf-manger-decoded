<?php
namespace oauth\alipay;


class alipay{
	function __construct(){
		if(!session_id()) session_start();
    }
	
	//插件信息
	public function meta(){
		return [
			'name'        => '支付宝登录',
			'description' => '支付宝登录',
			'author'      => '智简魔方',
			'logo_url'=> 'alipay.svg',//接口图片地址
			'version'=> '1.0.0',//版本号
		];
	}
	//插件接口配置信息
	public function config(){
		return [
			'APP_ID'=> [
				'type' => 'text',
				'name' => 'app_id',
				'desc' => '创建应用后生成，分配给应用的appid'
			],
			'开发者私钥'=> [
				'type' => 'textarea',
				'name' => 'app_private_key',
				'desc' => '开发者应用私钥，由开发者自己生成'
			],
			'支付宝公钥'=> [
				'type' => 'textarea',
				'name' => 'alipay_public_key',
				'desc' => '支付宝公钥'
			],
		];
	}
	//生成请求地址
	public function url($params){		
        //-------生成唯一随机串防CSRF攻击
        $state = md5(uniqid(rand(), TRUE));
		$_SESSION['oauth_alipay_state']=$state;

        //-------构造请求参数列表
        $keysArr = array(
            "app_id" => $params['app_id'],
            "redirect_uri" => $params['callback'],         
            "state" => $state,
            "scope" => "auth_user"
        );

        $login_url =  $this->combineURL("https://openauth.alipay.com/oauth2/publicAppAuthorize.htm",$keysArr);        
		return $login_url;

	}
	//回调地址
	public function callback($params){
		//判断state
		if($_SESSION['oauth_alipay_state']!=$params['state'] || empty($params['auth_code'])){
			return 'error';
		}
		//echo __DIR__ . '/lib/aop/AopClient.php';
 		require __DIR__ . '/lib/aop/AopClient.php';
		require __DIR__ . '/lib/aop/request/AlipaySystemOauthTokenRequest.php';
		require __DIR__ . '/lib/aop/request/AlipayUserInfoShareRequest.php'; 
		$aop = new \AopClient();
		//echo $aop->encryptType;exit;
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $params['app_id'];
		$aop->rsaPrivateKey = $params['app_private_key'];//请填写开发者私钥去头去尾去回车
		$aop->alipayrsaPublicKey= $params['alipay_public_key'];//请填写支付宝公钥
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->format = 'json';
		$aop->postCharset = 'UTF-8';
		$request = new \AlipaySystemOauthTokenRequest ();
		$request->setGrantType("authorization_code");
		$request->setCode($params['auth_code']);
		$result= $aop->execute ( $request); 		 
		if($result->error_response->sub_msg){
			return $result->error_response->sub_msg;
		}
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		if(!empty($resultCode)&&$resultCode != 10000){
			return 'error,获取授权访问令牌失败';
		}
		
		$request2 = new \AlipayUserInfoShareRequest ();
		$userinfo = $aop->execute ( $request2 , $result->$responseNode->access_token ); 

		$responseNode = str_replace(".", "_", $request2->getApiMethodName()) . "_response";
		
		if($userinfo->error_response->sub_msg){
			return $userinfo->error_response->sub_msg;
		}
		$resultCode = $userinfo->$responseNode->code;

		if(!empty($resultCode)&&$resultCode != 10000){
			return 'error,获取支付宝会员授权信息失败';
		} 
		$userinfo=$userinfo->$responseNode;
		if(strtolower($userinfo->gender)=="m") $sex=1; else if(strtolower($userinfo->gender)=="f") $sex=2;
		$callback=[
			'openid'=>$userinfo->user_id,
			'data'=>[
				'username'=>$userinfo->nick_name,
				'sex'=>$sex,
				'province'=>$userinfo->province,
				'city'=>$userinfo->city,
				'avatar'=>$userinfo->avatar,
			]
		];
		unset($_SESSION['oauth_alipay_state']);
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

}