<?php
namespace gateways\hpj_alipay_pay\lib;

class XunhupayClient
{
	private $apiurl = 'https://api.xunhupay.com';
	private $appid;
	private $appsecret;

	function __construct($config){
		$this->appid = $config['api_key'];
		$this->appsecret = $config['secret_key'];
		if(!empty($config['gateway'])) {
			$this->apiurl = $config['gateway'];
		}
	}

	//发起支付
	public function do_payment($params){
		$path = '/payment/do.html';
		return $this->execute($path, $params);
	}

	//查询订单
	public function query_payment($params){
		$path = '/payment/query.html';
		return $this->execute($path, $params);
	}

	//发起通用请求
	public function execute($path, $params){
		$url = $this->apiurl.$path;
		$publicParams = [
			'appid' => $this->appid,
			'time' => time(),
			'nonce_str' => str_shuffle(time())
		];
		$params = array_merge($publicParams, $params);
		$params['hash'] = $this->generate_hash($params, $this->appsecret);
		$response = $this->curl_post($url, json_encode($params));
		$result = json_decode($response, true);
		if(isset($result['errcode']) && $result['errcode']==0){
			$hash = $this->generate_hash($result, $this->appsecret);
			if(!isset($result['hash']) || $hash !== $hash){
				throw new \Exception('返回数据签名校验失败');
			}
			return $result;
		}else{
			throw new \Exception($result['errmsg']?$result['errmsg']:'返回数据解析失败');
		}
	}

	public function verify($arr){
		if(!isset($arr['hash'])) return false;
		$hash = $this->generate_hash($arr, $this->appsecret);
		return $hash === $arr['hash'];
	}

	private function curl_post($url, $post, $timeout = 10){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$httpheader[] = "Accept: */*";
		$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
		$httpheader[] = "Connection: close";
		$httpheader[] = "Content-Type: application/json; charset=utf-8";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	private function generate_hash($param, $key){
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "hash" && $v!=='' && !is_null($v)){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
		$sign = md5($signstr.$key);
		return $sign;
	}

}