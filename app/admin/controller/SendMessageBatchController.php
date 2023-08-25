<?php

namespace app\admin\controller;

/**
 * @title 营销信息
 * @description 接口说明
 */
class SendMessageBatchController extends GetUserController
{
	public $upload_dir = CMF_ROOT;
	public $progress_log = CMF_ROOT;
	private $attachment_path;
	private $attachment_url;
	private $system_attachment_path;
	private $system_attachment_url;
	protected $type;
	public function initialize()
	{
		parent::initialize();
		$this->send_method = ["email" => "邮件", "mobile" => "手机", "system" => "站内信"];
		$this->attachment_url = $this->request->host() . config("email_url");
		$this->attachment_path = config("email_attachments");
		$this->system_attachment_url = $this->request->host() . config("system_message_url");
		$this->system_attachment_path = config("system_message_attachments");
	}
	/**
	 * @title 获取营销推送筛选条件
	 * @desc 获取营销推送筛选条件
	 * @url /admin/sm_type
	 * @method  get
	 * @return  data -类型
	 * @return  group -客户分组
	 * @return  pgroup -产品分组
	 * @return  sale -销售
	 * @return  country -国家
	 * @return  language -语言
	 * @return  domainstatus -主机状态
	 * @return  api_type -接口类型
	 * @author lgd
	 * @version v1
	 */
	public function getSearchParams()
	{
		$clients = $this->getClientsListAsGroup();
		$sale_list = \think\Db::name("user")->field("id,user_nickname")->where("is_sale", 1)->where("sale_is_use", 1)->select()->toArray();
		$client_status = config("client_status");
		$language = get_language_list();
		$country = getCountryConfig(true);
		$products = $this->showUseableProductInfo(getProductList());
		$domainstatus = config("domainstatus");
		$api_type = $this->getProductInterface();
		$data = ["send_method" => $this->send_method, "country" => $country, "clients" => $clients, "client_status" => $client_status, "products" => $products, "sale" => $sale_list, "api_type" => $api_type, "language" => $language, "domainstatus" => $domainstatus];
		return jsonrule(["status" => 200, "msg" => "成功", "data" => $data]);
	}
	/**
	 * @title 营销信息-推送方式
	 * @desc 推送方式
	 * @url /admin/getSendMethod
	 * @method  get
	 * @version v1
	 */
	public function getSendMethod()
	{
		$method = [];
		foreach ($this->send_method as $key => $item) {
			$temp["name"] = $key;
			$temp["value"] = $item;
			$method[] = $temp;
		}
		$data = ["send_method" => $method];
		return jsonrule(["status" => 200, "msg" => "成功", "data" => $data]);
	}
	/**
	 * 获取接口类型
	 */
	private function getProductInterface()
	{
		$server_interface = \think\Db::name("servers")->field("id,name")->where("disabled", 0)->order("id", "desc")->select()->toArray();
		if ($server_interface) {
			foreach ($server_interface as &$item) {
				$item["id"] = "1_" . $item["id"];
				$item["type"] = "local_interface";
			}
		}
		$DCIMCloud_interface = \think\Db::name("zjmf_finance_api")->field("id,name")->order("id", "desc")->select()->toArray();
		if ($DCIMCloud_interface) {
			foreach ($DCIMCloud_interface as &$d_item) {
				$d_item["id"] = "2_" . $d_item["id"];
				$d_item["type"] = "dcim_interface";
			}
		}
		$server["id"] = 1;
		$server["name"] = "通用接口";
		$server["child"] = $server_interface;
		$DCIMCloud["id"] = 2;
		$DCIMCloud["name"] = "魔方财务API";
		$DCIMCloud["child"] = $DCIMCloud_interface;
		return [$server, $DCIMCloud];
	}
	/**
	 * 查找产品列表，按照产品组 层级,只显示有用字段
	 */
	private function showUseableProductInfo($products = [])
	{
		$list = [];
		if ($products) {
			foreach ($products as $item) {
				$temp["id"] = $item["id"];
				$temp["name"] = $item["name"];
				if (isset($item["product"]) && $item["product"]) {
					$temp["product"] = $this->showUseableProductInfo($item["product"]);
				}
				$list[] = $temp;
			}
		}
		return $list;
	}
	/**
	 * 查找客户列表，无分组用户+分组用户; 把未分组用户以名字命名为【未分组】用户放入到已分组用户列表中去
	 * 搜索所有用户，不管是什么状态的用户：1激活，0未激活，2关闭
	 */
	private function getClientsListAsGroup()
	{
		$no_groups_user = \think\Db::name("clients")->field("id,username as name,email")->where("groupid", 0);
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$no_groups_user->whereIn("id", $this->str);
		}
		$no_groups_user = $no_groups_user->select()->toArray();
		if ($no_groups_user) {
			foreach ($no_groups_user as &$n_item) {
				$n_item["name"] = $n_item["email"] ? $n_item["name"] . " - <" . $n_item["email"] . ">" : $n_item["name"];
			}
		}
		$no_groups_user_arr["id"] = 0;
		$no_groups_user_arr["name"] = "未分组";
		$no_groups_user_arr["client"] = $no_groups_user;
		$list["groups_user"] = [];
		$list["groups_user"][] = $no_groups_user_arr;
		$groups = \think\Db::name("client_groups")->field("id,group_name as name")->select()->toArray();
		foreach ($groups as $k => $v) {
			$client = db("clients")->field("id,username as name,email")->where("groupid", $v["id"]);
			if ($this->user["id"] != 1 && $this->user["is_sale"]) {
				$client->whereIn("id", $this->str);
			}
			$groups[$k]["client"] = $client->select()->toArray();
			foreach ($groups[$k]["client"] as &$item) {
				$item["id"] = $v["id"] . "_" . $item["id"];
				$item["name"] = $item["email"] ? $item["name"] . " - <" . $item["email"] . ">" : $item["name"];
			}
			$list["groups_user"][] = $groups[$k];
		}
		return $list;
	}
	/**
	 * @title 短信模板列表
	 * @description 接口说明:短信模板列表
	 * @author lgd
	 * @url /admin/mobiletemplate_list
	 * @method GET
	 * @param name:send_type type:string require:1 default:clients other: desc:clients-按客户，clients_and_host-按商品
	 * @return  smsoperator:运营商@
	 * @return  templates:模板信息@
	 * @templates  id:ID
	 * @templates  template_id:模板ID(短信运营商提供)
	 * @templates  type:0大陆，1非大陆
	 * @templates  title:模板标题
	 * @templates  content:模板内容
	 * @templates  remark:备注
	 * @templates  status:0未提交审核，1正在审核，2审核通过，3未通过审核
	 * @return default:邮件默认
	 */
	public function mobiletemplateList()
	{
		$pluginModel = new \app\admin\model\PluginModel();
		$plugins = $pluginModel->getList("sms");
		$pluginsFilter = [];
		foreach ($plugins as $k => $v) {
			$sms_type = array_column($v["sms_type"], "name");
			if ($v["status"] == 1 && in_array("cnpro", $sms_type)) {
				$pluginsFilter[] = strtolower($v["name"]);
			}
		}
		$templates = \think\Db::name("message_template")->alias("mt")->field("mt.id,mt.title,mt.template_id,mt.range_type,mt.content,mt.status,mt.sms_operator")->where("mt.status", 2)->where("mt.range_type", 2)->whereIn("mt.sms_operator", $pluginsFilter)->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "templates" => $templates]);
	}
	/**
	 * @title 筛选客户集合
	 * @description 接口说明:筛选客户集合
	 * @param name:send_type type:int require:true default:1 other: desc:发送类型，clients-按客户，clients_and_host-按商品
	 * @param name:client_ids type:array require:false default: other: desc:客户id
	 * @param name:client_status type:array require:false default: other: desc:客户状态
	 * @param name:sale_ids type:array require:false default: other: desc:销售id
	 * @param name:country type:array require:false default: other: desc:国家
	 * @param name:language type:array require:false default: other: desc:语言
	 * @param name:reg_times type:array require:false default: other: desc:注册时间，reg_times[0]范围开始天数，reg_times[1]范围结束天数
	 * @param name:certifi_status type:array require:false default: other: desc:实名状态
	 * @param name:is_bind_phone type:int require:false default: other: desc:绑定手机：0-未绑定，1-绑定
	 * @param name:is_bind_email type:int require:false default: other: desc:绑定邮箱：0-未绑定，1-绑定
	 * @param name:product_ids type:array require:false default: other: desc:产品id
	 * @param name:interface_ids type:array require:false default: other: desc:接口id
	 * @param name:domainstatus type:array require:false default: other: desc:主机状态
	 *
	 * @url /admin/searchlist
	 * @method POST
	 *
	 * @return  id:客户id
	 * @return  username:客户姓名
	 * @return  mobile:客户电话
	 * @return  email:客户邮箱
	 * @return  host_id:主机id (筛选类型为 clients_and_host 时返回)
	 * @return  client_id:主机中的客户id，同id (筛选类型为 clients_and_host 时返回)
	 * @return  host_domain:主机名 (筛选类型为 clients_and_host 时返回)
	 * @return  product_name:产品名 (筛选类型为 clients_and_host 时返回)
	 * @return  productid:产品id (筛选类型为 clients_and_host 时返回)
	 *
	 *
	 * @return  client_count:客户总数
	 */
	public function searchList()
	{
		$params = $data = $this->request->param();
		$params["send_type"] = $params["send_type"] ?? "clients";
		$clients = \think\Db::name("clients")->alias("c")->field("c.id ,c.username,c.phonenumber as mobile,c.email")->where(function (\think\db\Query $query) use($params) {
			if (!empty($params["client_ids"])) {
				$query->where("c.id", "in", $params["client_ids"]);
			}
			if (!empty($params["client_status"])) {
				$query->where("c.status", "in", $params["client_status"]);
			}
			if (!empty($params["sale_ids"])) {
				$query->where("c.sale_id", "in", $params["sale_ids"]);
			}
			if (!empty($params["language"])) {
				$query->where("c.language", "in", $params["language"]);
			}
			if (!empty($params["country"])) {
				$query->where("c.country", "in", $params["country"]);
			}
			if (!empty($params["reg_times"])) {
				if ($params["reg_times"][0] ?? 0) {
					$start_time = strtotime("-" . $params["reg_times"][0] . " days");
					$query->where("c.create_time", "elt", $start_time);
				}
				if ($params["reg_times"][1] ?? 0) {
					$end_time = strtotime("-" . $params["reg_times"][1] . " days");
					$query->where("c.create_time", "egt", $end_time);
				}
			}
			if (!empty($params["is_bind_phone"]) && count($params["is_bind_phone"]) != 2) {
				if (count($params["is_bind_phone"]) != 2) {
					if ($params["is_bind_phone"][0] == 1) {
						$query->where("c.phonenumber", "neq", "");
					} else {
						$query->where("c.phonenumber", "eq", "");
					}
				}
			}
			if (!empty($params["is_bind_email"])) {
				if (count($params["is_bind_email"]) != 2) {
					if ($params["is_bind_email"][0] == 1) {
						$query->where("c.email", "neq", "");
					} else {
						$query->where("c.email", "eq", "");
					}
				}
			}
		});
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$clients->whereIn("c.id", $this->str);
		}
		$clients = $clients->distinct("c.id", true)->select()->toArray();
		foreach ($clients as $cc_key => $cc_client) {
			if (!empty($params["certifi_status"])) {
				if (count($params["certifi_status"]) != 2) {
					if ($params["certifi_status"][0] == 1) {
						if (!checkCertify($cc_client["id"])) {
							unset($clients[$cc_key]);
						}
					} else {
						if (checkCertify($cc_client["id"]) == true) {
							unset($clients[$cc_key]);
						}
					}
				}
			}
		}
		$clients = array_values($clients);
		$search_user_list = [];
		if ($params["send_type"] == "clients") {
			$search_user_list = $clients;
		} elseif ($params["send_type"] == "clients_and_host") {
			$client_ids = array_column($clients, "id");
			$host = \think\Db::name("host")->alias("h")->field("h.id as host_id,h.uid as client_id,h.domain as host_domain,p.name as product_name,h.productid")->join("products p", "p.id = h.productid")->where(function (\think\db\Query $query) use($params) {
				if (!empty($params["product_ids"])) {
					$query->where("h.productid", "in", $params["product_ids"]);
				}
				if (!empty($params["domainstatus"])) {
					$query->where("h.domainstatus", "in", $params["domainstatus"]);
				}
				if (!empty($params["interface_ids"])) {
					$query->where("p.server_group", "in", $params["interface_ids"]);
				}
			})->select()->toArray();
			if (empty($host)) {
				return jsonrule(["status" => 400, "msg" => lang("没有满足条件的发送对象")]);
			}
			foreach ($host as $item) {
				if (in_array($item["client_id"], $client_ids)) {
					foreach ($clients as $client_item) {
						if ($client_item["id"] == $item["client_id"]) {
							$client_item = array_merge($client_item, $item);
							$search_user_list[] = $client_item;
						}
					}
				}
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $search_user_list, "client_count" => count($search_user_list), "send_type" => $params["send_type"]]);
	}
	/**
	 * @title 邮件模板列表
	 * @description 接口说明:邮件模板列表
	 * @param .name:hid type:int require:0 default:1 other: desc:hostid
	 * @return .email_list:邮件列表信息(按type分组显示)
	 * @return .email_list.type:类型
	 * @return .email_list.name:名称
	 * @return .email_list.disabled:0显示默认，1隐藏
	 * @return .email_list.custom:0系统邮件默认，1自定义
	 * @return .default:邮件默认
	 * @author wyh
	 * @url /admin/emailtemplate_list
	 * @method GET
	 */
	public function emailtemplateList()
	{
		$param = $this->request->param();
		$host = \think\Db::name("host")->alias("h")->join("products p", "p.id=h.productid")->where("h.id", $param["hid"])->field("p.type")->find();
		$results = \think\Db::name("email_templates")->field("id,type,disabled,name,custom")->where(function (\think\db\Query $query) {
			$data = $this->request->param();
			if (!empty($data["keyword"])) {
				$keyword = $data["keyword"];
				$query->where("name", "like", "%{$keyword}%");
			}
		})->where("type", "product")->where("language", "")->select();
		foreach ($results as $key => $value) {
			$elist[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $value);
		}
		return jsonrule(["status" => 200, "msg" => "请求成功", "email_list" => $results]);
	}
	/**
	 * @title 营销推送方式下的 邮件模板参数
	 * @description 接口说明: 带发送方式请求
	 * @param name:send_type type:int require:true default:1 other: desc:发送类型，clients-按客户，clients_and_host-按商品
	 * @author wyh
	 * @url /admin/email_template_params
	 * @method GET
	 */
	public function getEmailTemplateParams()
	{
		$params = $this->request->param();
		$send_type = $params["send_type"] ?? "clients";
		$emailarg = new \app\common\logic\Email();
		$emailarg->is_admin = true;
		$args_base = $emailarg->getBaseArg();
		$args_clients = $emailarg->getReplaceArg("general");
		$args_product = [];
		if ($send_type == "clients_and_host") {
			$args_product = $emailarg->getReplaceArg("product");
		}
		if ($args_product) {
			$data[] = ["label" => "args_product", "name" => "产品/服务相关", "list" => $args_product];
		}
		$data[] = ["label" => "args_clients", "name" => "客户相关", "list" => $args_clients];
		$data[] = ["label" => "args_base", "name" => "其他", "list" => $args_base];
		return jsonrule(["status" => 200, "msg" => "请求成功", "data" => $data]);
	}
	/**
	 * @title 邮件模板基本参数
	 * @description 接口说明:邮件模板基本参数
	 * @return .base_args:基础参数
	 * @return .combine:模板类型相关参数
	 * @author wyh
	 * @url /admin/edit_template
	 * @method GET
	 */
	public function editTemplate()
	{
		$params = $this->request->param();
		$emailarg = new \app\common\logic\Email();
		$emailarg->is_admin = true;
		$argsbase = $emailarg->getBaseArg();
		$argsarray["general"] = $emailarg->getReplaceArg("general");
		$argsarray["product"] = $emailarg->getReplaceArg("product");
		$argsarray["invoice"] = $emailarg->getReplaceArg("invoice");
		$argsarray["support"] = $emailarg->getReplaceArg("support");
		$argsarray["notification"] = $emailarg->getReplaceArg("notification");
		return jsonrule(["status" => 200, "msg" => "请求成功", "base_args" => $argsbase, "combine" => $argsarray]);
	}
	/**
	 * @title 批量发送营销信息
	 * @description 接口说明:发送邮件、短信、站内消息
	 * @author wyh
	 * @url /admin/sendmessage_post
	 * @method POST
	 * @param name:send_type type:array require:0 default:clients other: desc:发送类型，clients-按客户，clients_and_host-按商品
	 * @param name:send_mothod type:array require:1 default: other: desc:发送方式，email-邮件，mobile-手机，wechat-微信，system-站内信
	 *
	 * @param name:clients type:array require:1 default: other: desc:用户信息集，<br>send_type=clients，则包含用户基本信息<br>send_type=clients_and_host，则包含用户基本信息和产品信息
	 * /邮件参数
	 * @param name:email_subject type:string require:1 default: other: desc:邮件主题
	 * @param name:email_attachments type:array require:false default: other: desc:邮件附件
	 * @param name:email_message type:string require:1 default: other: desc:邮件内容
	 * /站内信参数
	 * @param name:system_subject type:string require:1 default: other: desc:站内信主题
	 * @param name:system_attachments type:array require:false default: other: desc:站内信附件
	 * @param name:system_message type:string require:1 default: other: desc:站内信内容
	 * /短信参数
	 * @param name:msgid type:int require:true default: other: desc:短信模板ID
	 * //其他参数
	 * @param name:is_market type:int require:false default:0 other: desc:营销信息：0-否，1-是
	 * @param name:repeat_sent type:int require:false default:0 other: desc:重复发送：0-否，1-是
	 * @param name:batch_num type:int require:false default:30 other: desc:批量发送个数
	 * @param name:delay_time type:int require:false default:10 other: desc:间隔时间,秒
	 */
	public function sendMessagePost()
	{
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			$app = [];
		} else {
			$_strcode = _strcode($zjmf_authorize, "DECODE", "zjmf_key_strcode");
			$_strcode = explode("|zjmf|", $_strcode);
			$authkey = "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDg6DKmQVwkQCzKcFYb0BBW7N2f\nI7DqL4MaiT6vibgEzH3EUFuBCRg3cXqCplJlk13PPbKMWMYsrc5cz7+k08kgTpD4\ntevlKOMNhYeXNk5ftZ0b6MAR0u5tiyEiATAjRwTpVmhOHOOh32MMBkf+NNWrZA/n\nzcLRV8GU7+LcJ8AH/QIDAQAB\n-----END PUBLIC KEY-----";
			$pu_key = openssl_pkey_get_public($authkey);
			foreach ($_strcode as $v) {
				openssl_public_decrypt(base64_decode($v), $de, $pu_key);
				$de_str .= $de;
			}
			$auth = json_decode($de_str, true);
			if (time() > $auth["last_license_time"] + 1296000 || ltrim(str_replace("https://", "", str_replace("http://", "", $auth["domain"])), "www.") != ltrim(str_replace("https://", "", str_replace("http://", "", $_SERVER["HTTP_HOST"])), "www.") || $auth["installation_path"] != CMF_ROOT || $auth["license"] != configuration("system_license")) {
				$app = [];
			} else {
				$app = $auth["app"];
			}
		}
		if (!in_array("marketingPush", $app)) {
			return jsonrule(["status" => 400, "msg" => "免费版该功能不可用"]);
		}
		$param = $this->request->param();
		$param["is_market"] = $param["is_market"] ?? 1;
		$param["repeat_sent"] = $param["repeat_sent"] ?? 0;
		$param["batch_num"] = $param["batch_num"] ?? 30;
		$param["delay_time"] = $param["delay_time"] ?? 10;
		$param = $this->handleAttchmentsAddress($param);
		if (isset($param["status"]) && $param["status"] == 400) {
			return jsonrule($param);
		}
		$check_param_result = $this->checkPostSendParam($param);
		if ($check_param_result["status"] == 400) {
			return jsonrule($check_param_result);
		}
		$param = $this->checkFinalSendUser($param);
		if (empty($param["clients"])) {
			$msg_str = "接收的客户未开启营销信息推送开关，请在个人资料中开启后再发送";
			return jsonrule(["status" => 400, "msg" => $msg_str]);
		}
		$delay_time = $param["delay_time"];
		if (in_array("mobile", $param["send_mothod"])) {
			$template = \think\Db::name("message_template")->where("id", $param["msgid"])->find();
			if (empty($template)) {
				return jsonrule(["status" => 400, "msg" => lang("无效的短信模板参数")]);
			}
			if ($template["status"] != 2) {
				return jsonrule(["status" => 400, "msg" => lang("该短信模板未通过审核")]);
			}
		}
		$param["batch_num"] = 30;
		$message["batch_num"] = $param["batch_num"];
		$message["clients"] = $param["clients"];
		$message["delay_time"] = $param["delay_time"];
		$message["is_market"] = $param["is_market"];
		$message["repeat_sent"] = $param["repeat_sent"];
		$message["request_time"] = $param["request_time"];
		$message["send_type"] = $param["send_type"];
		$curl_multi_data = [];
		foreach ($param["send_mothod"] as $send_type_key => $send_type) {
			switch ($send_type) {
				case "email":
					if (configuration("shd_allow_email_send") == 1) {
						$email_message = [];
						$email_message = $message;
						$email_message["email_subject"] = $param["email_subject"] ?? "";
						$email_message["email_attachments"] = $param["email_attachments"] ?? "";
						$email_message["email_message"] = $param["email_message"] ?? "";
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async_email_message", "data" => $email_message];
					}
					break;
				case "mobile":
					if (configuration("shd_allow_sms_send") == 1) {
						$sms_message = [];
						$sms_message = $message;
						$sms_message["msgid"] = $param["msgid"];
						$sms_message["template"] = $template;
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async_sms_message", "data" => $sms_message];
					}
					break;
				case "system":
					$system_message = [];
					$system_message = $message;
					$system_message["system_subject"] = $param["system_subject"] ?? "";
					$system_message["system_attachments"] = $param["system_attachments"] ?? "";
					$system_message["system_message"] = $param["system_message"] ?? "";
					$curl_multi_data[count($curl_multi_data)] = ["url" => "async_system_message", "data" => $system_message];
					break;
				default:
					break;
			}
		}
		if (count($curl_multi_data) > 0) {
			asyncCurlMulti($curl_multi_data);
		}
		return jsonrule(["status" => 200, "msg" => lang("队列发送中，请查看发送日志")]);
	}
	/**
	 * 检查最终符合规则的用户
	 */
	private function checkFinalSendUser($param)
	{
		$clients = explode(",", trim($param["clients"], ","));
		$system_clients = [];
		if ($param["is_market"] == 1) {
			$system_clients = \think\Db::name("clients")->where("status", 1)->where("marketing_emails_opt_in", 0)->field("id")->select()->toArray();
			$system_clients = array_column($system_clients, "id");
		}
		if ($param["send_type"] == "clients_and_host") {
			$useabled_user_ids = [];
			$_clients = "";
			foreach ($clients as $k => $v) {
				$list = explode(":", $v);
				$yes = true;
				if ($param["repeat_sent"] == 0) {
					if (in_array($list[0], $useabled_user_ids)) {
						unset($clients[$k]);
						$yes = false;
					} else {
						$useabled_user_ids[] = $list[0];
						$yes = true;
					}
				}
				if ($param["is_market"] == 1) {
					if (in_array($list[0], $system_clients)) {
						unset($clients[$k]);
						$yes = false;
					} else {
						$yes = true;
					}
				}
				if ($yes) {
					$_clients .= $v . ",";
				}
			}
			$param["clients"] = $_clients;
		} else {
			if ($param["repeat_sent"] == 0) {
				$clients = array_unique($clients);
			}
			if ($param["is_market"] == 1) {
				$clients = array_diff($clients, $system_clients);
			}
			$param["clients"] = implode(",", $clients);
		}
		return $param;
	}
	/**
	 * 发送模式下的数据检查
	 * @param array $param
	 * @return \think\Response
	 */
	private function checkPostSendParam($param = [])
	{
		if (in_array("system", $param["send_mothod"])) {
			if (empty($param["system_subject"])) {
				return ["status" => 400, "msg" => "站内信缺少标题"];
			}
			if (empty($param["system_message"])) {
				return ["status" => 400, "msg" => "站内信缺少正文内容"];
			}
		}
		if (empty($param["msgid"]) && in_array("mobile", $param["send_mothod"])) {
			return ["status" => 400, "msg" => "短信缺少短信模板"];
		}
		if (in_array("email", $param["send_mothod"])) {
			if (empty($param["email_subject"])) {
				return ["status" => 400, "msg" => "邮件缺少标题"];
			}
			if (empty($param["email_message"])) {
				return ["status" => 400, "msg" => "邮件缺少正文内容"];
			}
		}
		if (!in_array($param["is_market"], [0, 1])) {
			return ["status" => 400, "msg" => "无效的消息类型参数"];
		}
		if (!in_array($param["repeat_sent"], [0, 1])) {
			return ["status" => 400, "msg" => "无效的重复发送参数"];
		}
		if (!is_numeric($param["batch_num"]) || $param["batch_num"] < 0) {
			return ["status" => 400, "msg" => "无效的批量发送个数参数"];
		}
		if (!is_numeric($param["delay_time"]) || $param["delay_time"] < 0) {
			return ["status" => 400, "msg" => "无效的间隔时间参数"];
		}
		return ["status" => 200, "msg" => "check_ok"];
	}
	/**
	 * 处理附件，返回附件地址
	 * @param $param
	 * @return \think\Response
	 */
	private function handleAttchmentsAddress($param = [])
	{
		$upload = new \app\common\logic\Upload();
		if ($param["system_attachments"] && in_array("system", $param["send_mothod"])) {
			$tmp = $upload->moveTo($param["system_attachments"], $this->system_attachment_path);
			if (isset($tmp["error"])) {
				return ["status" => 400, "msg" => $tmp["error"]];
			}
			$param["system_attachments"] = is_array($tmp) ? implode(",", $tmp) : $tmp;
		}
		if ($param["email_attachments"] && in_array("email", $param["send_mothod"])) {
			$tmp = $upload->moveTo($param["email_attachments"], $this->attachment_path);
			if (isset($tmp["error"])) {
				return ["status" => 400, "msg" => $tmp["error"]];
			}
			$param["email_attachments"] = is_array($tmp) ? implode(",", $tmp) : $tmp;
		}
		return $param;
	}
	/**
	 * 发送手机短信逻辑处理
	 * @param array $param
	 * @return \think\Response
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	private function checkForbiddenSmsPlate($send_type = "clients", $message_template_link_name = "")
	{
		if ($send_type == "clients") {
			$forbidden_sms_name = ["generated_invoice", "invoice_pay", "invoice_overdue_pay", "submit_ticket", "ticket_reply", "host_suspend", "unpay_invoice", "order_refund", "invoice_payment_confirmation", "second_renew_product_reminder", "renew_product_reminder", "third_invoice_payment_reminder", "second_invoice_payment_reminder", "first_invoice_payment_reminder", "new_order_notice", "default_product_welcome", "service_termination_notification", "service_unsuspension_notification", "support_ticket_auto_close_notification", "support_ticket_opened", "email_bond_notice"];
			if (in_array($message_template_link_name, $forbidden_sms_name)) {
				return ["status" => 400, "msg" => "不能使用该短信模板,缺少相关信息"];
			}
		} elseif ($send_type == "clients_and_host") {
			$forbidden_sms_name = ["generated_invoice", "invoice_pay", "invoice_overdue_pay", "submit_ticket", "ticket_reply", "host_suspend", "unpay_invoice", "order_refund", "invoice_payment_confirmation", "default_product_welcome", "support_ticket_auto_close_notification", "support_ticket_opened", "email_bond_notice"];
			if (in_array($message_template_link_name, $forbidden_sms_name)) {
				return ["status" => 400, "msg" => "不能使用该短信模板,缺少相关信息"];
			}
		}
		return ["status" => 200, "msg" => "check_sms_template_ok"];
	}
	/**
	 * 发送短信息前的各种操作
	 * @param array $send_client 发送短信的客户信息
	 * @param string $send_type 推送营销信息的方式：clients-客户，clients_and_host-商品
	 * @param string $msgid 短信模板id
	 * @param $message_template_link
	 * @param $temp
	 * @param $type
	 * @return array
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function sendSmsBefore($send_client = [], $send_type = "clients", $msgid = "", $template, $sms_operator, $send_sync, $delay_time = 0)
	{
		$user_info = \think\Db::name("clients")->where("id", $send_client["id"])->find();
		$host = [];
		$order = [];
		if ($send_type == "clients_and_host") {
			$host = \think\Db::name("host")->alias("a")->field("a.id,a.uid,a.productid,a.domain,a.domainstatus,a.regdate,b.welcome_email,b.type,c.email,a.billingcycle,a.nextduedate,a.billingcycle")->field("b.pay_type,b.name,a.dedicatedip,a.username,a.password,a.os,a.assignedips,a.create_time,a.nextinvoicedate")->field("a.termination_date,a.port")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $send_client["host_id"])->find();
			$order = \think\Db::name("orders")->alias("o")->leftJoin("invoices i", "i.id = o.invoiceid")->leftJoin("invoice_items im", "i.id = im.invoice_id")->where("im.rel_id", $send_client["host_id"])->field("o.create_time,o.id,o.amount,i.paid_time,o.invoiceid,i.subtotal")->find();
		}
		$newvar = $this->replaceSMSContentParams($user_info, $host, $template, $sms_operator, $order);
		$sms = new \app\common\logic\Sms();
		$result = $sms->sendSmsForMarerking($send_client["mobile"], $msgid, $newvar, $send_sync, $send_client["id"], $delay_time, true);
		if (!$send_sync) {
			$result_status = $result["status"] == 200 ? true : false;
		} else {
			$result_status = $result;
		}
		if ($result_status) {
			return ["status" => 200, "msg" => lang("发送成功,请注意查收")];
		} else {
			return ["status" => 400, "msg" => lang("发送失败,失败原因:") . $result["msg"]];
		}
	}
	/**
	 * 替换邮件参数信息
	 */
	private function replaceEmailContentParams($email_content = "", $client = [])
	{
		$client_info = \think\Db::name("clients")->where("id", $client["id"])->find();
		$host = [];
		$product = [];
		$order = [];
		$server = [];
		if ($client["host_id"]) {
			$host = \think\Db::name("host")->where("id", $client["host_id"])->find();
			$order = \think\Db::name("orders")->alias("o")->leftJoin("invoices i", "i.id = o.invoiceid")->leftJoin("invoice_items im", "i.id = im.invoice_id")->where("im.rel_id", $host["id"])->field("o.*,i.paid_time")->find();
			if ($host && $host["serverid"]) {
				$server = \think\Db::name("servers")->where("id", $host["serverid"])->find();
			}
		}
		if ($client["productid"]) {
			$product = \think\Db::name("products")->where("id", $client["productid"])->find();
		}
		preg_match_all("/(?<=\\{)[^\\}]+/", $email_content, $matches);
		$var = $matches[0];
		foreach ($var as $key => $value) {
			switch ($value) {
				case "SYSTEM_COMPANYNAME":
					$email_content = preg_replace("/\\{SYSTEM_COMPANYNAME\\}/", configuration("company_name"), $email_content);
					break;
				case "COMPANY_DOMAIN":
					$email_content = preg_replace("/\\{COMPANY_DOMAIN\\}/", configuration("system_url"), $email_content);
					break;
				case "SYSTEM_EMAIL_LOGO_URL":
					$email_content = preg_replace("/\\{SYSTEM_EMAIL_LOGO_URL\\}/", configuration("domain") . configuration("logo_url"), $email_content);
					break;
				case "TEMPLATE_DATE":
					$email_content = preg_replace("/\\{TEMPLATE_DATE\\}/", date("Y-m-d"), $email_content);
					break;
				case "TEMPLATE_TIME":
					$email_content = preg_replace("/\\{TEMPLATE_TIME\\}/", date("H:i:s"), $email_content);
					break;
				case "CODE":
					$email_content = preg_replace("/\\{CODE\\}/", mt_rand(100000, 999999), $email_content);
					break;
				case "SEND_TIME":
					$email_content = preg_replace("/\\{SEND_TIME\\}/", date("Y-m-d H:i:s"), $email_content);
					break;
				case "SYSTEM_URL":
					$email_content = preg_replace("/\\{SYSTEM_URL\\}/", configuration("domain"), $email_content);
					break;
				case "SYSTEM_WEB_URL":
					$email_content = preg_replace("/\\{SYSTEM_WEB_URL\\}/", configuration("system_url"), $email_content);
					break;
				case "CLIENT_ID":
					$email_content = preg_replace("/\\{CLIENT_ID\\}/", $client_info["id"], $email_content);
					break;
				case "USERNAME":
					$email_content = preg_replace("/\\{USERNAME\\}/", $client_info["username"], $email_content);
					break;
				case "USER_COMPANY":
					$email_content = preg_replace("/\\{USER_COMPANY\\}/", $client_info["companyname"], $email_content);
					break;
				case "ACCOUNT_EMAIL":
					$email_content = preg_replace("/\\{ACCOUNT_EMAIL\\}/", $client_info["email"], $email_content);
					break;
				case "CLIENT_LOGIN_DATA_TIME":
					$email_content = preg_replace("/\\{CLIENT_LOGIN_DATA_TIME\\}/", date("Y-m-d H:i:s", $client_info["lastlogin"]), $email_content);
					break;
				case "CLIENT_ACTION_IP":
					$email_content = preg_replace("/\\{CLIENT_ACTION_IP\\}/", $client_info["lastloginip"], $email_content);
					break;
				case "CLIENT_ADDRESS1":
					$email_content = preg_replace("/\\{CLIENT_ADDRESS1\\}/", $client_info["address1"], $email_content);
					break;
				case "CLIENT_CITY":
					$email_content = preg_replace("/\\{CLIENT_CITY\\}/", $client_info["city"], $email_content);
					break;
				case "CLIENT_PROVINCE":
					$email_content = preg_replace("/\\{CLIENT_PROVINCE\\}/", $client_info["province"], $email_content);
					break;
				case "CLIENT_POSTCODE":
					$email_content = preg_replace("/\\{CLIENT_POSTCODE\\}/", $client_info["postcode"], $email_content);
					break;
				case "CLIENT_COUNTRY":
					$email_content = preg_replace("/\\{CLIENT_COUNTRY\\}/", $client_info["country"], $email_content);
					break;
				case "CLIENT_PHONENUMBER":
					$email_content = preg_replace("/\\{CLIENT_PHONENUMBER\\}/", $client_info["phonenumber"], $email_content);
					break;
				case "CLIENT_SIGNUP_DATE":
					$email_content = preg_replace("/\\{CLIENT_SIGNUP_DATE\\}/", date("Y-m-d H:i:s", $client_info["create_time"]), $email_content);
					break;
				case "CLIENT_CREDIT":
					$email_content = preg_replace("/\\{CLIENT_CREDIT\\}/", $client_info["credit"], $email_content);
					break;
				case "CLIENT_CC_TYPE":
					$email_content = preg_replace("/\\{CLIENT_CC_TYPE\\}/", $client_info["cardtype"], $email_content);
					break;
				case "CLIENT_CC_NUMBER":
					$email_content = preg_replace("/\\{CLIENT_CC_NUMBER\\}/", $client_info["cardlastfour"], $email_content);
					break;
				case "CLIENT_CC_EXPIRY":
					$email_content = preg_replace("/\\{CLIENT_CC_EXPIRY\\}/", $client_info["expdate"], $email_content);
					break;
				case "CLIENT_GROUP_ID":
					$email_content = preg_replace("/\\{CLIENT_GROUP_ID\\}/", $client_info["groupid"], $email_content);
					break;
				case "CLIENT_GROUP_NAME":
					$group_name = \think\Db::name("client_groups")->where("id", $client_info["groupid"])->value("group_name");
					$email_content = preg_replace("/\\{CLIENT_GROUP_NAME\\}/", $group_name, $email_content);
					break;
				case "CLIENT_DUE_INVOICES_BALANCE":
					$amount = \think\Db::name("invoices")->where("uid", $client_info["id"])->where("status", "Overdue")->sum("subtotal");
					$email_content = preg_replace("/\\{CLIENT_DUE_INVOICES_BALANCE\\}/", $amount, $email_content);
					break;
				case "CLIENT_STATUS":
					if ($client_info["status"] == 1) {
						$client_status = "激活";
					} elseif ($client_info["status"] == 0) {
						$client_status = "未激活";
					} elseif ($client_info["status"] == 2) {
						$client_status = "关闭";
					} else {
						$client_status = "未知";
					}
					$email_content = preg_replace("/\\{CLIENT_STATUS\\}/", $client_status, $email_content);
					break;
				case "PRODUCT_USER":
					$PRODUCT_USER = $host["username"] ?? "";
					$email_content = preg_replace("/\\{PRODUCT_USER\\}/", $PRODUCT_USER, $email_content);
					break;
				case "PRODUCT_PASSWD":
					$PRODUCT_PASSWD = $host["password"] ? cmf_decrypt($host["password"]) : "";
					$email_content = preg_replace("/\\{PRODUCT_PASSWD\\}/", $PRODUCT_PASSWD, $email_content);
					break;
				case "PRODUCT_TERMINATE_TIME":
					$PRODUCT_TERMINATE_TIME = date("Y-m-d H:i:s", $host["nextduedate"]) ?? "";
					$email_content = preg_replace("/\\{PRODUCT_TERMINATE_TIME\\}/", $PRODUCT_TERMINATE_TIME, $email_content);
					break;
				case "PRODUCT_BINLLY_CYCLE":
					$config_billing_cycle = config("billing_cycle");
					$PRODUCT_BINLLY_CYCLE = $config_billing_cycle[$host["billingcycle"]] ?? "";
					$email_content = preg_replace("/\\{PRODUCT_BINLLY_CYCLE\\}/", $PRODUCT_BINLLY_CYCLE, $email_content);
					break;
				case "PRODUCT_NAME":
					$PRODUCT_NAME = $product["name"] ?? "";
					$email_content = preg_replace("/\\{PRODUCT_NAME\\}/", $PRODUCT_NAME, $email_content);
					break;
				case "HOSTNAME":
					$HOSTNAME = $host["domain"] ?? "";
					$email_content = preg_replace("/\\{HOSTNAME\\}/", $HOSTNAME, $email_content);
					break;
				case "PRODUCT_MAINIP":
					$PRODUCT_MAINIP = $host["dedicatedip"] ?? "";
					if ($PRODUCT_MAINIP) {
						$PRODUCT_MAINIP .= $host["port"] ? ":" . $host["port"] : "";
					}
					$email_content = preg_replace("/\\{PRODUCT_MAINIP\\}/", $PRODUCT_MAINIP, $email_content);
					break;
				case "PRODUCT_DCIMBMS_OS":
					$email_content = preg_replace("/\\{PRODUCT_DCIMBMS_OS\\}/", $host["os"] ?? "", $email_content);
					break;
				case "PRODUCT_ADDONIP":
					$email_content = preg_replace("/\\{PRODUCT_ADDONIP\\}/", $host["assignedips"] ?? "", $email_content);
					break;
				case "PRODUCT_FIRST_TIME":
					$email_content = preg_replace("/\\{PRODUCT_FIRST_TIME\\}/", $host["create_time"] ? date("Y-m-d H:i:s", $host["create_time"]) : "", $email_content);
					break;
				case "PRODUCT_END_TIME":
					$nextduedate = $host["nextduedate"] ? date("Y-m-d H:i:s", $host["nextduedate"]) : "";
					$email_content = preg_replace("/\\{PRODUCT_END_TIME\\}/", $nextduedate, $email_content);
					break;
				case "PRODUCT_BINLLY_CYCLE":
					$email_content = preg_replace("/\\{PRODUCT_BINLLY_CYCLE\\}/", $host["billingcycle"] ?? "", $email_content);
					break;
				case "ORDER_CREATE_TIME":
					$ORDER_CREATE_TIME = $order["create_time"] ? date("Y-m-d H:i:s", $order["create_time"]) : "";
					$email_content = preg_replace("/\\{ORDER_CREATE_TIME\\}/", $ORDER_CREATE_TIME, $email_content);
					break;
				case "ORDER_ID":
					$email_content = preg_replace("/\\{ORDER_ID\\}/", $order["id"] ?? "", $email_content);
					break;
				case "ORDER_TOTAL_FEE":
					$email_content = preg_replace("/\\{ORDER_TOTAL_FEE\\}/", ($order["amount"] ?? 0) . " 元", $email_content);
					break;
				case "INVOICE_PAID_TIME":
					$INVOICE_PAID_TIME = $order["paid_time"] ? date("Y-m-d H:i:s", $order["paid_time"]) : "";
					$email_content = preg_replace("/\\{INVOICE_PAID_TIME\\}/", $INVOICE_PAID_TIME, $email_content);
					break;
				case "REG_DATE":
					$REG_DATE = $host["regdate"] ? date("Y-m-d H:i:s", $host["regdate"]) : "";
					$email_content = preg_replace("/\\{REG_DATE\\}/", $REG_DATE, $email_content);
					break;
				case "SERVICE_STATUS":
					$email_content = preg_replace("/\\{SERVICE_STATUS\\}/", $server["link_status"] ? "成功" : "失败", $email_content);
					break;
				case "PRODUCT_DESCRIPTION":
					$email_content = preg_replace("/\\{PRODUCT_DESCRIPTION\\}/", $product["description"], $email_content);
					break;
				case "SERVER_NAME":
					$email_content = preg_replace("/\\{SERVER_NAME\\}/", $server["name"], $email_content);
					break;
				case "SERVER_IP":
					$email_content = preg_replace("/\\{SERVER_IP\\}/", $server["ip_address"], $email_content);
					break;
				case "SERVICE_SUSPENSION_REASON":
					$email_content = preg_replace("/\\{SERVICE_SUSPENSION_REASON\\}/", $host["suspendreason"], $email_content);
					break;
				case "SERVER_HOSTNAME":
					$email_content = preg_replace("/\\{SERVER_HOSTNAME\\}/", $server["name"], $email_content);
					break;
				default:
					break;
			}
		}
		return $email_content;
	}
	/**
	 * 替换短信模板中的参数信息
	 */
	private function replaceSMSContentParams($user_info, $host, $template, $sms_operator, $order)
	{
		$template_content = $template["content"];
		if ($sms_operator == "submail") {
			preg_match_all("/(?<=@var\\()[^\\)]+/", $template_content, $matches);
		}
		if ($sms_operator == "aliyun") {
			preg_match_all("/(?<=\\\$\\{)[^\\}]+/", $template_content, $matches);
		}
		$admin_id = session("ADMIN_ID");
		$admin_info = \think\Db::name("user")->where("id", $admin_id)->value("user_login");
		$var = $matches[0];
		$newvar = [];
		foreach ($var as $key => $value) {
			$key = $value;
			switch ($value) {
				case "system_companyname":
					$v = configuration("company_name");
					break;
				case "code":
					$v = mt_rand(100000, 999999);
					break;
				case "send_time":
					$v = date("Y-m-d H:i:s");
					break;
				case "system_url":
					$v = configuration("system_url") . "/admin";
					break;
				case "system_ewb_url":
					$v = configuration("system_url");
					break;
				case "system_email_logo_url":
					$v = configuration("logo_url_home");
					break;
				case "username":
					$v = $user_info["username"];
					break;
				case "account":
					$v = $user_info["username"] . "(" . $user_info["phonenumber"] . ")";
					break;
				case "user_company":
					$v = $user_info["companyname"];
					break;
				case "account_email":
					$v = $user_info["email"];
					break;
				case "login_data_time":
					$v = date("Y-m-d H:i:s", $user_info["lastlogin"]);
					break;
				case "action_ip":
					$v = $user_info["lastloginip"];
					break;
				case "time":
					$v = date("Y-m-d H:i:s", $user_info["lastlogin"]);
					break;
				case "epw_type":
					$v = "手机";
					break;
				case "epw_account":
					$v = $user_info["phonenumber"];
					break;
				case "address":
					$v = $user_info["lastloginip"];
					break;
				case "admin_account_name":
					$v = $admin_info["user_login"];
					break;
				case "admin_login_data_time":
					$v = date("Y-m-d H:i:s", $admin_info["last_act_time"]);
					break;
				case "admin_action_ip":
					$v = $admin_info["last_login_ip"];
					break;
				case "product_name":
					$v = $host["name"];
					break;
				case "hostname":
					$v = $host["domain"];
					break;
				case "product_user":
					$v = $host["username"];
					break;
				case "product_passwd":
					$v = aesPasswordDecode($host["password"]);
					break;
				case "product_mainip":
					$v = $host["dedicatedip"];
					$v .= $host["port"] ? ":" . $host["port"] : "";
					break;
				case "product_dcimbms_os":
					$v = $host["os"];
					break;
				case "product_addonip":
					$v = $host["assignedips"];
					break;
				case "product_first_time":
					$v = date("Y-m-d H:i:s", $host["create_time"]);
					break;
				case "product_end_time":
					$v = date("Y-m-d H:i:s", $host["nextduedate"]);
					break;
				case "product_binlly_cycle":
					$billing_cycle = config("billing_cycle");
					$v = $billing_cycle[$host["billingcycle"]];
					break;
				case "order_create_time":
					$v = date("Y-m-d H:i:s", $order["create_time"]);
					break;
				case "order_id":
					$v = $order["id"];
					break;
				case "order_total_fee":
					$v = $order["amount"];
					break;
				case "invoice_paid_time":
					$v = date("Y-m-d H:i:s", $order["paid_time"]);
					break;
				case "invoiceid":
					$v = $order["invoiceid"];
					break;
				case "total":
					$v = $order["subtotal"];
					break;
				case "product_terminate_time":
					$v = date("Y-m-d H:i:s", $host["nextduedate"]);
					break;
				default:
					break;
			}
			$newvar[$key] = $v;
		}
		return $newvar;
	}
	/**
	 * 发送站内信逻辑处理
	 * @param array $param
	 */
	public function update_smslog($data, $rpwd)
	{
		file_put_contents($this->progress_log . $rpwd . "_sms_log.log", $data . "\n");
	}
	public function get_smslog($ideny)
	{
		file_get_contents($this->progress_log . $ideny . "_sms_log.log");
	}
	/**
	 * @title 获取进度
	 * @description 获取进度
	 * @param .name:identy type:string require:0 default:1 other: desc:标识符
	 * @return .total:总数
	 * @return .done:已完成
	 * @return .done:成功
	 * @return .fail:失败
	 * @author lgd
	 * @url /admin/get_progress
	 * @method GET
	 */
	public function getProgress()
	{
		$param = $this->request->param();
		$ideny = $param["identy"];
		$ideny = "H4fHuq9B";
		$sms = file_get_contents($this->progress_log . $ideny . "_sms_log.log");
		return $sms;
	}
}