<?php

namespace mail\alimail\lib;

class AliyunDm
{
	private $AccessKeyId;
	private $AccessKeySecret;

	function __construct($AccessKeyId, $AccessKeySecret)
	{
		$this->AccessKeyId = $AccessKeyId;
		$this->AccessKeySecret = $AccessKeySecret;
	}

	private function aliyunSignature($parameters, $accessKeySecret, $method)
	{
		ksort($parameters);
		$canonicalizedQueryString = '';
		foreach ($parameters as $key => $value) {
			if($value === null) continue;
			$canonicalizedQueryString .= '&' . $this->percentEncode($key) . '=' . $this->percentEncode($value);
		}
		$stringToSign = $method . '&%2F&' . $this->percentencode(substr($canonicalizedQueryString, 1));
		$signature = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&", true));

		return $signature;
	}

	private function percentEncode($str)
	{
		$res = urlencode($str);
		$res = preg_replace('/\+/', '%20', $res);
		$res = preg_replace('/\*/', '%2A', $res);
		$res = preg_replace('/%7E/', '~', $res);
		return $res;
	}
	
	public function send($to, $sub, $msg, $from, $from_name)
	{
		if (empty($this->AccessKeyId) || empty($this->AccessKeySecret)) return false;
		$url = 'https://dm.aliyuncs.com/';
		$data = array(
			'Action' => 'SingleSendMail',
			'AccountName' => $from,
			'ReplyToAddress' => 'false',
			'AddressType' => 1,
			'ToAddress' => $to,
			'FromAlias' => $from_name,
			'Subject' => $sub,
			'HtmlBody' => $msg,
			'Format' => 'JSON',
			'Version' => '2015-11-23',
			'AccessKeyId' => $this->AccessKeyId,
			'SignatureMethod' => 'HMAC-SHA1',
			'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
			'SignatureVersion' => '1.0',
			'SignatureNonce' => $this->random(8)
		);
		$data['Signature'] = $this->aliyunSignature($data, $this->AccessKeySecret, 'POST');
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		$json = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		$arr = json_decode($json, true);
		if ($httpCode == 200) {
			return true;
		} else {
			return $arr['Message'];
		}
	}

	private function random($length) {
		$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, 35);
		$seed = $seed.'zZ'.strtoupper($seed);
		$hash = '';
		$max = strlen($seed) - 1;
		for($i = 0; $i < $length; $i++) {
			$hash .= $seed[mt_rand(0, $max)];
		}
		return $hash;
	}
}
