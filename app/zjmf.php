<?php

function judgeApiIs()
{
	return configuration("allow_resource_api") ? true : false;
}
function getAddressByApiType($api_type, $pid)
{
	$product = \think\Db::name("products")->where("id", $pid)->find();
	$type = $product["type"];
	$list = [];
	if ($api_type == "zjmf_api") {
		$list = \think\Db::name("zjmf_finance_api")->field("id,name,type")->select()->toArray();
		$api_type = ["manual" => "手动", "zjmf_api" => "智简魔方", "whmcs" => "WHMCS"];
		foreach ($list as &$v) {
			$v["name"] = "【" . $api_type[$v["type"]] . "】-" . $v["name"];
		}
	} elseif ($api_type == "resource") {
	} else {
		$server_model = new \app\common\model\ServersModel();
		if ($type == "dcim" || $type == "dcimcloud") {
			$type_normal = $type;
		} else {
			$type_normal = "normal";
		}
		$list = $server_model->getServerGroups("*", $type_normal);
		array_unshift($list, ["id" => 0, "name" => lang("NULL"), "type" => "", "system_type" => "normal"]);
	}
	return $list ?? [];
}
function getZjmfUpstreamProducts($id)
{
	$api = \think\Db::name("zjmf_finance_api")->where("id", $id)->find();
	if ($api["is_resource"]) {
		$path = "resource/products";
	} else {
		$path = "cart/all";
	}
	$result = zjmfCurl($id, $path, [], 30, "GET");
	if ($result["status"] == 200) {
		return $result["data"];
	} else {
		return [];
	}
}
function getZjmfUpstreamProductsInfo($id, $pids = [], $timeout = 10)
{
	$path = "api/product/proinfo";
	$result = zjmfCurl($id, $path, ["pids" => $pids], $timeout, "GET");
	return $result;
}
function getZjmfUpstreamProductsDetail($id, $pids = [], $timeout = 10)
{
	$path = "api/product/prodetail";
	$result = zjmfCurl($id, $path, ["pids" => $pids], $timeout, "GET");
	return $result;
}
function getResourceRate($id, $pid)
{
	$path = "resource/rate";
	$result = zjmfCurl($id, $path, ["pid" => $pid], 30, "GET");
	if ($result["status"] == 200) {
		return $result["data"];
	} else {
		return [];
	}
}
function getZjmfUpstreamProductConfig($id, $pid)
{
	$data = ["pid" => $pid];
	$result = zjmfCurl($id, "cart/get_product_config", $data, 30, "GET");
	return $result;
}
function getZjmfUostreamHostInfo($id, $hid)
{
	$data = ["host_id" => $hid, "source" => "API"];
	$result = zjmfCurl($id, "host/header", $data, 30, "GET");
	if ($result["status"] == 200) {
		return $result["data"]["host_data"];
	} else {
		return [];
	}
}
function pushTicketReply($id)
{
	$ticket_reply = \think\Db::name("ticket_reply")->alias("a")->leftJoin("ticket b", "b.id=a.tid")->leftJoin("host c", "c.id=b.host_id")->leftJoin("products d", "d.id=c.productid")->field("a.id as rid,c.password,a.content,c.id,c.stream_info,b.tid,a.attachment")->where("a.id", $id)->find();
	$stream_info = json_decode($ticket_reply["stream_info"], true);
	unset($ticket_reply["stream_info"]);
	if (!empty($stream_info["downstream_url"]) && !empty($stream_info["downstream_token"])) {
		$attachment = [];
		if (!empty($ticket_reply["attachment"])) {
			$ticket_reply["attachment"] = explode(",", $ticket_reply["attachment"]);
			foreach ($ticket_reply["attachment"] as $key => $value) {
				$res = curlUpload(rtrim($stream_info["downstream_url"], "/") . "/upload_image", config("ticket_attachments") . $value, substr($value, strrpos($value, "^") + 1));
				if ($res !== false) {
					$res = json_decode($res, true);
					if ($res["status"] == 200) {
						if (!empty($res["savename"])) {
							$attachment[] = $res["savename"];
						}
					}
				}
			}
		}
		$ticket_reply["id"] = $stream_info["downstream_id"];
		$ticket_reply["password"] = cmf_decrypt($ticket_reply["password"]) ?: "";
		$url = rtrim($stream_info["downstream_url"], "/") . "/api/ticket_reply/sync";
		$sign = createSign(["id" => $ticket_reply["id"]], $stream_info["downstream_token"]);
		$ticket_reply["attachment"] = $attachment;
		$ticket_reply["content"] = html_entity_decode($ticket_reply["content"]);
		$post_data = array_merge($ticket_reply, $sign);
		commonCurl($url, $post_data, 5);
		\think\Db::name("ticket_reply")->where("id", $id)->update(["is_deliver" => 1]);
	}
}
function pushHostInfo($id, $other_field = "", $type = "")
{
	$field = "id,productid,domain,username,password,dedicatedip,assignedips,port,os,os_url,stream_info,nextduedate,domainstatus";
	if (!empty($other_field)) {
		$field .= "," . $other_field;
	}
	$host = \think\Db::name("host")->field($field)->where("id", $id)->find();
	$stream_info = json_decode($host["stream_info"], true);
	$pid = $host["productid"];
	$pushhost = [];
	if ($type == "create") {
		$pushhost = \think\Db::name("zjmf_pushhost")->field("id")->where("host_id", $id)->find();
	}
	unset($host["stream_info"]);
	unset($host["productid"]);
	if (!empty($stream_info["downstream_url"]) && !empty($stream_info["downstream_token"]) && empty($pushhost)) {
		$host["host_id"] = $host["id"];
		$host["id"] = $stream_info["downstream_id"];
		$host["password"] = cmf_decrypt($host["password"]) ?: "";
		$ippassword = (new \app\common\logic\Dcim())->getPanelPass($id, $pid);
		if ($ippassword !== false) {
			$host["ippassword"] = $ippassword;
		}
		$url = rtrim($stream_info["downstream_url"], "/") . "/api/host/sync";
		$sign = createSign(["id" => $host["id"]], $stream_info["downstream_token"]);
		if (empty($host["nextduedate"])) {
			unset($host["nextduedate"]);
		}
		if (!empty($type)) {
			$host["type"] = $type;
		}
		$post_data = array_merge($host, $sign);
		$res = commonCurl($url, $post_data, 30);
		if ($type == "create") {
			if ($res["status"] == 200 || $res["status"] == 400) {
				$zjmf_pushhost = ["host_id" => $id, "url" => $url, "status" => 1, "post_data" => json_encode($post_data), "time" => time(), "num" => 1];
			} else {
				$zjmf_pushhost = ["host_id" => $id, "url" => $url, "status" => 0, "post_data" => json_encode($post_data), "time" => time(), "num" => 1];
			}
			\think\Db::name("zjmf_pushhost")->insert($zjmf_pushhost);
		}
		return $res;
	}
}
function createSign($params, $token)
{
	$rand_str = randStr(6);
	$params["token"] = $token;
	$params["rand_str"] = $rand_str;
	ksort($params, SORT_STRING);
	$str = json_encode($params);
	$sign = md5($str);
	$sign = strtoupper($sign);
	$res["signature"] = $sign;
	$res["rand_str"] = $rand_str;
	return $res;
}
function validateSign($params, $sign)
{
	$data = ["id" => $params["id"], "token" => $params["token"], "rand_str" => $params["rand_str"]];
	ksort($data, SORT_STRING);
	$str = json_encode($data);
	$signature = md5($str);
	return strtoupper($signature) === $sign;
}
/**
 * 时间 2020-08-06
 * @desc 
 * @author hh
 * @version v1
 * @param   int    $api_id  财务APIid
 * @param   string $path    接口路径
 * @param   array  $data    
 * @param   int    $timeout 超时时间
 * @param   string $request 请求方式(GET,POST,PUT,DELETE)
 * @return  array
 */
function zjmfCurl($api_id, $path, $data = [], $timeout = 30, $request = "POST")
{
	$api = \think\Db::name("zjmf_finance_api")->where("id", $api_id)->find();
	if (empty($api)) {
		$result["status"] = 400;
		$result["msg"] = "API账号或秘钥错误";
		return $result;
	}
	$url = rtrim($api["hostname"], "/");
	if ($api["is_resource"] == 1) {
		$login_url = $url . "/resource_login";
		$login_data = ["username" => $api["username"], "password" => aesPasswordDecode($api["password"]), "type" => "agent"];
	} else {
		$login_url = $url . "/zjmf_api_login";
		$login_data = ["username" => $api["username"], "password" => aesPasswordDecode($api["password"])];
	}
	$jwt = zjmfApiLogin($api_id, $login_url, $login_data);
	if ($jwt["status"] != 200) {
		return $jwt;
	}
	$header = ["Authorization: Bearer " . $jwt["jwt"]];
	$url = rtrim($url, "/") . "/" . $path;
	$res = commonCurl($url, $data, $timeout, $request, $header);
	if ($res["status"] == 405) {
		$jwt = zjmfApiLogin($api_id, $login_url, $login_data, true);
		if ($jwt["status"] != 200) {
			return $jwt;
		}
		$header = ["Authorization: Bearer " . $jwt["jwt"]];
		$res = commonCurl($url, $data, $timeout, $request, $header);
		if ($res["status"] == 405) {
			$res["status"] = 400;
			$res["msg"] = "API账号密码错误";
		}
	}
	return $res;
}
function zjmfCurlHasFile($api_id, $path, $data = [], $timeout = 30, $file = [])
{
	$api = \think\Db::name("zjmf_finance_api")->where("id", $api_id)->find();
	$url = $api["hostname"];
	if ($api["is_resource"] == 1) {
		$login_url = $url . "/resource_login";
		$login_data = ["username" => $api["username"], "password" => aesPasswordDecode($api["password"]), "type" => "agent"];
	} else {
		$login_url = $url . "/zjmf_api_login";
		$login_data = ["username" => $api["username"], "password" => aesPasswordDecode($api["password"])];
	}
	$jwt = zjmfApiLogin($api_id, $login_url, $login_data);
	if ($jwt["status"] != 200) {
		return $jwt;
	}
	$header = ["Authorization: Bearer " . $jwt["jwt"]];
	$url = rtrim($url, "/") . "/" . $path;
	$res = curlHasFile($url, $data, $timeout, $header, $file);
	if ($res["status"] == 405) {
		$jwt = zjmfApiLogin($api_id, $login_url, $login_data, true);
		if ($jwt["status"] != 200) {
			return $jwt;
		}
		$res = curlHasFile($url, $data, $timeout, $header, $file);
		if ($res["status"] == 405) {
			$res["status"] = 400;
			$res["msg"] = "资源池API账号密码错误";
		}
	}
	return $res;
}
function curlHasFile($url, $bodys, $timeout, $headers, $file)
{
	$method = "POST";
	$parse = parse_url($url);
	$host = $parse["scheme"] . "://" . $parse["host"];
	if (!empty($parse["port"])) {
		$host = $host . ":" . $parse["port"];
	}
	array_push($headers, "Content-Type:multipart/form-data; charset=UTF-8");
	$curl = curl_init();
	foreach ($file as $key => $img) {
		if (is_array($img)) {
			foreach ($img as $k => $item) {
				if ($orgin = explode("^", $item)[1]) {
					$suffixorgin = strripos($orgin, ".");
					if ($suffixorgin === false) {
						$filename = $orgin;
					} else {
						$filename = substr($orgin, 0, $suffixorgin);
					}
				}
				if (class_exists("\\CURLFile")) {
					curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
					$suffixIndex = strripos($item, ".");
					if ($suffixIndex === false) {
						$suffix = "jpg";
					} else {
						$suffix = substr($item, $suffixIndex + 1);
					}
					$filename = $filename ?: time() . rand(1000, 9999);
					$data[$key . "[{$k}]"] = new CURLFile(realpath($item), "image/" . $suffix, $filename . "." . $suffix);
				} else {
					if (defined("CURLOPT_SAFE_UPLOAD")) {
						curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
					}
					$data[$key . "[{$k}]"] = "@" . realpath($item);
				}
			}
		} else {
			if ($orgin = explode("^", $img)[1]) {
				$suffixorgin = strripos($orgin, ".");
				if ($suffixorgin === false) {
					$filename = $orgin;
				} else {
					$filename = substr($orgin, 0, $suffixorgin);
				}
			}
			if (class_exists("\\CURLFile")) {
				curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
				$suffixIndex = strripos($img, ".");
				if ($suffixIndex === false) {
					$suffix = "jpg";
				} else {
					$suffix = substr($img, $suffixIndex + 1);
				}
				$filename = $filename ?: time() . rand(1000, 9999);
				$data = [$key => new CURLFile(realpath($img), "image/" . $suffix, $filename . "." . $suffix)];
			} else {
				if (defined("CURLOPT_SAFE_UPLOAD")) {
					curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
				}
				$data = [$key => "@" . realpath($img)];
			}
		}
		$bodys = array_merge($bodys, $data);
	}
	curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_FAILONERROR, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	if (1 == strpos("\$" . $host, "https://")) {
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	}
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
	$result = curl_exec($curl);
	$error = curl_error($curl);
	if (!empty($error)) {
		return ["code" => 500, "msg" => "CURL ERROR:" . $error];
	}
	$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	if ($http_code == 200) {
		$result = json_decode($result, true);
	} else {
		$result = ["code" => 500, "msg" => "请求失败,HTTP状态码:" . $http_code . ",错误信息:" . $result];
	}
	curl_close($curl);
	return $result;
}
/**
 * 时间 2020-08-06
 * @desc API登录
 * @url 
 * @author hh
 * @version v1
 * @param   int     $id    APIID
 * @param   boolean $force 是否强制刷新缓存
 */
function zjmfApiLogin($id, $url, $data, $force = false)
{
	$key = "zjmf_finance_jwt_" . $id;
	$jwt = \think\facade\Cache::get($key);
	if (empty($jwt) || $force) {
		$res = commonCurl($url, $data);
		if ($res["status"] == 200) {
			$jwt = $res["jwt"];
			\think\facade\Cache::set($key, $jwt, 5400.0);
		} else {
			\think\facade\Cache::rm($key);
			return $res;
		}
	}
	$result["jwt"] = $jwt;
	$result["status"] = 200;
	return $result;
}
function commonCurl($url, $data = [], $timeout = 30, $request = "POST", $header = [])
{
	$curl = curl_init();
	$request = strtoupper($request);
	if ($request == "GET") {
		$s = "";
		if (!empty($data)) {
			foreach ($data as $k => $v) {
				if (empty($v)) {
					$data[$k] = "";
				}
			}
			$s = http_build_query($data);
		}
		if ($s) {
			$s = "?" . $s;
		}
		curl_setopt($curl, CURLOPT_URL, $url . $s);
	} else {
		curl_setopt($curl, CURLOPT_URL, $url);
	}
	curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)");
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_REFERER, request()->host());
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	if ($request == "GET") {
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HTTPGET, 1);
	}
	if ($request == "POST") {
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		if (is_array($data)) {
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		} else {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
	}
	if ($request == "PUT" || $request == "DELETE") {
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request);
		if (is_array($data)) {
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		} else {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
	}
	if (!empty($header)) {
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	}
	$content = curl_exec($curl);
	$error = curl_error($curl);
	if (!empty($error)) {
		return ["status" => 400, "msg" => "CURL ERROR:" . $error];
	}
	$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);
	if ($http_code == 200) {
		$result = json_decode($content, true);
	} else {
		$result["status"] = 400;
		$result["msg"] = "请求失败,HTTP状态码:" . $http_code;
		$result["http_code"] = $http_code;
		$result["content"] = $content;
	}
	return $result;
}
function upstreamBates($flag, $price, $setup, $end = false)
{
	$bates = 0;
	if ($price <= 0) {
		if ($flag) {
			if ($flag["type"] == 1) {
				$bates = $flag["bates"] / 100;
				$setup = $bates * $setup;
			} else {
				$bates = $flag["bates"];
				if ($end) {
				} else {
					$cp = $setup;
					$setup = $setup - $bates;
					if ($setup < 0) {
						$setup = 0;
						$bates = $cp;
					}
				}
			}
		}
	} else {
		if ($flag) {
			if ($flag["type"] == 1) {
				$bates = $flag["bates"] / 100;
				$price = $bates * $price;
				$setup = $bates * $setup;
			} else {
				$bates = $flag["bates"];
				if ($end) {
				} else {
					$price = $price - $bates;
					if ($price < 0) {
						$setup = $setup + $price;
						if ($setup < 0) {
							$setup = 0;
						}
						$price = 0;
					}
				}
			}
		}
	}
	return [$price, $setup, $bates];
}
function apiResourceLog($uid, $desc, $pid = null, $version = null, $source = "API")
{
	$data = ["uid" => intval($uid), "description" => $desc ? $desc : "", "ip" => get_client_ip6(), "create_time" => time(), "update_time" => 0, "pid" => $pid ?: 0, "version" => $version ?: 0, "port" => get_remote_port(), "source" => $source];
	$res = \think\Db::name("api_resource_log")->insert($data);
	return !empty($res);
}
function whmcsCurlPost($id, $action = "on", $data = [], $timeout = 30)
{
	$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
	$api = \think\Db::name("zjmf_finance_api")->where("id", $host["zjmf_api_id"])->find();
	$up_id = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $host["productid"])->where("b.fieldname", "hostid")->value("value");
	$url = $api["hostname"] . "/" . "modules/addons/idcsmart_api/api.php?action=/v1/hosts/{$up_id}/module/{$action}";
	$url_data = ["apiname" => $api["username"], "apikey" => aesPasswordDecode($api["password"])];
	$url_data = array_merge($url_data, $data);
	$module_res = commoncurl($url, $url_data, $timeout);
	return $module_res;
}