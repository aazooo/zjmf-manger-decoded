<?php

namespace app\admin\controller;

/**
 * @title 应用商店
 * @description 接口说明: 应用商店
 */
class AppStoreController extends \cmf\controller\BaseController
{
	public $market_url = "https://my.idcsmart.com";
	/**
	 * @time 2021-03-25
	 * @title 生成token
	 * @description 生成token
	 * @url /admin/app_store/set_token
	 * @method  GET
	 * @author xujin
	 * @version v1
	 */
	public function setToken()
	{
		if (cache("?market_token")) {
			$token = cache("market_token");
		} else {
			$token = randStr(12);
			cache("market_token", $token, 300);
		}
		$result = ["status" => 200, "market_url" => $this->market_url . "/market/index?from=" . request()->domain() . "/" . adminAddress() . "&token=" . $token . "&time=" . time()];
		return jsonrule($result);
	}
	/**
	 * @time 2021-03-25
	 * @title 校验token
	 * @description 校验token
	 * @url /admin/app_store/check_token
	 * @method  GET
	 * @author xujin
	 * @version v1
	 * @param   .name:token type:string require:1 desc:密钥
	 */
	public function checkToken()
	{
		$token = input("get.token", "");
		if ($token == cache("market_token")) {
			$result = ["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "license" => configuration("system_license")];
		} else {
			$result = ["status" => 400, "msg" => lang("ERROR MESSAGE")];
		}
		return jsonrule($result);
	}
	/**
	 * @time 2021-03-25
	 * @title 获取已购买应用最新版本
	 * @description 获取已购买应用最新版本
	 * @url /admin/app_store/new_version
	 * @method  GET
	 * @author xujin
	 * @version v1
	 * @param   .name:token type:string require:1 desc:密钥
	 */
	public function getNewVersion()
	{
		$res = commonCurl($this->market_url . "/market/app_version", ["request_time" => time(), "license" => configuration("system_license")], 30, "GET");
		if (isset($res["status"]) && $res["status"] == 200) {
			foreach ($res["data"] as $key => $value) {
				if ($value["app_type"] == "templates") {
					$file = CMF_ROOT . "/public/themes";
				} else {
					$file = WEB_ROOT . "plugins/" . $value["app_type"];
				}
				$old_version = file_get_contents($file . "/" . $value["uuid"] . "_version.txt");
				$res["data"][$key]["old_version"] = !empty($old_version) ? $old_version : "1.0.0";
				$res["data"][$key]["app_version"] = !empty($value["app_version"]) ? str_replace("V", "", $value["app_version"]) : "1.0.0";
			}
			$result = $res;
		} else {
			$result = ["status" => 400, "msg" => lang("ERROR MESSAGE")];
		}
		return jsonrule($result);
	}
	/**
	 * @time 2020-10-13
	 * @title 安装应用
	 * @description 安装应用
	 * @url /admin/app_store/app/:id/install
	 * @method  POST
	 * @author xujin
	 * @version v1
	 */
	public function install($id)
	{
		if (!extension_loaded("ionCube Loader")) {
			return jsonrule(["status" => 400, "msg" => "未安装ionCube扩展不可安装应用"]);
		}
		\compareLicense();
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			return jsonrule(["status" => 307, "msg" => "授权错误,请检查域名或ip"]);
		} else {
			$auth = \de_authorize($zjmf_authorize);
			$ip = \de_systemip(configuration("authsystemip"));
			if ($ip != $auth["ip"] && !empty($ip)) {
				return jsonrule(["status" => 307, "msg" => "授权错误,请检查ip"]);
			}
			if (time() > $auth["last_license_time"] + 604800 || ltrim(str_replace("https://", "", str_replace("http://", "", $auth["domain"])), "www.") != ltrim(str_replace("https://", "", str_replace("http://", "", $_SERVER["HTTP_HOST"])), "www.") || $auth["installation_path"] != CMF_ROOT || $auth["license"] != configuration("system_license")) {
				return jsonrule(["status" => 307, "msg" => "授权错误,请检查域名或ip"]);
			} else {
				if (!empty($auth["facetoken"])) {
					return jsonrule(["status" => 307, "msg" => "您的授权已被暂停,请前往智简魔方会员中心检查授权状态"]);
				}
				if ($auth["status"] == "Suspend") {
					return jsonrule(["status" => 307, "msg" => "您的授权已被暂停,请前往智简魔方会员中心检查授权状态"]);
				}
				$app = $auth["app"];
			}
		}
		$res = commonCurl($this->market_url . "/market/app_detail", ["id" => $id, "request_time" => time()], 30, "GET");
		if (in_array($res["data"]["product"]["uuid"], $app)) {
			if ($res["data"]["product"]["app_type"] == "systems" || empty($res["data"]["product"]["app_type"])) {
				return jsonrule(["status" => 200, "msg" => "应用安装成功"]);
			} else {
				if ($res["data"]["product"]["app_type"] == "templates") {
					$dir = "/public/themes/" . $res["data"]["product"]["uuid"] . ".zip";
					$content = curl_download($this->market_url . "/download/app_file?id={$id}&jwt=" . cache("market_jwt"), $dir);
					if ($content) {
						$file = CMF_ROOT . "/public/themes";
						$version = str_replace("V", "", $res["data"]["product"]["last_version"]);
						$uuid = $res["data"]["product"]["uuid"];
						$app_type = $res["data"]["product"]["app_type"];
						$res = unzip(CMF_ROOT . $dir, $file);
						if ($res["status"] == 200) {
							file_put_contents($file . "/" . $uuid . "_version.txt", $version);
							unlink(CMF_ROOT . $dir);
							return jsonrule(["status" => 200, "msg" => "应用安装成功", "data" => $app_type]);
						} else {
							return jsonrule(["status" => 400, "msg" => "应用文件解压失败,失败code:" . $res["msg"] . ";请到网站目录下解压下载的文件" . $dir]);
						}
					} else {
						return jsonrule(["status" => 400, "msg" => "应用下载失败"]);
					}
				} else {
					$dir = "/public/plugins/" . $res["data"]["product"]["app_type"] . "/" . $res["data"]["product"]["uuid"] . ".zip";
					$content = curl_download($this->market_url . "/download/app_file?id={$id}&jwt=" . cache("market_jwt"), $dir);
					if ($content) {
						$file = WEB_ROOT . "plugins/" . $res["data"]["product"]["app_type"];
						$version = str_replace("V", "", $res["data"]["product"]["last_version"]);
						$uuid = $res["data"]["product"]["uuid"];
						$app_type = $res["data"]["product"]["app_type"];
						$res = unzip(CMF_ROOT . $dir, $file);
						if ($res["status"] == 200) {
							file_put_contents($file . "/" . $uuid . "_version.txt", $version);
							unlink(CMF_ROOT . $dir);
							return jsonrule(["status" => 200, "msg" => "应用安装成功", "data" => $app_type]);
						} else {
							return jsonrule(["status" => 400, "msg" => "应用文件解压失败,失败code:" . $res["msg"] . ";请到网站目录下解压下载的文件" . $dir]);
						}
					} else {
						return jsonrule(["status" => 400, "msg" => "应用下载失败"]);
					}
				}
			}
		} else {
			return jsonrule(["status" => 400, "msg" => "应用未授权不可安装"]);
		}
		return jsonrule($result);
	}
	/**
	 * @time 2020-10-13
	 * @title 卸载应用
	 * @description 卸载应用
	 * @url /admin/app_store/app/:id/uninstall
	 * @method  DELETE
	 * @author xujin
	 * @version v1
	 */
	private function marketRequest($app = "", $data = [], $timeout = 30, $method = "POST")
	{
		if (!cache("?market_jwt")) {
			return ["status" => 400, "msg" => "未登录应用商店无法操作"];
		}
		$data["request_time"] = time();
		$result = commonCurl($this->market_url . "/" . $app, $data, $timeout, $method, ["Authorization: JWT " . cache("market_jwt")]);
		if ($result["status"] == 405) {
			$username = cache("market_username");
			$password = cache("market_password");
			if (cmf_check_mobile($username)) {
				$res = commonCurl($this->market_url . "/market/login_pass_phone", ["phone_code" => "+86", "phone" => $username, "password" => $password, "license" => configuration("system_license"), "request_time" => time()]);
			} else {
				$res = commonCurl($this->market_url . "/market/login_pass_email", ["email" => $username, "password" => $password, "license" => configuration("system_license"), "request_time" => time()]);
			}
			if ($res["status"] == 200) {
				cache("market_username", $username, 604800);
				cache("market_password", $password, 604800);
				cache("market_hostid", $res["hostid"], 604800);
				cache("market_jwt", $res["jwt"], 604800);
				cache("market_uname", $res["username"], 604800);
				unset($res["jwt"]);
				unset($res["hostid"]);
				$result = commonCurl($this->market_url . "/" . $app, $data, $timeout, $method, ["Authorization: JWT " . cache("market_jwt")]);
			} else {
				cache("market_username", null);
				cache("market_password", null);
				cache("market_hostid", null);
				cache("market_jwt", null);
				cache("market_uname", null);
				return ["status" => 400, "msg" => "未登录应用商店无法操作"];
			}
		}
		if ($result["status"] == 405) {
			$result["status"] = 400;
		}
		return $result;
	}
}