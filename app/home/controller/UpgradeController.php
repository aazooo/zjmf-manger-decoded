<?php

namespace app\home\controller;

/**
 * @title 产品升降级
 * @description 接口说明：产品升降级
 */
class UpgradeController extends CommonController
{
	/**
	 * @title 升降级产品可配置项页面
	 * @description 接口说明:升降级产品可配置项页面
	 * @author wyh
	 * @url /upgrade/index/:hid
	 * @method GET
	 * @param  .name:hid type:number require:1 default: desc: 产品ID(host表的ID)
	 * @param  .name:currencyid type:number require:0 default: desc:货币ID（可选）
	 * @return   host:原产品信息@
	 * @host  oid:可配置项ID
	 * @host  option_name:可配置项名称
	 * @host  option_type:类型
	 * @host  qty:(类型为4时，qty为数量)
	 * @host  suboption_name:子项名称
	 * @host  subid:子项ID
	 * @host  fee:配置子项价格
	 * @host  setupfee:配置子项初装费
	 * @return   options:配置项信息,所有配置项@
	 * @option   id:配置项ID
	 * @option   option_name:配置项名称
	 * @option   option_type:配置项类型
	 * @option   qty_minimum：
	 * @option   qty_maximum：
	 * @option   sub:所有子项@
	 * @child   id:子项ID
	 * @child   config_id:配置项ID
	 * @child   option_name:子项名称
	 * @child   qty_minimum：
	 * @child   qty_maximum：
	 * @child   show_pricing：价格展示(下拉显示此值)
	 */
	public function index()
	{
		$re = $data = [];
		$params = $this->request->param();
		$hid = isset($params["hid"]) ? intval($params["hid"]) : "";
		if (!$hid) {
			return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$currency = isset($params["currencyid"]) ? intval($params["currencyid"]) : "";
		$uid = request()->uid;
		if (!$uid) {
			return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$currencyid = priorityCurrency($uid, $currency);
		$currency = (new \app\common\logic\Currencies())->getCurrencies("id,code,prefix,suffix", $currencyid)[0];
		$data["currency"] = $currency;
		$upgrade_logic = new \app\common\logic\Upgrade();
		try {
			$upgrade_logic->judgeUpgradeConfigError($hid);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
		$hosts = \think\Db::name("host")->alias("h")->field("pco.linkage_pid,pco.linkage_top_pid")->field("pco.option_name as option_name,pco.id as oid,pco.option_type,pcos.option_name as suboption_name,pcos.id as subid,hco.qty,h.billingcycle,h.flag,pri.*,pco.qty_stage,pco.unit")->leftJoin("host_config_options hco", "h.id = hco.relid")->leftJoin("product_config_options pco", "pco.id = hco.configid")->leftJoin("product_config_options_sub pcos", "pcos.id = hco.optionid")->leftJoin("pricing pri", "pri.relid = pcos.id")->where("h.id", $hid)->where("h.uid", $uid)->where("pri.currency", $currencyid)->where("pri.type", "configoptions")->where("pco.upgrade", 1)->select()->toArray();
		$cart = new \app\common\logic\Cart();
		$product = \think\Db::name("host")->field("productid,billingcycle")->where("id", $hid)->find();
		$cycle = $product["billingcycle"];
		$pid = $product["productid"];
		$configoptions_logic = new \app\common\logic\ConfigOptions();
		$configInfo = $configoptions_logic->getConfigInfo($pid);
		$allOption = $configoptions_logic->configShow($configInfo, $currencyid, $cycle, $uid, true);
		$hostFilters = [];
		$h = [];
		foreach ($hosts as $key => $host) {
			$option_name = explode("|", $host["option_name"]);
			if ($host["option_type"] != 5 && $host["option_type"] != 12 && $option_name[0] != "system_disk_size") {
				$h["oid"] = $host["oid"];
				$h["id"] = $host["oid"];
				$h["flag"] = $host["flag"];
				$h["option_name"] = $option_name[1] ? $option_name[1] : $host["option_name"];
				$h["option_type"] = $host["option_type"];
				$h["qty"] = $host["qty"];
				$h["suboption_name"] = explode("|", $host["suboption_name"])[1] ? explode("|", $host["suboption_name"])[1] : $host["suboption_name"];
				$h["suboption_name"] = implode(" ", explode("^", $h["suboption_name"]));
				$h["suboption_name_first"] = explode("|", $host["suboption_name"])[1] ? explode("|", $host["suboption_name"])[0] : $host["suboption_name"];
				if ($h["option_type"] == 3 && $h["qty"] == 0) {
					$h["subid"] = 0;
				} else {
					$h["subid"] = $host["subid"];
				}
				$h["fee"] = $host[$host["billingcycle"]];
				$h["setupfee"] = $host[$cart->changeCycleToupfee($host["billingcycle"])];
				$h["qty_minimum"] = 0;
				$h["qty_maximum"] = 0;
				$h["qty_stage"] = $host["qty_stage"];
				$h["unit"] = $host["unit"];
				$h["linkage_pid"] = $host["linkage_pid"];
				$h["linkage_top_pid"] = $host["linkage_top_pid"];
				$h["sub"] = [];
				foreach ($allOption as $vv) {
					if ($vv["id"] == $h["oid"]) {
						$h["qty_minimum"] = $vv["qty_minimum"];
						$h["qty_maximum"] = $vv["qty_maximum"];
						if (count($vv["sub"]) > 1 || judgeQuantity($host["option_type"]) || judgeYesNo($host["option_type"])) {
							$sub = $vv["sub"];
							if ($host["option_type"] == 13) {
								$subfilter = [];
								foreach ($sub as $v) {
									if (floatval($v["option_name_first"]) >= floatval($h["suboption_name_first"])) {
										$subfilter[] = $v;
									}
								}
							} else {
								if ($host["option_type"] == 14 || $host["option_type"] == 19) {
									$subfilter = [];
									$min = 0;
									foreach ($sub as &$v) {
										if ($h["subid"] == $v["id"]) {
											$min = $v["qty_minimum"];
											$v["qty_minimum"] = $h["qty"];
										}
									}
									foreach ($sub as $v2) {
										if ($min <= $v["qty_minimum"]) {
											$subfilter[] = $v2;
										}
									}
								} else {
									$subfilter = $sub;
								}
							}
							$h["sub"] = $subfilter;
						}
					}
				}
				if (!empty($h["sub"])) {
					$hostFilters[] = array_map(function ($v) {
						return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
					}, $h);
				}
			}
		}
		$hostFilters = $this->handleLinkAgeLevel($hostFilters);
		$hostFilters = $this->handleTreeArr($hostFilters);
		$cids = \think\Db::name("product_config_options")->alias("a")->field("a.id")->leftJoin("product_config_links b", "b.gid = a.gid")->leftJoin("product_config_groups c", "a.gid = c.id")->where("b.pid", $pid)->order("a.order", "asc")->order("a.id", "asc")->column("a.id");
		$links = \think\Db::name("product_config_options_links")->whereIN("config_id", $cids)->where("type", "condition")->where("relation_id", 0)->withAttr("sub_id", function ($value) {
			return json_decode($value, true);
		})->select()->toArray();
		if (!empty($links[0])) {
			foreach ($links as &$link) {
				$result = \think\Db::name("product_config_options_links")->where("relation_id", $link["id"])->withAttr("sub_id", function ($value) {
					return json_decode($value, true);
				})->select()->toArray();
				$link["result"] = $result;
			}
		}
		if ($links) {
			$hostconfigoptions = \think\Db::name("host")->alias("h")->field("hco.qty,hco.configid,hco.optionid,pco.hidden,pco.upgrade")->leftJoin("host_config_options hco", "h.id = hco.relid")->leftJoin("product_config_options pco", "pco.id = hco.configid")->where("h.id", $hid)->where("h.uid", $uid)->select()->toArray();
			$links_config_id = array_column($links, "config_id");
			$links_config_id = array_unique($links_config_id);
			foreach ($hostconfigoptions as $k => $v) {
				if (in_array($v["configid"], $links_config_id) && ($v["hidden"] == 1 || $v["upgrade"] == 0)) {
					$host_config_options[$k]["configid"] = $v["configid"];
					$host_config_options[$k]["optionid"] = $v["optionid"];
					$host_config_options[$k]["qty"] = $v["qty"];
				}
			}
			$data["host_config_options"] = $host_config_options ? $host_config_options : [];
		}
		$data["links"] = $links ? $links : [];
		$data["host"] = $hostFilters;
		$data["pid"] = $pid;
		$re["status"] = 200;
		$re["msg"] = lang("SUCCESS MESSAGE");
		$re["data"] = $data;
		return jsons($re);
	}
	public function handleLinkAgeLevel($data)
	{
		$req = $this->request;
		if (!$data) {
			return $data;
		}
		$data = array_column($data, null, "id");
		$configOption = new \app\common\logic\ConfigOptions();
		foreach ($data as $k => $v) {
			if ($v["option_type"] != 20 || $v["linkage_pid"] != 0) {
				continue;
			}
			$req->cid = $cid = $v["id"];
			if ($v["subid"]) {
				$req->sub_id = $v["subid"];
			}
			$all_list = $configOption->webGetLinkAgeList($req);
			$linkAge = $configOption->webSetLinkAgeListDefaultVal($all_list, $req);
			$linkAge_ids = $linkAge ? array_column($linkAge, "id") : [];
			foreach ($linkAge as $val) {
				if (isset($data[$val["id"]])) {
					$data[$val["id"]]["checkSubId"] = $val["checkSubId"];
				}
			}
			$data = array_filter($data, function ($v) use($linkAge_ids, $cid) {
				if ($v["option_type"] != 20) {
					return true;
				}
				if ($v["linkage_top_pid"] != $cid) {
					return true;
				}
				if (in_array($v["id"], $linkAge_ids)) {
					return true;
				}
				return false;
			});
		}
		return $configOption->getTree($data);
	}
	public function handleTreeArr($data)
	{
		if (!$data) {
			return $data;
		}
		foreach ($data as $key => $val) {
			if (isset($val["son"]) && $val["son"]) {
				$data[$key]["son"] = changeTwoArr($val["son"]);
			}
		}
		return $data;
	}
	/**
	 * @title 升降级产品可配置项页面提交(包括使用优惠码)
	 * @description 接口说明:升降级产品可配置项页面提交
	 * @author wyh
	 * @url /upgrade/upgrade_config_post
	 * @method POST
	 * @param  .name:hid type:number require:1 default: desc: 产品ID(host表的ID)
	 * @param  .name:currencyid type:number require:0 default: desc:货币ID（可选）
	 * @param  .name:pormo_code type:number require:0 default: desc: 优惠码(可选)
	 * @param  .name:configoption[配置项ID] type:string require:1 default:1 other: desc:所选择的子项ID,拉条传数量(当所有配置项都无变化时,不请求接口)
	 */
	public function upgradeConfigPost()
	{
		try {
			if ($this->request->isPost()) {
				$params = $this->request->param();
				$hid = isset($params["hid"]) ? intval($params["hid"]) : "";
				if (!$hid) {
					return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
				}
				cache("upgrade_down_config_" . $hid, null);
				$upgrade_logic = new \app\common\logic\Upgrade();
				if (!$upgrade_logic->judgeUpgradeConfigError($hid)) {
					return jsons(["status" => 400, "msg" => lang("当前产品无法升级或降级可配置项")]);
				}
				$configoptions = $params["configoption"];
				if (!$upgrade_logic->checkChange($hid, $configoptions)) {
					return jsons(["status" => 400, "msg" => lang("请选择配置项")]);
				}
				$data["hid"] = $hid;
				$data["configoptions"] = $configoptions;
				if (!empty($configoptions) && is_array($configoptions)) {
					cache("upgrade_down_config_" . $hid, $data, 86400);
					return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
				} else {
					return jsons(["status" => 400, "msg" => "配置项非数组"]);
				}
			}
			return jsons(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 升降级产品可配置项页面
	 * @description 接口说明:升降级产品可配置项
	 * @author wyh
	 * @url /upgrade/upgrade_config_page
	 * @method GET
	 * @param  .name:hid type:number require:1 default: desc: 产品ID(host表的ID)
	 */
	public function getUpgradeConfigPage()
	{
		try {
			$params = $this->request->param();
			$hid = isset($params["hid"]) ? intval($params["hid"]) : "";
			if (!$hid) {
				return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$upgrade_logic = new \app\common\logic\Upgrade();
			if (!$upgrade_logic->judgeUpgradeConfigError($hid)) {
				return jsons(["status" => 400, "msg" => lang("当前产品无法升级或降级可配置项")]);
			}
			$data = cache("upgrade_down_config_" . $hid);
			if (!$data) {
				return jsons(["status" => 400, "msg" => "请重新选择配置"]);
			}
			$configoptions = $data["configoptions"];
			$promo_code = $data["promo_code"] ?? "";
			$currencyid = isset($params["currencyid"]) ? intval($params["currencyid"]) : "";
			$uid = request()->uid;
			$currencyid = priorityCurrency($uid, $currencyid);
			$upgrade_logic = new \app\common\logic\Upgrade();
			$re = $upgrade_logic->upgradeConfigCommon($hid, $configoptions, $currencyid, false, $promo_code);
			return jsons($re);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 升降级产品可配置项--应用优惠码
	 * @description 接口说明:升降级产品可配置项--应用优惠码
	 * @author wyh
	 * @url /upgrade/add_promo_code
	 * @method POST
	 * @param  .name:hid type:number require:1 default: desc: 产品ID(host表的ID)
	 * @param  .name:pormo_code type:number require:0 default: desc: 优惠码(可选)
	 */
	public function addPromoCodeToConfig()
	{
		try {
			if ($this->request->isPost()) {
				$params = $this->request->param();
				$hid = isset($params["hid"]) ? intval($params["hid"]) : "";
				if (!$hid) {
					return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
				}
				$upgrade_logic = new \app\common\logic\Upgrade();
				if (!$upgrade_logic->judgeUpgradeConfigError($hid)) {
					return jsons(["status" => 400, "msg" => lang("优惠码无效")]);
				}
				$promo_code = $params["pormo_code"];
				$result = $upgrade_logic->checkUpgradePromo($promo_code, $hid);
				if ($result["status"] != 200) {
					$result["msg"] = "优惠码无效";
					return jsons($result);
				}
				$data = cache("upgrade_down_config_" . $hid);
				if (!$data) {
					return jsons(["status" => 400, "msg" => "优惠码无效"]);
				}
				$data["promo_code"] = $promo_code;
				cache("upgrade_down_config_" . $hid, $data, 86400);
				return jsons(["status" => 200, "msg" => "应用优惠码成功"]);
			}
			return jsons(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 升降级产品可配置项--移除优惠码
	 * @description 接口说明:升降级产品可配置项--移除优惠码
	 * @author wyh
	 * @url /upgrade/remove_promo_code
	 * @method POST
	 * @param  .name:hid type:number require:1 default: desc: 产品ID(host表的ID)
	 */
	public function removePromoCodeFromConfig()
	{
		try {
			if ($this->request->isPost()) {
				$params = $this->request->param();
				$hid = isset($params["hid"]) ? intval($params["hid"]) : "";
				if (!$hid) {
					return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
				}
				$upgrade_logic = new \app\common\logic\Upgrade();
				if (!$upgrade_logic->judgeUpgradeConfigError($hid)) {
					return jsons(["status" => 400, "msg" => lang("当前产品无法升级或降级可配置项")]);
				}
				$data = cache("upgrade_down_config_" . $hid);
				if (!$data) {
					return jsons(["status" => 400, "msg" => "请重新选择配置"]);
				}
				$data["promo_code"] = "";
				cache("upgrade_down_config_" . $hid, $data, 86400);
				return jsons(["status" => 200, "msg" => "移除优惠码成功"]);
			}
			return jsons(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 升降级产品可配置项--结算
	 * @description 接口说明:升降级产品可配置项--结算
	 * @author wyh
	 * @url /upgrade/checkout_config_upgrade
	 * @method POST
	 * @param  .name:hid type:number require:1 default: desc: 产品ID(host表的ID)
	 */
	public function checkoutConfigUpgrade()
	{
		try {
			if ($this->request->isPost()) {
				$params = $this->request->param();
				$hid = isset($params["hid"]) ? intval($params["hid"]) : "";
				if (!$hid) {
					return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
				}
				$upgrade_logic = new \app\common\logic\Upgrade();
				if (!$upgrade_logic->judgeUpgradeConfigError($hid)) {
					return jsons(["status" => 400, "msg" => lang("当前产品无法升级或降级可配置项")]);
				}
				$data = cache("upgrade_down_config_" . $hid);
				if (!$data) {
					return jsons(["status" => 400, "msg" => "请重新选择配置"]);
				}
				$configoptions = $data["configoptions"];
				$promo_code = $data["promo_code"] ?? "";
				$currencyid = isset($data["currencyid"]) ? intval($data["currencyid"]) : "";
				$uid = request()->uid;
				$currencyid = priorityCurrency($uid, $currencyid);
				$payment = \think\Db::name("host")->where("id", $hid)->value("payment");
				$desc = "";
				if (cache(md5(serialize($data) . "-" . $hid . "-" . get_client_ip()))) {
					return jsons(["status" => 400, "msg" => "请求过于频繁"]);
				}
				cache(md5(serialize($data) . "-" . $hid . "-" . get_client_ip()), "upgrade config", 20);
				$productid = \think\Db::name("host")->where("id", $hid)->value("productid");
				$configoption_res = \think\Db::name("host_config_options")->where("relid", $hid)->select()->toArray();
				$configoption = [];
				foreach ($configoption_res as $k => $v) {
					$configoption[$v["configid"]] = $v["qty"] ?: $v["optionid"];
				}
				foreach ($configoptions as $ks => $vs) {
					$configoption[$ks] = $vs;
				}
				$senior = new \app\common\logic\SeniorConf();
				$msg = $senior->checkConf($productid, $configoption);
				if ($msg) {
					return jsons(["status" => 400, "msg" => $msg]);
				}
				$percent_value = $params["resource_percent_value"] ?: "";
				if (!empty($configoptions) && is_array($configoptions)) {
					$re = $upgrade_logic->upgradeConfigCommon($hid, $configoptions, $currencyid, false, $promo_code, $payment, true, $percent_value);
					return jsons($re);
				} else {
					return jsons(["status" => 400, "msg" => "配置项非数组"]);
				}
			}
			return jsons(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 升降级产品页面
	 * @description 接口说明:升降级产品页面
	 * @author wyh
	 * @url /upgrade/upgrade_product/:hid
	 * @method GET
	 * @param  .name:hid type:number require:1 default: desc: 产品ID(host表的ID)
	 * @param  .name:currencyid type:number require:0 default: desc: 货币ID（可选）
	 * @return  currency:使用货币信息
	 * @return  old_host:当前产品@
	 * @old_host  host:产品组+产品名
	 * @old_host  domain:域名
	 * @old_host  description:描述
	 * @old_host  pid:产品ID
	 * @return  host:可升级的产品选项@
	 * @host  pid:产品ID
	 * @host  host:产品组+产品名
	 * @host  description:描述
	 * @host  cycle:可选周期项@
	 * @cycle  price:产品价格
	 * @cycle  setup_fee:初装费
	 * @cycle  billingcycle:周期
	 * @cycle  billingcycle_zh:中文周期
	 */
	public function upgradeProduct()
	{
		try {
			$re = $data = [];
			$re["status"] = 200;
			$re["msg"] = lang("SUCCESS MESSAGE");
			$params = $this->request->param();
			$hid = isset($params["hid"]) && !empty($params["hid"]) ? intval($params["hid"]) : "";
			if (!$hid) {
				return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$upgrade_logic = new \app\common\logic\Upgrade();
			if (!$upgrade_logic->judgeUpgradeConfigError($hid, "product")) {
				return jsons(["status" => 400, "msg" => "当前产品无法升级或降级"]);
			}
			$currency = $params["currencyid"] ?? "";
			$uid = request()->uid;
			$currency_id = priorityCurrency($uid, $currency);
			$oldhost = \think\Db::name("product_groups")->alias("pg")->field("p.name as host,h.domain,p.description,p.id as pid,h.uid,h.flag,h.firstpaymentamount,h.billingcycle")->withAttr("billingcycle", function ($value) {
				return config("app.billing_cycle_unit")[$value];
			})->leftJoin("products p", "p.gid = pg.id")->leftJoin("host h", "h.productid = p.id")->where("h.id", $hid)->find();
			if ($oldhost["uid"] != $uid) {
				return json(["status" => 400, "msg" => "非法操作"]);
			}
			$oldhost = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $oldhost);
			$host = \think\Db::name("products")->alias("p")->field("p.id as pid,p.name as host,p.description")->leftJoin("product_groups pg", "p.gid = pg.id")->select()->toArray();
			$upgrade_logic = new \app\common\logic\Upgrade();
			$pids = $upgrade_logic->allowUpgradeProducts($oldhost["pid"]);
			$host_filter = [];
			foreach ($host as $k => $product) {
				if ($product["pid"] != $oldhost["pid"] && in_array($product["pid"], $pids)) {
					$product_model = new \app\common\model\ProductModel();
					$cycle = $product_model->getProductCycle($product["pid"], $currency_id, "", "", "", $uid, "", "", $host["flag"], 1);
					$product["cycle"] = $cycle;
					$host_filter[] = $product;
				}
			}
			$currency = (new \app\common\logic\Currencies())->getCurrencies("id,code,prefix,suffix", $currency_id)[0];
			$data["currency"] = $currency;
			$data["old_host"] = $oldhost;
			$data["host"] = $host_filter;
			$re["data"] = $data;
			return jsons($re);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 升降级产品页面提交(包括使用优惠码的情况)
	 * @description 接口说明:升降级产品页面提交
	 * @author wyh
	 * @url /upgrade/upgrade_product_post
	 * @method POST
	 * @param  .name:hid type:number require:1 default: desc: 产品ID(host表的ID)
	 * @param  .name:pid type:number require:1 default: desc: 新产品ID(host表的ID)
	 * @param  .name:billingcycle type:number require:1 default: desc: 周期
	 * @param  .name:currencyid type:number require:0 default: desc: 货币ID（可选）
	 * @return  old_host:原产品信息@
	 * @old_host  host:产品组+产品名
	 * @old_host  domain:域名
	 * @return  des:描述
	 * @return  amount:差价
	 * @return  discount:优惠
	 * @return  amount_total:支付金额
	 * @return  payment:支付方式
	 * @return  hid:原产品ID
	 * @return  pid:产品ID
	 * @return  billingcycle:周期
	 */
	public function upgradeProductPost()
	{
		try {
			if ($this->request->isPost()) {
				$params = $this->request->param();
				$hid = isset($params["hid"]) && !empty($params["hid"]) ? intval($params["hid"]) : "";
				if (!$hid) {
					return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
				}
				$new_pid = isset($params["pid"]) && !empty($params["pid"]) ? intval($params["pid"]) : "";
				if (!$new_pid) {
					return jsons(["status" => 400, "msg" => lang("PLEASE_SELECT_THE_PRODUCT")]);
				}
				$upgrade_logic = new \app\common\logic\Upgrade();
				if (!$upgrade_logic->judgeUpgradeConfigError($hid, "product")) {
					return jsons(["status" => 400, "msg" => "当前产品无法升级或降级"]);
				}
				$currency_id = isset($params["currencyid"]) && !empty($params["currencyid"]) ? intval($params["currencyid"]) : "";
				$billingcycle = isset($params["billingcycle"]) && !empty($params["billingcycle"]) ? strtolower(trim($params["billingcycle"])) : "";
				$uid = request()->uid;
				$host = \think\Db::name("host")->field("uid")->where("id", $hid)->find();
				if ($host["uid"] != $uid) {
					return json(["status" => 400, "msg" => "非法操作"]);
				}
				$data = [];
				$data["hid"] = $hid;
				$data["pid"] = $new_pid;
				$data["billingcycle"] = $billingcycle;
				$data["currencyid"] = $currency_id;
				cache("upgrade_down_product_" . $hid, $data, 86400);
				return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
			}
			return jsons(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 升降级产品页面
	 * @description 接口说明:升降级产品页面
	 * @author wyh
	 * @time 2020-06-22
	 * @url /upgrade/upgrade_product_page
	 * @return  old_host:原产品信息@
	 * @old_host id:原产品ID
	 * @old_host host:产品名称
	 * @old_host domain:产品主机
	 * @return des:新产品描述
	 * @return amount:小计
	 * @return discount:折扣
	 * @return amount_total:总计
	 * @return currency:货币
	 * @return promo_code:优惠码
	 * @return billingcycle:周期
	 * @return billingcycle_zh:周期中文
	 * @method GET
	 * @param  .name:hid type:number require:1 default: desc: 产品ID(host表的ID)
	 */
	public function getUpgradeProductPage()
	{
		try {
			$params = $this->request->param();
			$hid = isset($params["hid"]) ? intval($params["hid"]) : "";
			if (!$hid) {
				return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$uid = request()->uid;
			$host = \think\Db::name("host")->field("uid")->where("id", $hid)->find();
			if ($host["uid"] != $uid) {
				return json(["status" => 400, "msg" => "非法操作"]);
			}
			$upgrade_logic = new \app\common\logic\Upgrade();
			if (!$upgrade_logic->judgeUpgradeConfigError($hid, "product")) {
				return jsons(["status" => 400, "msg" => "当前产品无法升级或降级"]);
			}
			$data = cache("upgrade_down_product_" . $hid);
			if (!$data) {
				return jsons(["status" => 400, "msg" => "请重新选择产品"]);
			}
			$hid = $data["hid"];
			$new_pid = $data["pid"];
			$billingcycle = $data["billingcycle"];
			$currency_id = $data["currencyid"];
			$promo_code = $data["promo_code"] ?? "";
			$upgrade_logic = new \app\common\logic\Upgrade();
			$re = $upgrade_logic->upgradeProductCommon($hid, $new_pid, $billingcycle, $currency_id, $promo_code);
			return jsons($re);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 升降级产品--应用优惠码
	 * @description 接口说明:升降级产品--应用优惠码
	 * @author wyh
	 * @url /upgrade/add_promo_code_product
	 * @method POST
	 * @param  .name:hid type:number require:1 default: desc: 产品ID(host表的ID)
	 * @param  .name:pormo_code type:number require:0 default: desc: 优惠码(可选)
	 */
	public function addPromoToProduct()
	{
		try {
			if ($this->request->isPost()) {
				$params = $this->request->param();
				$hid = isset($params["hid"]) ? intval($params["hid"]) : "";
				$data = cache("upgrade_down_product_" . $hid);
				if (!$hid) {
					return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
				}
				$uid = request()->uid;
				$host = \think\Db::name("host")->field("uid")->where("id", $hid)->find();
				if ($host["uid"] != $uid) {
					return json(["status" => 400, "msg" => "非法操作"]);
				}
				$upgrade_logic = new \app\common\logic\Upgrade();
				if (!$upgrade_logic->judgeUpgradeConfigError($hid, $params["upgrade_type"] ?? "product")) {
					return jsons(["status" => 400, "msg" => "当前产品无法升级或降级"]);
				}
				$promo_code = $params["pormo_code"] ?? "";
				$new_pid = null;
				$upgrade_type = "option";
				if ($params["upgrade_type"] == "product") {
					$new_pid = $data["pid"];
					$upgrade_type = "product";
				}
				$new_billingcycle = $data["billingcycle"];
				$result = $upgrade_logic->checkUpgradePromo($promo_code, $hid, $new_pid, $new_billingcycle, $upgrade_type);
				if ($result["status"] != 200) {
					return jsons($result);
				}
				if (!$data) {
					return jsons(["status" => 400, "msg" => "优惠码无效"]);
				}
				$data["promo_code"] = $promo_code;
				cache("upgrade_down_product_" . $hid, $data, 86400);
				return jsons(["status" => 200, "msg" => "应用优惠码成功"]);
			}
			return jsons(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 升降级产品--移除优惠码
	 * @description 接口说明:升降级产品--移除优惠码
	 * @author wyh
	 * @url /upgrade/remove_promo_code_product
	 * @method POST
	 * @param  .name:hid type:number require:1 default: desc: 产品ID(host表的ID)
	 */
	public function RemovePromoFromProduct()
	{
		try {
			if ($this->request->isPost()) {
				$params = $this->request->param();
				$hid = isset($params["hid"]) ? intval($params["hid"]) : "";
				$data = cache("upgrade_down_product_" . $hid);
				if (!$hid) {
					return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
				}
				$uid = request()->uid;
				$host = \think\Db::name("host")->field("uid")->where("id", $hid)->find();
				if ($host["uid"] != $uid) {
					return json(["status" => 400, "msg" => "非法操作"]);
				}
				$upgrade_logic = new \app\common\logic\Upgrade();
				if (!$upgrade_logic->judgeUpgradeConfigError($hid, "product")) {
					return jsons(["status" => 400, "msg" => "当前产品无法升级或降级"]);
				}
				if (!$data) {
					return jsons(["status" => 400, "msg" => "请重新选择产品"]);
				}
				\think\Db::name("host")->where("id", $hid)->update(["promoid" => 0]);
				$data["promo_code"] = "";
				cache("upgrade_down_product_" . $hid, $data, 86400);
				return jsons(["status" => 200, "msg" => "移除优惠码成功"]);
			}
			return jsons(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 升降级产品结算
	 * @description 接口说明:升降级产品结算
	 * @author wyh
	 * @url /upgrade/checkout_upgrade_product
	 * @method POST
	 * @param  .name:hid type:number require:1 default: desc: 产品ID(host表的ID)
	 * @param  .name:payment type:number require:1 default: desc: 支付方式
	 * @return  old_host:原产品信息@
	 * @old_host  host:产品组+产品名
	 * @old_host  domain:域名
	 * @return  des:描述
	 * @return  amount:差价
	 * @return  discount:优惠
	 * @return  amount_total:支付金额
	 * @return  payment:支付方式
	 * @return  order_id:订单ID
	 * @return  invoice_id:账单ID
	 */
	public function checkoutProductUpgrade()
	{
		try {
			if ($this->request->isPost()) {
				$params = $this->request->param();
				$hid = isset($params["hid"]) && !empty($params["hid"]) ? intval($params["hid"]) : "";
				if (!$hid) {
					return jsons(["status" => 400, "msg" => lang("ID_ERROR")]);
				}
				$upgrade_logic = new \app\common\logic\Upgrade();
				if (!$upgrade_logic->judgeUpgradeConfigError($hid, "product")) {
					return jsons(["status" => 400, "msg" => "当前产品无法升级或降级"]);
				}
				$uid = request()->uid;
				$host = \think\Db::name("host")->field("uid")->where("id", $hid)->find();
				if ($host["uid"] != $uid) {
					return json(["status" => 400, "msg" => "非法操作"]);
				}
				$payment = isset($params["payment"]) && !empty($params["payment"]) ? $params["payment"] : "";
				$data = cache("upgrade_down_product_" . $hid);
				if (!$data) {
					return jsons(["status" => 400, "msg" => "请重新选择产品"]);
				}
				if (cache(md5(serialize($data) . "-" . $hid . "-" . get_client_ip()))) {
					return jsons(["status" => 400, "msg" => "请求过于频繁"]);
				}
				cache(md5(serialize($data) . "-" . $hid . "-" . get_client_ip()), "upgrade", 20);
				$newpid = $data["pid"];
				$billingcycle = $data["billingcycle"];
				$currencyid = $data["currencyid"];
				$promocode = $data["promo_code"] ?? "";
				$upgrade_logic = new \app\common\logic\Upgrade();
				$percent_value = $params["resource_percent_value"] ?: "";
				$result = $upgrade_logic->upgradeProductCommon($hid, $newpid, $billingcycle, $currencyid, $promocode, $payment, true, $percent_value);
				return jsons($result);
			}
			return jsons(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
}