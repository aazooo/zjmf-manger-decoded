<?php

namespace app\admin\controller;

/**
 * @title 后台全局数据
 * @description 后台全局数据
 */
class CommonController extends GetUserController
{
	/**
	 * @title 后台全局接口
	 * @description 接口说明:
	 * @throws
	 * @author wyh
	 * @url /admin/common
	 * @return company_name:公司名称
	 * @return system_url:系统链接
	 * @return system_title:系统标题
	 * @return domain:网站域名
	 * @return gateway:支付方式@
	 * @gateway name:名称
	 * @gateway title:标题
	 * @gateway status:状态
	 * @gateway module:模块
	 * @gateway url:地址
	 * @return admin:管理员名称
	 * @return system_language:系统语言
	 * @return config: 配置信息@
	 * @config  client_certifi_status:客户认证状态@
	 * @config  client_status:认证状态@
	 * @config  invoice_payment_status:账单支付状态@
	 * @config  order_status:订单状态@
	 * @config  domainstatus:产品状态@
	 * @config  user_is_sale:是否销售状态@
	 * @config  user_sale_is_use:销售是否启用@
	 * @config  rule:菜单列表@
	 * @config  auth:权限集@
	 * @config  sale:销售列表@
	 * @return  second_verify_admin:是否开启后台二次验证
	 * @return  second_verify_action_admin:开启的二次验证动作
	 * @method get
	 */
	public function common()
	{
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			$app = [];
		} else {
			$_strcode = _strcode($zjmf_authorize, "DECODE", "zjmf_key_strcode");
			$_strcode = explode("|zjmf|", $_strcode);
			$authkey = "-----BEGIN PUBLIC KEY-----\r\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDg6DKmQVwkQCzKcFYb0BBW7N2f\r\nI7DqL4MaiT6vibgEzH3EUFuBCRg3cXqCplJlk13PPbKMWMYsrc5cz7+k08kgTpD4\r\ntevlKOMNhYeXNk5ftZ0b6MAR0u5tiyEiATAjRwTpVmhOHOOh32MMBkf+NNWrZA/n\r\nzcLRV8GU7+LcJ8AH/QIDAQAB\r\n-----END PUBLIC KEY-----";
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
		$data = [];
		$data["company_name"] = configuration("company_name") ?? "顺戴网络";
		$data["domain"] = configuration("domain");
		$data["system_url"] = configuration("system_url");
		$data["gateway"] = gateway_list1();
		$data["admin"] = \think\Db::name("user")->where("id", cmf_get_current_admin_id())->value("user_login");
		$data["system_language"] = configuration("language");
		$data["system_title"] = configuration("title");
		$tmp = config();
		$data["config"] = $tmp["public"];
		$data["sale"] = \think\Db::name("user")->where([["is_sale", "=", 1], ["sale_is_use", "=", 1]])->field("id,user_nickname")->select()->toArray();
		$sessionAdminId = session("ADMIN_ID");
		$auth_id = session("AUTH_IDS_" . $sessionAdminId);
		$data["rule"] = json_decode($auth_id);
		$data["is_aff"] = configuration("affiliate_enabled");
		$data["per_page_limit"] = configuration("per_page_limit") ?? 50;
		$data["second_verify_admin"] = configuration("second_verify_admin") ?? 0;
		$data["second_verify_action_admin"] = explode(",", configuration("second_verify_action_admin"));
		$data["seniorConfig"] = in_array("seniorConfig", $app) ? true : false;
		$pro_support_features = [];
		if (in_array("seniorConfig", $app)) {
			$pro_support_features[] = "seniorConfig";
		}
		if (in_array("marketingPush", $app)) {
			$pro_support_features[] = "marketingPush";
		}
		if (in_array("InvoiceContract", $app)) {
			$pro_support_features[] = "InvoiceContract";
		}
		if (in_array("Oauth", $app)) {
			$pro_support_features[] = "Oauth";
		}
		$data["pro_support_features"] = $pro_support_features;
		$version = configuration("upgrade_send_template_version");
		if (empty($version)) {
			$version = microtime(true) - 86400;
			updateConfiguration("upgrade_send_template_version", $version);
		}
		$version_data = date("Ymd", $version);
		if ($version_data < date("Ymd")) {
			\think\Db::startTrans();
			try {
				$res = \think\Db::name("configuration")->where("setting", "upgrade_send_template_version")->where("value", $version)->update(["value" => microtime(true)]);
				if (is_integer($res) && $res > 0) {
					upgradeSmsTemplate();
					upgradeEmilTemplate();
					\think\Db::commit();
				} else {
					\think\Db::rollback();
				}
			} catch (\Exception $exception) {
				\think\Db::rollback();
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 系统后台消息通知
	 * @description 接口说明: 后台首页,前端定时调用此接口,info非空时,浏览器声音提醒
	 * @throws
	 * @author wyh
	 * @url /admin/common/info_notice
	 * @return info:系统消息通知内容
	 * @method get
	 */
	public function infoNotice()
	{
		$info = \think\Db::name("info_notice")->field("info")->where("info", "<>", "")->where("admin", 1)->select()->toArray();
		$dec = "";
		foreach ($info as $v) {
			$dec .= $v["info"] . "\n";
		}
		$data = ["info" => $dec];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 系统后台支付方式
	 * @description 接口说明: 系统后台支付方式
	 * @throws
	 * @author lgd
	 * @url /admin/common/get_getways
	 * @return gateway:系统后台支付方式
	 * @method get
	 */
	public function getGetways()
	{
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "gateway" => gateway_list1()]);
	}
	/**
	 * @title 系统邮件模板列表
	 * @description 接口说明: 系统邮件模板列表
	 * @param  .name:type type:string require:1 desc:'product'
	 * @throws
	 * @author lgd
	 * @url /admin/common/get_email_tem
	 * @return email:系统邮件模板列表
	 * @method get
	 */
	public function getEmailTem()
	{
		$param = $this->request->param();
		$emailTemplateModel = new \app\admin\model\EmailTemplatesModel();
		$emailTemplategroupData = $emailTemplateModel->getEmailTemplates($param["type"]);
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "email" => $emailTemplategroupData]);
	}
	/**
	 * @title 系统用户分组
	 * @description 接口说明: 系统用户分组
	 * @throws
	 * @author lgd
	 * @url /admin/common/get_client_groups
	 * @return client_groups:系统用户分组
	 * @method get
	 */
	public function getClientGroups()
	{
		$clientGroups = \think\Db::name("client_groups")->field("id,group_name,group_colour")->select()->toArray();
		$clientGroupsFilter = [];
		foreach ($clientGroups as $key => $clientGroup) {
			$clientGroupsFilter[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $clientGroup);
		}
		array_unshift($clientGroupsFilter, ["id" => 0, "group_name" => lang("NULL")]);
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "client_groups" => $clientGroupsFilter]);
	}
	/**
	 * @title 商品列表
	 * @description 接口说明: 商品列表
	 * @throws
	 * @param  .name:id type:int require:1 desc:商品id
	 * @param  .name:type type:string require:1 desc:0
	 * @author lgd
	 * @url /admin/common/get_product_list
	 * @return client_groups:商品列表
	 * @method get
	 */
	public function getProductList()
	{
		$param = $this->request->param();
		$type = $param["type"] ?? 0;
		$id = $param["id"] ?? 0;
		$product_list = getProductList($type, $id);
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "client_groups" => $product_list]);
	}
	/**
	 * @title 优惠码
	 * @description 接口说明: 优惠码
	 * @throws
	 * @param  .name:type type:string require:1 desc:0
	 * @author lgd
	 * @url /admin/common/get_promo_code
	 * @return promo_code:优惠码
	 * @method get
	 */
	public function getPromoCode()
	{
		$param = $this->request->param();
		$type = $param["type"] ?? true;
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "promo_code" => getAllPromoData($type)]);
	}
	/**
	 * @title 客户产品列表
	 * @description 接口说明: 客户产品列表
	 * @throws
	 * @param  .name:uid type:id require:1 desc:用户id
	 * @author lgd
	 * @url /admin/common/host_list
	 * @return host_list:客户产品列表
	 * @method get
	 */
	public function getHostList()
	{
		$param = $this->request->param();
		$uid = $param["uid"] ?? 0;
		$host_list = $this->getHostLists($uid);
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "host_list" => $host_list]);
	}
	private function getHostLists($uid)
	{
		$host_list = \think\Db::name("host")->field("h.id,p.name,h.domainstatus,h.dedicatedip,p.type")->alias("h")->leftJoin("products p", "p.id=h.productid")->withAttr("domainstatus", function ($value) {
			$domainstatus = [];
			$domainstatus["color"] = config("public.domainstatus")[$value]["color"];
			$domainstatus["name"] = $value;
			$domainstatus["name_zh"] = config("public.domainstatus")[$value]["name"];
			return $domainstatus;
		})->withAttr("type", function ($value) {
			return config("product_type")[$value];
		})->where("h.uid", $uid)->order("h.id", "desc")->select()->toArray();
		return $host_list ?: [];
	}
	/**
	 * @title 国家列表
	 * @description 接口说明: 国家列表
	 * @throws
	 * @author lgd
	 * @url /admin/common/get_sms_country
	 * @return promo_code:国家列表
	 * @method get
	 */
	public function getSmsCountry()
	{
		$smsCountry = \think\Db::name("sms_country")->field("id,nicename,name_zh,phone_code")->select();
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "sms_country" => $smsCountry]);
	}
	/**
	 * @title 可配置项
	 * @description 接口说明: 可配置项
	 * @throws
	 * @author lgd
	 * @url /admin/common/product_config_options
	 * @return config_options:可配置项
	 * @method get
	 */
	public function getProductConfigOptions()
	{
		$groups = \think\Db::name("product_config_groups")->field("id,name")->select()->toArray();
		foreach ($groups as &$group) {
			$group["options"] = \think\Db::name("product_config_options")->field("id,option_name")->where("gid", $group["id"])->select()->toArray();
		}
		$data = ["groups" => $groups];
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 销售列表
	 * @description 接口说明:销售列表
	 * @author lgd
	 * @url /admin/common/sale_list
	 * @method get
	 */
	public function saleList()
	{
		$list = \think\Db::name("user")->field("id as value,user_nickname as label")->where("is_sale", 1)->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $list]);
	}
	/**
	 * @title 上游部门列表
	 * @description 接口说明: 上游部门列表
	 * @throws
	 * @param  .name:id type:array require:1 desc:上游id
	 * @author xj
	 * @url /admin/common/get_upstream_ticket_department_list
	 * @return upstream_ticket_departments:部门列表
	 * @method get
	 */
	public function getUpstreamTicketDepartmentList()
	{
		$param = $this->request->param();
		$zjmf_api_id = $param["id"];
		$token = [];
		foreach ($zjmf_api_id as $v) {
			$api = \think\Db::name("zjmf_finance_api")->field("hostname,username,password")->where("id", $v)->find();
			$url = rtrim($api["hostname"], "/");
			$login_url = $url . "/zjmf_api_login";
			$login_data = ["username" => $api["username"], "password" => aesPasswordDecode($api["password"])];
			$jwt = zjmfApiLogin($v, $login_url, $login_data);
			if ($jwt["status"] == 200) {
				$token[$v] = ["url" => $url, "jwt" => $jwt["jwt"]];
			}
		}
		$data = [];
		foreach ($token as $k => $v) {
			$data[$k] = ["url" => $v["url"] . "/ticket/department", "header" => ["Authorization: Bearer " . $v["jwt"]]];
		}
		$res = batch_curl($data, "GET", 30);
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "upstream_ticket_departments" => $res]);
	}
}