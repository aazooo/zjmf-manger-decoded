<?php

namespace app\home\model;

class CertificationModel extends \think\Model
{
	private $imagesave;
	protected $type = ["more" => "array"];
	public function checkOtherUsed($idcard, $clientid, $type = 1)
	{
		$where = [["auth_card_number", "=", $idcard], ["status", "<>", 2]];
		$companyUsed = \think\Db::name("certifi_company")->where($where)->find();
		$personalUsed = \think\Db::name("certifi_person")->where($where)->find();
		$ret = false;
		if (isset($companyUsed["id"]) && $companyUsed["auth_user_id"] != $clientid) {
			$ret = true;
		}
		if (isset($personalUsed["id"]) && $personalUsed["auth_user_id"] != $clientid) {
			$ret = true;
		}
		return $ret;
	}
	public function checkThree($param, $num)
	{
		$query = createLinkstrings($param);
		$appcode = configuration("certifi_appcode");
		$res = httpstothree($appcode, $query, $num);
		$res["status"] = $res["ret"];
		return $res;
	}
	public function phoneThree($param)
	{
		$query = createLinkstrings($param);
		$appcode = configuration("certifi_phonethree_appcode");
		$res = $this->httpsPhoneThree($appcode, $query);
		$res["status"] = $res["code"];
		return $res;
	}
	private function httpsPhoneThree($appcode, $querys)
	{
		$host = "https://phone3.market.alicloudapi.com/phonethree";
		$method = "GET";
		$headers = [];
		array_push($headers, "Authorization:APPCODE " . $appcode);
		$url = $host . "?" . $querys;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		if (1 == strpos("\$" . $host, "https://")) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		}
		$result = curl_exec($curl);
		curl_close($curl);
		return json_decode($result, true);
	}
	public function configuration($config)
	{
		$result = \think\Db::name("configuration")->field("value")->whereRaw("setting = :setting", ["setting" => $config])->find();
		$re = $result["value"];
		return $re;
	}
	public function passCertifi($client_id)
	{
		$res = \think\Db::name("certification")->where("uid", $client_id)->find();
		if (!empty($res)) {
			if ($res[false] && $res["statuss"] == 1) {
				$re["status"] = 400;
				$re["msg"] = "您已完成个人认证";
			}
			if ($res[true] && $res["statuss"] == 1) {
				$re["status"] = 400;
				$re["msg"] = "您已完成企业认证";
			}
			return $re;
		}
	}
	public function checkCertifi($client_id)
	{
		$res = \think\Db::name("certification")->where("uid", $client_id)->find();
		if (!empty($res)) {
			if ($res[false] && $res["statuss"] == 3) {
				$re["status"] = 400;
				$re["msg"] = "您已提交个人认证资料，待审核";
			}
			if ($res[true] && $res["statuss"] == 3) {
				$re["status"] = 400;
				$re["msg"] = "您已提交企业认证资料，待审核";
			}
			return $re;
		}
	}
}