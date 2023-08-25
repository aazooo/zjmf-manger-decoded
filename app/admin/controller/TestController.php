<?php

namespace app\admin\controller;

class TestController extends \think\Controller
{
	public function marketPingLun()
	{
	}
	public function index()
	{
		$persons = \think\Db::name("shd_certifi_person")->select()->toArray();
		$temp = [];
		foreach ($persons as $person) {
			$pic = "";
			if ($person["img_one"]) {
				$pic = $pic . $person["img_one"];
			}
			if ($person["img_two"]) {
				$pic = $pic . "," . $person["img_two"];
			}
			if ($person["img_three"]) {
				$pic = $pic . "," . $person["img_three"];
			}
			$temp["uid"] = $person["auth_user_id"];
			$temp["certifi_name"] = $person["auth_real_name"];
			$temp["company_name"] = "";
			$temp["card_type"] = 1;
			$temp["idcard"] = $person["auth_card_number"];
			$temp["company_organ_code"] = "";
			$temp["error"] = $person["auth_fail"] ?? "";
			$temp["pic"] = $pic;
			$temp["create_time"] = $person["create_time"];
			$temp["status"] = $person["status"];
			$temp["type"] = 1;
			$temp["bank"] = "";
			$temp["phone"] = "";
			$temp["certifi_type"] = "Idcsmartali";
			$temp["custom_fields_log"] = "";
			$temp["notes"] = $person["auth_fail"];
		}
		$companys = \think\Db::name("shd_certifi_company")->select()->toArray();
		foreach ($companys as $company) {
			$pic = "";
			if ($person["img_one"]) {
				$pic = $pic . $company["img_one"];
			}
			if ($person["img_two"]) {
				$pic = $pic . "," . $company["img_two"];
			}
			if ($person["img_three"]) {
				$pic = $pic . "," . $company["img_three"];
			}
			if ($person["img_four"]) {
				$pic = $pic . "," . $company["img_four"];
			}
			$temp["uid"] = $company["auth_user_id"];
			$temp["certifi_name"] = $company["auth_real_name"];
			$temp["company_name"] = $company["company_name"];
			$temp["card_type"] = 1;
			$temp["idcard"] = $company["auth_card_number"];
			$temp["company_organ_code"] = $company["company_organ_code"] ?? "";
			$temp["error"] = $company["auth_fail"] ?? "";
			$temp["pic"] = $pic;
			$temp["create_time"] = $company["create_time"];
			$temp["status"] = $company["status"];
			$temp["type"] = 2;
			$temp["bank"] = "";
			$temp["phone"] = "";
			$temp["certifi_type"] = "Idcsmartali";
			$temp["custom_fields_log"] = "";
			$temp["notes"] = $company["auth_fail"];
		}
		$success = \think\Db::name("certifi_log")->insertAll($temp);
		var_dump($success);
		echo "success";
		exit;
		var_dump(cmf_password("iulr1654", "8a3qiskagkYKAkoAjc"));
		exit;
		$data = [];
		$data["html"] = "<button class=\"custom-button\">转移</button>";
		$data["js"] = "<script>\$(\".custom-button\").on(\"click\", function(){alert(\"转移\")})</script>";
		echo json_encode($data);
		exit;
		var_dump("think\\Controller");
		exit;
		var_dump(date("Y-m-d H:i:s", strtotime("1 month", 1643527434)));
		exit;
		$regdate = 1646030736;
		$nextduedate = 1648449968;
		var_dump(getMonthNum(date("Y-m-d", $regdate), date("Y-m-d", $nextduedate)));
		exit;
		$value = "e&Qh8gakM8a9]lul|o)o{Vc1n@0xf8nj";
		var_dump($value);
		$value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML401);
		var_dump($value);
		exit;
		var_dump(291);
		exit;
		var_dump(1);
		exit;
		$a = new \stdClass();
		$a->foo = "bar";
		$b = clone $a;
		var_dump($a, $b);
		exit;
		$a = \think\Db::name("upper_reaches_res")->where("in_ip", "103.107.191.85")->find();
		var_dump($a);
		exit;
		$json = "{ \"foo\": \"bar\", \"number\": 42 }";
		$stdInstance = json_decode($json);
		var_dump($stdInstance);
		exit;
		echo $stdInstance->foo . PHP_EOL;
		echo $stdInstance->number . PHP_EOL;
		exit;
		$a = hook("template_after_service_domainstatus_selected");
		var_dump($a);
		exit;
		$news_list = \think\Db::name("tmp_news_lists")->select()->toArray();
		foreach ($news_list as $key => $value) {
			$id = \think\Db::name("news_menu")->insertGetId(["admin_id" => 32, "parent_id" => 3, "title" => $value["title"], "keywords" => $value["keywords"], "description" => $value["description"], "create_time" => $value["create_time"], "push_time" => $value["create_time"]]);
			\think\Db::name("news")->insertGetId(["relid" => $id, "content" => $value["content"]]);
		}
		var_dump("111");
		exit;
		$host = \think\Db::name("host")->alias("h")->field("p.id,h.uid")->leftJoin("orders o", "o.id=h.orderid")->leftJoin("invoices i", "i.id=o.invoiceid")->leftJoin("products p", "p.id=h.productid")->leftJoin("customfields d", "p.id=d.relid AND d.type='product' AND d.fieldname='hostid'")->leftJoin("customfieldsvalues e", "e.fieldid=d.id AND e.relid=h.id")->leftJoin("host f", "f.id=e.value")->where("p.app_status", 1)->where("p.retired", 0)->where("p.hidden", 0)->where("f.domain", "<>", "")->where("o.invoiceid=0 OR i.status='Paid'")->where("h.create_time", "<", time() - 604800)->limit(10)->page(2)->select()->toArray();
		var_dump($host);
		exit;
		foreach ($host as $key => $value) {
			$evaluation = \think\Db::name("evaluation")->where("uid", $v["uid"])->where("eid", 0)->where("rid", $v["id"])->where("type", "products")->find();
			if (empty($evaluation)) {
				\think\Db::name("evaluation")->insertGetId(["type" => "products", "rid" => $v["id"], "uid" => $v["uid"], "content" => "默认好评", "score" => 5, "create_time" => time(), "update_time" => time()]);
			}
			$evaluations = \think\Db::name("evaluation")->where("rid", $v["id"])->where("type", "products")->where("eid", 0)->select()->toArray();
			if (count($evaluations) > 0) {
				$app_score = round(array_sum(array_column($evaluations, "score")) / count($evaluations), 1);
				\think\Db::name("products")->where("id", $v["id"])->update(["app_score" => $app_score]);
			}
		}
		editEmailTplNullLink();
		exit;
		$msg = "<span style=\"margin: 0; padding: 0; display: inline-block; margin-top: 55px;\">查看订单详情：<span style=\"color: blue;\">链接</span></span>";
		$msg = htmlspecialchars($msg);
		$data = \think\Db::name("email_templates")->whereLike("message", "%" . $msg . "%")->find();
		if ($data) {
			$datas = \think\Db::name("email_templates")->where("name_en", $data["name_en"])->select()->toArray();
		}
		echo "<pre>";
		var_dump($datas);
		exit;
		var_dump($_SERVER);
		exit;
		$url = "https://smsapi.cn-north-4.myhuaweicloud.com:443/sms/batchSendSms/v1";
		$APP_KEY = "X5t7qGHd139xhU8c2M82b8P0wjV8";
		$APP_SECRET = "l2BPXNcKsWABxNFEFi19sAmdEwjc";
		$sender = "1069368924410000614";
		$TEMPLATE_ID = "0941dafa9e3e417a935f82a2186b0508";
		$signature = "华为云短信测试";
		$receiver = "+8613617638338";
		$statusCallback = "";
		$TEMPLATE_PARAS = "[\"369751\"]";
		date_default_timezone_set("Asia/Shanghai");
		$now = date("Y-m-d\\TH:i:s\\Z");
		$nonce = uniqid();
		$base64 = base64_encode(hash("sha256", $nonce . $now . $APP_SECRET));
		$buildWsseHeader = sprintf("UsernameToken Username=\"%s\",PasswordDigest=\"%s\",Nonce=\"%s\",Created=\"%s\"", $APP_KEY, $base64, $nonce, $now);
		$headers = ["Content-Type: application/x-www-form-urlencoded", "Authorization: WSSE realm=\"SDP\",profile=\"UsernameToken\",type=\"Appkey\"", "X-WSSE: " . $buildWsseHeader];
		$data = http_build_query(["from" => $sender, "to" => $receiver, "templateId" => $TEMPLATE_ID, "templateParas" => $TEMPLATE_PARAS, "statusCallback" => $statusCallback]);
		$context_options = ["http" => ["method" => "POST", "header" => $headers, "content" => $data, "ignore_errors" => true], "ssl" => ["verify_peer" => false, "verify_peer_name" => false]];
		print_r($context_options) . PHP_EOL;
		$response = file_get_contents($url, false, stream_context_create($context_options));
		print_r($response) . PHP_EOL;
		exit;
		\think\Db::startTrans();
		try {
			$shd_resource_auto_evaluate = configuration("shd_resource_auto_evaluate");
			$orders = \think\Db::name("invoices")->alias("c")->field("c.id,c.uid,f.productid")->leftJoin("invoice_items b", "c.id=b.invoice_id")->leftJoin("host d", "b.rel_id=d.id AND b.type in ('host','renew')")->leftJoin("upgrades u", "b.rel_id=u.id AND b.type in ('upgrade')")->leftJoin("host v", "u.relid=v.id")->leftJoin("products e", "d.productid=e.id OR v.productid=e.id")->leftJoin("res_products f", "e.id=f.productid")->leftJoin("res_sign_for sf", "sf.invoiceid = c.id")->leftJoin("res_evaluation re", "re.rid=c.id")->where("sf.status", 1)->where("f.productid", ">", 0)->where("re.id is null")->where("c.create_time", "<", time() - $shd_resource_auto_evaluate * 24 * 3600)->group("c.id")->select()->toArray();
			foreach ($orders as $key => $value) {
				\think\Db::name("res_evaluation")->insertGetId(["type" => "great", "rid" => $value["id"], "pid" => $value["productid"], "uid" => $value["uid"], "content" => "评价方未及时做出评价，系统默认好评！", "score" => 5, "netword_score" => 5, "hardware_score" => 5, "img" => "", "create_time" => time(), "update_time" => time()]);
			}
			$productid = array_unique(array_column($orders, "productid"));
			foreach ($productid as $key => $value) {
				$evaluations = \think\Db::name("res_evaluation")->where("pid", $value)->where("eid", 0)->select()->toArray();
				if (count($evaluations) > 0) {
					$score = round(array_sum(array_column($evaluations, "score")) / count($evaluations), 1);
					\think\Db::name("res_products")->where("productid", $value)->update(["score" => $score]);
				}
			}
			$evaluations = \think\Db::name("res_evaluation")->alias("a")->field("a.id,a.score,a.netword_score,a.hardware_score,a.pid,b.shop_id")->leftJoin("res_products b", "b.productid=a.pid")->select()->toArray();
			$shops = [];
			foreach ($evaluations as $key => $value) {
				if (!isset($shops[$value["shop_id"]])) {
					$shops[$value["shop_id"]] = ["score" => $value["score"] + $value["netword_score"] + $value["hardware_score"], "count" => 1];
				} else {
					$shops[$value["shop_id"]]["score"] += $value["score"] + $value["netword_score"] + $value["hardware_score"];
					$shops[$value["shop_id"]]["count"]++;
				}
			}
			foreach ($shops as $key => $value) {
				$score = round($value["score"] / $value["count"] / 3, 1);
				\think\Db::name("res_shop")->where("id", $key)->update(["score" => $score]);
			}
			\think\Db::commit();
			echo "success";
		} catch (\Throwable $e) {
			\think\Db::rollback();
			echo $e->getMessage() . "line:" . $e->getLine();
		}
		exit;
		certifiBusinessBtn();
		exit;
		addNavToMenus();
		var_dump(1);
		exit;
		recurse_copy(CMF_ROOT . "modules/", CMF_ROOT . "public/plugins/");
		rename(CMF_ROOT . "modules", CMF_ROOT . "modules_old");
		var_dump(1234);
		exit;
		$cid = 103222;
		$currency = 1;
		$qty = 35;
		$arr = \think\Db::name("product_config_options_sub")->alias("a")->field("a.qty_minimum,a.qty_maximum")->leftJoin("pricing b", "a.id = b.relid")->where("b.type", "configoptions")->where("b.currency", $currency)->where("a.config_id", $cid)->select()->toArray();
		array_multisort($arr, array_column($arr, "qty_maximum"));
		foreach ($arr as $k => $v) {
			if ($qty <= $v["qty_maximum"] && $v["qty_minimum"] <= $qty) {
				$min = $k;
			}
		}
		var_dump(intval($arr[$min - 1]["qty_maximum"]));
		exit;
		var_dump($arr);
		exit;
		var_dump($subs);
		exit;
		var_dump((new \app\common\logic\Contract())->getContractProducts());
		exit;
		addWebDefaultNav();
		addWebFootDefaultNav();
		createWebDefaultMenu();
		exit;
		$id = 9;
		$contract = \think\Db::name("contract")->where("id", $id)->find();
		$contract["product_id"] = explode(",", $contract["product_id"]);
		$product_ids = \think\Db::name("contract")->where("product_id", "<>", "")->column("product_id");
		$pid_arr = [];
		foreach ($product_ids as $product_id) {
			$pids = explode(",", $product_id);
			$pid_arr = array_merge($pid_arr, $pids);
		}
		$pid_arr = array_unique($pid_arr);
		$pid_arr = array_diff($pid_arr, $contract["product_id"]);
		var_dump($pid_arr);
		exit;
		var_dump($_SERVER);
		exit;
		var_dump(cmf_get_current_admin_id());
		exit;
		var_dump(cmf_parse_name("mzpay1", 1));
		exit;
		$params = $this->request->param();
		$pid = intval($params["pid"]);
		$hid = intval($params["hid"]);
		$adminAddress = trim($params["adminAddress"]);
		$password = trim($params["password"]);
		$uid = request()->uid;
		$host = \think\Db::name("host")->alias("a")->field("a.id,d.value domain2,a.domain,e.ip_address,e.hostname,e.secure,e.port,e.username,e.password")->leftJoin("products b", "b.id=a.productid")->leftJoin("customfields c", "c.relid=b.id AND c.type='product' AND c.fieldname='domain'")->leftJoin("customfieldsvalues d", "d.relid=a.id AND d.fieldid=c.id")->leftJoin("servers e", "e.gid=b.server_group")->where("b.config_option1", "finance")->whereIn("b.config_option2", ["free", "professional"])->where("d.value", "<>", "")->where("a.uid", $uid)->where("a.id", $hid)->find();
		if (empty($host)) {
			return jsons(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
		if ($host["secure"] == 1) {
			$host["server_http_prefix"] = "https";
		} else {
			$host["server_http_prefix"] = "http";
		}
		$url = $host["server_http_prefix"] . "://" . ($host["hostname"] ?: $host["ip_address"]);
		$url = rtrim($url, "/");
		if (!empty($host["port"])) {
			$url = $url . ":" . $host["port"];
		}
		$url .= "/app/api/secure_password";
		$res = commonCurl($url, ["token" => $host["username"], "license" => $host["domain"], "secure_password" => $password], 30, "PUT");
		if ($res["status"] == 200) {
			if (stripos($host["domain2"], "https://") === false && stripos($host["domain2"], "http://") === false) {
				$result = commonCurl("https://" . $host["domain2"] . "/" . $adminAddress . "/app_store/check_token", [], 5, "GET");
				if (isset($result["status"]) && !isset($result["http_code"])) {
					$host["domain2"] = "https://" . $host["domain2"];
				} else {
					$host["domain2"] = "http://" . $host["domain2"];
				}
			}
			$res = commonCurl($host["domain2"] . "/" . $adminAddress . "/app_store/app/" . $pid . "/install", ["token" => $params["password"]], 300, "POST");
			if ($res["status"] == 200) {
				$app_type = \think\Db::name("products")->where("id", $pid)->value("app_type");
				$result = ["status" => 200, "data" => $app_type, "msg" => lang("SUCCESS MESSAGE")];
			} else {
				$result = $res;
			}
		} else {
			$result = ["status" => 401, "msg" => $res["msg"] ?? lang("ERROR MESSAGE")];
		}
		var_dump($result);
		exit;
		$url = "https://php.zjmf.cf:18443/upload.php";
		$file = CMF_ROOT . "uploads/common/default/f19286c76d2057c97586b19203937a7d1621327555^mzpay.zip";
		$data = [];
		$file = realpath($file);
		$data = ["file" => new \CURLFile($file)];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		$ssl = substr($url, 0, 8) == "https://" ? true : false;
		if ($ssl) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($ch);
		curl_close($ch);
		var_dump($response);
		exit;
		var_dump(1112);
		exit;
		$time = 0;
		$file = CMF_ROOT . "uploads/common/default/checkapp" . $time;
		$res = unzip(CMF_ROOT . "uploads/common/default/f19286c76d2057c97586b19203937a7d1621327555^mzpay.zip", $file);
		if ($res["status"] == 200) {
			$arr = [];
			$handler = opendir($file);
			while (($filename = readdir($handler)) !== false) {
				if ($filename != "." && $filename != "..") {
					array_push($arr, $filename);
				}
			}
			if (count($arr) == 1) {
			}
			$file = $file . "/" . $arr[0];
			if (is_dir($file)) {
				$arr = [];
				$handler = opendir($file);
				while (($filename = readdir($handler)) !== false) {
					if ($filename != "." && $filename != "..") {
						array_push($arr, $filename);
					}
				}
				foreach ($arr as $v) {
					if (strpos($v, ".php") !== false) {
						$uuid = str_replace(".php", "", $v);
						if (strpos($v, "Plugin.php") !== false) {
							$uuid = str_replace("Plugin.php", "", $v);
							break;
						}
					}
				}
				var_dump($uuid);
				exit;
			} else {
				var_dump($file);
				exit;
			}
		} else {
			var_dump($res);
			exit;
		}
		$cookiename = "xxxx.txt";
		$re = $this->localGet("https://my.ddos-guard.net/attack?AttackSearch%5Bip%5D=&AttackSearch%5Bstart_attack_date%5D=&AttackSearch%5Bend_attack_date%5D=&AttackSearch%5Bprotocol%5D=6", null, 10, $cookiename);
		\phpQuery::newDocumentHtml($re);
		$re = pq("tbody")->find("tr");
		$arr = [];
		foreach ($re as $rv) {
			$ip = pq($rv)->find("a")->attr("data-ip");
			$flow = pq($rv)->find("a")->attr("data-size");
			$flow = str_replace(",", "", $flow);
			if (stripos($flow, "Gbps") !== false) {
				$flow = intval($flow) * 1024 * 1024 * 1024;
			} elseif (stripos($flow, "Mbps") !== false) {
				$flow = intval($flow) * 1024 * 1024;
			} elseif (stripos($flow, "Kbps") !== false) {
				$flow = intval($flow) * 1024;
			} else {
				$flow = intval($flow);
			}
			$start_time = pq($rv)->find("a")->attr("data-start");
			$start_time = strtotime($start_time) + 18000;
			$end_time = pq($rv)->find("a")->attr("data-end");
			$end_time = strtotime($end_time) + 18000;
			$arr[] = ["ip" => $ip, "flow" => $flow, "start_time" => $start_time, "end_time" => $end_time];
		}
		var_dump($re);
		exit;
		$re = $this->localGet("https://my.ddos-guard.net/attack?AttackSearch%5Bip%5D=&AttackSearch%5Bstart_attack_date%5D=&AttackSearch%5Bend_attack_date%5D=&AttackSearch%5Bprotocol%5D=17", null, 10, $cookiename);
		\phpQuery::newDocumentHtml($re);
		$re = pq("tbody")->find("tr");
		$arr = [];
		foreach ($re as $rv) {
			$ip = pq($rv)->find("a")->attr("data-ip");
			$flow = pq($rv)->find("a")->attr("data-size");
			$flow = str_replace(",", "", $flow);
			if (stripos($flow, "Gbps") !== false) {
				$flow = intval($flow) * 1024 * 1024 * 1024;
			} elseif (stripos($flow, "Mbps") !== false) {
				$flow = intval($flow) * 1024 * 1024;
			} elseif (stripos($flow, "Kbps") !== false) {
				$flow = intval($flow) * 1024;
			} else {
				$flow = intval($flow);
			}
			$start_time = pq($rv)->find("a")->attr("data-start");
			$start_time = strtotime($start_time) + 18000;
			$end_time = pq($rv)->find("a")->attr("data-end");
			$end_time = strtotime($end_time) + 18000;
			$add = false;
			foreach ($arr as $k => $v) {
				if ($v["ip"] == $ip && ($v["start_time"] <= $start_time && $start_time <= $v["end_time"] || $v["start_time"] <= $end_time && $end_time <= $v["end_time"])) {
					if ($v["start_time"] <= $start_time && $start_time <= $v["end_time"]) {
						$arr[$k]["flow"] += $flow;
						$arr[$k]["end_time"] = $v["end_time"] < $end_time ? $end_time : $v["end_time"];
					} else {
						if ($v["start_time"] <= $end_time && $end_time <= $v["end_time"]) {
							$arr[$k]["flow"] += $flow;
							$arr[$k]["start_time"] = $start_time < $v["start_time"] ? $start_time : $v["start_time"];
						}
					}
					$add = true;
				}
			}
			if ($add === false) {
				$arr[] = ["ip" => $ip, "flow" => $flow, "start_time" => $start_time, "end_time" => $end_time];
			}
		}
		var_dump($arr);
		exit;
		$password = "RAP3p2MB3@";
		$public_key = openssl_pkey_get_public($public_key);
		openssl_public_encrypt($password, $em, $public_key);
		$em = base64_encode($em);
		$data = ["username" => "ts_azuretech", "password" => $em, "encrypted" => "true", "is_staff" => "false"];
		$re = commonCurl("https://adbos.nsfocus.cloud/api/v1/login/", $data, 90, "POST");
		$token = $re["token"];
		$header = ["Authorization: cToken " . $token];
		$re = commonCurl("https://adbos.nsfocus.cloud/api/v1/scrubbingmonitorgroups/", [], 30, "GET", $header);
		$group_id = $re[0]["id"];
		$re = commonCurl("https://adbos.nsfocus.cloud/api/v1/scrubbingmonitorgroups/" . $group_id . "/monitor/ads/events/?chart=table&limit=100&offset=5&orderby=-time", [], 90, "GET", $header);
		$arr = $re["results"];
		var_dump($re);
		exit;
	}
	public function index1()
	{
		createMenus();
		productMenu();
		exit;
		$log = "20210326,2.2.4,20210329更新,https://license.soft13.idcsmart.com/upgrade/beta/2.2.4.zip\n20210401,2.2.5,test,https://license.soft13.idcsmart.com/upgrade/beta/2.2.5.zip\n20210401,2.2.6,20210401更新,https://license.soft13.idcsmart.com/upgrade/beta/2.2.6.zip\n20210401,2.2.7,20210401更新,https://license.soft13.idcsmart.com/upgrade/beta/2.2.7.zip\n20210408,2.2.8,test,https://license.soft13.idcsmart.com/upgrade/beta/2.2.8.zip\n20210409,2.2.9,test,https://license.soft13.idcsmart.com/upgrade/beta/2.2.9.zip\n20210421,2.3.1,test,https://license.soft13.idcsmart.com/upgrade/beta/2.3.1.zip";
		$arr = explode("\n", $log);
		$last = "";
		recurseGetLastVersion($last, $arr);
		var_dump($last);
		exit;
		$content = "http://www.baidu.com?rand=" . rand(1000, 9999);
		$qrCode = new \cmf\phpqrcode\QRcode();
		var_dump(generateQRfromShd($content));
		exit;
		header("Content-Type: " . $qrCode->getContentType());
		echo $qrCode->writeString();
		exit;
		$urlToEncode = "http://www.helloweba.com";
		echo generateQRfromGoogle($urlToEncode);
		exit;
		if ($request->isGet()) {
			if ($step == "authstart") {
				$_config = \think\Db::name("plugin")->where("module", "certification")->where("status", 1)->where("name", $type)->value("config");
				$_config = json_decode($_config, true);
				if ($action == "personal") {
					$table = "certifi_person";
				} else {
					$table = "certifi_company";
				}
				$tmp = \think\Db::name($table)->where("auth_user_id", $uid)->find();
				if (empty($tmp)) {
					header("location:{$this->ViewModel->domain}/verified?action={$action}");
					exit;
				}
				$free = intval($_config["free"]);
				$count = \think\Db::name("certifi_log")->where("uid", $uid)->where("card_type", 1)->where("certifi_type", $type)->count();
				$pay = false;
				if ($free == 0 || $free > 0 && $free <= $count) {
					$pay = true;
				}
				if (floatval($_config["amount"]) > 0 && $pay) {
					$invoice = \think\Db::name("invoice_items")->alias("a")->field("b.status,b.id,b.url")->leftJoin("invoices b", "a.invoice_id = b.id")->where("a.uid", $uid)->where("a.rel_id", $uid)->where("a.type", $table)->order("a.id", "desc")->find();
					if ($invoice["status"] != "Paid") {
						$invoiceid = $invoice["id"];
						header("location:{$this->ViewModel->domain}/viewbilling?id={$invoiceid}");
						exit;
					}
				}
			}
		}
		$client = new \think\api\Client("e70619dc387492c0de951f7c9bb29b44");
		try {
			$result = $client->idcardAuth()->withIdNum("500109199409309110")->withName("吴育华")->request();
		} catch (\Exception $e) {
			var_dump($e->getMessage());
			exit;
		}
		var_dump($result);
		exit;
		var_dump($res);
		exit;
		var_dump(getPluginsList());
		exit;
		$addons = ["ProductDivert", "DemoStyle"];
		array_push($addons, "");
		var_dump($addons);
		exit;
		var_dump(pay(1));
		exit;
		var_dump(hook("template_after_service_domainstatus_selected"));
		exit;
		var_dump(shd_addon_url("ProductDivert://Index/addhelp", ["hid" => 3636], true));
		exit;
		var_dump(\config("shd_hooks"));
		exit;
		$class = new \ReflectionClass("app\\home\\controller\\HooksController");
		$methods = $class->getMethods();
		$methods_filter = [];
		foreach ($methods as $method) {
			$methods_filter[] = $method->name;
		}
		var_dump($methods_filter);
		exit;
		$a = shd_addon_url("DemoStyle://Index/addhelp", [], true);
		var_dump($a);
		exit;
		header("Set-Cookie: testcookie=中文; path=/; domain=w2.test.idcsmart.com; expires=" . gmstrftime("%A, %d-%b-%Y %H:%M:%S GMT", time() + 9600));
		$fun = function ($a, $b) {
			$a += $b;
			return $a;
		};
		$arr = [1, 2, 3, 4, 6, 7];
		$res = array_reduce($arr, $fun);
		var_dump($res);
		exit;
		$res = callbackCustom("this is a test", function ($param) {
			return $param;
		});
		var_dump($res);
		exit;
		$submail = new \app\common\logic\SubmailSms();
		$data = ["TemplateCode" => "ijd1G"];
		$res = $submail->submailSingleSendMessageInland($data);
		var_dump($res);
		exit;
		$str = ")((()))(";
		var_dump($this->isClosed($str));
		exit;
		if (is_dir(CMF_ROOT . "public/admin")) {
			var_dump(11);
		}
		var_dump(22);
		exit;
		$url = request()->domain() . "/" . adminAddress() . "/plugins";
		\think\Db::name("auth_rule")->where("id", 2041)->update(["url" => $url]);
		$mo = new \app\admin\model\PluginModel();
		var_dump($mo->getPluginsMeun());
		exit;
		$config = $this->getCronConfig();
		$this->generateRepaymentBill();
		$products = \think\Db::name("products")->field("id,name")->where("id", 817)->select()->toArray();
		foreach ($products as &$product) {
			$pid = $product["id"];
			$configs = \think\Db::name("product_config_options")->alias("a")->field("a.id,a.option_name")->leftJoin("product_config_groups b", "a.gid = b.id")->leftJoin("product_config_links c", "c.gid = b.id")->where("c.pid", $pid)->select()->toArray();
			foreach ($configs as &$config) {
				$cid = $config["id"];
				$subs = \think\Db::name("product_config_options_sub")->field("id,option_name")->where("config_id", $cid)->select()->toArray();
				$config["subs"] = $subs;
			}
			$product["configs"] = $configs;
		}
		$data = ["products" => $products];
		return json(["status" => 200, "data" => $data]);
		var_dump($products);
		exit;
		var_dump(hook("after_daily_cron"));
		exit;
		$start_time = strtotime(date("Y-m-d", time()));
		$end_time = $start_time + 86400;
		var_dump($start_time, $end_time);
		exit;
		var_dump(getAdminThemesAll());
		exit;
		var_dump(json_encode([]));
		exit;
		$oids_all = [];
		$oids_all = array_merge($oids_all, [1, 2, 3]);
		$oids_all = array_merge($oids_all, [3, 4, 5]);
		var_dump($oids_all);
		exit;
		$groups = [""];
		if (true) {
			echo "真";
		} else {
			echo "假";
		}
		exit;
		if (!file_exists("/tmp/session")) {
			mkdir("/tmp/session", 493, true);
		}
		var_dump(session_save_path(dirname(dirname($_SERVER["DOCUMENT_ROOT"])) . "/session"));
		var_dump(session_save_path());
		exit;
		$cancellation_time = configuration("cancellation_time") ?: 1;
		$lifetime = $cancellation_time * 24 * 60 * 60;
		$config = array_merge(config("session"), ["expire" => $lifetime]);
		var_dump($config);
		exit;
		var_dump(get_files(WEB_ROOT . "themes/cart"));
		exit;
		$src = file_get_contents(WEB_ROOT . "plugins/addons/dingtalk_ticket/index.tpl");
		$menu = preg_replace_callback("/(?:\\{)(.*)(?:\\})/i", function ($m) {
			$m = substr($m[1], 1);
			$m = "return " . $m . ";";
			return eval($m);
		}, $src);
		var_dump($menu);
		exit;
		preg_match_all("/(?:\\{)(.*)(?:\\})/i", $str, $result);
		$fun = substr($result[1][0], 1);
		var_dump($fun);
		eval("\$str = \"{$fun}\";");
		var_dump($str);
		exit;
		var_dump(file_exists(WEB_ROOT . "plugins/addons/dingtalk_ticket/index.tpl"));
		exit;
		var_dump(\request()->domain() . "/" . adminAddress() . "/plugins");
		exit;
		var_dump(url("plugin/install", ["name" => "UserAction"]));
		exit;
		$model = new \app\admin\model\PluginModel();
		var_dump(hook("after_ticket_create", ["id" => 314]));
		exit;
		var_dump(shd_addon_url("DingtalkTicket://AdminIndex/setWebHook"));
		exit;
		$a = shd_addon_url("ClientCare://ClientCare/index", [], true);
		var_dump($a);
		exit;
		$type = "gateways";
		var_dump(config("plugins_dir")[$type]);
		exit;
		var_dump(hook("wyhtsetalsdfjl"));
		exit;
		var_dump(hook("shd_application_hook_test", ["id" => 1]));
		exit;
		var_dump(WEB_ROOT . "themes");
		exit;
		$params = $this->request->param();
		$config = (new \think\captcha\Captcha())->getConfig();
		$config_data = [];
		foreach ($config as $k => $v) {
			$config_data[$k] = $params[cmf_parse_name($k, 0)] ?? $v;
		}
		updateConfiguration("captcha_configuration", json_encode($config_data));
		var_dump(configuration("captcha_configuration"));
		exit;
		$captcha = new \think\captcha\Captcha();
		var_dump($captcha->config);
		exit;
		$result = configuration(["is_captcha", "captcha_length", "captcha_combination"], [0, 5, 1]);
		var_dump($result);
		exit;
		$res = getNextTime("monthly", 0, 1613701016);
		$res = ($res - 1613701016) / 24 / 3600;
		var_dump($res);
		exit;
		var_dump(getSale());
		exit;
		$a = bcsub(2, 0, 2);
		var_dump(floatval($a));
		exit;
		$aa = getSaleProductUser(765, 5);
		var_dump($aa);
		exit;
		var_dump(shd_addon_url("ClientCare://ClientCare/index"));
		exit;
		var_dump(hook("wyhtsetalsdfjl"));
		exit;
		var_dump(2222);
		exit;
		$a = hook("specify_client_payment", ["id" => 21]);
		var_dump($a);
		exit;
		$cancellation_time = configuration("cancellation_time") ?: 1;
		$lifetime = $cancellation_time * 24 * 60 * 60;
		$config = array_merge(config("session"), ["expire" => $lifetime]);
		session($config);
		var_dump(ini_get("session.cookie_lifetime"));
		exit;
		var_dump(1111111);
		exit;
		$a = json_decode("", true);
		var_dump($a);
		exit;
		var_dump(json_encode($params["login_register_custom_require"]));
		exit;
		var_dump(11);
		exit;
		$ip = $_SERVER["SERVER_ADDR"];
		$domain = $_SERVER["HTTP_HOST"];
		$type = "finance";
		$system_license = configuration("system_license");
		$system_token = configuration("system_token");
		$install_version = configuration("update_last_version");
		$data = ["ip" => $ip, "domain" => $domain, "type" => $type, "license" => $system_license, "system_token" => $system_token, "install_version" => $install_version ?? "1.0.0", "token" => config("auth_token"), "installation_path" => CMF_ROOT];
		$url = config("auth_address");
		$result = postRequest($url, $data, "", 90);
		var_dump($result);
		exit;
		changeProductCycle();
		$ids = [2498];
		if (!empty($ids[0])) {
			$hosts = \think\Db::name("host")->whereIn("orderid", $ids)->select()->toArray();
		}
		$ids = [];
		$hosts = \think\Db::name("host")->whereIn("orderid", $ids)->select()->toArray();
		$res = setTicketHandle();
		var_dump($res);
		exit;
		$ids = [];
		$hosts = \think\Db::name("host")->whereIn("orderid", $ids)->select()->toArray();
		var_dump($hosts);
		exit;
		$clients = [1 => "test", 3 => "adf", 5 => "asdf", 8 => "asdfsaf"];
		var_dump(array_values($clients));
		exit;
		$res = ticketReplyDeliver(237);
		var_dump($res);
		exit;
		$email_logic = new \app\common\logic\Email();
		$email_logic->sendEmailBind("132456@qq.com", "bind email");
		var_dump(floatval(null));
		exit;
		$a = new class
		{
			private $x = 14234;
			public function log($msg, $var)
			{
				echo $msg . $var;
			}
		};
		$getX = function ($msg, $var) {
			return $this->log($msg, $var);
		};
		echo $getX->call($a, "asdfasdtestt", "testA");
		exit;
		$a->log("teststst");
		exit;
		var_dump(url("index/step4"));
		exit;
		$a = request()->filter();
		var_dump($a);
		exit;
		$length = 12;
		$type = "";
		$rule = [];
		$upper = $rule["rule"]["upper"] ?: 1;
		$lower = $rule["rule"]["lower"] ?: 1;
		$num = $rule["rule"]["num"] ?: 1;
		$special = $rule["rule"]["special"] ?: 0;
		$upper_default = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$lower_default = "abcdefghijklmnopqrstuvwxyz";
		$num_default = "0123456789";
		$special_default = "~@#\$%^&*(){}[]|";
		$arr = [];
		if ($upper) {
			array_push($arr, $upper_default);
		}
		if ($lower) {
			array_push($arr, $lower_default);
		}
		if ($num) {
			array_push($arr, $num_default);
		}
		if ($type != "dcimcloud" && $special) {
			array_push($arr, $special_default);
		}
		$randstr = "";
		$count = count($arr);
		for ($i = 0; $i < $count; $i++) {
			$randstr .= $arr[$i][mt_rand(0, strlen($arr[$i]) - 1)];
		}
		$str = implode("", $arr);
		$len = strlen($str) - 1;
		$randstr2 = "";
		for ($j = 0; $j < $len; $j++) {
			$randstr2 .= $str[mt_rand(0, $len)];
		}
		$randstr2 = substr($randstr2, 0, $length - $count);
		$randstr .= $randstr2;
		return str_shuffle($randstr);
		var_dump($arr);
		exit;
		$a = true;
		if ($a) {
			echo "true";
		}
		echo "false";
		exit;
		$arr = [1, 2, 3, 4, 5, 6];
		if (count($arr) == 0) {
		} else {
			for ($i = 0; $i < count($arr); $i++) {
				echo $arr[$i];
			}
		}
		exit;
		$upgrade_system_logic = new \app\common\logic\UpgradeSystem();
		$last_version = $upgrade_system_logic->getLastVersion();
		var_dump($last_version);
		exit;
		var_dump(date("Y-m-d H:i:s", null));
		exit;
		$title = \think\Db::name("voucher_type")->field("id,title,issue_type")->where("uid", 1)->select()->toArray();
		$title_filter = [];
		foreach ($title as $v) {
			$title_filter[$v["issue_type"]][] = $v;
		}
		var_dump($title_filter);
		exit;
		$type = config("invoice_type_all");
		unset($type["recharge"]);
		unset($type["combine"]);
		var_dump(array_keys($type));
		exit;
		var_dump(date("Y-m-d H:i:s", 0));
		exit;
		$a = $_SERVER["SERVER_PORT"];
		var_dump(\request()->port());
		exit;
		$admin_application = config("database.admin_application") ?? "admin";
		$url = request()->domain() . "/{$admin_application}/async";
		$info = parse_url($url);
		var_dump($info);
		exit;
		$a = json_decode($a, true);
		var_dump($a);
		exit;
		$arr = ["teate13", "teate13sadf", "teate13rdsf", "teate13asdf", "teate13afddffffffffffffasssss"];
		foreach ($arr as &$a) {
			$a = str_replace("a", "b", $a);
		}
		var_dump($arr);
		exit;
		var_dump($accounts);
		$ticket = \think\Db::name("ticket_reply")->where("id", 9)->find();
		$a = htmlspecialchars_decode($ticket["content"], ENT_QUOTES);
		preg_match_all("/<p>(.*?)<\\/p>/", $a, $out);
		if ($out[1][0]) {
			$a = $out[1][0];
			$a = preg_replace("/<\\s*img\\s+[^>]*?src\\s*=\\s*('|\\\")(.*?)\\1[^>]*?\\/?\\s*>/i", "", $a);
		}
		var_dump($a);
		exit;
		var_dump($out[1][0]);
		exit;
		$phone = "18423457945";
		if (strlen($phone) > 11) {
			$phone = mb_substr($phone, 2);
		}
		var_dump($phone);
		exit;
		$need_sub_id_filter = [];
		$result_sub_id = [3, 5];
		$need_sub_id = [1, 2, 3, 4, 5, 6];
		foreach ($result_sub_id as $mmm) {
			foreach ($need_sub_id as $kkk => $vvv) {
				if ($mmm == $vvv) {
					$need_sub_id = [$vvv];
				}
			}
		}
		var_dump($need_sub_id);
		exit;
		$vserverid = \think\Db::name("customfields")->alias("a")->leftJoin("customfieldsvalues b", "a.id = b.fieldid")->where("a.type", "product")->where("a.relid", 491)->where("a.fieldname", "vserverid")->where("b.relid", 1753)->value("b.value");
		$params["customfields"]["vserverid"] = $vserverid;
		var_dump($params);
		exit;
		var_dump(gethostbyname($_SERVER["SERVER_NAME"]));
		exit;
		$a = extension_loaded("ionCube Loader");
		var_dump(1111);
		exit;
		$arr = [255230 => ["qty_minimum" => 0, "qty_maximum" => 0], 255229 => ["qty_minimum" => 0, "qty_maximum" => 0]];
		ksort($arr);
		var_dump(json_encode($arr));
		exit;
		$uniq_arr = [["config_id" => "41440", "relation" => "eq", "sub_id" => "255230"], ["config_id" => 41440, "relation" => "eq", "sub_id" => "255230"]];
		$arr = array_unique($uniq_arr);
		var_dump($arr);
		exit;
		$a = [11243, 12433, 442, 2424];
		natsort($a);
		var_dump($a);
		exit;
		var_dump(PATHINFO_FILENAME);
		exit;
		$a["a"] = 1324;
		var_dump($a["a"] = false);
		var_dump($a["a"]);
		exit;
		var_dump((new \wuyuhua\test\wuyuhua())->testwyh());
		exit;
		$class = "think\\cap";
		$a = strtr($class, "\\", DIRECTORY_SEPARATOR) . ".php";
		var_dump(DIRECTORY_SEPARATOR);
		exit;
		$row["id"] = 801352;
		$row["suffix"] = 1;
		$row["id"] = $row["id"] . "a" . intval($row["suffix"] + 1);
		$str = explode("a", $row["id"])[0];
		var_dump($str);
		exit;
		var_dump("success");
		exit;
		var_dump("success");
		exit;
		$streamok = function_exists("stream_socket_client");
		var_dump($streamok);
		exit;
		$a = \think\facade\Log::getLog();
		var_dump($a);
		exit;
		\think\Db::name("clients")->where("email", "like", "%default.email%")->update(["email" => ""]);
		var_dump("success");
		exit;
		$ids = [1, 2, 3, 4];
		$a = array_pop($ids);
		var_dump($a);
		exit;
		$pl = new \app\admin\model\PluginModel();
		$a = $pl->customInit();
		var_dump($a);
		exit;
		$page = 11;
		$limit = 10;
		$arr = [];
		for ($i = 1; $i <= 101; $i++) {
			$arr[] = ["id" => $i];
		}
		$offset = ($page - 1) * $limit;
		$length = $limit;
		$arr_filter = array_slice($arr, $offset, $length);
		var_dump($arr_filter);
		exit;
		$hids = \think\Db::name("host_config_options")->distinct(true)->column("relid");
		$cids = \think\Db::name("host_config_options")->distinct(true)->column("configid");
		foreach ($hids as $hid) {
			foreach ($cids as $cid) {
				$count = \think\Db::name("host_config_options")->where("relid", $hid)->where("configid", $cid)->count();
				if ($count > 1) {
					$config = \think\Db::name("host_config_options")->where("relid", $hid)->where("configid", $cid)->find();
					\think\Db::name("host_config_options")->where("relid", $hid)->where("configid", $cid)->delete();
					\think\Db::name("host_config_options")->insert($config);
				}
			}
		}
		$arr1 = [["relid" => 1, "configid" => 1, "optionid" => 1, "upstream_oid" => 1, "upstream_subid" => 1], ["relid" => 2, "configid" => 1, "optionid" => 1, "upstream_oid" => 1, "upstream_subid" => 1], ["relid" => 2, "configid" => 2, "optionid" => 2, "upstream_oid" => 2, "upstream_subid" => 2], ["relid" => 3, "configid" => 3, "optionid" => 3, "upstream_oid" => 3, "upstream_subid" => 3]];
		$arr2 = [["upstream_id" => 1], ["upstream_id" => 2]];
		$arr3 = [["upstream_id" => 1], ["upstream_id" => 2]];
		foreach ($arr2 as $k2 => $v2) {
			foreach ($arr3 as $k3 => $v3) {
				foreach ($arr1 as $k => $v) {
					if ($v["upstream_oid"] == $v2["upstream_id"] && $v["upstream_subid"] == $v3["upstream_id"]) {
						echo "success" . PHP_EOL;
						unset($arr1[$k]);
					}
				}
			}
		}
		var_dump($arr1);
		exit;
		$cmd = "cd " . CMF_ROOT . " && php think create";
		$a = shell_exec($cmd);
		var_dump($a);
		exit;
		var_dump(randStrToPass(6, 1));
		exit;
		var_dump(randCode(12, -1));
		exit;
		$str = randCode(12, -1);
		$b = preg_match("/[\\x{4e00}-\\x{9fa5}]+/u", $str);
		$a = preg_match("/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\\W_]).{8,}/", $str);
		var_dump($a);
		exit;
		var_dump(gateway_list()[0]["name"]);
		exit;
		$day = configuration("cron_invoice_unpaid_email");
		$start = strtotime(date("Y-m-d")) + $day * 24 * 3600;
		$end = strtotime(date("Y-m-d")) + $day * 24 * 3600 + 86400 - 1;
		var_dump(date("Y-m-d H:i:s", $start), date("Y-m-d H:i:s", $end));
		exit;
		debug_zval_dump($a);
		exit;
		debug_zval_dump("atte");
		exit;
		$name = \think\Db::name("plugin")->where("id", 57)->value("name");
		$new = gateway_list()[0]["name"] ?? "";
		\think\Db::name("clients")->where("defaultgateway", $name)->update(["defaultgateway" => $new]);
		var_dump(gateway_list()[0]["name"]);
		exit;
		$a = \think\Db::name("host")->where("productid", 1)->column("id");
		var_dump($a);
		exit;
		$a = cmf_plugin_url("UserAction://Index/index?id=28");
		var_dump($a);
		exit;
		phpinfo();
		exit;
		var_dump(extension_loaded("Zend OPcache"));
		exit;
		$a = cmf_parse_name("aliPay", 0);
		var_dump($a);
		exit;
		var_dump($res);
		exit;
		$a = base64EncodeImage(CMF_ROOT . "modules/gateways/" . cmf_parse_name("Paypal", 0) . "/Paypal.png");
		var_dump($a);
		exit;
		$res = \think\Db::name("clients")->where("id", 28)->find();
		var_dump($res);
		exit;
		$logic = new \app\common\logic\Developer(5);
		$res = $logic->getSpellTotal(0, 0);
		$res = $logic->getDeveloperSellCount(5, 297);
		var_dump($res);
		exit;
		$a = [1 => 20, 51 => 100, 21 => 50];
		ksort($a);
		$key = array_keys($a);
		$value = array_values($a);
		$flag = true;
		for ($i = 0; $i < count($a) - 1; $i++) {
			if ($key[$i + 1] < $value[$i]) {
				$flag = false;
			}
		}
		var_dump($flag);
		exit;
		$upgrade = new \app\common\logic\UpgradeSystem();
		var_dump(get_class($upgrade));
		exit;
		$reflect = new \ReflectionObject($upgrade);
		$props = $reflect->getProperties();
		foreach ($props as $v) {
			echo $v->getName() . "\n";
		}
		$m = $reflect->getMethods();
		foreach ($m as $vv) {
			echo $vv->getName() . "\n";
		}
		exit;
		var_dump($props);
		exit;
		var_dump(quantityStagePrice(2628, 1, 30, "monthly"));
		exit;
		var_dump(1);
		exit;
		$obj = \think\Db::init(["type" => "mysql"]);
		var_dump($obj);
		exit;
		return json(["status" => 200, "msg" => "不要乱来!"]);
		var_dump(CMF_ROOT . "modules/gateways/user_custom");
		exit;
		$logo_url = configuration("logo_url");
		if (strpos($logo_url, configuration("domain")) === false) {
			$logo_url = configuration("domain") . $logo_url;
		}
		var_dump($logo_url);
		exit;
		$cart_logic = new \app\common\logic\Cart();
		$res = $cart_logic->getProductDefaultConfigPrice(132, 1, "monthly");
		return json($res);
		$sms = new \app\common\logic\Sms();
		$client = check_type_is_use(1, 277, $sms);
		var_dump($client);
		exit;
		$rows = \think\Db::name("orders")->alias("o")->join("clients c", "o.uid=c.id")->join("currencies cu", "cu.id = c.currency")->leftJoin("user u", "u.id = c.sale_id")->field("c.username,o.id,o.uid,o.status,o.ordernum,o.create_time,o.invoiceid,o.amount,o.payment,cu.prefix,cu.suffix,u.user_nickname")->select()->toArray();
		var_dump($rows);
		exit;
		p($request);
		$name = [];
		$c = ["name" => "张三", "age" => 18];
		extract($c);
		p($name);
		list($a, $b) = ["name" => "张三", "age" => 18];
		list($a, $b) = [2, 3];
		dump($a);
		dump($b);
		exit;
		$promo = new \app\common\model\PromoCodeModel();
		$res = $promo::select();
		return json($res);
		exit;
		echo strtolower(preg_replace("/(?<=[a-z])([A-Z])/", "_\$1", "fooBar"));
		echo "<br>";
		echo strtolower(preg_replace("/(?<=[a-z])([A-Z])/", "_\$1", "foo"));
		echo "<br>";
		echo strtolower(preg_replace("/(?<=[a-z])([A-Z])/", "_\$1", "fooBarB"));
		echo "<br>";
	}
	public function creditLimitInvoice($config)
	{
		if ($config["cron_credit_limit_invoice_unpaid_email"] > 0) {
			$day = $config["cron_credit_limit_invoice_unpaid_email"];
			$host = \think\Db::name("invoices")->alias("b")->field("b.id,c.email,c.phone_code,c.phonenumber,b.total,d.suffix,b.uid")->leftJoin("clients c", "b.uid=c.id")->leftJoin("currencies d", "d.id = c.currency")->withAttr("total", function ($value, $data) {
				return $value . $data["suffix"];
			})->where("b.status", "Unpaid")->where("b.due_time", ">=", strtotime(date("Y-m-d")) + $day * 24 * 3600)->where("b.due_time", "<=", strtotime(date("Y-m-d")) + $day * 24 * 3600 + 86400 - 1)->where("b.delete_time", 0)->where("b.type", "credit_limit")->select()->toArray();
			if (!empty($host[0])) {
				foreach ($host as $vv) {
					if (!cancelRequest($vv["id"])) {
						$email = new \app\common\logic\Email();
						$result = $email->sendEmailBase($vv["id"], "信用额账单已生成", "invoice", true);
						$message_template_type = array_column(config("message_template_type"), "id", "name");
						$tmp = \think\Db::name("invoices")->field("id,total")->where("id", $vv["id"])->find();
						$sms = new \app\common\logic\Sms();
						$client = check_type_is_use($message_template_type[strtolower("credit_limit_invoice_notice")], $vv["uid"], $sms);
						if ($client) {
							$params = ["invoiceid" => $vv["id"], "total" => $vv["total"]];
							$sms->sendSms($message_template_type[strtolower("credit_limit_invoice_notice")], $client["phone_code"] . $client["phonenumber"], $params, false, $vv["uid"]);
						}
						if ($result) {
							$this->ad_log("信用额账单未付款提醒", "invoice", "发送邮件成功");
							active_log("信用额账单未付款提醒 -  User ID:" . $vv["uid"] . "发送邮件成功", $vv["uid"]);
						} else {
							$this->ad_log("信用额账单未付款提醒", "invoice", "发送邮件失败");
							active_log("信用额账单未付款提醒 -  User ID:" . $vv["uid"] . "发送邮件失败", $vv["uid"]);
						}
					}
				}
			}
		}
		if ($config["cron_credit_limit_invoice_third_overdue_email"] > 0) {
			$this->creditLimitInvoiceDueSend($config, 2);
		}
		if ($config["cron_credit_limit_invoice_second_overdue_email"] > 0) {
			$this->creditLimitInvoiceDueSend($config, 1);
		}
		if ($config["cron_credit_limit_invoice_first_overdue_email"] > 0) {
			$this->creditLimitInvoiceDueSend($config, 0);
		}
	}
	private function creditLimitInvoiceDueSend($config, $times = 0)
	{
		if ($times == 0) {
			$str = "first";
		} elseif ($times == 1) {
			$str = "second";
		} else {
			$str = "third";
		}
		$day = $config["cron_credit_limit_invoice_{$str}_overdue_email"];
		$before_day_start_time = strtotime("-{$day} days", strtotime(date("Y-m-d")));
		$before_day_end_time = strtotime("+1 days -1 seconds", $before_day_start_time);
		$host = \think\Db::name("invoices")->alias("b")->field("b.id,c.email,c.phone_code,c.phonenumber,b.total,d.suffix,b.uid")->leftJoin("clients c", "b.uid=c.id")->leftJoin("currencies d", "d.id = c.currency")->withAttr("total", function ($value, $data) {
			return $value . $data["suffix"];
		})->where("b.status", "Unpaid")->where("b.due_time", "<=", $before_day_end_time)->where("b.due_email_times", $times)->where("b.delete_time", 0)->where("b.type", "credit_limit")->select()->toArray();
		if (!empty($host[0])) {
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$hostids = [];
			foreach ($host as $v) {
				$email = new \app\common\logic\Email();
				$result = $email->sendEmailBase($v["id"], "信用额账单逾期提醒", "credit_limit", true);
				$sms = new \app\common\logic\Sms();
				$client = check_type_is_use($message_template_type[strtolower("credit_limit_invoice_payment_reminder")], $v["uid"], $sms);
				if ($client) {
					$params = ["invoiceid" => $v["id"], "total" => $v["total"]];
					$sms->sendSms($message_template_type[strtolower("credit_limit_invoice_payment_reminder")], $client["phone_code"] . $client["phonenumber"], $params, false, $v["uid"]);
				}
				if ($result) {
					$this->ad_log("信用额账单逾期提醒", "invoice", "逾期账单" . $v["id"] . "第" . ($times + 1) . "次邮件提醒成功");
					active_log("信用额账单逾期提醒 - User ID:" . $v["uid"] . "逾期账单#Invoice ID:" . $v["id"] . "第" . ($times + 1) . "次邮件提醒成功", $v["uid"]);
					\think\Db::name("invoices")->where("id", $v["id"])->where("delete_time", 0)->where("due_email_times", $times)->setInc("due_email_times");
				} else {
					$this->ad_log("信用额账单逾期提醒", "invoice", "逾期账单" . $v["id"] . "第" . ($times + 1) . "次邮件提醒失败");
					active_log("信用额账单逾期提醒 - User ID:" . $v["uid"] . "逾期账单#Invoice ID:" . $v["id"] . "第" . ($times + 1) . "次邮件提醒失败", $v["uid"]);
				}
				var_dump($result);
				exit;
			}
		}
	}
	private function generateRepaymentBill()
	{
		if (cache("?generate_repayment_bill")) {
			return false;
		}
		$year = \intval(date("Y"));
		$month = \intval(date("m"));
		$day = \intval(date("d"));
		$days = date("t", strtotime($year . "-" . $month));
		if ($day == $days) {
			if ($day == 28) {
				$whereIn = [28, 29, 30, 31];
			} elseif ($day == 29) {
				$whereIn = [29, 30, 31];
			} elseif ($day == 30) {
				$whereIn = [30, 31];
			} else {
				$whereIn = [31];
			}
		} else {
			$whereIn = [$day];
		}
		$clients = \think\Db::name("clients")->field("id")->whereIn("bill_generation_date", $whereIn)->select()->toArray();
		if (count($clients) == 0) {
			return false;
		}
		$invoice = new \app\common\logic\Invoices();
		foreach ($clients as $key => $value) {
			$invoice->createCreditLimit($value["id"]);
		}
	}
	private function getCronConfig()
	{
		$cron_config = config("cron_config");
		$keys = array_keys($cron_config);
		$keys[] = "auto_pay_renew";
		$config = getConfig($keys);
		$config = array_merge($cron_config, $config);
		return $config;
	}
	private function ad_log($name = "", $method = "", $value = "")
	{
		$idata = ["name" => $name, "method" => $method, "value" => $value, "create_time" => time()];
		$id = \think\Db::name("cron_log")->insertGetId($idata);
		return $id;
	}
	/**
	 * xml转数组
	 * @xml xml代码
	 * @param  
	 */
	private function _xmlToJson($xml = "")
	{
		$arr = json_decode(json_encode(simplexml_load_string($xml)), true);
		return $this->_emptyArray($arr);
	}
	/**
	 * xml转数组，xml值为空时转成一维数组
	 * @xml xml代码
	 * @param  
	 */
	private function _emptyArray($array = [])
	{
		if (is_array($array)) {
			foreach ($array as $k => $v) {
				if (is_array($v) && count($v) == 0) {
					$array[$k] = "";
				} else {
					if (is_array($v) && count($v) > 0) {
						$array[$k] = $this->_emptyArray($v);
					}
				}
			}
		}
		return $array;
	}
	public function localPost($url, $data, $proxy = null, $timeout = 10, $cookiename = "", $header = [])
	{
		if (!$url) {
			return false;
		}
		if ($data) {
			$data = http_build_query($data);
		}
		$ssl = substr($url, 0, 8) == "https://" ? true : false;
		$curl = curl_init();
		if (!is_null($proxy)) {
			curl_setopt($curl, CURLOPT_PROXY, $proxy);
		}
		curl_setopt($curl, CURLOPT_URL, $url);
		if ($ssl) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		}
		if (!empty($header)) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		}
		$cookie_file = dirname(__FILE__) . "/" . $cookiename;
		curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
		$content = curl_exec($curl);
		$curl_errno = curl_errno($curl);
		curl_close($curl);
		if ($curl_errno > 0) {
			return false;
		}
		return $content;
	}
	public function localJson($url, $data = null, $json = false, $timeout = 30, $cookiename = "", $header = [])
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		$ssl = substr($url, 0, 8) == "https://" ? true : false;
		if ($ssl) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		}
		if (!empty($header)) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		}
		$cookie_file = dirname(__FILE__) . "/" . $cookiename;
		curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
		if (!empty($data)) {
			if ($json && is_array($data)) {
				$data = json_encode($data);
			}
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			if ($json) {
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type:application/json;charset=utf-8", "Content-Length:" . strlen($data)]);
			}
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
		$res = curl_exec($curl);
		$errorno = curl_errno($curl);
		curl_close($curl);
		if ($errorno) {
			return ["errorno" => false, "errmsg" => $errorno];
		}
		return json_decode($res, true);
	}
	public function localGet($url, $proxy = null, $timeout = 10, $cookiename = "", $header = [])
	{
		if (!$url) {
			return false;
		}
		$ssl = substr($url, 0, 8) == "https://" ? true : false;
		$curl = curl_init();
		if (!is_null($proxy)) {
			curl_setopt($curl, CURLOPT_PROXY, $proxy);
		}
		curl_setopt($curl, CURLOPT_URL, $url);
		if ($ssl) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
		}
		if (!empty($header)) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		}
		$cookie_file = dirname(__FILE__) . "/" . $cookiename;
		curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
		$content = curl_exec($curl);
		$curl_errno = curl_errno($curl);
		curl_close($curl);
		if ($curl_errno > 0) {
			return false;
		}
		return $content;
	}
}