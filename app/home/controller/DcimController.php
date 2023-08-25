<?php

namespace app\home\controller;

/**
 * @title 前台对接DCIM管理
 * @description 接口说明：前台产品功能及接口
 */
class DcimController extends CommonController
{
	public function initialize()
	{
		parent::initialize();
		if (!intval(request()->is_api)) {
			$action = request()->action();
			if ($action == "hardoff") {
				$action = "hard_off";
			} elseif ($action == "hardreboot") {
				$action = "hard_reboot";
			} elseif ($action == "crackpass") {
				$action = "crack_pass";
			}
			$client = \think\Db::name("clients")->where("id", request()->uid)->find();
			$mobile = $client["phonenumber"];
			$email = $client["email"];
			if (isSecondVerify($action)) {
				$action = cmf_parse_name($action, 0);
				$code = request()->code;
				if (empty($code)) {
					echo json_encode(["status" => 400, "msg" => lang("DCIM_CODE_REQUIRE")]);
					exit;
				}
				if ($code != cache($action . "_" . $mobile) && $code != cache($action . "_" . $email)) {
					echo json_encode(["status" => 400, "msg" => "验证码错误"]);
					exit;
				}
			}
			cache($action . "_" . $mobile, null);
			cache($action . "_" . $email, null);
		}
		if (request()->id) {
			$is_certifi = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", request()->uid)->where("a.id", intval(request()->id))->value("c.is_certifi");
		} else {
			$is_certifi = "";
		}
		$is_certifi = json_decode($is_certifi, true) ?: [];
		if (!empty($is_certifi)) {
			$action = request()->action();
			if ($action == "hardoff") {
				$action = "hard_off";
			} elseif ($action == "hardreboot") {
				$action = "hard_reboot";
			} elseif ($action == "crackpass") {
				$action = "crack_pass";
			}
			if ($is_certifi[$action] == 1 && !checkCertify(request()->uid)) {
				echo json_encode(["status" => 400, "msg" => lang("DCIM_CHECK_CERTIFY_ERROR")]);
				exit;
			}
		}
	}
	/**
	 * @time 2020-05-15
	 * @title 购买流量包生成账单
	 * @description 购买流量包生成账单
	 * @url /dcim/buy_flow_packet
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:产品ID
	 * @param   .name:fid type:int require:1 desc:流量包ID
	 * @return  invoiceid:账单ID
	 */
	public function buyFlowPacket(\think\Request $request)
	{
		$id = input("post.id", 0, "intval");
		$fid = input("post.fid", 0, "intval");
		$uid = $request->uid;
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.dcimid,a.serverid,a.bwlimit,b.api_type,b.zjmf_api_id,b.upstream_price_type,b.upstream_price_value,b.name as productname,a.domain,a.dedicatedip")->leftJoin("products b", "a.productid=b.id")->where("a.uid", $uid)->where("a.id", $id)->whereIn("a.domainstatus", ["Active", "Suspended"])->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		if ($host["api_type"] == "zjmf_api") {
			$upstream_header = zjmfCurl($host["zjmf_api_id"], "/host/header", ["host_id" => $host["dcimid"]], 30, "GET");
			if ($upstream_header["status"] == 400) {
				$result["status"] = 400;
				$result["msg"] = lang("DCIM_GET_UPSTREAM_HEADER_ERROR");
				return json($result);
			}
			$flow_packet = [];
			foreach ($upstream_header["data"]["dcim"]["flowpacket"] as $v) {
				if ($v["id"] == $fid) {
					$flow_packet = $v;
					break;
				}
			}
			if (empty($flow_packet)) {
				$result["status"] = 400;
				$result["msg"] = lang("DCIM_GET_UPSTREAM_HEADER_ERROR");
				return json($result);
			}
			if ($flow_packet["leave"] == 0) {
				$result["status"] = 400;
				$result["msg"] = lang("DCIM_FLOW_PACKET_LEAVE_ERROR");
				return json($result);
			}
			if ($host["upstream_price_type"] == "percent") {
				$flow_packet["price"] = round($flow_packet["price"] * $host["upstream_price_value"] / 100, 2);
			}
		} else {
			if ($host["bwlimit"] == 0) {
				$result["status"] = 400;
				$result["msg"] = lang("DCIM_HOST_BWLIMIT");
				return json($result);
			}
			$flow_packet = \think\Db::name("dcim_flow_packet")->where("id", $fid)->where("status", 1)->whereRaw("FIND_IN_SET('{$host["productid"]}', allow_products)")->find();
			if (empty($flow_packet)) {
				$result["status"] = 400;
				$result["msg"] = lang("DCIM_GET_UPSTREAM_HEADER_ERROR");
				return json($result);
			}
			if ($flow_packet["stock"] > 0 && $flow_packet["sale_times"] >= $flow_packet["stock"]) {
				$result["status"] = 400;
				$result["msg"] = lang("DCIM_FLOW_PACKET_LEAVE_ERROR");
				return json($result);
			}
		}
		$invoice_data = ["uid" => $uid, "price" => $flow_packet["price"], "relid" => $id, "description" => "流量包订购，大小：" . $flow_packet["capacity"] . "Gb;" . $host["productname"] . "(" . $host["domain"] . "),IP({$host["dedicatedip"]})", "type" => "zjmf_flow_packet"];
		$r = add_custom_invoice($invoice_data);
		if ($r["status"] != 200) {
			return json($r);
		}
		$invoiceid = $r["invoiceid"];
		$data = ["uid" => $uid, "relid" => $fid, "name" => $flow_packet["name"], "price" => $flow_packet["price"], "status" => 0, "create_time" => time(), "capacity" => $flow_packet["capacity"], "invoiceid" => $invoiceid, "type" => "flow_packet", "hostid" => $id];
		$record = \think\Db::name("dcim_buy_record")->insertGetId($data);
		if ($record) {
			active_log_final(sprintf($this->lang["Dcim_home_buyFlowPacket"], $flow_packet["name"], $flow_packet["capacity"], $id, $invoiceid), $uid, 2, $id, 2);
			hook("flow_packet_invoice_create", ["invoiceid" => $invoiceid, "hostid" => $id, "price" => $flow_packet["price"], "name" => $flow_packet["name"], "capacity" => $flow_packet["capacity"], "flowpacketid" => $fid]);
			$result["status"] = 200;
			$result["msg"] = lang("DCIM_MAKE_PAY_SUCCESS");
			$result["data"]["invoiceid"] = $invoiceid;
		} else {
			$result["status"] = 400;
			$result["msg"] = lang("DCIM_MAKE_PAY_SUCCESS_ERROR", [$invoiceid]);
		}
		return json($result);
	}
	/**
	 * @time 2020-05-15
	 * @title 购买重装次数生成账单
	 * @description 购买重装次数生成账单
	 * @url /dcim/buy_reinstall_times
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:产品ID
	 * @return  invoiceid:账单ID
	 */
	public function buyReinstallTimes(\think\Request $request)
	{
		$id = input("post.id", 0, "intval");
		$uid = $request->uid;
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.dcimid,a.serverid,a.reinstall_info,b.type,b.api_type,b.zjmf_api_id,b.upstream_price_type,b.upstream_price_value,b.config_option1,c.reinstall_times,c.buy_times,c.reinstall_price,c.auth")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $uid)->where("a.id", $id)->whereIn("b.type", "dcim,dcimcloud")->where("a.domainstatus", "Active")->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		$buy = false;
		if ($host["api_type"] == "zjmf_api") {
			$res = zjmfCurl($host["zjmf_api_id"], "/dcim/check_reinstall", ["id" => $host["dcimid"]]);
			if ($res["status"] == 400 && $res["price"] > 0) {
				$buy = true;
				if ($host["upstream_price_type"] == "percent") {
					$host["reinstall_price"] = round($res["price"] * $host["upstream_price_value"] / 100, 2);
				} else {
					$host["reinstall_price"] = $res["price"];
				}
			} else {
				$result["status"] = 400;
				$result["msg"] = "不能购买次数";
				return json($result);
			}
		} else {
			if ($host["buy_times"] == 0 || $host["reinstall_price"] < 0.01) {
				$result["status"] = 400;
				$result["msg"] = "不能购买次数";
				return json($result);
			}
			if ($host["reinstall_times"] == 0) {
				$result["status"] = 400;
				$result["msg"] = "不需要购买次数";
				return json($result);
			}
			$reinstall_info = json_decode($host["reinstall_info"], true);
			$num = $reinstall_info["num"] ?? 0;
			if (empty($reinstall_info) || strtotime("this week Monday") > strtotime($reinstall_info["date"])) {
				$num = 0;
			}
			if ($host["buy_times"] == 1) {
				$buy_times = get_buy_reinstall_times($uid, $id);
			} else {
				$buy_times = 0;
			}
			$buy = $host["reinstall_times"] > 0 && $host["reinstall_times"] + $buy_times <= $num;
		}
		if ($buy) {
			$invoice_data = ["uid" => $uid, "price" => $host["reinstall_price"], "relid" => $id, "description" => "购买重装次数", "type" => "zjmf_reinstall_times"];
			$r = add_custom_invoice($invoice_data);
			if ($r["status"] != 200) {
				return json($r);
			}
			$invoiceid = $r["invoiceid"];
			$data = ["uid" => $uid, "relid" => 0, "name" => "重装次数", "price" => $host["reinstall_price"], "status" => 0, "create_time" => time(), "capacity" => 1, "invoiceid" => $invoiceid, "type" => "reinstall_times", "hostid" => $id];
			$record = \think\Db::name("dcim_buy_record")->insertGetId($data);
			if ($record) {
				active_log_final(sprintf($this->lang["Dcim_home_buyReinstallTimes"], $id, $invoiceid), $uid, 2, $id, 2);
				$result["status"] = 200;
				$result["msg"] = "生成支付账单成功，请前往支付";
				$result["data"]["invoiceid"] = $invoiceid;
			} else {
				$result["status"] = 400;
				$result["msg"] = "购买重装次数错误，请联系客服，不要支付生成的账单，ID为：" . $invoiceid;
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = "不需要购买次数";
		}
		return json($result);
	}
	/**
	 * @time 2020-05-16
	 * @title 验证是否可以重装
	 * @description 验证是否可以重装
	 * @url /dcim/check_reinstall
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:产品ID
	 * @return  num:本周已重装次数
	 * @return  max_times:最大重装次数(0不限)
	 * @return  price:重装次数价格(返回该参数说明已达上限并且可以购买重装次数)
	 */
	public function checkReinstall(\think\Request $request)
	{
		$id = input("post.id", 0, "intval");
		$uid = $request->uid;
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.serverid,a.reinstall_info,a.dcimid,b.type,b.api_type,b.zjmf_api_id,b.upstream_price_type,b.upstream_price_value,b.config_option1,c.reinstall_times,c.buy_times,c.reinstall_price,c.auth")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $uid)->where("a.id", $id)->whereIn("b.type", "dcim,dcimcloud")->whereIn("a.domainstatus", ["Active"])->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		if ($host["api_type"] == "zjmf_api") {
			$result = zjmfCurl($host["zjmf_api_id"], "/dcim/check_reinstall", ["id" => $host["dcimid"]]);
			if ($result["status"] == 400 && $result["price"] > 0) {
				if ($host["upstream_price_type"] == "percent") {
					$result["price"] = round($result["price"] * $host["upstream_price_value"] / 100, 2);
				}
			}
		} else {
			if ($host["type"] == "dcim" && $host["config_option1"] != "bms") {
				$auth = json_decode($host["auth"], true);
				if ($auth["reinstall"] != "on") {
					$result["status"] = 403;
					$result["msg"] = "没有权限";
					return json($result);
				}
			}
			if ($host["reinstall_times"] == 0) {
				$result["status"] = 200;
				$result["msg"] = "可以重装";
				$result["max_times"] = 0;
				return json($result);
			}
			$reinstall_info = json_decode($host["reinstall_info"], true);
			$num = $reinstall_info["num"] ?? 0;
			if (empty($reinstall_info) || strtotime("this week Monday") > strtotime($reinstall_info["date"])) {
				$num = 0;
			}
			if ($host["buy_times"] == 1) {
				$buy_times = get_buy_reinstall_times($uid, $id);
			} else {
				$buy_times = 0;
			}
			if ($host["reinstall_times"] > 0 && $host["reinstall_times"] + $buy_times <= $num) {
				if ($host["buy_times"] > 0) {
					$result["status"] = 400;
					$result["msg"] = "可以购买重装次数";
					$result["price"] = $host["reinstall_price"];
				} else {
					$result["status"] = 400;
					$result["msg"] = "本周重装次数已达最大限额，请下周重试或联系技术支持";
				}
				return json($result);
			}
			$result["status"] = 200;
			$result["msg"] = "可以重装";
			$result["num"] = $num;
			$result["max_times"] = $host["reinstall_times"] + $buy_times;
		}
		return json($result);
	}
	/**
	 * @time 2020-05-14
	 * @title 开机
	 * @description 开机
	 * @url /dcim/on
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:产品ID
	 */
	public function on(\think\Request $request)
	{
		$uid = $request->uid;
		$id = input("post.id", 0, "intval");
		$check = check_dcim_auth($id, $uid, "on");
		if ($check["status"] != 200) {
			return json($check);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->on($id);
		return json($result);
	}
	/**
	 * @time 2020-05-14
	 * @title 关机
	 * @description 关机
	 * @url /dcim/off
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:产品ID
	 */
	public function off(\think\Request $request)
	{
		$uid = $request->uid;
		$id = input("post.id", 0, "intval");
		$check = check_dcim_auth($id, $uid, "off");
		if ($check["status"] != 200) {
			return json($check);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->off($id);
		return json($result);
	}
	/**
	 * @time 2020-05-14
	 * @title 重启
	 * @description 重启
	 * @url /dcim/reboot
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 */
	public function reboot(\think\Request $request)
	{
		$uid = $request->uid;
		$id = input("post.id", 0, "intval");
		$check = check_dcim_auth($id, $uid, "reboot");
		if ($check["status"] != 200) {
			return json($check);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->reboot($id);
		return json($result);
	}
	/**
	 * @time 2020-05-15
	 * @title 重置BMC
	 * @description 重置BMC
	 * @url /dcim/bmc
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 */
	public function bmc(\think\Request $request)
	{
		$uid = $request->uid;
		$id = input("post.id", 0, "intval");
		$check = check_dcim_auth($id, $uid, "bmc");
		if ($check["status"] != 200) {
			return json($check);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->bmc($id);
		if ($result["status"] == 400) {
			$result["msg"] = "重置失败";
		}
		return json($result);
	}
	/**
	 * @time 2020-05-15
	 * @title 获取kvm
	 * @description 获取kvm
	 * @url /dcim/kvm
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @return  name:下载的文件名
	 * @return  token:验证标识
	 */
	public function kvm(\think\Request $request)
	{
		$uid = $request->uid;
		$id = input("post.id", 0, "intval");
		$check = check_dcim_auth($id, $uid, "kvm");
		if ($check["status"] != 200) {
			return json($check);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->kvm($id);
		return json($result);
	}
	/**
	 * @time 2020-05-15
	 * @title 获取ikvm
	 * @description 获取ikvm
	 * @url /dcim/ikvm
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @return  name:下载的文件名
	 * @return  token:验证标识
	 */
	public function ikvm(\think\Request $request)
	{
		$uid = $request->uid;
		$id = input("post.id", 0, "intval");
		$check = check_dcim_auth($id, $uid, "ikvm");
		if ($check["status"] != 200) {
			return json($check);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->ikvm($id);
		return json($result);
	}
	/**
	 * @time 2020-05-18
	 * @title 下载java文件
	 * @description 下载java文件
	 * @url /dcim/download
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:name type:string require:1 desc:要下载的文件名
	 * @param   .name:token type:string require:1 desc:验证的表示
	 */
	public function download()
	{
		$token = input("get.token");
		if (empty($token)) {
			return json(["status" => 400, "msg" => "禁止操作"]);
		}
		$token = aesPasswordDecode($token);
		$arr = explode("|", $token);
		if (count($arr) == 2 && $arr[1] == "zjmf" && time() - $arr[0] < 30) {
		} else {
			return json(["status" => 400, "msg" => "禁止操作"]);
		}
		$name = input("get.name");
		$name = str_replace("/", "", $name);
		header("Access-Control-Expose-Headers: Content-disposition");
		$file = UPLOAD_PATH . "common/default/" . $name . ".jnlp";
		if (file_exists($file)) {
			$length = filesize($file);
			$showname = $name . ".jnlp";
			$expire = 1800;
			header("Pragma: public");
			header("Cache-control: max-age=" . $expire);
			header("Expires: " . gmdate("D, d M Y H:i:s", time() + $expire) . "GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . "GMT");
			header("Content-Disposition: attachment; filename=" . $showname);
			header("Content-Length: " . $length);
			header("Content-type: text/x-java-source");
			header("Content-Encoding: none");
			header("Content-Transfer-Encoding: binary");
			readfile($file);
			sleep(2);
			unlink($file);
		} else {
			return \think\Response::create()->code(404);
		}
	}
	/**
	 * @time 2020-05-18
	 * @title 重装系统
	 * @description 重装系统
	 * @url /dcim/reinstall
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:os type:int require:1 desc:操作系统ID
	 * @param   .name:password type:string require:1 desc:密码(六位以上且由大小写字母数字三种组成)
	 * @param   .name:mcon type:int require:0 desc:附加配置ID
	 * @param   .name:action type:int require:1 desc:分区(0默认1附加配置)
	 * @param   .name:port type:int require:1 desc:端口号
	 * @param   .name:part_type type:int require:0 desc:分区类型(windows才有0全盘格式化1第一分区格式化) default:0
	 * @param   .name:disk type:int require:0 desc:磁盘号(从0开始分区为附加配置时不需要) default:0
	 * @param   .name:check_disk_size type:int require:0 desc:是否验证磁盘 default:0
	 * @return   confirm:失败时可能会返回,true弹出确认框取消或者继续安装,继续安装把参数check_disk_size=0和其他原有参数重新发起重装即可
	 * @return   price:重装次数价格(返回该参数说明已达上限并且可以购买重装次数)
	 */
	public function reinstall(\think\Request $request)
	{
		$params = input("post.");
		$id = $params["id"];
		$uid = $request->uid;
		$validate = new \app\common\validate\DcimValidate();
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return json(["status" => 406, "msg" => $validate->getError()]);
		}
		$check = check_dcim_auth($id, $uid, "reinstall");
		if ($check["status"] != 200) {
			return json($check);
		}
		$data = ["rootpass" => $params["password"], "action" => $params["action"], "mos" => $params["os"], "mcon" => $params["mcon"], "port" => $params["port"], "disk" => $params["disk"] ?? 0, "check_disk_size" => $params["check_disk_size"] ?? 0, "part_type" => $params["part_type"] ?? 0];
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = false;
		$result = $dcim->reinstall($id, $data);
		return json($result);
	}
	/**
	 * @time 2020-05-18
	 * @title 获取重装,救援系统,重置密码进度
	 * @description 获取重装,救援系统,重置密码进度
	 * @url /dcim/resintall_status
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @return  disk_check:弹出错误时@
	 * @disk_check  value:disk_part的值
	 * @disk_check  description:描述
	 * @return  error_type:0,1,2,其他(当error_type>0并且progress>=20时弹出磁盘分区错误提示,1Windows磁盘错误,2Windows分区错误,其他Windows磁盘分区提示)
	 * @return  error_msg:当error_type>0时弹出磁盘分区错误提示信息
	 * @return  disk_info:当显示弹出磁盘分区错误提示@
	 * @disk_info  disk:磁盘
	 * @disk_info  part:分区
	 * @disk_info  size:大小
	 * @disk_info  type:类型
	 * @disk_info  windows:类型
	 * @return  progress:进度
	 * @return  windows_finish:是否是windows已完成
	 * @return  hostid:当前产品ID
	 * @return  task_type:类型(0重装系统,1救援系统,2重置密码,3获取硬件信息)
	 * @return  reinstall_msg:重装信息
	 * @return  crackPwd:当有数据返回时,弹出重置密码用户选择@
	 * crackPwd  user:可选择的用户
	 * crackPwd  password:重置的密码
	 * @return  step:当前步骤描述
	 * @return  last_result.act:上次执行操作
	 * @return  last_result.status:上次执行结果(1成功2失败)
	 */
	public function getReinstallStatus(\think\Request $request)
	{
		$uid = $request->uid;
		$id = input("get.id", 0, "intval");
		$host = \think\Db::name("host")->alias("a")->field("a.domainstatus")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $uid)->where("a.id", $id)->where("b.type", "dcim")->where("domainstatus", "Active")->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->reinstallStatus($id);
		return json($result);
	}
	/**
	 * @time 2020-05-18
	 * @title 救援系统
	 * @description 救援系统
	 * @url /dcim/rescue
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:system type:int require:1 desc:操作系统(1Linux2Windows)
	 */
	public function rescue(\think\Request $request)
	{
		$uid = $request->uid;
		$id = input("post.id", 0, "intval");
		$system = input("post.system", 0, "intval");
		$check = check_dcim_auth($id, $uid, "rescue");
		if ($check["status"] != 200) {
			return json($check);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->rescue($id, $system);
		return json($result);
	}
	/**
	 * @time 2020-05-18
	 * @title 重置密码
	 * @description 重置密码
	 * @url /dcim/crack_pass
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:password type:string require:1 desc:密码
	 * @param   .name:other_user type:int require:0 desc:是否重置其他用户(0不是1是) default:0
	 * @param   .name:user type:string require:0 desc:自定义需要重置的用户名(用户名不能包含中文空格@符)
	 * @param   .name:action type:string require:0 desc:获取进度有crackPwd时选择用户后传chooseUser
	 */
	public function crackPass(\think\Request $request)
	{
		$params = input("post.");
		$id = $params["id"];
		$uid = $request->uid;
		$data = ["crack_password" => $params["password"], "other_user" => intval($params["other_user"]), "user" => $params["user"] ?? "", "action" => $params["action"] ?? ""];
		$product = \think\Db::name("host")->alias("a")->field("a.serverid,a.dcimid,a.show_last_act_message,a.uid,b.config_option1,b.api_type,b.zjmf_api_id,b.password,a.productid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.id", $id)->find();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if ($product["api_type"] == "whmcs") {
			$dcimid = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $product["productid"])->where("b.fieldname", "hostid")->value("value");
			$product["dcimid"] = $dcimid;
		}
		if (empty($product["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			return $result;
		}
		$check_pass = (new \app\common\logic\Shop($product["uid"]))->checkHostPassword($data["crack_password"], $product["productid"]);
		if ($check_pass["status"] == 400) {
			return json($check_pass);
		}
		$check = check_dcim_auth($id, $uid, "crack_pass");
		if ($check["status"] != 200) {
			return json($check);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->crackPass($id, $data);
		return json($result);
	}
	/**
	 * @time 2020-05-18
	 * @title 获取用量信息
	 * @description 获取用量信息
	 * @url /dcim/traffic_usage
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:start type:string require:0 desc:开始日期(YYYY-MM-DD)
	 * @param   .name:end type:string require:0 desc:结束日期(YYYY-MM-DD)
	 * @return  0:流量数据@
	 * @0  time:横坐标值
	 * @0  value:纵坐标值(单位Mbps)
	 */
	public function getTrafficUsage(\think\Request $request)
	{
		$id = input("get.id");
		$uid = $request->uid;
		$host = \think\Db::name("host")->alias("a")->field("a.regdate")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $uid)->where("a.id", $id)->where("b.type", "dcim")->whereIn("domainstatus", "Active,Suspended")->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		$end = input("get.end");
		$start = input("get.start");
		$end = strtotime($end) ? date("Y-m-d", strtotime($end)) : date("Y-m-d");
		$start = strtotime($start) ? date("Y-m-d", strtotime($start)) : date("Y-m-d", strtotime("-30 days"));
		if (str_replace("-", "", $start) < str_replace("-", "", date("Y-m-d", $host["regdate"]))) {
			$start = date("Y-m-d", $host["regdate"]);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->getTrafficUsage($id, $start, $end);
		return json($result);
	}
	/**
	 * @time 2020-05-18
	 * @title 取消重装,救援,重置密码
	 * @description 取消重装,救援,重置密码
	 * @url /dcim/cancel_task
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 */
	public function cancelReinstall(\think\Request $request)
	{
		$id = input("post.id");
		$uid = $request->uid;
		$host = \think\Db::name("host")->alias("a")->field("a.regdate")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $uid)->where("a.id", $id)->where("b.type", "dcim")->where("domainstatus", "Active")->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->cancelReinstall($id);
		return json($result);
	}
	/**
	 * @time 2020-05-19
	 * @title 重装解除暂停
	 * @description 重装解除暂停,重装有disk_check时可以调用
	 * @url /dcim/unsuspend_reinstall
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:disk_part type:string require:1 desc:重装返回的disk_part
	 */
	public function unsuspendReload(\think\Request $request)
	{
		$id = input("post.id");
		$uid = $request->uid;
		$host = \think\Db::name("host")->alias("a")->field("a.regdate")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $uid)->where("a.id", $id)->where("b.type", "dcim")->where("domainstatus", "Active")->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->unsuspendReload($id, input("post.disk_part"));
		return json($result);
	}
	/**
	 * @time 2020-05-19
	 * @title 刷新所有电源状态
	 * @description 刷新所有电源状态
	 * @url /dcim/refresh_all_power_status
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param  .name:id type:array require:1 desc:状态为Active的hostID
	 * @return  0:列表数据@
	 * @0  id:hostID
	 * @0  status:状态(on开机off关机error无法连接not_support不支持电源控制)
	 * @0  msg:状态信息描述
	 */
	public function refreshPowerStatus(\think\Request $request)
	{
		$uid = $request->uid;
		$id = input("post.id");
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.dcimid,c.hostname,c.username,c.password,c.secure,c.port,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->leftJoin("servers c", "a.serverid=c.id")->where("a.uid", $uid)->whereIn("a.id", $id)->where("b.type", "dcim")->where("a.domainstatus", "Active")->select()->toArray();
		$result["data"] = [];
		if (!empty($host)) {
			$data = [];
			$zjmf_api = [];
			foreach ($host as $v) {
				if ($v["api_type"] == "zjmf_api") {
					$zjmf_api[$v["zjmf_api_id"]][$v["dcimid"]] = $v["id"];
					continue;
				}
				$protocol = $v["secure"] == 1 ? "https://" : "http://";
				$url = $protocol . $v["hostname"];
				if (!empty($v["port"])) {
					$url .= ":" . $v["port"];
				}
				$data[$v["id"]] = ["url" => $url . "/index.php?m=api&a=ipmiPowerSync", "data" => ["username" => $v["username"], "password" => aesPasswordDecode($v["password"]) ?? "", "id" => $v["dcimid"]]];
			}
			$res = [];
			if (!empty($data)) {
				$res = batch_curl_post($data, 20);
				foreach ($res as $k => $v) {
					$one["id"] = $k;
					if ($v["http_code"] != 200) {
						$one["status"] = "error";
						$one["msg"] = $v["msg"] ?? "获取失败";
					} else {
						if ($v["data"]["status"] == "success") {
							if ($v["data"]["msg"] == "on") {
								$one["status"] = "on";
							} elseif ($v["data"]["msg"] == "off") {
								$one["status"] = "off";
							} else {
								$one["status"] = "error";
							}
						} else {
							if ($v["data"]["msg"] == "nonsupport") {
								$one["status"] = "not_support";
							} else {
								$one["status"] = "error";
							}
						}
						$one["msg"] = $v["data"]["power_msg"] ?? "";
					}
					$result["data"][] = $one;
				}
			}
			if (empty($result["data"])) {
				$result["data"] = [];
			}
			foreach ($zjmf_api as $k => $v) {
				$r = zjmfCurl($k, "/dcim/refresh_all_power_status", ["id" => array_keys($v)]);
				if ($r["status"] == 200) {
					foreach ($r["data"] as $vv) {
						$result["data"][] = ["id" => $v[$vv["id"]], "msg" => $vv["msg"] ?: "获取失败", "status" => $vv["status"]];
					}
				} else {
					foreach ($v as $vv) {
						$result["data"][] = ["id" => $vv, "msg" => "获取失败", "status" => "error"];
					}
				}
			}
		}
		$result["status"] = 200;
		return json($result);
	}
	/**
	 * @time 2020-05-19
	 * @title 获取流量图信息
	 * @description 获取流量图信息
	 * @url /dcim/traffic
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:switch_id type:int require:1 desc:交换机ID
	 * @param   .name:port_name type:string require:1 desc:端口名称
	 * @param   .name:start_time type:int require:0 desc:开始时间(毫秒时间戳)
	 * @param   .name:end_time type:int require:0 desc:结束时间(毫秒时间戳)
	 * @return  unit:流量单位
	 * @return  traffic:流量数据@
	 * @traffic  time:毫秒时间戳
	 * @traffic  value:值
	 * @traffic  type:类型(in进流量,out出流量)
	 */
	public function traffic(\think\Request $request)
	{
		$id = input("post.id");
		$uid = $request->uid;
		$params = input("post.");
		$host = \think\Db::name("host")->alias("a")->field("a.regdate")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $uid)->where("a.id", $id)->where("b.type", "dcim")->where("domainstatus", "Active")->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		$check = check_dcim_auth($id, $uid, "traffic");
		if ($check["status"] != 200) {
			return json($check);
		}
		if (empty($params["end_time"])) {
			$params["end_time"] = time() . "000";
		}
		if (empty($params["start_time"])) {
			$params["start_time"] = strtotime("-7 days") . "000";
		}
		if ($params["start_time"] > $params["end_time"]) {
			$result["status"] = 400;
			$result["msg"] = "开始时间不能晚于结束时间";
		}
		$start_time = date("Ymd", $params["start_time"] / 1000);
		if ($start_time < date("Ymd", $host["regdate"])) {
			$params["start_time"] = $host["regdate"] . "000";
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->traffic($id, $params);
		return json($result);
	}
	/**
	 * @time 2020-05-22
	 * @title novnc
	 * @description novnc
	 * @url /dcim/novnc
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param  .name:id type:int require:1 desc:host ID
	 */
	public function novnc(\think\Request $request)
	{
		$id = input("post.id");
		$restart = input("post.restart", 0, "intval");
		$uid = $request->uid;
		$host = \think\Db::name("host")->alias("a")->field("a.regdate")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $uid)->where("a.id", $id)->where("b.type", "dcim")->where("domainstatus", "Active")->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
		} else {
			$check = check_dcim_auth($id, $uid, "novnc");
			if ($check["status"] != 200) {
				return json($check);
			}
			$dcim = new \app\common\logic\Dcim();
			$result = $dcim->novnc($id, $restart);
		}
		return json($result);
	}
	/**
	 * @time 2020-05-22
	 * @title novnc页面
	 * @description novnc页面
	 * @url /dcim/novnc
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:password type:string require:1 desc:novnc返回的密码
	 * @param   .name:url type:int require:1 desc:novnc返回的url
	 */
	public function novncPage()
	{
		$password = input("get.password");
		$url = input("get.url");
		$url = base64_decode(urldecode($url));
		$host_token = input("get.host_token");
		$type = input("get.type");
		$data = ["url" => $url, "password" => $password, "host_token" => !empty($host_token) ? aesPasswordDecode($host_token) : "", "restart_vnc" => "", "id" => input("get.id", 0, "intval")];
		if (!empty($host_token)) {
			$data["paste_button"] = "<div id=\"pastePassword\">粘贴密码</div>";
		} else {
			$data["paste_button"] = "";
		}
		if ($type == "dcim") {
			$data["restart_vnc"] = "<div id=\"restart_vnc\">强制刷新vnc</div>";
		}
		return view("./vendor/dcim/novnc.html")->assign($data);
	}
	/**
	 * @time 2020-05-19
	 * @title 获取是否在重装
	 * @description 获取是否在重装
	 * @url /dcim/check_all_status
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param  .name:id type:array require:1 desc:状态为Active的hostID
	 * @return  0:列表数据@
	 * @0  :正在重装的hostID
	 */
	public function checkAllReinstallStatus(\think\Request $request)
	{
		$uid = $request->uid;
		$id = input("post.id");
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.dcimid,c.hostname,c.username,c.password,c.secure,c.port")->leftJoin("products b", "a.productid=b.id")->leftJoin("servers c", "a.serverid=c.id")->where("a.uid", $uid)->whereIn("a.id", $id)->where("b.type", "dcim")->where("a.dcimid", ">", 0)->where("a.domainstatus", "Active")->select()->toArray();
		$result["data"] = [];
		if (!empty($host)) {
			$data = [];
			foreach ($host as $v) {
				$protocol = $v["secure"] == 1 ? "https://" : "http://";
				$url = $protocol . $v["hostname"];
				if (!empty($v["port"])) {
					$url .= ":" . $v["port"];
				}
				$data[$v["id"]] = ["url" => $url . "/index.php?m=api&a=getReinstallStatus", "data" => ["username" => $v["username"], "password" => aesPasswordDecode($v["password"]) ?? "", "id" => $v["dcimid"], "hostid" => $v["id"]]];
			}
			$res = batch_curl_post($data, 20);
			foreach ($res as $k => $v) {
				if ($v["data"]["status"] == "success" && $v["http_code"] == 200 && !empty($v["data"]["data"]) && !$v["data"]["data"]["windows_finish"]) {
					$result["data"][] = $k;
				}
			}
		}
		$result["status"] = 200;
		return json($result);
	}
	/**
	 * @time 2020-05-26
	 * @title 获取DCIM产品详情
	 * @description 获取DCIM产品详情
	 * @url /dcim/detail
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @return  switch:交换机数据@
	 * @switch  switch_id:连接的交换机ID
	 * @switch  name:端口名称
	 */
	public function detail(\think\Request $request)
	{
		$id = input("get.id");
		$uid = $request->uid;
		$host = \think\Db::name("host")->alias("a")->field("a.regdate")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $uid)->where("a.id", $id)->where("b.type", "dcim")->where("domainstatus", "Active")->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->detail($id);
		return json($result);
	}
	/**
	 * @time 2020-05-26
	 * @title 隐藏上次重装/重置密码/救援系统结果
	 * @description 隐藏上次重装/重置密码/救援系统结果
	 * @url /dcim/hide_result
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:array require:1 desc:hostID
	 */
	public function hideLastResult(\think\Request $request)
	{
		$id = input("post.id");
		$uid = $request->uid;
		$host = \think\Db::name("host")->alias("a")->field("a.regdate,a.dcimid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $uid)->where("a.id", $id)->where("b.type", "dcim")->where("domainstatus", "Active")->find();
		if (empty($host) || empty($host["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$result = zjmfCurl($host["zjmf_api_id"], "/dcim/hide_result", $post_data);
		} else {
			\think\Db::name("host")->where("id", $id)->update(["show_last_act_message" => 0]);
			$result["status"] = 200;
		}
		return json($result);
	}
	/**
	 * @time 2020-05-29
	 * @title 获取电源状态
	 * @description 获取电源状态
	 * @url /dcim/refresh_power_status
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:hostID
	 * @return  power:电源状态(on开机off关机error无法连接not_support不支持电源控制)
	 * @return  msg:状态信息描述
	 */
	public function refreshServerPowerStatus()
	{
		$id = input("post.id", 0, "intval");
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->refreshPowerStatus($id);
		return json($result);
	}
}