<?php

namespace app\home\controller;

/**
 * @title 前台购物车
 * @description 接口说明：前台购物车
 */
class CartController extends CommonController
{
	private $imageaddress;
	private $allowSystem;
	private $system;
	private $osIco;
	private $ext = "svg";
	public function initialize()
	{
		parent::initialize();
		$this->allowSystem = config("allow_system");
		$this->system = config("system_list");
		$this->imageaddress = config("servers");
		$this->osIco = config("system");
	}
	private function getProductType($pid = 0)
	{
		$list = (new \app\common\logic\Menu())->getOneNavs("client", null);
		$p_list = array_filter($list, function ($v) {
			return $v["nav_type"] == 2;
		});
		if ($pid) {
			foreach ($p_list as $key => $val) {
				$p_list[$key]["is_active"] = 0;
				if (in_array($pid, explode(",", $val["relid"]))) {
					$p_list[$key]["is_active"] = 1;
				}
			}
		}
		return array_values($p_list);
	}
	private function setProToNav($param, $id)
	{
		$menu = new \app\common\logic\Menu();
		$menu_list = $menu->getOneNavs("client", null);
		if (!$param["ptype"] || !isset($menu_list[$param["ptype"]])) {
			throw new \Exception("前台导航页面不存在！");
		}
		foreach ($menu_list as $key => $val) {
			if ($val["nav_type"] == 2) {
				$relid = explode(",", $val["relid"]);
				$is_exits = array_search($id, $relid);
				if ($is_exits !== false) {
					unset($relid[$is_exits]);
					$menu_list[$key]["relid"] = implode(",", $relid);
				}
			}
		}
		$p_relid = explode(",", $menu_list[$param["ptype"]]["relid"]);
		$menu_list[$param["ptype"]]["relid"] = implode(",", array_merge($p_relid, [$id]));
		return $menu->editDefaultNav($menu_list);
	}
	/**
	 * @title 获取分组
	 * @description 接口说明:获取分组
	 * @author xiong
	 * @time 2021-10-20
	 * @url /cart/productsgroups
	 * @method POST
	 */
	public function postProductGroups()
	{
		$resource = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$resource_id = $resource["id"];
		$first_groups = \think\Db::name("product_first_groups")->field("id,name")->where("hidden", 0)->where("is_upstream", 1)->where("zjmf_api_id", $resource_id)->order("order", "asc")->order("id", "asc")->select()->toArray();
		$product_groups = \think\Db::name("product_groups")->field("id,gid,name")->where("hidden", 0)->where("is_upstream", 1)->where("zjfm_api_id", $resource_id)->order("order", "asc")->order("id", "asc")->select()->toArray();
		$groups = [];
		$_product_groups = [];
		foreach ($product_groups as $second_key => $second) {
			$_product_groups[$second["gid"]][$second_key]["id"] = $second["id"];
			$_product_groups[$second["gid"]][$second_key]["name"] = $second["name"];
		}
		foreach ($first_groups as $first_key => $first) {
			if (!empty($_product_groups[$second["gid"]])) {
				$groups[$first_key]["id"] = $first["id"];
				$groups[$first_key]["name"] = $first["name"];
				$groups[$first_key]["second"] = array_merge($_product_groups[$second["gid"]]);
			}
		}
		if (count($groups) == 0) {
			$groups[0]["id"] = 0;
			$groups[0]["name"] = "一级分组(系统默认)";
			$second[0]["id"] = 0;
			$second[0]["name"] = "二级分组(系统默认)";
			$groups[0]["second"] = $second;
		}
		return jsons(["status" => 200, "data" => $groups]);
	}
	/**
	 * @title 导入商品
	 * @description 接口说明:导入商品
	 * @author wyh
	 * @time 2021-08-25
	 * @url /cart/createproducts
	 * @method POST
	 */
	public function postCreateProducts()
	{
		$param = $this->request->param();
		\think\Db::startTrans();
		try {
			$percent_value = $param["percent_value"];
			$supplier_currency = $param["currency"];
			$currency = \think\Db::name("currencies")->where("default", 1)->value("code");
			if ($currency == $supplier_currency["code"]) {
				$rate = 1;
			} else {
				$arr = getRate("json");
				$rate = bcdiv($arr[$currency], $arr[$supplier_currency["code"]], 20);
			}
			$product = $param["product"];
			$product_groups_id = $param["product_groups_id"];
			$token = $param["token"];
			$pid = $product["id"];
			unset($product["id"]);
			unset($product["gid"]);
			unset($product["token"]);
			unset($product["resource_pid"]);
			unset($product["info"]);
			unset($product["version"]);
			unset($product["app_file"]);
			unset($product["instruction"]);
			unset($product["icon"]);
			unset($product["unretired_time"]);
			unset($product["reason"]);
			unset($product["app_tag_id"]);
			unset($product["professional_discount"]);
			unset($product["app_auth"]);
			unset($product["app_open_source"]);
			$api_type = $product["api_type"];
			$upstream_pid = $product["upstream_pid"];
			$upstream_price_type = $product["upstream_price_type"];
			$upstream_price_value = $product["upstream_price_value"];
			$resource = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
			$resource_id = $resource["id"];
			if (!empty($product_groups_id)) {
				$gexist = \think\Db::name("product_groups")->where("id", $product_groups_id)->find();
				$fgid = $gexist["gid"];
				$gid = $gexist["id"];
			} else {
				$fexist = \think\Db::name("product_first_groups")->where("name", "一级分组(系统默认)")->find();
				if (empty($fexist)) {
					$fgid = \think\Db::name("product_first_groups")->insertGetId(["name" => "一级分组(系统默认)", "hidden" => 0, "order" => 0, "create_time" => time(), "zjmf_api_id" => $resource_id, "is_upstream" => 1]);
				} else {
					$fgid = $fexist["id"];
				}
				$gexist = \think\Db::name("product_groups")->where("name", "二级分组(系统默认)")->find();
				if (empty($gexist)) {
					$gid = \think\Db::name("product_groups")->insertGetId(["name" => "二级分组(系统默认)", "headline" => "", "tagline" => "", "order_frm_tpl" => "default", "disabled_gateways" => "", "order" => 0, "type" => 1, "create_time" => time(), "gid" => $fgid, "tpl_type" => "default", "is_upstream" => 1, "zjfm_api_id" => $resource_id]);
				} else {
					$gid = $gexist["id"];
				}
			}
			$product["gid"] = $gid;
			$product["api_type"] = "zjmf_api";
			$product["zjmf_api_id"] = $resource_id;
			$product["server_group"] = $resource_id;
			$product["upstream_version"] = $product["location_version"] ?: 1;
			$product["location_version"] = 1;
			$product["upstream_pid"] = $pid;
			$product["upstream_price_type"] = "percent";
			$product["upstream_price_value"] = $percent_value;
			$product = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $product);
			$id = \think\Db::name("products")->insertGetId($product);
			$ptype["ptype"] = $this->getProductType()[0]["id"];
			$this->setProToNav($ptype, $id);
			$up = ["product_shopping_url" => request()->domain() . "/cart?action=configureproduct&pid=" . $id, "product_group_url" => request()->domain() . "/cart?gid=" . $gid . "&fid=" . $fgid];
			\think\Db::name("products")->where("id", $id)->update($up);
			$currencies = \think\Db::name("currencies")->field("id,code")->where("default", 1)->select()->toArray();
			$product_pricings = $param["product_pricings"];
			$price_type = config("price_type");
			if (!empty($product_pricings[0])) {
				foreach ($currencies as $currency) {
					foreach ($product_pricings as $product_pricing) {
						if ($product_pricing["code"] == $currency["code"]) {
							unset($product_pricing["id"]);
							unset($product_pricing["code"]);
							$product_pricing["relid"] = $id;
							$product_pricing["currency"] = $currency["id"];
							if ($api_type == "zjmf_api" && $upstream_pid > 0 && $upstream_price_type == "percent") {
								foreach ($price_type as $v) {
									$product_pricing[$v[0]] = $product_pricing[$v[0]] * $upstream_price_value / 100;
									$product_pricing[$v[1]] = $product_pricing[$v[1]] * $upstream_price_value / 100;
								}
							}
							\think\Db::name("pricing")->insert($product_pricing);
						} else {
							unset($product_pricing["id"]);
							unset($product_pricing["code"]);
							$product_pricing["relid"] = $id;
							$product_pricing["currency"] = $currency["id"];
							foreach ($price_type as $v) {
								if ($product_pricing[$v[0]] >= 0) {
									$product_pricing[$v[0]] = $product_pricing[$v[0]] * $rate;
								}
								$product_pricing[$v[1]] = $product_pricing[$v[1]] * $rate;
								if ($api_type == "zjmf_api" && $upstream_pid > 0 && $upstream_price_type == "percent") {
									if ($product_pricing[$v[0]] >= 0) {
										$product_pricing[$v[0]] = $product_pricing[$v[0]] * $upstream_price_value / 100;
									}
									$product_pricing[$v[1]] = $product_pricing[$v[1]] * $upstream_price_value / 100;
								}
							}
							\think\Db::name("pricing")->insert($product_pricing);
						}
					}
				}
			}
			$customfields = $param["customfields"];
			if (!empty($customfields[0])) {
				foreach ($customfields as $customfield) {
					$customfield["type"] = "product";
					$customfield["relid"] = $id;
					$customfield["adminonly"] = 0;
					$customfield["create_time"] = time();
					$customfield["update_time"] = 0;
					$customfield["upstream_id"] = $customfield["id"];
					unset($customfield["id"]);
					\think\Db::name("customfields")->insertGetId($customfield);
				}
			}
			$config_groups = $param["config_groups"];
			if (!empty($config_groups[0])) {
				foreach ($config_groups as $config_group) {
					$options = $config_group["options"];
					$config_group["upstream_id"] = $config_group["id"];
					unset($config_group["id"]);
					unset($config_group["options"]);
					$gid = \think\Db::name("product_config_groups")->insertGetId($config_group);
					$config_link = ["gid" => $gid, "pid" => $id];
					\think\Db::name("product_config_links")->insert($config_link);
					foreach ($options as $option) {
						unset($option["advanced"]);
						$subs = $option["sub"];
						$option["upstream_id"] = $option["id"];
						unset($option["id"]);
						unset($option["gid"]);
						unset($option["sub"]);
						$option["gid"] = $gid;
						$option["auto"] = 1;
						$option["is_rebate"] = $option["is_rebate"] ?? 1;
						$option["qty_stage"] = $option["qty_stage"] ?? 0;
						$config_id = \think\Db::name("product_config_options")->insertGetId($option);
						foreach ($subs as $sub) {
							$pricings = $sub["pricings"];
							$sub["upstream_id"] = $sub["id"];
							unset($sub["id"]);
							unset($sub["config_id"]);
							unset($sub["pricings"]);
							$sub["config_id"] = $config_id;
							$sub_id = \think\Db::name("product_config_options_sub")->insertGetId($sub);
							foreach ($currencies as $currency) {
								foreach ($pricings as $pricing) {
									if ($pricing["code"] == $currency["code"]) {
										unset($pricing["id"]);
										unset($pricing["currency"]);
										unset($pricing["relid"]);
										unset($pricing["code"]);
										$pricing["currency"] = $currency["id"];
										$pricing["relid"] = $sub_id;
										if ($api_type == "zjmf_api" && $upstream_pid > 0 && $upstream_price_type == "percent") {
											foreach ($price_type as $v) {
												$pricing[$v[0]] = $pricing[$v[0]] * $upstream_price_value / 100;
												$pricing[$v[1]] = $pricing[$v[1]] * $upstream_price_value / 100;
											}
										}
										\think\Db::name("pricing")->insert($pricing);
									} else {
										unset($pricing["id"]);
										unset($pricing["currency"]);
										unset($pricing["relid"]);
										unset($pricing["code"]);
										$pricing["currency"] = $currency["id"];
										$pricing["relid"] = $sub_id;
										foreach ($price_type as $v) {
											$pricing[$v[0]] = $rate * $pricing[$v[0]];
											$pricing[$v[1]] = $rate * $pricing[$v[1]];
											if ($api_type == "zjmf_api" && $upstream_pid > 0 && $upstream_price_type == "percent") {
												$pricing[$v[0]] = $pricing[$v[0]] * $upstream_price_value / 100;
												$pricing[$v[1]] = $pricing[$v[1]] * $upstream_price_value / 100;
											}
										}
										\think\Db::name("pricing")->insert($pricing);
									}
								}
							}
						}
					}
				}
			}
			$advanced = $param["advanced"];
			foreach ($advanced as $m) {
				if ($m["type"] == "condition") {
					$advanced_sub_id = $m["sub_id"];
					$new_advanced_data = [];
					foreach ($advanced_sub_id as $nn => $mm) {
						$advanced_sub = \think\Db::name("product_config_options_sub")->field("config_id,id")->where("upstream_id", $nn)->order("id", "desc")->find();
						$new_advanced_sub_id = $advanced_sub["id"];
						$config_id_condition = $advanced_sub["config_id"];
						$new_advanced_data[$new_advanced_sub_id] = $mm;
					}
					$new_advanced = ["config_id" => intval($config_id_condition), "sub_id" => json_encode($new_advanced_data), "relation" => $m["relation"], "type" => $m["type"], "relation_id" => 0, "upstream_id" => $m["id"]];
					$condition_id = \think\Db::name("product_config_options_links")->insertGetId($new_advanced);
					foreach ($advanced as $m3) {
						if ($m3["type"] == "result") {
							if ($m3["relation_id"] == $m["id"]) {
								$advanced_sub_id_result = $m3["sub_id"];
								$new_advanced_data_result = [];
								foreach ($advanced_sub_id_result as $n4 => $m4) {
									$new_advanced_sub_result = \think\Db::name("product_config_options_sub")->field("config_id,id")->where("upstream_id", $n4)->order("id", "desc")->find();
									$new_advanced_sub_id_result = $new_advanced_sub_result["id"];
									$config_id_result = $new_advanced_sub_result["config_id"];
									$new_advanced_data_result[$new_advanced_sub_id_result] = $m4;
								}
								$result_advanced = ["config_id" => intval($config_id_result), "sub_id" => json_encode($new_advanced_data_result), "relation" => $m3["relation"], "type" => $m3["type"], "relation_id" => $condition_id, "upstream_id" => $m3["id"]];
								\think\Db::name("product_config_options_links")->insertGetId($result_advanced);
							}
						}
					}
				}
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsons(["status" => 400, "msg" => "代理失败:" . $e->getMessage()]);
		}
		return jsons(["status" => 200, "msg" => "代理成功", "rate" => $rate]);
	}
	public function getCredit()
	{
		$uid = $this->request->uid;
		$credit = \think\Db::name("clients")->where("id", $uid)->value("credit");
		$currency = getUserCurrency($uid);
		$data = ["credit" => $credit, "currency" => $currency];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function getQty()
	{
		$param = $this->request->param();
		$pid = intval($param["pid"]);
		$product = \think\Db::name("products")->field("qty,stock_control,hidden")->where("id", $pid)->find();
		$data = ["product" => $product];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * 时间 2021-06-23
	 * @title 批量拉取产品信息的前台会员中心接口
	 * @desc 批量拉取产品信息的前台会员中心接口
	 * @url /cart/hostinfo
	 * @param  .name:hostid[] type:int require:1 desc:产品ID，数组
	 * @param  .name:all type:int require:0 desc:当all=1时，表示需要配置数据
	 * @method GET
	 * @author wyh
	 * @return  hosts:基础数据@
	 * @hosts domain:主机
	 * @hosts dedicatedip:ip
	 * @hosts assignedips：附加ip
	 * @hosts create_time：购买时间
	 * @hosts nextduedate：到期时间
	 * @hosts billingcycle：周期
	 * @hosts billingcycle_zh
	 * @hosts firstpaymentamount：首付金额
	 * @hosts amount：续费金额
	 * @hosts port：端口
	 * @hosts username：用户名
	 * @hosts password：密码
	 * @hosts initiative_renew：自动续费
	 * @hosts domainstatus：状态
	 * @hosts domainstatus_zh
	 */
	public function hostInfo()
	{
		$param = $this->request->param();
		$uid = request()->uid;
		$hids = is_array($param["hostid"]) ? $param["hostid"] : [];
		$hosts = \think\Db::name("host")->field("id,productid,domain,dedicatedip,assignedips,create_time,nextduedate,billingcycle,billingcycle as billingcycle_zh,firstpaymentamount,amount,port,username,password,initiative_renew,domainstatus,domainstatus as domainstatus_zh")->where("uid", $uid)->whereIn("id", $hids)->withAttr("billingcycle_zh", function ($value, $data) {
			return config("billing_cycle")[$value];
		})->withAttr("domainstatus_zh", function ($value, $data) {
			return config("public.domainstatus")[$value];
		})->withAttr("assignedips", function ($value) {
			return explode(",", $value);
		})->withAttr("password", function ($value) {
			return cmf_decrypt($value);
		})->order("id", "desc")->select()->toArray();
		$user_currcy = getUserCurrency($uid);
		if ($param["all"]) {
			$user_currcy_id = $user_currcy["id"];
			foreach ($hosts as $host) {
				$hostid = $host["id"];
				$productid = $host["productid"];
				$billingcycle = $host["billingcycle"];
				$host_option_config = \think\Db::name("host_config_options")->where("relid", $hostid)->select()->toArray();
				$returndata["host_option_config"] = $host_option_config;
				$config_option_logic = new \app\common\logic\ConfigOptions();
				$configInfo = $config_option_logic->getConfigInfo($productid, true);
				$config_array = $config_option_logic->configShow($configInfo, $user_currcy_id, $billingcycle);
				$host["config_array"] = $config_array;
			}
		}
		$data = ["hosts" => $hosts, "currency" => $user_currcy["prefix"]];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function ontrialAndMax()
	{
		$uid = \request()->uid;
		$param = $this->request->param();
		$pid = intval($param["pid"]);
		$pro = \think\Db::name("products")->where("id", $pid)->find();
		if ($pro["api_type"] == "resource") {
			return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["product" => $pro]]);
		}
		$pro = \think\Db::name("api_user_product")->field("ontrial,qty")->where("uid", $uid)->where("pid", $pid)->find();
		$pro = $pro ?: [];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["product" => $pro]]);
	}
	/**
	 * 时间 2021-05-27
	 * @title API概览
	 * @desc API概览,上游管理下游
	 * @url /cart/summary
	 * @param  .name:uid type:int require:1 desc:客户ID
	 * @method GET
	 * @author wyh
	 * @return  client:基础数据@
	 * @client api_password:api密钥
	 * @client api_create_time:开通时间
	 * @client agent_count:代理商品数量
	 * @client host_count:API产品数量 总量
	 * @client active_count:API产品数量 已激活
	 * @client api_count:昨日api请求次数
	 * @client ratio:日环比
	 * @client up:1上升，0下降
	 * @return form_api:最近7天每天的api请求次数
	 * @return free_products:豁免产品@
	 * @free_products id:
	 * @free_products name:名称
	 * @free_products ontrial:试用数量
	 * @free_products qty:最大购买数量
	 */
	public function summary()
	{
		$uid = request()->uid;
		$client = \think\Db::name("clients")->field("api_password,api_create_time,api_open,lock_reason,api_lock_time")->where("id", $uid)->find();
		if (configuration("allow_resource_api") == 0 || $client["api_open"] == 0) {
			return jsons(["status" => 400, "msg" => "暂未开通API功能"]);
		}
		$client["api_password"] = aesPasswordDecode($client["api_password"]);
		$agent_pids = \think\Db::name("api_resource_log")->where("uid", $uid)->where("pid", "<>", 0)->field("pid")->distinct(true)->column("pid");
		$host_count = \think\Db::name("host")->whereIn("productid", $agent_pids)->where("uid", $uid)->where("stream_info", "like", "%downstream_url%")->count();
		$active_count = \think\Db::name("host")->where("domainstatus", "Active")->whereIn("productid", $agent_pids)->where("uid", $uid)->where("stream_info", "like", "%downstream_url%")->count();
		$client["agent_count"] = count($agent_pids);
		$client["host_count"] = $host_count;
		$client["active_count"] = $active_count;
		$yesterday_start = strtotime(date("Y-m-d", time()));
		$yesterday_end = $yesterday_start + 86400;
		$api_count = \think\Db::name("api_resource_log")->where("uid", $uid)->whereBetweenTime("create_time", $yesterday_start, $yesterday_end)->count();
		$client["api_count"] = $api_count;
		$before_yesterday_start = strtotime(date("Y-m-d", strtotime("-1 days")));
		$before_yesterday_end = $before_yesterday_start + 86400;
		$api_count2 = \think\Db::name("api_resource_log")->where("uid", $uid)->whereBetweenTime("create_time", $before_yesterday_start, $before_yesterday_end)->count();
		$ratio1 = bcdiv($api_count, $api_count2, 2) * 100;
		$client["ratio"] = $ratio1 . "%";
		$before_yesterday_start2 = strtotime(date("Y-m-d", strtotime("-2 days")));
		$before_yesterday_end2 = $before_yesterday_start2 + 86400;
		$api_count3 = \think\Db::name("api_resource_log")->where("uid", $uid)->whereBetweenTime("create_time", $before_yesterday_start2, $before_yesterday_end2)->count();
		$ratio2 = bcdiv($api_count3, $api_count2, 2) * 100;
		$client["up"] = 0;
		if ($ratio2 <= $ratio1) {
			$client["up"] = 1;
		}
		$form_api = $this->getEveryDayTotal(strtotime(date("Y-m-d", strtotime("-6 days"))));
		$free_products = \think\Db::name("api_user_product")->field("a.id,b.name,a.ontrial,a.qty")->alias("a")->leftJoin("products b", "a.pid = b.id")->where("uid", $uid)->select()->toArray();
		$data = ["client" => $client, "form_api" => $form_api, "free_products" => $free_products];
		$result = ["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data];
		return jsons($result);
	}
	private function getEveryDayTotal($month_start)
	{
		$days = 7;
		$month_every_day_total = [];
		for ($i = 0; $i <= $days - 1; $i++) {
			${$i + 1 . "_start"} = strtotime("+" . $i . " days", $month_start);
			${$i + 1 . "_end"} = strtotime("+" . ($i + 1) . " days -1 seconds", $month_start);
			${$i + 1 . "_total"} = $this->getTotalSummary(${$i + 1 . "_start"}, ${$i + 1 . "_end"});
			array_push($month_every_day_total, ${$i + 1 . "_total"});
		}
		return $month_every_day_total;
	}
	private function getTotalSummary($start, $end)
	{
		$total = \think\Db::name("api_resource_log")->where("uid", request()->uid)->whereBetweenTime("create_time", $start, $end)->count();
		return intval($total);
	}
	/**
	 * @title 获取所有产品
	 * @description 接口说明:获取所有产品
	 * @author wyh
	 * @url /cart/all
	 * @method GET
	 * @return .products:所有在售产品
	 * @return .count:产品数量
	 */
	public function getProducts()
	{
		$uid = request()->uid;
		$desc = "客户User ID:{$uid}在" . date("Y-m-d H:i:s") . "调取cart/all接口获取产品数据";
		apiResourceLog($uid, $desc);
		$developer_app_product_type = config("developer_app_product_type");
		$count = \think\Db::name("products")->where("hidden", 0)->where("retired", 0)->whereNotIn("type", array_keys($developer_app_product_type))->count();
		$groups = \think\Db::name("product_groups")->field("id,name")->where("hidden", 0)->select()->toArray();
		foreach ($groups as &$group) {
			$products = \think\Db::name("products")->field("id,type,name,description")->where("gid", $group["id"])->where("hidden", 0)->where("retired", 0)->whereNotIn("type", array_keys($developer_app_product_type))->select()->toArray();
			$group["products"] = $products;
		}
		$code = \think\Db::name("currencies")->where("default", 1)->value("code");
		$data = ["products" => $groups, "count" => $count, "currency" => $code];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 获取产品配置
	 * @description 接口说明:获取产品配置
	 * @author wyh
	 * @url /cart/get_product_config
	 * @method GET
	 * @param .name:pid type:int require:1 default:1 other: desc:产品ID
	 * @return .products:
	 */
	public function getProductConfig()
	{
		$data = [];
		$params = $this->request->param();
		$pid = intval($params["pid"]);
		$uid = request()->uid;
		$flag = getSaleProductUser($pid, $uid);
		$data["flag"] = $flag;
		$product = \think\Db::name("products")->where("id", $pid)->where(function (\think\db\Query $query) {
		})->find();
		if (empty($product)) {
			return jsons(["status" => 400, "msg" => lang("CART_PRO_CONF_NOTFOUND")]);
		}
		$desc = "客户User ID:{$uid}在" . date("Y-m-d H:i:s") . "调取cart/get_product_config接口";
		apiResourceLog($uid, $desc, $pid, $product["location_version"]);
		unset($product["upstream_product_shopping_url"]);
		$data["products"] = $product;
		$fields = \think\Db::name("customfields")->field("id,fieldname,description,fieldtype,fieldoptions,regexpr,required,showorder,showinvoice,sortorder,showdetail")->where("type", "product")->where("relid", $pid)->where("adminonly", 0)->where("showorder", 1)->order("sortorder desc")->select()->toArray();
		$data["customfields"] = $fields;
		$product_pricings = \think\Db::name("pricing")->alias("a")->field("a.*,b.code")->leftJoin("currencies b", "a.currency = b.id")->where("a.type", "product")->where("a.relid", $pid)->where("b.default", 1)->select()->toArray();
		$data["product_pricings"] = $product_pricings;
		$config_groups = \think\Db::name("product_config_groups")->alias("a")->leftJoin("product_config_links b", "a.id = b.gid")->field("a.id,a.name,a.description")->where("b.pid", $pid)->select()->toArray();
		$config_links_data = \think\Db::name("product_config_links")->where("pid", $pid)->select()->toArray();
		$oids_all = [];
		foreach ($config_groups as $k => $v) {
			$options = \think\Db::name("product_config_options")->where("gid", $v["id"])->where("hidden", 0)->select()->toArray();
			foreach ($options as $kk => $vv) {
				$subs = \think\Db::name("product_config_options_sub")->where("config_id", $vv["id"])->where("hidden", 0)->select()->toArray();
				foreach ($subs as $kkk => $vvv) {
					$pricings = \think\Db::name("pricing")->alias("a")->field("a.*,b.code")->leftJoin("currencies b", "a.currency = b.id")->where("type", "configoptions")->where("relid", $vvv["id"])->where("b.default", 1)->select()->toArray();
					$subs[$kkk]["pricings"] = $pricings;
				}
				$options[$kk]["sub"] = $subs;
			}
			$oids_all = array_merge($oids_all, array_column($options, "id"));
			$config_groups[$k]["options"] = $options;
		}
		$advanced = \think\Db::name("product_config_options_links")->whereIn("config_id", array_unique($oids_all))->order("id", "asc")->select()->toArray();
		foreach ($advanced as &$advance) {
			$advance["sub_id"] = json_decode($advance["sub_id"], true);
		}
		$data["advanced"] = $advanced;
		$data["config_groups"] = $config_groups;
		$data["config_links"] = array_column($config_links_data, "gid");
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 产品搜索--
	 * @description 接口说明:产品搜索--
	 * @author wyh
	 * @url /cart/global_search
	 * @method GET
	 * @param .name:keywords type:string require:0 default:0 other: desc:产品搜索关键词
	 * @return .product_groups:所有产品组信息
	 * @return .product_groups.id:产品组id
	 * @return .product_groups.name:产品组名称
	 * @return .product_groups.headline:产品组标题
	 * @return .product_groups.tagline:产品组标签
	 * @return .product_groups.order:产品组排序
	 * @return .currencies:所有货币信息
	 * @return .currencies.id:所有货币信息
	 * @return .currencies.code:符号
	 * @return .currencies.prefix:前缀
	 * @return .currencies.suffix:后缀
	 * @return .default_currency:默认选中货币
	 * @return .products:此产品组下所有产品的信息
	 * @return .products.id:产品ID
	 * @return .products.type:产品类型
	 * @return .products.gid:产品所属产品组
	 *
	 * @return .products.name:产品名称
	 * @return .products.description:产品描述
	 * @return .products.pay_method:付费方式(预付费'prepayment','后付费：postpaid'，)
	 * @return .products.tax:税率
	 * @return .products.order:产品排序
	 * @return .products.currencyid.product_price:此产品currencyid货币下的产品价格(选择周期第一个大于等于0的价格数据及相应的初装费)
	 * @return .products.currencyid.setup_fee:此产品currencyid货币下的产品初装费
	 * @return .products.currencyid.billingcycle:付费周期
	 */
	public function globalSearch()
	{
		$params = $this->request->param();
		$currencies = get_currency();
		$currenciesfilter = [];
		foreach ($currencies as $kk => $currencie) {
			$currenciesfilter[$kk] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $currencie);
		}
		$currency = isset($params["currencyid"]) ? intval($params["currencyid"]) : "";
		$uid = !empty(request()->uid) ? request()->uid : "";
		$currency = $this->currencyPriority($currency, $uid);
		$currencyid = $currency["id"];
		$keywords = isset($params["keywords"]) ? trim($params["keywords"]) : "";
		if (empty($keywords)) {
			return jsons(["status" => 200, "msg" => "请求成功", "count" => 0, "currencies" => $currenciesfilter, "default_currency" => $currency, "products" => []]);
		}
		$app_gid = \think\Db::name("product_groups")->where("order_frm_tpl", "uuid")->value("id");
		$where = function (\think\db\Query $query) use($app_gid, $keywords) {
			$query->where("a.gid", "<>", $app_gid)->where("a.hidden", 0)->where("b.hidden", 0)->where("c.hidden", 0)->where("a.retired", 0);
			if (!empty($keywords)) {
				$query->where("a.name", "like", "%{$keywords}%");
			}
		};
		$count = \think\Db::name("products")->alias("a")->leftJoin("product_groups b", "a.gid = b.id")->leftJoin("product_first_groups c", "b.gid = c.id")->where($where)->count();
		$products = \think\Db::name("products")->alias("a")->field("a.id,a.type,a.gid,a.name,a.description,a.pay_method,a.tax,a.order,a.pay_type,a.api_type,a.upstream_version,a.upstream_price_type,a.upstream_price_value,a.stock_control,a.qty")->leftJoin("product_groups b", "a.gid = b.id")->leftJoin("product_first_groups c", "b.gid = c.id")->where($where)->order("a.order", "asc")->select()->toArray();
		foreach ($products as $kkk => $product) {
			$filterproducts[$kkk] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $product);
		}
		foreach ($filterproducts as $key => $v) {
			if (!empty($v)) {
				$paytype = (array) json_decode($v["pay_type"]);
				$pricing = \think\Db::name("pricing")->where("type", "product")->where("relid", $v["id"])->where("currency", $currencyid)->find();
				if (!empty($paytype["pay_ontrial_status"])) {
					if ($pricing["ontrial"] >= 0) {
						$v["product_price"] = $pricing["ontrial"];
						$v["setup_fee"] = $pricing["ontrialfee"];
						$v["billingcycle"] = "ontrial";
						$v["billingcycle_zh"] = lang("ONTRIAL");
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
					}
					$v["ontrial"] = 1;
					$v["ontrial_cycle"] = $paytype["pay_ontrial_cycle"];
					$v["ontrial_cycle_type"] = $paytype["pay_ontrial_cycle_type"] ?: "day";
					$v["ontrial_price"] = $pricing["ontrial"];
					$v["ontrial_setup_fee"] = $pricing["ontrialfee"];
				} else {
					$v["ontrial"] = 0;
				}
				if ($paytype["pay_type"] == "free") {
					$v["product_price"] = 0;
					$v["setup_fee"] = 0;
					$v["billingcycle"] = "free";
					$v["billingcycle_zh"] = lang("FREE");
				} elseif ($paytype["pay_type"] == "onetime") {
					if ($pricing["onetime"] >= 0) {
						$v["product_price"] = $pricing["onetime"];
						$v["setup_fee"] = $pricing["osetupfee"];
						$v["billingcycle"] = "onetime";
						$v["billingcycle_zh"] = lang("ONETIME");
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
					}
				} else {
					if (!empty($pricing) && $paytype["pay_type"] == "recurring") {
						if ($pricing["hour"] >= 0) {
							$v["product_price"] = $pricing["hour"];
							$v["setup_fee"] = $pricing["hsetupfee"];
							$v["billingcycle"] = "hour";
							$v["billingcycle_zh"] = lang("HOUR");
						} elseif ($pricing["day"] >= 0) {
							$v["product_price"] = $pricing["day"];
							$v["setup_fee"] = $pricing["dsetupfee"];
							$v["billingcycle"] = "day";
							$v["billingcycle_zh"] = lang("DAY");
						} elseif ($pricing["monthly"] >= 0) {
							$v["product_price"] = $pricing["monthly"];
							$v["setup_fee"] = $pricing["msetupfee"];
							$v["billingcycle"] = "monthly";
							$v["billingcycle_zh"] = lang("MONTHLY");
						} elseif ($pricing["quarterly"] >= 0) {
							$v["product_price"] = $pricing["quarterly"];
							$v["setup_fee"] = $pricing["qsetupfee"];
							$v["billingcycle"] = "quarterly";
							$v["billingcycle_zh"] = lang("QUARTERLY");
						} elseif ($pricing["semiannually"] >= 0) {
							$v["product_price"] = $pricing["semiannually"];
							$v["setup_fee"] = $pricing["ssetupfee"];
							$v["billingcycle"] = "semiannually";
							$v["billingcycle_zh"] = lang("SEMIANNUALLY");
						} elseif ($pricing["annually"] >= 0) {
							$v["product_price"] = $pricing["annually"];
							$v["setup_fee"] = $pricing["asetupfee"];
							$v["billingcycle"] = "annually";
							$v["billingcycle_zh"] = lang("ANNUALLY");
						} elseif ($pricing["biennially"] >= 0) {
							$v["product_price"] = $pricing["biennially"];
							$v["setup_fee"] = $pricing["bsetupfee"];
							$v["billingcycle"] = "biennially";
							$v["billingcycle_zh"] = lang("BIENNIALLY");
						} elseif ($pricing["triennially"] >= 0) {
							$v["product_price"] = $pricing["triennially"];
							$v["setup_fee"] = $pricing["tsetupfee"];
							$v["billingcycle"] = "triennially";
							$v["billingcycle_zh"] = lang("TRIENNIALLY");
						} elseif ($pricing["fourly"] >= 0) {
							$v["product_price"] = $pricing["fourly"];
							$v["setup_fee"] = $pricing["foursetupfee"];
							$v["billingcycle"] = "fourly";
							$v["billingcycle_zh"] = lang("FOURLY");
						} elseif ($pricing["fively"] >= 0) {
							$v["product_price"] = $pricing["fively"];
							$v["setup_fee"] = $pricing["fivesetupfee"];
							$v["billingcycle"] = "fively";
							$v["billingcycle_zh"] = lang("FIVELY");
						} elseif ($pricing["sixly"] >= 0) {
							$v["product_price"] = $pricing["sixly"];
							$v["setup_fee"] = $pricing["sixsetupfee"];
							$v["billingcycle"] = "sixly";
							$v["billingcycle_zh"] = lang("SIXLY");
						} elseif ($pricing["sevenly"] >= 0) {
							$v["product_price"] = $pricing["sevenly"];
							$v["setup_fee"] = $pricing["sevensetupfee"];
							$v["billingcycle"] = "sevenly";
							$v["billingcycle_zh"] = lang("SEVENLY");
						} elseif ($pricing["eightly"] >= 0) {
							$v["product_price"] = $pricing["eightly"];
							$v["setup_fee"] = $pricing["eightsetupfee"];
							$v["billingcycle"] = "eightly";
							$v["billingcycle_zh"] = lang("EIGHTLY");
						} elseif ($pricing["ninely"] >= 0) {
							$v["product_price"] = $pricing["ninely"];
							$v["setup_fee"] = $pricing["ninesetupfee"];
							$v["billingcycle"] = "ninely";
							$v["billingcycle_zh"] = lang("NINELY");
						} elseif ($pricing["tenly"] >= 0) {
							$v["product_price"] = $pricing["tenly"];
							$v["setup_fee"] = $pricing["tensetupfee"];
							$v["billingcycle"] = "tenly";
							$v["billingcycle_zh"] = lang("TENLY");
						} else {
							$v["product_price"] = 0;
							$v["setup_fee"] = 0;
							$v["billingcycle"] = "";
							$v["billingcycle_zh"] = lang("PRICE_CONFIG_ERROR");
						}
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
					}
				}
				if ($paytype["pay_type"] == "recurring" && in_array($v["type"], array_keys(config("developer_app_product_type")))) {
					if ($pricing["annually"] > 0) {
						$v["product_price"] = $pricing["annually"];
						$v["setup_fee"] = $pricing["asetupfee"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					}
				}
				$v["product_price"] = bcadd($v["setup_fee"], $v["product_price"], 2);
				$cart_logic = new \app\common\logic\Cart();
				$rebate_total = 0;
				$config_total = $cart_logic->getProductDefaultConfigPrice($v["id"], $currencyid, $v["billingcycle"], $rebate_total);
				$rebate_total = bcadd($v["product_price"], $rebate_total, 2);
				$v["product_price"] = bcadd($v["product_price"], $config_total, 2);
				if ($v["api_type"] == "zjmf_api" && $v["upstream_version"] > 0 && $v["upstream_price_type"] == "percent") {
					$v["product_price"] = bcmul($v["product_price"], $v["upstream_price_value"], 2) / 100;
					if ($v["ontrial"] == 1) {
						$v["ontrial_price"] = bcmul($v["ontrial_price"], $v["upstream_price_value"] / 100, 2);
						$v["ontrial_setup_fee"] = bcmul($v["ontrial_setup_fee"], $v["upstream_price_value"] / 100, 2);
					}
					$rebate_total = bcmul($rebate_total, $v["upstream_price_value"], 2) / 100;
				}
				if ($v["api_type"] == "resource") {
					$grade = resourceUserGradePercent($uid, $v["id"]);
					$v["product_price"] = bcmul($v["product_price"], $grade / 100, 2);
					if ($v["ontrial"] == 1) {
						$v["ontrial_price"] = bcmul($v["ontrial_price"], $grade / 100, 2);
						$v["ontrial_setup_fee"] = bcmul($v["ontrial_setup_fee"], $grade / 100, 2);
					}
					$rebate_total = bcmul($rebate_total, $grade / 100, 2);
				}
				$flag = getSaleProductUser($v["id"], $uid);
				$v["sale_price"] = $v["bates"] = 0;
				if ($flag) {
					if ($flag["type"] == 1) {
						$bates = bcdiv($flag["bates"], 100, 2);
						$rebate = bcmul($rebate_total, 1 - $bates, 2) < 0 ? 0 : bcmul($rebate_total, 1 - $bates, 2);
						$v["sale_price"] = bcsub($v["product_price"], $rebate, 2) < 0 ? 0 : bcsub($v["product_price"], $rebate, 2);
						$v["bates"] = bcmul($v["product_price"], 1 - $bates, 2);
					} elseif ($flag["type"] == 2) {
						$bates = $flag["bates"];
						$rebate = $rebate_total < $bates ? $rebate_total : $bates;
						$v["sale_price"] = bcsub($v["product_price"], $rebate, 2) < 0 ? 0 : bcsub($v["product_price"], $rebate, 2);
						$v["bates"] = $bates;
					}
				}
			}
			$v["pay_type"] = json_decode($v["pay_type"], true);
			$newfilterproducts[$key] = $v;
		}
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "count" => $count, "currencies" => $currenciesfilter, "default_currency" => $currency, "products" => $newfilterproducts ?: [], "order_page_style" => intval(configuration("order_page_style"))]);
	}
	/**
	 * @title 购物车支付，信用接口
	 * @description 接口说明: 购物车支付，信用接口
	 * @author wyh
	 * @url  /cartgateway
	 * @method GET
	 * @return gateways: 支付方式
	 * @return client:客户@
	 * @client credit:余额
	 * @client is_open_credit_limit:1开启信用额支付
	 * @client credit_limit_balance:信用额
	 */
	public function getGateway()
	{
		$client = \think\Db::name("clients")->field("credit,credit_limit,is_open_credit_limit,currency")->where("id", request()->uid)->find();
		$client["is_open_credit_limit"] = configuration("credit_limit") == 1 ? $client["is_open_credit_limit"] : 0;
		$client["amount_to_be_settled"] = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", request()->uid)->sum("total");
		$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", request()->uid)->sum("total");
		$client["credit_limit_used"] = round($client["amount_to_be_settled"] + $unpaid, 2);
		$client["credit_limit_balance"] = round($client["credit_limit"] - $client["credit_limit_used"] > 0 ? $client["credit_limit"] - $client["credit_limit_used"] : 0, 2);
		$data = ["gateways" => gateway_list(), "client" => $client];
		return jsons(["status" => 200, "msg" => "请求成功", "data" => $data]);
	}
	/**
	 * @title 产品首页
	 * @description 接口说明:产品首页( gid==uuid时:官网首页)
	 * @author wyh
	 * @url /cart/index
	 * @method GET
	 * @param .name:first_gid type:int require:0 default:1 other: desc:一级分组ID
	 * @param .name:gid type:int require:0 default:1 other: desc:产品组ID(可选参数，无此参数，默认显示数据库表第一个产品组及其产品)
	 * @param .name:keywords type:string require:0 default:0 other: desc:产品搜索关键词
	 * @param .name:type type:string require:0 default:1 other: desc:官网应用商店列表uuid
	 * @param .name:currencyid type:int require:0 default:1 other: desc:货币ID（可选）
	 * @return  first_groups:一级分组信息
	 * @return .product_groups:所有产品组信息
	 * @return .product_groups.id:产品组id
	 * @return .product_groups.name:产品组名称
	 * @return .product_groups.headline:产品组标题
	 * @return .product_groups.tagline:产品组标签
	 * @return .product_groups.order:产品组排序
	 * @return .currencies:所有货币信息
	 * @return .currencies.id:所有货币信息
	 * @return .currencies.code:符号
	 * @return .currencies.prefix:前缀
	 * @return .currencies.suffix:后缀
	 * @return .default_currency:默认选中货币
	 * @return .products:此产品组下所有产品的信息
	 * @return .products.id:产品ID
	 * @return .products.type:产品类型
	 * @return .products.gid:产品所属产品组
	 * @return .products.name:产品名称
	 * @return .products.description:产品描述
	 * @return .products.pay_method:付费方式(预付费'prepayment','后付费：postpaid'，)
	 * @return .products.tax:税率
	 * @return .products.order:产品排序
	 * @return .products.product_price:产品价格
	 * @return .products.sale_price:客户折扣价格
	 * @return .products.currencyid.product_price:此产品currencyid货币下的产品价格(选择周期第一个大于等于0的价格数据及相应的初装费)
	 * @return .products.currencyid.setup_fee:此产品currencyid货币下的产品初装费
	 * @return .products.currencyid.billingcycle:付费周期
	 * @return  ontrial:1时,表示有试用周期,ontrial_cycle表示天数，ontrial_price表示产品价格,ontrial_setup_fee表示初装费；0时，不管;ontrial_cycle_type试用单位:day/hour
	 */
	public function index()
	{
		$params = $this->request->param();
		$currencies = get_currency();
		$currenciesfilter = [];
		foreach ($currencies as $kk => $currencie) {
			$currenciesfilter[$kk] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $currencie);
		}
		$currency = isset($params["currencyid"]) ? intval($params["currencyid"]) : "";
		$uid = !empty(request()->uid) ? request()->uid : "";
		$currency = $this->currencyPriority($currency, $uid);
		$currencyid = $currency["id"];
		if (!$currencyid) {
			return jsons(["status" => 400, "msg" => lang("NO_THIS_CURRENCY")]);
		}
		$first_groups = \think\Db::name("product_first_groups")->field("id,name")->where("hidden", 0)->order("order", "asc")->order("id", "asc")->select()->toArray();
		if (isset($params["first_gid"]) && !empty($params["first_gid"])) {
			$first_gid = intval($params["first_gid"]);
			$productgroups = $this->getProductGroups($first_gid);
		} else {
			$default_first = \think\Db::name("product_first_groups")->field("id")->where("hidden", 0)->order("order", "asc")->order("id", "asc")->find();
			$first_gid = $default_first["id"];
			$productgroups = $this->getProductGroups($first_gid);
		}
		if (isset($params["type"]) && $params["type"] == "uuid" || !empty($p_uid)) {
			$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
			$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
			$order = !empty($params["order"]) ? trim($params["order"]) : "id";
			$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
			$gid = \think\Db::name("product_groups")->where("order_frm_tpl", "uuid")->value("id");
			$url = request()->domain() . config("app_file_url");
			$products = \think\Db::name("products")->field("id,info,type,gid,name,description,pay_method,tax,order,pay_type,api_type,upstream_version,upstream_price_type,upstream_price_value,stock_control,qty,icon")->where("gid", $gid)->where("hidden", 0)->where("retired", 0)->where("p_uid", ">", 0)->where(function (\think\db\Query $query) use($p_uid) {
				if (!empty($p_uid)) {
					$query->where("p_uid", $p_uid);
				}
			})->withAttr("icon", function ($value) use($url) {
				$icon = explode(",", $value);
				foreach ($icon as &$v) {
					$v = $url . $v;
				}
				return $icon;
			})->order($order, $sort)->order("order", "asc")->select()->toArray();
		} else {
			if (isset($params["gid"]) && !empty($params["gid"])) {
				$gid = intval($params["gid"]);
				$products = \think\Db::name("products")->field("id,type,gid,name,description,pay_method,tax,order,pay_type,api_type,upstream_version,upstream_price_type,upstream_price_value,stock_control,qty")->where("gid", $gid)->where("hidden", 0)->where("retired", 0)->order("order", "asc")->select()->toArray();
			} else {
				$defaultgroup = \think\Db::name("product_groups")->where("gid", $first_gid)->where("hidden", 0)->where("order_frm_tpl", "<>", "uuid")->order("order", "asc")->order("id", "asc")->find();
				if (!empty($defaultgroup)) {
					$groupid = $defaultgroup["id"];
					$products = \think\Db::name("products")->field("id,type,gid,name,description,pay_method,tax,order,pay_type,api_type,upstream_version,upstream_price_type,upstream_price_value,stock_control,qty")->where("gid", $groupid)->where("hidden", 0)->where("retired", 0)->order("order", "asc")->select()->toArray();
				}
			}
		}
		foreach ($products as $kkk => $product) {
			$filterproducts[$kkk] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $product);
		}
		foreach ($filterproducts as $key => $v) {
			if (!empty($v)) {
				$paytype = (array) json_decode($v["pay_type"]);
				$pricing = \think\Db::name("pricing")->where("type", "product")->where("relid", $v["id"])->where("currency", $currencyid)->find();
				if (!empty($paytype["pay_ontrial_status"])) {
					if ($pricing["ontrial"] >= 0) {
						$v["product_price"] = $pricing["ontrial"];
						$v["setup_fee"] = $pricing["ontrialfee"];
						$v["billingcycle"] = "ontrial";
						$v["billingcycle_zh"] = lang("ONTRIAL");
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
					}
					$v["ontrial"] = 1;
					$v["ontrial_cycle"] = $paytype["pay_ontrial_cycle"];
					$v["ontrial_cycle_type"] = $paytype["pay_ontrial_cycle_type"] ?: "day";
					$v["ontrial_price"] = $pricing["ontrial"];
					$v["ontrial_setup_fee"] = $pricing["ontrialfee"];
				} else {
					$v["ontrial"] = 0;
				}
				if ($paytype["pay_type"] == "free") {
					$v["product_price"] = 0;
					$v["setup_fee"] = 0;
					$v["billingcycle"] = "free";
					$v["billingcycle_zh"] = lang("FREE");
				} elseif ($paytype["pay_type"] == "onetime") {
					if ($pricing["onetime"] >= 0) {
						$v["product_price"] = $pricing["onetime"];
						$v["setup_fee"] = $pricing["osetupfee"];
						$v["billingcycle"] = "onetime";
						$v["billingcycle_zh"] = lang("ONETIME");
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
					}
				} else {
					if (!empty($pricing) && $paytype["pay_type"] == "recurring") {
						if ($pricing["hour"] >= 0) {
							$v["product_price"] = $pricing["hour"];
							$v["setup_fee"] = $pricing["hsetupfee"];
							$v["billingcycle"] = "hour";
							$v["billingcycle_zh"] = lang("HOUR");
						} elseif ($pricing["day"] >= 0) {
							$v["product_price"] = $pricing["day"];
							$v["setup_fee"] = $pricing["dsetupfee"];
							$v["billingcycle"] = "day";
							$v["billingcycle_zh"] = lang("DAY");
						} elseif ($pricing["monthly"] >= 0) {
							$v["product_price"] = $pricing["monthly"];
							$v["setup_fee"] = $pricing["msetupfee"];
							$v["billingcycle"] = "monthly";
							$v["billingcycle_zh"] = lang("MONTHLY");
						} elseif ($pricing["quarterly"] >= 0) {
							$v["product_price"] = $pricing["quarterly"];
							$v["setup_fee"] = $pricing["qsetupfee"];
							$v["billingcycle"] = "quarterly";
							$v["billingcycle_zh"] = lang("QUARTERLY");
						} elseif ($pricing["semiannually"] >= 0) {
							$v["product_price"] = $pricing["semiannually"];
							$v["setup_fee"] = $pricing["ssetupfee"];
							$v["billingcycle"] = "semiannually";
							$v["billingcycle_zh"] = lang("SEMIANNUALLY");
						} elseif ($pricing["annually"] >= 0) {
							$v["product_price"] = $pricing["annually"];
							$v["setup_fee"] = $pricing["asetupfee"];
							$v["billingcycle"] = "annually";
							$v["billingcycle_zh"] = lang("ANNUALLY");
						} elseif ($pricing["biennially"] >= 0) {
							$v["product_price"] = $pricing["biennially"];
							$v["setup_fee"] = $pricing["bsetupfee"];
							$v["billingcycle"] = "biennially";
							$v["billingcycle_zh"] = lang("BIENNIALLY");
						} elseif ($pricing["triennially"] >= 0) {
							$v["product_price"] = $pricing["triennially"];
							$v["setup_fee"] = $pricing["tsetupfee"];
							$v["billingcycle"] = "triennially";
							$v["billingcycle_zh"] = lang("TRIENNIALLY");
						} elseif ($pricing["fourly"] >= 0) {
							$v["product_price"] = $pricing["fourly"];
							$v["setup_fee"] = $pricing["foursetupfee"];
							$v["billingcycle"] = "fourly";
							$v["billingcycle_zh"] = lang("FOURLY");
						} elseif ($pricing["fively"] >= 0) {
							$v["product_price"] = $pricing["fively"];
							$v["setup_fee"] = $pricing["fivesetupfee"];
							$v["billingcycle"] = "fively";
							$v["billingcycle_zh"] = lang("FIVELY");
						} elseif ($pricing["sixly"] >= 0) {
							$v["product_price"] = $pricing["sixly"];
							$v["setup_fee"] = $pricing["sixsetupfee"];
							$v["billingcycle"] = "sixly";
							$v["billingcycle_zh"] = lang("SIXLY");
						} elseif ($pricing["sevenly"] >= 0) {
							$v["product_price"] = $pricing["sevenly"];
							$v["setup_fee"] = $pricing["sevensetupfee"];
							$v["billingcycle"] = "sevenly";
							$v["billingcycle_zh"] = lang("SEVENLY");
						} elseif ($pricing["eightly"] >= 0) {
							$v["product_price"] = $pricing["eightly"];
							$v["setup_fee"] = $pricing["eightsetupfee"];
							$v["billingcycle"] = "eightly";
							$v["billingcycle_zh"] = lang("EIGHTLY");
						} elseif ($pricing["ninely"] >= 0) {
							$v["product_price"] = $pricing["ninely"];
							$v["setup_fee"] = $pricing["ninesetupfee"];
							$v["billingcycle"] = "ninely";
							$v["billingcycle_zh"] = lang("NINELY");
						} elseif ($pricing["tenly"] >= 0) {
							$v["product_price"] = $pricing["tenly"];
							$v["setup_fee"] = $pricing["tensetupfee"];
							$v["billingcycle"] = "tenly";
							$v["billingcycle_zh"] = lang("TENLY");
						} else {
							$v["product_price"] = 0;
							$v["setup_fee"] = 0;
							$v["billingcycle"] = "";
							$v["billingcycle_zh"] = lang("PRICE_CONFIG_ERROR");
						}
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
					}
				}
				if ($paytype["pay_type"] == "recurring" && in_array($v["type"], array_keys(config("developer_app_product_type")))) {
					if ($pricing["annually"] > 0) {
						$v["product_price"] = $pricing["annually"];
						$v["setup_fee"] = $pricing["asetupfee"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					}
				}
				$v["product_price"] = bcadd($v["setup_fee"], $v["product_price"], 2);
				$cart_logic = new \app\common\logic\Cart();
				$rebate_total = 0;
				$config_total = $cart_logic->getProductDefaultConfigPrice($v["id"], $currencyid, $v["billingcycle"], $rebate_total);
				$rebate_total = bcadd($v["product_price"], $rebate_total, 2);
				$v["product_price"] = bcadd($v["product_price"], $config_total, 2);
				if ($v["api_type"] == "zjmf_api" && $v["upstream_version"] > 0 && $v["upstream_price_type"] == "percent") {
					$v["product_price"] = bcmul($v["product_price"], $v["upstream_price_value"] / 100, 2);
					if ($v["ontrial"] == 1) {
						$v["ontrial_price"] = bcmul($v["ontrial_price"], $v["upstream_price_value"] / 100, 2);
						$v["ontrial_setup_fee"] = bcmul($v["ontrial_setup_fee"], $v["upstream_price_value"] / 100, 2);
					}
					$rebate_total = bcmul($rebate_total, $v["upstream_price_value"] / 100, 2);
				}
				if ($v["api_type"] == "resource") {
					$grade = resourceUserGradePercent($uid, $v["id"]);
					$v["product_price"] = bcmul($v["product_price"], $grade / 100, 2);
					if ($v["ontrial"] == 1) {
						$v["ontrial_price"] = bcmul($v["ontrial_price"], $grade / 100, 2);
						$v["ontrial_setup_fee"] = bcmul($v["ontrial_setup_fee"], $grade / 100, 2);
					}
					$rebate_total = bcmul($rebate_total, $grade / 100, 2);
				}
				$flag = getSaleProductUser($v["id"], $uid);
				$v["sale_price"] = $v["bates"] = 0;
				$v["has_bates"] = 0;
				if ($flag) {
					if ($flag["type"] == 1) {
						$bates = bcdiv($flag["bates"], 100, 2);
						$rebate = bcmul($rebate_total, 1 - $bates, 2) < 0 ? 0 : bcmul($rebate_total, 1 - $bates, 2);
						$v["sale_price"] = bcsub($v["product_price"], $rebate, 2) < 0 ? 0 : bcsub($v["product_price"], $rebate, 2);
						$v["bates"] = bcmul($v["product_price"], 1 - $bates, 2);
					} elseif ($flag["type"] == 2) {
						$bates = $flag["bates"];
						$rebate = $rebate_total < $bates ? $rebate_total : $bates;
						$v["sale_price"] = bcsub($v["product_price"], $rebate, 2) < 0 ? 0 : bcsub($v["product_price"], $rebate, 2);
						$v["bates"] = $bates;
					}
					$v["has_bates"] = 1;
				}
			}
			$v["pay_type"] = json_decode($v["pay_type"], true);
			$newfilterproducts[$key] = $v;
			if ($v["billingcycle"] == "") {
				unset($newfilterproducts[$key]);
			}
		}
		$newfilterproducts = array_values($newfilterproducts);
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "product_groups" => $productgroups ?? [], "currencies" => $currenciesfilter, "default_currency" => $currency, "products" => $newfilterproducts, "first_groups" => $first_groups, "order_page_style" => intval(configuration("order_page_style"))]);
	}
	/**
	 * @title 选择配置页面
	 * @description 接口说明:选择配置页面 + (可选数量 --wyh20200705新增)
	 * @author wyh
	 * @url /cart/set_config
	 * @method get
	 * @param .name:pid type:int require:1 default:1 other: desc:产品ID
	 * @param .name:billingcycle type:int require:1 default:1 other: desc:周期
	 * @return  product:当周期为ontrial时,会多一个参数pay_ontrial_cycle(试用天数)
	 */
	public function setConfig()
	{
		$data = $this->request->param();
		$pid = intval($data["pid"]);
		$billingcycle = isset($data["billingcycle"]) ? $data["billingcycle"] : "";
		$pro = \think\Db::name("products")->where("id", $pid)->find();
		if ($pro["hidden"] == 1 && $pro["api_type"] == "resource") {
			return jsons(["status" => 400, "msg" => "商品不存在"]);
		}
		if ($pro["api_type"] == "zjmf_api") {
			$zjmf_finance_api_id = $pro["zjmf_api_id"];
			$upstream_pid = $pro["upstream_pid"];
			$api = \think\Db::name("zjmf_finance_api")->where("id", $zjmf_finance_api_id)->find();
			if ($api["auto_update"] == 1) {
				$param = ["pid" => $pid, "zjmf_finance_api_id" => $zjmf_finance_api_id, "upstream_pid" => $upstream_pid, "timeout" => 2, "page_type" => "set_config_page", "upstream_price_type" => $pro["upstream_price_type"], "upstream_price_value" => $pro["upstream_price_value"]];
				(new \app\common\logic\Product())->syncProduct($param);
			}
		}
		$servers = \think\Db::name("products")->alias("p")->field("s.id,s.name,s.noc")->leftJoin("server_groups sg", "sg.id = p.server_group")->leftJoin("servers s", "s.gid = sg.id")->where("p.id", $pid)->select()->toArray();
		$serversfilter = [];
		foreach ($servers as $key => $server) {
			$serversfilter[$key]["id"] = $server["id"];
			$serversfilter[$key]["name"] = $server["name"];
			if (!empty($server["noc"])) {
				$noc = $this->imageaddress . $server["noc"];
				$serversfilter[$key]["noc"] = base64EncodeImage($noc);
			} else {
				$serversfilter[$key]["noc"] = "";
			}
		}
		$uid = request()->uid;
		$currencyid = priorityCurrency($uid);
		$currency = get_currency();
		if (empty($billingcycle)) {
			$product_model = new \app\common\model\ProductModel();
			$billingcycle = $product_model->getProductCycle($pid, $currencyid, "", "", "", "", "", "", 1)[0]["billingcycle"] ?: "";
		}
		$customfields = new \app\common\logic\Customfields();
		$fields = $customfields->getCartCustomField($pid);
		$cart = new \app\common\logic\Cart();
		$product = $cart->getProductCycle($pid, $currencyid);
		$config_logic = new \app\common\logic\ConfigOptions();
		$alloption = $config_logic->getConfigInfo($pid);
		$hook_filter = hook_one("pre_cart_product_config", ["uid" => $uid, "pid" => $pid, "options" => $alloption]);
		if ($hook_filter) {
			$alloption = $hook_filter;
		}
		$alloption = $config_logic->configShow($alloption, $currencyid, $billingcycle);
		$alloption = $this->handleLinkAgeLevel($alloption);
		$alloption = $this->handleTreeArr($alloption);
		$pro = \think\Db::name("products")->field("allow_qty,pay_type")->where("id", $pid)->find();
		$allow_qty = $pro["allow_qty"];
		$developer_app = checkDeveloperApp($pid);
		if (!empty($developer_app)) {
			$hosts = \think\Db::name("host")->alias("a")->field("a.id,b.name,a.dedicatedip")->leftJoin("products b", "a.productid = b.id")->leftJoin("customfields c", "c.relid = b.id")->where("c.type", "product")->where("a.domainstatus", "Active")->where(function (\think\db\Query $query) use($developer_app) {
				if ($developer_app["type"] == "finance") {
					$query->where("c.fieldname", "type_zjmffinance");
				} elseif ($developer_app["type"] == "zjmf_cloud") {
					$query->where("c.fieldname", "type_zjmfcloud");
				} elseif ($developer_app["type"] == "zjmf_dcim") {
					$query->where("c.fieldname", "type_dcim");
				}
			})->where("a.uid", $uid)->select()->toArray();
		}
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
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "servers" => $serversfilter, "currency" => $currency, "dafault_currencyid" => $currencyid, "product" => $product, "option" => $alloption, "custom_fields" => $fields, "allow_qty" => $allow_qty, "developer_app" => !empty($developer_app) ? 1 : 0, "hosts" => $hosts ?? [], "links" => $links ?? []]);
	}
	public function getLinkAgeListJson()
	{
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $this->getLinkAgeList()]);
	}
	public function getLinkAgeList()
	{
		$req = $this->request;
		$currencyid = priorityCurrency($req->uid);
		$product_model = new \app\common\model\ProductModel();
		$billingcycle = $product_model->getProductCycle($req->pid, $currencyid, "", "", "", "", "", "", 1)[0]["billingcycle"] ?: "";
		$config_logic = new \app\common\logic\ConfigOptions();
		$alloption = $config_logic->getConfigInfo($req->pid);
		$alloption = $config_logic->configShow($alloption, $currencyid, $billingcycle);
		if (!$alloption) {
			return $alloption;
		}
		$data = array_column($alloption, null, "id");
		$all_list = $config_logic->webGetLinkAgeList($req);
		$linkAge = $config_logic->webSetLinkAgeListDefaultVal($all_list, $req);
		$list = [];
		foreach ($linkAge as $val) {
			if (isset($data[$val["id"]])) {
				$data[$val["id"]]["checkSubId"] = $val["checkSubId"];
				$list[] = $data[$val["id"]];
			}
		}
		$list = $config_logic->getTree($list);
		return $this->handleTreeArr($list);
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
	private function getProductGroups($first_gid = "")
	{
		$productgroups = \think\Db::name("product_groups")->field("id,name,headline,tagline,order,gid,order_frm_tpl,tpl_type")->where("hidden", 0)->where("order_frm_tpl", "<>", "uuid")->where(function (\think\db\Query $query) use($first_gid) {
			if (!empty($first_gid)) {
				$query->where("gid", $first_gid);
			}
		})->order("order", "asc")->select();
		foreach ($productgroups as $key => $productgroup) {
			$filterproductgroups[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $productgroup);
		}
		return $filterproductgroups;
	}
	private function getProductFirstGroups()
	{
		$productfirstgroups = \think\Db::name("product_first_groups")->field("id,name,order")->where("hidden", 0)->order("order", "asc")->select();
		foreach ($productfirstgroups as $key => $productfirstgroup) {
			$filterproductfirstgroups[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $productfirstgroup);
		}
		return $filterproductfirstgroups;
	}
	/**
	 * @title 配置页面异步请求计算总价--
	 * @description 接口说明:配置页面异步请求计算总价  + (可选数量 --wyh20200705新增)
	 * @author wyh
	 * @url /cart/get_total
	 * @method POST
	 * @param .name:pid type:int require:1 default:1 other: desc:产品ID
	 * @param .name:billingcycle type:string require:1 default:1 other: desc:周期名称(比如：day、ontrial、monthly、hour等)
	 * @param .name:qty type:string require:0 default:1 other: desc:非必传,产品数量
	 * @param .name:configoption[配置项ID] type:string require:1 default:1 other: desc:配置子项ID(或者数量)后端根据配置项ID的类型判断是子项ID还是数量！
	 * @param .name:currencyid type:int require:0 default:1 other: desc:货币ID
	 * @param .name:customfield[自定义字段ID] type:int require:0 default:1 other: desc:自定义字段
	 * @return  currency:货币信息
	 * @return  products:产品信息@
	 * @products  name:
	 * @products  billingcycle:
	 * @products  product_setup_fee:产品初装费
	 * @products  product_price:产品价格
	 * @products  setupfee_total:产品初装费（总：包括选择多个数量）
	 * @products  price_total:产品周期费用（总：包括选择多个数量）
	 * @products  signal_setupfee:单个产品初装费（产品初装费+配置项初装费）
	 * @products  signal_price:产品周期费用(单个产品,不含初装费)
	 * @products  child:配置项+子项+价格@
	 * @child  option_name:配置项名称
	 * @child  suboption_name:子项名称
	 * @child  suboption_setup_fee:子项初装费
	 * @child  suboption_price:子项价格
	 * @child  suboption_price_total:子项总价
	 * @child  qty:数量(拉条的数量,前端需要判断是否有此值)
	 * @return total:总计
	 */
	public function getTotal()
	{
		if ($this->request->isPost() || VIEW_TEMPLATE_WEBSITE === true) {
			$param = $this->request->only(["pid", "billingcycle", "configoption", "customfield", "currencyid", "qty"]);
			$billingcycle = $param["billingcycle"];
			$configoption = $param["configoption"];
			$currencyid = isset($param["currencyid"]) ? $param["currencyid"] : "";
			$qty = isset($param["qty"]) && intval($param["qty"]) > 0 ? intval($param["qty"]) : 1;
			$pid = $param["pid"];
			$res = $product_filter = $all_option = [];
			$setupfeetotal = $total = $signal_setupfee = $price_total = $signal_price = 0;
			$salesetupfeetotal = $saletotal = $salesignal_setupfee = $saleprice_total = $salesignal_price = 0;
			$res["status"] = 200;
			$res["msg"] = lang("SUCCESS MESSAGE");
			$cart = new \app\common\logic\Cart();
			$rebate_setupfee = $rebate_price = $rebate_signal_price = 0;
			$uid = !empty(request()->uid) ? request()->uid : "";
			$currency = $this->currencyPriority($currencyid, $uid);
			$res["currency"] = $currency;
			$currencyid = $currency["id"];
			if (!in_array($billingcycle, array_keys(config("billing_cycle")))) {
				$product_model = new \app\common\model\ProductModel();
				$billingcycle = $product_model->getProductCycle($pid, $currencyid, "", "", "", "", "", "", 1)[0]["billingcycle"] ?? "";
			}
			$product_model = new \app\common\model\ProductModel();
			if (!$product_model->checkProductPrice($pid, $billingcycle, $currencyid)) {
				return jsons(["status" => 400, "msg" => lang("CART_GETTOTAL_PRICE_ERROR")]);
			}
			$setupfeecycle = $cart->changeCycleToupfee($billingcycle);
			$product = \think\Db::name("products")->alias("a")->field("a.id as productid,a.name,a.pay_type,b.*,a.api_type,a.upstream_version,a.upstream_price_type,a.upstream_price_value,a.hidden,a.stock_control,a.qty")->leftJoin("pricing b", "a.id = b.relid")->where("a.id", $pid)->where("b.type", "product")->where("b.currency", $currencyid)->find();
			if (!$product) {
				return jsons(["status" => 400, "msg" => lang("CART_GETTOTAL_PRODUCT_ERROR")]);
			}
			if ($product["api_type"] == "zjmf_api" && $product["upstream_price_type"] == "percent") {
				$is_ajmf_api = true;
			} else {
				$is_ajmf_api = false;
			}
			bcscale(2);
			$pay_ontrial_cycle = json_decode($product["pay_type"], true);
			$product_setup_fee = $product[$setupfeecycle] > 0 ? $product[$setupfeecycle] : 0;
			$product_price = $product[$billingcycle] > 0 ? $product[$billingcycle] : 0;
			$product_filter["product_name"] = $product["name"];
			$product_filter["billingcycle"] = $billingcycle;
			$product_filter["billingcycle_zh"] = config("billing_cycle")[$billingcycle];
			$product_filter["product_setup_fee"] = bcsub($product_setup_fee, 0);
			$product_filter["product_price"] = bcsub($product_price, 0);
			$product_filter["pay_day_cycle"] = $pay_ontrial_cycle["pay_day_cycle"];
			$product_filter["pay_hour_cycle"] = $pay_ontrial_cycle["pay_hour_cycle"];
			$product_filter["pay_ontrial_cycle"] = $pay_ontrial_cycle["pay_ontrial_cycle"];
			$product_filter["pay_ontrial_cycle_type"] = $pay_ontrial_cycle["pay_ontrial_cycle_type"] ?: "day";
			$product_filter["stock_control"] = $product["stock_control"];
			$product_filter["qty"] = $product["qty"];
			$total += bcmul($product_setup_fee, $qty);
			$total += bcmul($product_price, $qty);
			$setupfeetotal += bcmul($product_setup_fee, $qty);
			$rebate_setupfee += bcmul($product_setup_fee, $qty);
			$rebate_price += bcmul($product_price, $qty);
			$rebate_signal_price = $product_price;
			$edition = getEdition();
			$signal_setupfee += $product_setup_fee;
			$signal_price += $product_price;
			$flag = getSaleProductUser($pid, $uid);
			$bates = 0;
			if ($flag["type"] == 1) {
				$bates = 1 - $flag["bates"] / 100;
				$saletotal = bcsub($total, bcmul($total, $bates, 2), 2);
				$salesetupfeetotal = bcsub($setupfeetotal, bcmul($setupfeetotal, $bates, 2), 2);
				$salesignal_setupfee = bcsub($product_setup_fee, bcmul($product_setup_fee, $bates, 2), 2);
				$salesignal_price = bcsub($product_price, bcmul($product_price, $bates, 2), 2);
				$product_sale_setup_fee = $salesignal_setupfee;
				$product_sale_price = $salesignal_price;
			} else {
				$saletotal = $total;
				$salesetupfeetotal = $setupfeetotal;
				$salesignal_setupfee = $product_setup_fee;
				$salesignal_price = $product_price;
				$product_sale_setup_fee = $salesignal_setupfee;
				$product_sale_price = $salesignal_price;
			}
			$configoptions_logic = new \app\common\logic\ConfigOptions();
			$configoption = $configoptions_logic->filterConfigOptions($pid, $configoption);
			foreach ($configoption as $key => $value) {
				$option1 = \think\Db::name("product_config_options")->field("option_type,unit")->where("id", $key)->find();
				$option_type = $option1["option_type"];
				$option_unit = $option1["unit"];
				if ($option_type && $value) {
					$option_filter = [];
					if (!judgeQuantity($option_type)) {
						$option = \think\Db::name("product_config_options_sub")->alias("pcos")->field("pco.is_discount,pcos.option_name as suboption_name,pco.option_type,pco.option_name as option_name,pco.hidden,p.*,pco.is_rebate")->leftJoin("product_config_options pco", "pco.id = pcos.config_id")->leftJoin("pricing p", "p.relid = pcos.id")->where("pcos.id", $value)->where("pcos.config_id", $key)->where("p.type", "configoptions")->where("p.currency", $currencyid)->find();
						if (!$option) {
							return jsons(["status" => 400, "msg" => lang("ERROR_OPERATE")]);
						} else {
							$optionname = $option["option_name"];
							$suboptionname = $option["suboption_name"];
							$optionprice = $option[$billingcycle] > 0 ? $option[$billingcycle] : 0;
							$optionupfee = $option[$setupfeecycle] > 0 ? $option[$setupfeecycle] : 0;
							$optionsaleprice = $option[$billingcycle] > 0 ? $option[$billingcycle] : 0;
							$optionsaleupfee = $option[$setupfeecycle] > 0 ? $option[$setupfeecycle] : 0;
							$option_filter["hidden"] = $option["hidden"];
							$option_filter["option_name"] = explode("|", $optionname)[1] ? explode("|", $optionname)[1] : $optionname;
							$option_filter["suboption_name"] = $suboptionname_deal = explode("|", $suboptionname)[1] ? explode("|", $suboptionname)[1] : $suboptionname;
							$option_filter["sub_name"] = $option_filter["suboption_name"];
							if (explode("^", $suboptionname_deal)[1]) {
								$option_filter_suboption_name = explode("^", $suboptionname_deal);
								$option_filter["suboption_name"] = implode(" ", $option_filter_suboption_name);
								if ($option["option_type"] == 12) {
									$option_filter["icon_flag"] = trim($option_filter_suboption_name[0]);
									$option_filter["sub_name"] = $option_filter_suboption_name[1];
								} elseif ($option["option_type"] == 5) {
									$iconos = strtolower($option_filter_suboption_name[0]);
									switch ($iconos) {
										case "windows":
											$icon_os = 1;
											break;
										case "centos":
											$icon_os = 2;
											break;
										case "ubuntu":
											$icon_os = 3;
											break;
										case "debian":
											$icon_os = 4;
											break;
										case "esxi":
											$icon_os = 5;
											break;
										case "xenserver":
											$icon_os = 6;
											break;
										case "freebsd":
											$icon_os = 7;
											break;
										case "fedora":
											$icon_os = 8;
											break;
										default:
											$icon_os = 9;
									}
									$option_filter["icon_os"] = $icon_os;
									$option_filter["sub_name"] = $option_filter_suboption_name[1];
								}
							}
							if ($flag && $flag["type"] == 1) {
								if ($is_ajmf_api) {
									$option_filter["suboption_setup_fee"] = bcsub(bcmul($optionupfee, $product["upstream_price_value"]) / 100, 0);
									$option_filter["suboption_sale_setup_fee"] = round(bcsub($option_filter["suboption_setup_fee"], bcmul($option_filter["suboption_setup_fee"], $bates, 2), 2), 2);
									$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_sale_setup_fee"] > 0 ? $option_filter["suboption_sale_setup_fee"] : 0;
									$option_filter["suboption_price"] = bcsub(bcmul($optionprice, $product["upstream_price_value"]) / 100, 0);
									$option_filter["suboption_sale_price"] = round(bcsub($option_filter["suboption_price"], bcmul($option_filter["suboption_price"], $bates, 2), 2), 2);
									$option_filter["suboption_sale_price"] = $option_filter["suboption_sale_price"] > 0 ? $option_filter["suboption_sale_price"] : 0;
									$option_filter["suboption_price_total"] = bcadd(bcmul($optionupfee, $product["upstream_price_value"]) / 100, bcmul($optionprice, $product["upstream_price_value"]) / 100);
									$option_filter["suboption_sale_price_total"] = round(bcsub($option_filter["suboption_price_total"], bcmul($option_filter["suboption_price_total"], $bates, 2), 2), 2);
									$option_filter["suboption_sale_price_total"] = $option_filter["suboption_sale_price_total"] > 0 ? $option_filter["suboption_sale_price_total"] : 0;
								} else {
									$option_filter["suboption_setup_fee"] = bcsub($optionupfee, 0);
									$option_filter["suboption_sale_setup_fee"] = round(bcsub($option_filter["suboption_setup_fee"], bcmul($option_filter["suboption_setup_fee"], $bates, 2), 2), 2);
									$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_sale_setup_fee"] > 0 ? $option_filter["suboption_sale_setup_fee"] : 0;
									$option_filter["suboption_price"] = bcsub($optionprice, 0);
									$option_filter["suboption_sale_price"] = round(bcsub($option_filter["suboption_price"], bcmul($option_filter["suboption_price"], $bates, 2), 2), 2);
									$option_filter["suboption_sale_price"] = $option_filter["suboption_sale_price"] > 0 ? $option_filter["suboption_sale_price"] : 0;
									$option_filter["suboption_price_total"] = bcadd($optionprice, $optionupfee);
									$option_filter["suboption_sale_price_total"] = round(bcsub($option_filter["suboption_price_total"], bcmul($option_filter["suboption_price_total"], $bates, 2), 2), 2);
									$option_filter["suboption_sale_price_total"] = $option_filter["suboption_sale_price_total"] > 0 ? $option_filter["suboption_sale_price_total"] : 0;
								}
								$optionsaleupfee = round(bcsub($optionsaleupfee, bcmul($optionsaleupfee, $bates, 2), 2), 2);
								$optionsaleprice = round(bcsub($optionsaleprice, bcmul($optionsaleprice, $bates, 2), 2), 2);
							} else {
								if ($is_ajmf_api) {
									$option_filter["suboption_setup_fee"] = bcsub(bcmul($optionupfee, $product["upstream_price_value"]) / 100, 0);
									$option_filter["suboption_price"] = bcsub(bcmul($optionprice, $product["upstream_price_value"]) / 100, 0);
									$option_filter["suboption_price_total"] = bcadd(bcmul($optionupfee, $product["upstream_price_value"]) / 100, bcmul($optionprice, $product["upstream_price_value"]) / 100);
									$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_setup_fee"];
									$option_filter["suboption_sale_price"] = $option_filter["suboption_price"];
									$option_filter["suboption_sale_price_total"] = $option_filter["suboption_price_total"];
								} else {
									$option_filter["suboption_setup_fee"] = bcsub($optionupfee, 0);
									$option_filter["suboption_price"] = bcsub($optionprice, 0);
									$option_filter["suboption_price_total"] = bcadd($optionprice, $optionupfee);
									$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_setup_fee"];
									$option_filter["suboption_sale_price"] = $option_filter["suboption_price"];
									$option_filter["suboption_sale_price_total"] = $option_filter["suboption_price_total"];
								}
							}
							$saletotal += bcmul($optionsaleupfee, $qty);
							$saletotal += bcmul($optionsaleprice, $qty);
							$salesetupfeetotal += bcmul($optionsaleupfee, $qty);
							$salesignal_setupfee += $optionsaleupfee;
							$salesignal_price += $optionsaleprice;
							$option_filter["option_type"] = $option_type;
							$total += bcmul($optionupfee, $qty);
							$total += bcmul($optionprice, $qty);
							$setupfeetotal += bcmul($optionupfee, $qty);
							$signal_setupfee += $optionupfee;
							$signal_price += $optionprice;
							$all_option[] = $option_filter;
							if ($option["is_rebate"] || !$edition) {
								$rebate_setupfee += bcmul($optionupfee, $qty);
								$rebate_price += bcmul($optionprice, $qty);
								$rebate_signal_price += $optionprice;
							}
						}
					} else {
						$options = \think\Db::name("product_config_options_sub")->alias("pcos")->field("pcos.option_name as suboption_name,pcos.qty_minimum,pcos.qty_maximum,pco.option_type,pco.hidden,pco.option_name as option_name,pco.qty_minimum as min,pco.qty_maximum as max,pco.is_discount,p.*,pco.is_rebate")->leftJoin("product_config_options pco", "pco.id = pcos.config_id")->leftJoin("pricing p", "p.relid = pcos.id")->where("pcos.config_id", $key)->where("p.type", "configoptions")->where("currency", $currencyid)->select();
						if (!empty($options[0])) {
							foreach ($options as $option) {
								$min = $option["qty_minimum"];
								$max = $option["qty_maximum"];
								if ($value > 0 && $option["min"] <= $value && $value <= $option["max"] && $min <= $value && $value <= $max) {
									$optionprice = $option[$billingcycle] <= 0 ? 0 : $option[$billingcycle] * $value;
									$optionupfee = $option[$setupfeecycle] <= 0 ? 0 : $option[$setupfeecycle];
									$optionsaleprice = $option[$billingcycle] <= 0 ? 0 : $option[$billingcycle] * $value;
									$optionsaleupfee = $option[$setupfeecycle] <= 0 ? 0 : $option[$setupfeecycle];
									if ($flag && $option["is_discount"] == 1 && $flag["type"] == 1) {
										if (judgeQuantityStage($option_type)) {
											$sum = quantityStagePrice($key, $currencyid, $value, $billingcycle);
											$optionprice = $sum[0];
											$optionupfee = $sum[1];
											$optionsaleprice = $sum[0];
											$optionsaleupfee = $sum[1];
										}
									} else {
										if (judgeQuantityStage($option_type)) {
											$sum = quantityStagePrice($key, $currencyid, $value, $billingcycle);
											$optionprice = $sum[0];
											$optionupfee = $sum[1];
											$optionsaleprice = $sum[0];
											$optionsaleupfee = $sum[1];
										}
									}
									$suboptionname = $option["suboption_name"];
									$optionname = $option["option_name"];
									$option_filter["hidden"] = $option["hidden"];
									$option_filter["option_name"] = explode("|", $optionname)[1] ? explode("|", $optionname)[1] : $optionname;
									$option_filter["suboption_name"] = explode("|", $suboptionname)[1] ? explode("|", $suboptionname)[1] : $suboptionname;
									if ($flag && $flag["type"] == 1) {
										if ($is_ajmf_api) {
											$option_filter["suboption_setup_fee"] = bcsub(bcmul($optionupfee, $product["upstream_price_value"]) / 100, 0);
											$option_filter["suboption_sale_setup_fee"] = round(bcsub($option_filter["suboption_setup_fee"], bcmul($option_filter["suboption_setup_fee"], $bates, 2), 2), 2);
											$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_sale_setup_fee"] > 0 ? $option_filter["suboption_sale_setup_fee"] : 0;
											$option_filter["suboption_price"] = bcsub(bcmul($optionprice, $product["upstream_price_value"]) / 100, 0);
											$option_filter["suboption_sale_price"] = round(bcsub($option_filter["suboption_price"], bcmul($option_filter["suboption_price"], $bates, 2), 2), 2);
											$option_filter["suboption_sale_price"] = $option_filter["suboption_sale_price"] > 0 ? $option_filter["suboption_sale_price"] : 0;
											$option_filter["suboption_price_total"] = bcadd(bcmul($optionupfee, $product["upstream_price_value"]) / 100, bcmul($optionprice, $product["upstream_price_value"]) / 100);
											$option_filter["suboption_sale_price_total"] = round(bcsub($option_filter["suboption_price_total"], bcmul($option_filter["suboption_price_total"], $bates, 2), 2), 2);
											$option_filter["suboption_sale_price_total"] = $option_filter["suboption_sale_price_total"] > 0 ? $option_filter["suboption_sale_price_total"] : 0;
										} else {
											$option_filter["suboption_setup_fee"] = bcsub($optionupfee, 0);
											$option_filter["suboption_sale_setup_fee"] = round(bcsub($option_filter["suboption_setup_fee"], bcmul($option_filter["suboption_setup_fee"], $bates, 2), 2), 2);
											$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_sale_setup_fee"] > 0 ? $option_filter["suboption_sale_setup_fee"] : 0;
											$option_filter["suboption_price"] = bcsub($optionprice, 0);
											$option_filter["suboption_sale_price"] = round(bcsub($option_filter["suboption_price"], bcmul($option_filter["suboption_price"], $bates, 2), 2), 2);
											$option_filter["suboption_sale_price"] = $option_filter["suboption_sale_price"] > 0 ? $option_filter["suboption_sale_price"] : 0;
											$option_filter["suboption_price_total"] = bcadd($optionprice, $optionupfee);
											$option_filter["suboption_sale_price_total"] = round(bcsub($option_filter["suboption_price_total"], bcmul($option_filter["suboption_price_total"], $bates, 2), 2), 2);
											$option_filter["suboption_sale_price_total"] = $option_filter["suboption_sale_price_total"] > 0 ? $option_filter["suboption_sale_price_total"] : 0;
										}
										$optionsaleupfee = round(bcsub($optionsaleupfee, bcmul($optionsaleupfee, $bates, 2), 2), 2);
										$optionsaleprice = round(bcsub($optionsaleprice, bcmul($optionsaleprice, $bates, 2), 2), 2);
									} else {
										if ($is_ajmf_api) {
											$option_filter["suboption_setup_fee"] = bcsub(bcmul($optionupfee, $product["upstream_price_value"]) / 100, 0);
											$option_filter["suboption_price"] = bcsub(bcmul($optionprice, $product["upstream_price_value"]) / 100, 0);
											$option_filter["suboption_price_total"] = bcadd(bcmul($optionupfee, $product["upstream_price_value"]) / 100, bcmul($optionprice, $product["upstream_price_value"]) / 100);
											$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_setup_fee"];
											$option_filter["suboption_sale_price"] = $option_filter["suboption_price"];
											$option_filter["suboption_sale_price_total"] = $option_filter["suboption_price_total"];
										} else {
											$option_filter["suboption_setup_fee"] = bcsub($optionupfee, 0);
											$option_filter["suboption_price"] = bcsub($optionprice, 0);
											$option_filter["suboption_price_total"] = bcadd($optionprice, $optionupfee);
											$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_setup_fee"];
											$option_filter["suboption_sale_price"] = $option_filter["suboption_price"];
											$option_filter["suboption_sale_price_total"] = $option_filter["suboption_price_total"];
										}
									}
									$saletotal += bcmul($optionsaleupfee, $qty);
									$saletotal += bcmul($optionsaleprice, $qty);
									$salesetupfeetotal += bcmul($optionsaleupfee, $qty);
									$salesignal_setupfee += $optionsaleupfee;
									$salesignal_price += $optionsaleprice;
									$option_filter["qty"] = $value . $option_unit;
									$option_filter["option_type"] = $option_type;
									$total += bcmul($optionupfee, $qty);
									$total += bcmul($optionprice, $qty);
									$setupfeetotal += bcmul($optionupfee, $qty);
									$signal_setupfee += $optionupfee;
									$signal_price += $optionprice;
									$all_option[] = $option_filter;
									if ($option["is_rebate"] || !$edition) {
										$rebate_setupfee += bcmul($optionupfee, $qty);
										$rebate_price += bcmul($optionprice, $qty);
										$rebate_signal_price += $optionprice;
									}
									break;
								}
							}
						}
					}
				}
			}
			foreach ($all_option as $kk => $vv) {
				if ($vv["hidden"] == 1) {
					unset($all_option[$kk]);
				}
			}
			if ($is_ajmf_api) {
				$total = bcmul($total, $product["upstream_price_value"] / 100);
				$saletotal = bcmul($saletotal, $product["upstream_price_value"]) / 100;
				$salesignal_price = bcmul($salesignal_price, $product["upstream_price_value"]) / 100;
				$setupfeetotal = bcmul($setupfeetotal, $product["upstream_price_value"]) / 100;
				$signal_setupfee = bcmul($signal_setupfee, $product["upstream_price_value"]) / 100;
				$signal_price = bcmul($signal_price, $product["upstream_price_value"]) / 100;
				$product_filter["product_setup_fee"] = bcmul($product_filter["product_setup_fee"], $product["upstream_price_value"]) / 100;
				$product_filter["product_price"] = round(bcmul($product_filter["product_price"], $product["upstream_price_value"]) / 100, 2);
				$rebate_setupfee = bcmul($rebate_setupfee, $product["upstream_price_value"]) / 100;
				$rebate_price = bcmul($rebate_price, $product["upstream_price_value"]) / 100;
				$rebate_signal_price = bcmul($rebate_signal_price, $product["upstream_price_value"]) / 100;
			}
			if ($product["api_type"] == "resource" && function_exists("resourceUserGradePercent")) {
				$percent = resourceUserGradePercent($uid, $product["productid"]);
				$total = bcmul($total, $percent / 100);
				$saletotal = bcmul($saletotal, $percent) / 100;
				$salesignal_price = bcmul($salesignal_price, $percent) / 100;
				$setupfeetotal = bcmul($setupfeetotal, $percent) / 100;
				$signal_setupfee = bcmul($signal_setupfee, $percent) / 100;
				$signal_price = bcmul($signal_price, $percent) / 100;
				$product_filter["product_setup_fee"] = bcmul($product_filter["product_setup_fee"], $percent) / 100;
				$product_filter["product_price"] = round(bcmul($product_filter["product_price"], $percent) / 100, 2);
				$rebate_setupfee = bcmul($rebate_setupfee, $percent) / 100;
				$rebate_price = bcmul($rebate_price, $percent) / 100;
				$rebate_signal_price = bcmul($rebate_signal_price, $percent) / 100;
			}
			$res["products"] = $product_filter;
			$res["products"]["child"] = $all_option;
			$res["products"]["product_sale_setup_fee"] = $product_sale_setup_fee;
			$res["products"]["product_sale_price"] = $product_sale_price;
			$res["products"]["setupfee_total"] = bcsub($setupfeetotal, 0);
			$res["products"]["total"] = bcsub($total, 0);
			$res["products"]["signal_setupfee"] = bcsub($signal_setupfee, 0);
			$res["products"]["signal_price"] = bcsub($signal_price, 0);
			if ($pay_ontrial_cycle["pay_type"] == "free") {
				$res["products"]["duration"] = 0;
			} elseif ($pay_ontrial_cycle["pay_type"] == "onetime") {
				$res["products"]["duration"] = 0;
			} else {
				$res["products"]["duration"] = getNextTime($billingcycle, $pay_ontrial_cycle["pay_" . $billingcycle . "_cycle"], 0, $pay_ontrial_cycle["pay_ontrial_cycle_type"] ?: "day") - time();
			}
			if ($flag) {
				if ($flag["type"] == 1) {
					$res["products"]["sale_setupfee_total"] = bcsub($rebate_setupfee * $flag["bates"] / 100 + $setupfeetotal - $rebate_setupfee, 0);
					$res["products"]["sale_price"] = bcsub($rebate_price * $flag["bates"] / 100 + $total - $setupfeetotal - $rebate_price, 0);
					$res["products"]["sale_signal_price"] = bcsub($rebate_signal_price * $flag["bates"] / 100 + $signal_price - $rebate_signal_price, 0);
					$res["products"]["bates"] = bcsub($total, $saletotal, 2);
				} elseif ($flag["type"] == 2) {
					$bates = $flag["bates"];
					$res["products"]["bates"] = $bates;
					if ($bates <= $rebate_price) {
						$res["products"]["sale_price"] = $total - $setupfeetotal - $bates * $qty;
						$res["products"]["sale_setupfee_total"] = $setupfeetotal;
						$res["products"]["sale_signal_price"] = $signal_price - $bates;
					} else {
						$negative = $bates - $rebate_price;
						$res["products"]["sale_price"] = bcsub($total - $setupfeetotal - $rebate_price, 0);
						$res["products"]["sale_setupfee_total"] = $rebate_setupfee - $negative >= 0 ? bcsub($rebate_setupfee - $negative + $total - $setupfeetotal - $rebate_price, 0) : bcsub($total - $setupfeetotal - $rebate_price, 0);
						$res["products"]["sale_signal_price"] = bcsub($signal_price - $rebate_price / $qty, 0);
					}
				}
			} else {
				$res["products"]["sale_setupfee_total"] = 0.0;
				$res["products"]["sale_signal_price"] = 0.0;
				$res["products"]["sale_price"] = 0.0;
				$res["products"]["bates"] = 0.0;
			}
			$res["products"]["type"] = $flag;
			return jsons($res);
		}
		return jsons(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 获取购物车页面数据
	 * @description 接口说明:获取购物车页面数据
	 * @author 萧十一郎
	 * @url /cart/get_shop_data
	 * @method GET
	 * @param  .name:currency type:number require:0 default: desc: 货币id
	 * @param  .name:pos[] type:array require:0 default: desc: 购物车勾选的产品位置,数组
	 * @return  currency:货币数据@
	 * @currency  id:货币ID code:货币标识 prefix:货币前缀 suffix:货币后缀
	 * @return  cart_products:购物车产品数组@
	 * @cart_products  productid:产品ID
	 * @cart_products  productsname:产品名
	 * @cart_products  serverid:可用区ID
	 * @cart_products  servername:可用区域名
	 * @cart_products  billingcycle:周期
	 * @cart_products  billingcycle_desc:周期描述
	 * @cart_products  configoptions:可配置选项 ["配置项" => "配置值"]
	 * @cart_products  pricing:该产品价格（不带价格单位）
	 * @cart_products  setup_pricing:该产品安装费用（不带价格单位）
	 * @cart_products  pricing_show:该产品价格
	 * @cart_products  setup_pricing_show:该产品安装费用
	 * @return promo:优惠码数据@
	 * @promo  promo_desc:优惠码描述
	 * @promo  promo_desc_str:优惠码描述(带优惠码)
	 * @promo  promo_price:优惠码抵扣价格
	 * @promo  promo_price_str:优惠码抵扣价格（带价格单位）
	 * @return  total_pricing:价格小计（带价格单位）
	 * @return  total_setup_pricing:安装小计（带价格单位）
	 * @return  subtotal:总价格小计（带价格单位）
	 * @return  total_price:合计
	 * @return  total_desc:合计（带价格单位）
	 */
	public function getShopDataPage(\think\Request $request)
	{
		$param = $request->param();
		$currency = intval($param["currency"]);
		$pos = [];
		if (isset($param["pos"]) && is_array($param["pos"]) && !empty($param["pos"])) {
			$pos = $param["pos"];
		}
		$uid = $request->uid;
		$shop = new \app\common\logic\Shop($uid);
		$pagedata = $shop->getShopPageData($currency, $pos);
		$pagedata["gateway_list"] = gateway_list("gateways");
		$pagedata["default_gateway"] = getGateway($uid);
		$client_credit = \think\Db::name("clients")->where("id", $uid)->value("credit");
		$pagedata["credit"] = $client_credit > 0 ? $client_credit : number_format(0, 2);
		return jsons(["status" => 200, "msg" => lang("CART_FETDATA_SUCCESS"), "data" => $pagedata]);
	}
	/**
	 * @title 修改购物车产品数量
	 * @description 接口说明:修改购物车产品数量,前端异步请求
	 * @author wyh
	 * @time 2020-07-05
	 * @url /cart/modify_product_qty
	 * @method POST
	 * @param  .name:i type:number require:1 default: desc: 产品在购物车位置标识
	 * @param  .name:qty type:number require:1 default: desc: 产品数量
	 * @param  .name:pos[] type:array require:0 default: desc:购物车已选产品位置
	 * @return  currency:货币数据@
	 * @currency  id:货币ID code:货币标识 prefix:货币前缀 suffix:货币后缀
	 * @return  cart_products:购物车产品数组@
	 * @cart_products  productid:产品ID
	 * @cart_products  productsname:产品名
	 * @cart_products  serverid:可用区ID
	 * @cart_products  servername:可用区域名
	 * @cart_products  billingcycle:周期
	 * @cart_products  billingcycle_desc:周期描述
	 * @cart_products  configoptions:可配置选项 ["配置项" => "配置值"]
	 * @cart_products  pricing:该产品价格（不带价格单位）
	 * @cart_products  setup_pricing:该产品安装费用（不带价格单位）
	 * @cart_products  pricing_show:该产品价格
	 * @cart_products  setup_pricing_show:该产品安装费用
	 * @return promo:优惠码数据@
	 * @promo  promo_desc:优惠码描述
	 * @promo  promo_desc_str:优惠码描述(带优惠码)
	 * @promo  promo_price:优惠码抵扣价格
	 * @promo  promo_price_str:优惠码抵扣价格（带价格单位）
	 * @return  total_pricing:价格小计（带价格单位）
	 * @return  total_setup_pricing:安装小计（带价格单位）
	 * @return  subtotal:总价格小计（带价格单位）
	 * @return  total_price:合计
	 * @return  total_desc:合计（带价格单位）
	 */
	public function modifyProductQty()
	{
		if ($this->request->isPost()) {
			$params = $this->request->param();
			$i = intval($params["i"]);
			$qty = intval($params["qty"]);
			if (!is_numeric($i)) {
				return jsons(["status" => 400, "msg" => lang("CART_MODIFY_PROD_MUSH_NUMBER")]);
			}
			if (!is_numeric($qty) || $qty <= 0) {
				return jsons(["status" => 400, "msg" => lang("CART_MODIFY_PROD_MUSH_NUMBER_ZERO")]);
			}
			$pos = [];
			if (isset($params["pos"]) && is_array($params["pos"]) && !empty($params["pos"])) {
				$pos = $params["pos"];
			}
			$uid = \request()->uid;
			$shop_logic = new \app\common\logic\Shop($uid);
			$res = $shop_logic->modifyQty($i, $qty);
			if ($res["status"] != "success") {
				return jsons($res);
			}
			$pagedata = $shop_logic->getShopPageData(0, $pos);
			$pagedata["gateway_list"] = gateway_list("gateways");
			$pagedata["default_gateway"] = getGateway($uid);
			$client_credit = \think\Db::name("clients")->where("id", $uid)->value("credit");
			$pagedata["credit"] = $client_credit > 0 ? $client_credit : number_format(0, 2);
			return jsons(["status" => 200, "msg" => lang("CART_FETDATA_SUCCESS"), "data" => $pagedata]);
		}
		return jsons(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 结算购物车
	 * @description 结算购物车
	 * @author huanghao
	 * @url         /cart/settle
	 * @method      POST
	 * @time        2019-12-02
	 * @param 		.name:payment type:string require:0 default: other: desc:支付方式 网关列表的名称,不传表示余额支付
	 * @param       .name:pos[] type:array require:0 default: desc:产品在购物车位置标识,i=0,1,2……自然数
	 * @param       .name:checkout type:int require:0 default: desc:1直接结算，0加入购物车
	 * @return      [type]     [description]
	 * @return      invoiceid:账单id(跳转到网关支付页面，可不携带支付方式)
	 */
	public function settle(\think\Request $request)
	{
		$uid = $request->uid;
		$payment = input("post.payment", "");
		$checkout = input("post.checkout", 0);
		$default_payment = \think\Db::name("clients")->where("id", $uid)->value("defaultgateway");
		$use_credit = input("post.use_credit", 0);
		$user_info = \think\Db::name("clients")->where("id", $uid)->find();
		$shop = new \app\common\logic\Shop($uid);
		$cart = \think\Db::name("cart_session")->where("uid", $uid)->find();
		$cart_data = $remain_data = json_decode($cart["cart_data"], true);
		$pos_param = $this->request->param();
		$cart_products_filter = [];
		if (isset($pos_param["pos"]) && is_array($pos_param["pos"]) && !empty($pos_param["pos"])) {
			$pos = $pos_param["pos"];
			if (!empty($cart_data["products"])) {
				foreach ($pos as $n) {
					if (isset($cart_data["products"][$n])) {
						$cart_products_filter[$n] = $cart_data["products"][$n];
					}
				}
			}
		}
		$new_cart_data = "";
		if (!empty($cart_products_filter)) {
			$diff = array_diff_key($remain_data["products"], $cart_products_filter);
			$remain_data["products"] = [];
			foreach ($diff as $nn => $mm) {
				$remain_data["products"][] = $mm;
			}
			if (!empty($remain_data)) {
				$new_cart_data = json_encode($remain_data);
			}
			$cart_data["products"] = $cart_products_filter;
		}
		if (!empty($pos_param["cart_data"])) {
			\think\Db::name("cart_session")->where("uid", $uid)->update(["cart_data" => "", "update_time" => time()]);
			$cart_data = [];
			$pos_param["cart_data"]["configoptions"] = $shop->configfilter($pos_param["cart_data"]["pid"], $pos_param["cart_data"]["configoptions"]);
			$cart_data["products"][0] = $pos_param["cart_data"];
		}
		if (empty($cart_data["products"])) {
			$result["status"] = 406;
			$result["msg"] = lang("CART_SETTLE_CART_ERROR");
			return jsons($result);
		}
		$prod = [];
		foreach ($cart_data["products"] as $k => $value) {
			$product = \think\Db::name("products")->field("id,name,is_truename,clientscount,api_type,upstream_pid,zjmf_api_id,pay_type")->where("id", $value["pid"])->find();
			$api = \think\Db::name("api_user_product")->field("ontrial,qty")->where("uid", $uid)->where("pid", $value["pid"])->find();
			if (!empty($api)) {
				$product["clientscount"] = intval($api["qty"]);
			}
			if ($product["api_type"] == "zjmf_api") {
				$res = zjmfCurl($product["zjmf_api_id"], "cart/ontrialmax", ["pid" => $product["upstream_pid"]], 5, "GET");
				if (!empty($res["data"])) {
					$product["clientscount"] = intval($res["data"]["product"]["qty"]);
				}
			}
			if (empty($prod[$value["pid"]])) {
				$prod[$value["pid"]]["name"] = $product["name"];
				$prod[$value["pid"]]["clientscount"] = $product["clientscount"];
				$prod[$value["pid"]]["qty"] = $value["qty"];
			} else {
				$prod[$value["pid"]]["qty"] += $value["qty"];
			}
			$pay_type = json_decode($product["pay_type"], true);
			$prod[$value["pid"]]["clientscount_rule"] = !getEdition() ? 0 : $pay_type["clientscount_rule"] ?? 0;
		}
		foreach ($prod as $k => $value) {
			if ($value["clientscount"] > 0) {
				$pay_ontrial_num_rule = $value["clientscount_rule"];
				$whereMap = [];
				if ($pay_ontrial_num_rule) {
					$whereMap["domainstatus"] = "Active";
				}
				$productcounbt = \think\Db::name("host")->field("id")->where("productid", $k)->where("uid", $uid)->where($whereMap)->count();
				if ($value["clientscount"] < $productcounbt + $value["qty"]) {
					$result["status"] = 406;
					$result["msg"] = lang("CART_SETTLE_CLIENT_COUNT_ERROR", [$value["name"]]);
					return jsons($result);
					break;
				}
			}
		}
		$msg = $msg1 = $res_msg1 = $res_msg2 = [];
		$flag = checkCertify($uid);
		$flag1 = $user_info["phonenumber"];
		foreach ($cart_data["products"] as $k => $value) {
			$product = \think\Db::name("products")->field("name,is_truename,is_bind_phone")->where("id", $value["pid"])->find();
			if (!$flag && ($product["is_truename"] == 1 || configuration("certifi_isrealname") == 1)) {
				$msg[] = $product["name"];
			}
			if (!$flag1 && $product["is_bind_phone"] == 1) {
				$msg1[] = $product["name"];
			}
		}
		if (!$flag && count($msg) >= 1) {
			$msg = array_unique($msg);
			$res_msg1 = ["status" => 410, "msg" => lang("CART_SETTLE_PRO_FLAG_ERROR", [implode("，", $msg)])];
		}
		if (!$flag1 && count($msg1) >= 1) {
			$res_msg2 = ["status" => 415, "msg" => lang("CART_SETTLE_PRO_PHONE_ERROR", [implode("，", $msg1)])];
		}
		if ($res_msg1 && $res_msg2) {
			$res_msg["status"] = $res_msg1["status"];
			$res_msg["msg"] = $res_msg1["msg"];
			$res_msg["msg_phone"] = $res_msg2["msg"];
			return jsons($res_msg);
		} else {
			if ($res_msg1) {
				return jsons($res_msg1);
			} else {
				if ($res_msg2) {
					return jsons($res_msg2);
				}
			}
		}
		$gateway = gateway_list1();
		if (!empty($payment) && !in_array($payment, array_column($gateway, "name"))) {
			$result["status"] = 406;
			$result["msg"] = lang("PAY_TYPE_ERROR");
			return jsons($result);
		}
		$product_items = [];
		$promo_error = "";
		$product_error = [];
		$create_invoice = false;
		if (!empty($cart_data["promo"]) && $checkout == 0) {
			$promo = \think\Db::name("promo_code")->where("code", $cart_data["promo"])->find();
			if (!empty($promo)) {
				if ($promo["start_time"] > 0 && $promo["start_time"] > time()) {
					$promo_error = "优惠码还未发放";
					$promo = [];
				}
				if ($promo["max_times"] != 0 && $promo["used"] >= $promo["max_times"] || $promo["expiration_time"] > 0 && $promo["expiration_time"] <= time()) {
					$promo_error = "优惠码已过期";
					$promo = [];
				}
				$has_active_order = \think\Db::name("orders")->where("uid", $uid)->find();
				if (!empty($promo["only_new_client"]) && !empty($has_active_order)) {
					$promo_error = "优惠码只适用于新用户";
					$promo = [];
				}
				$has_active_order = \think\Db::name("orders")->where("uid", $uid)->where("status", "Active")->find();
				if (!empty($promo["only_old_client"]) && empty($has_active_order)) {
					$promo_error = "优惠码只适用于老用户";
					$promo = [];
				}
				if (!empty($promo["once_per_client"])) {
					if (\think\Db::name("orders")->where("uid", $uid)->where("promo_code", $cart_data["promo"])->find()) {
						$promo_error = "已使用过该优惠码";
						$promo = [];
					}
				}
				if (!empty($promo["requires"])) {
					$need_pid = explode(",", $promo["requires"]);
					$now_pid = array_column($cart_data["products"], "pid");
					if (!empty($promo["requires_exist"])) {
						$has_products = \think\Db::name("host")->field("productid")->where("uid", $uid)->where("domainstatus", "Active")->select()->toArray();
						$has_products = array_column($has_products, "productid") ?: [];
						$now_pid = array_merge($now_pid, $has_products);
					}
					$intersect = array_intersect($need_pid, $now_pid);
					if (empty($intersect)) {
						$promo_error = "不满足使用该优惠码条件";
						$promo = [];
					}
				}
				if (!empty($promo)) {
					$promo["appliesto"] = explode(",", $promo["appliesto"]);
				}
			}
		}
		$currency_info = \think\Db::name("currencies")->field("id")->where("id", $user_info["currency"])->find();
		if (empty($currency_info)) {
			$currency_info = \think\Db::name("currencies")->field("id")->where("default", 1)->find();
		}
		$currency = $currency_info["id"];
		$total = 0;
		$products = [];
		$price_type = config("price_type");
		bcscale(2);
		$auth_id = 0;
		$app_id = 0;
		$productModel = new \app\common\model\ProductModel();
		foreach ($cart_data["products"] as $k => $v) {
			$qty = $v["qty"];
			$checkProcut = $productModel->checkProductPrice($v["pid"], $v["billingcycle"], $currency);
			if (!$checkProcut) {
				return jsons(["status" => 400, "msg" => lang("CART_SETTLE_PRO_BILL_ERROR")]);
			}
			if ($v["billingcycle"] == "free") {
				$product = \think\Db::name("products")->where("id", $v["pid"])->find();
			} else {
				$product_price_type = $price_type[$v["billingcycle"]];
				if (empty($product_price_type)) {
					$result["status"] = 406;
					$result["msg"] = lang("CART_SETTLE_PRO_BILL_ERROR");
					return jsons($result);
				}
				$product_price_field = "b." . implode(",b.", $product_price_type);
				$product = \think\Db::name("products")->alias("a")->field("a.*," . $product_price_field)->leftJoin("pricing b", "b.type=\"product\" and a.id=b.relid and currency=" . $currency)->where("a.id", $v["pid"])->find();
			}
			$products[] = $product;
			if (empty($product)) {
				$result["status"] = 406;
				$result["msg"] = lang("ID_ERROR");
				return jsons($result);
			}
			if (!judgeOntrialNum($v["pid"], $uid, $qty, false, true) && $v["billingcycle"] == "ontrial") {
				return jsons(["status" => 400, "msg" => lang("CART_ONTRIAL_NUM", [$product["name"]])]);
			}
			$pay_type = json_decode($product["pay_type"], true);
			if (!empty($pay_type["pay_ontrial_condition"]) && $v["billingcycle"] == "ontrial") {
				$one_error = [];
				foreach ($pay_type["pay_ontrial_condition"] as $vv) {
					if ($vv == "realname" && !checkCertify($uid)) {
						$one_error[] = "实名认证";
					}
					if ($vv == "email" && empty($user_info["email"])) {
						$one_error[] = "邮箱验证";
					}
					if ($vv == "phone" && empty($user_info["phonenumber"])) {
						$one_error[] = "手机验证";
					}
					if ($vv == "wechat" && empty($user_info["wechat_id"])) {
						$one_error[] = "微信验证";
					}
				}
				if (!empty($one_error)) {
					$product_error[] = "产品" . $product["name"] . ",试用需要" . implode(",", $one_error);
				}
			}
			if (!empty($product_error)) {
				continue;
			}
			if (!empty($product["retired"])) {
				$result["status"] = 406;
				$result["msg"] = lang("CART_SETTLE_PRO_RETIRED", [$product["name"]]);
				return jsons($result);
			}
			if (!empty($product["stock_control"]) && $product["qty"] <= 0) {
				$result["status"] = 406;
				$result["msg"] = lang("CART_SETTLE_PRO_STOCK_CONTROL", [$product["name"]]);
				return jsons($result);
			}
			$nextduedate = time();
			$customfields = \think\Db::name("customfields")->where("relid", $v["pid"])->where("type", "product")->order("sortorder", "asc")->select()->toArray();
			$item_desc = [];
			$_products = \think\Db::name("products")->field("server_group as gid")->where("id", $v["pid"])->find();
			$server = [];
			if ($_products) {
				$server = getServesId($_products["gid"]);
			}
			if ($pay_type["pay_type"] == "free") {
				$v["billingcycle"] = "free";
				$product_item = ["uid" => $uid, "productid" => $v["pid"], "serverid" => $server["id"] ?? 0, "regdate" => time(), "payment" => $payment, "firstpaymentamount" => 0, "amount" => 0, "billingcycle" => $v["billingcycle"], "domainstatus" => "Pending", "create_time" => time(), "auto_terminate_reason" => "", "product_config" => [], "customfields" => [], "dcim_os" => array_keys($v["os"])[0] ?? 0, "os" => array_values($v["os"])[0] ?? "", "host" => $v["host"] ?? "", "password" => $v["password"] ?? "", "qty" => $qty, "percent_value" => $product["upstream_price_value"]];
				$item_desc[] = $product["name"] . " (" . date("Y-m-d H", time()) . " - ) ";
				foreach ($customfields as $ck => $cv) {
					if (isset($v["customfield"][$cv["id"]])) {
						$product_item["customfields"][] = ["fieldid" => $cv["id"], "value" => $v["customfield"][$cv["id"]]];
					}
				}
				$developer_app = checkDeveloperApp($v["pid"]);
				if (!empty($developer_app)) {
					$host_custom = \think\Db::name("customfields")->field("id")->where("type", "product")->where("relid", $v["pid"])->where("fieldname", "hostid")->order("id", "asc")->find();
					if (!empty($host_custom)) {
						$app_custom = ["fieldid" => $host_custom["id"], "value" => $v["hostid"]];
						$product_item["customfields"][] = $app_custom;
					}
					$auth_id = $v["hostid"];
					$app_id = $v["pid"];
				}
				$config_price = $productModel->getConfigOptionsPrice($v["pid"], $currency, $product_price_type);
				if (!empty($v["configoptions"])) {
					foreach ($config_price as $kkk => $vvv) {
						if (isset($v["configoptions"][$vvv["id"]])) {
							if (judgeOs($vvv["option_type"])) {
								$configoptions_logic = new \app\common\logic\ConfigOptions();
								$os = $configoptions_logic->getOs($vvv["id"], $v["configoptions"][$vvv["id"]]);
								$product_item["os"] = $os["os"] ?? "";
								$product_item["os_url"] = $os["os_url"] ?? "";
							}
							if (judgeQuantity($vvv["option_type"])) {
								if ($v["configoptions"][$vvv["id"]] < $vvv["qty_minimum"]) {
									$v["configoptions"][$vvv["id"]] = $vvv["qty_minimum"];
								}
								if ($v["configoptions"][$vvv["id"]] > $vvv["qty_maximum"]) {
									$v["configoptions"][$vvv["id"]] = $vvv["qty_minimum"];
								}
								$sub_id = 0;
								foreach ($vvv["sub"] as $kkkk => $vvvv) {
									if ($sub_id == 0) {
										$sub_id = $kkkk;
									}
									if (strpos($vvvv["option_name"], "-") !== false) {
										$range = explode("-", $vvvv["option_name"]);
										if (is_numeric($range[0]) && is_numeric($range[1]) && $range[1] >= $v["configoptions"][$vvv["id"]] && $v["configoptions"][$vvv["id"]] >= $range[0]) {
											$sub_id = $kkkk;
											break;
										}
									}
								}
								if ($sub_id > 0) {
									$product_item["product_config"][] = ["configid" => $vvv["id"], "optionid" => $sub_id, "qty" => $v["configoptions"][$vvv["id"]]];
								}
							} else {
								if (isset($vvv["sub"][$v["configoptions"][$vvv["id"]]])) {
									$product_item["product_config"][] = ["configid" => $vvv["id"], "optionid" => $v["configoptions"][$vvv["id"]], "qty" => 0];
								}
							}
						}
					}
				}
				$product_items[] = $product_item;
			} else {
				if (is_numeric($product[$v["billingcycle"]]) && $product[$v["billingcycle"]] == -1) {
					$result["status"] = 406;
					$result["msg"] = lang("CART_SETTLE_PRO_BILL_ERROR");
					return jsons($result);
				}
				$create_invoice = true;
				$product_item = ["uid" => $uid, "productid" => $v["pid"], "serverid" => $server["id"] ?? 0, "regdate" => time(), "payment" => $payment, "billingcycle" => $v["billingcycle"], "nextduedate" => $nextduedate, "nextinvoicedate" => $nextduedate, "domainstatus" => "Pending", "create_time" => time(), "auto_terminate_reason" => "", "invoices_items" => [], "product_config" => [], "customfields" => [], "dcim_os" => array_keys($v["os"])[0] ?? 0, "os" => array_values($v["os"])[0] ?? "", "host" => $v["host"] ?? "", "password" => $v["password"] ?? "", "qty" => $qty, "percent_value" => $product["upstream_price_value"]];
				$next_time = getNextTime($v["billingcycle"], $pay_type["pay_" . $v["billingcycle"] . "_cycle"], 0, $pay_type["pay_ontrial_cycle_type"] ?: "day");
				$item_desc = $item_desc_home = [];
				if ($pay_type["pay_type"] == "onetime") {
					$item_desc[] = $item_desc_home[] = $product["name"];
				} else {
					$item_desc[] = $item_desc_home[] = $product["name"] . " (" . date("Y-m-d H", time()) . " - " . date("Y-m-d H", $next_time) . ") ";
				}
				foreach ($customfields as $ck => $cv) {
					if (isset($v["customfield"][$cv["id"]])) {
						$product_item["customfields"][] = ["fieldid" => $cv["id"], "value" => $v["customfield"][$cv["id"]]];
					}
				}
				$developer_app = checkDeveloperApp($v["pid"]);
				if (!empty($developer_app)) {
					$host_custom = \think\Db::name("customfields")->field("id")->where("type", "product")->where("relid", $v["pid"])->where("fieldname", "hostid")->order("id", "asc")->find();
					if (!empty($host_custom)) {
						$app_custom = ["fieldid" => $host_custom["id"], "value" => $v["hostid"]];
						$product_item["customfields"][] = $app_custom;
					}
					$auth_id = $v["hostid"];
					$app_id = $v["pid"];
				}
				$price_setup = $product[$product_price_type[1]];
				$price_cycle = $product[$product_price_type[0]];
				if ($app_id > 0 && $auth_id > 0) {
					$product = \think\Db::name("products")->field("id,professional_discount")->where("id", $app_id)->where("p_uid", ">", 0)->find();
					$activity = \think\Db::name("app_activity_rel")->alias("a")->field("a.id,b.object,b.discount")->leftJoin("app_activity b", "b.id=a.activity_id")->where("b.start_time", "<=", time())->where("b.end_time", ">=", time())->where("a.pid", $app_id)->find();
					$auth = \think\Db::name("host")->alias("a")->field("a.id,b.config_option2")->leftJoin("products b", "b.id=a.productid")->where("a.id", $auth_id)->find();
					if (!empty($auth) && $auth["config_option2"] == "professional") {
						if (!empty($activity)) {
							$price_cycle = in_array($activity["object"], [0, 1]) ? round($price_cycle * (100 - $activity["discount"]) / 100, 2) : $price_cycle;
						}
						$price_cycle = round($price_cycle * (100 - $product["professional_discount"]) / 100, 2);
					} else {
						if (!empty($activity)) {
							$price_cycle = in_array($activity["object"], [0, 2]) ? round($price_cycle * (100 - $activity["discount"]) / 100, 2) : $price_cycle;
						}
					}
				}
				$product_base_sale = $product[$product_price_type[1]] + $product[$product_price_type[0]];
				$product_base_sale_setupfee = $product[$product_price_type[1]];
				$product_base_sale_price = $product[$product_price_type[0]];
				$product_rebate_price = $product[$product_price_type[0]];
				$product_rebate_setupfee = $product[$product_price_type[1]];
				$edition = getEdition();
				$config_price = $productModel->getConfigOptionsPrice($v["pid"], $currency, $product_price_type);
				$configoptions_base_sale = [];
				if (!empty($v["configoptions"])) {
					foreach ($config_price as $kkk => $vvv) {
						if (isset($v["configoptions"][$vvv["id"]])) {
							if (judgeOs($vvv["option_type"])) {
								$configoptions_logic = app("app\\common\\logic\\ConfigOptions");
								$os = $configoptions_logic->getOs($vvv["id"], $v["configoptions"][$vvv["id"]]);
								$product_item["os"] = $os["os"] ?? "";
								$product_item["os_url"] = $os["os_url"] ?? "";
							}
							if (strpos($vvv["option_name"], "|") !== false) {
								$item_desc_name = substr($vvv["option_name"], strpos($vvv["option_name"], "|"));
							} else {
								$item_desc_name = $vvv["option_name"];
							}
							if (judgeQuantity($vvv["option_type"])) {
								if ($v["configoptions"][$vvv["id"]] < $vvv["qty_minimum"]) {
									$v["configoptions"][$vvv["id"]] = $vvv["qty_minimum"];
								}
								if ($v["configoptions"][$vvv["id"]] > $vvv["qty_maximum"]) {
									$v["configoptions"][$vvv["id"]] = $vvv["qty_minimum"];
								}
								$sub_price_setup = 0;
								$sub_price_cycle = 0;
								$config_base_sale = 0;
								$config_base_sale_setupfee = 0;
								$sub_id = 0;
								foreach ($vvv["sub"] as $kkkk => $vvvv) {
									if ($sub_price_setup === "") {
										$sub_id = $kkkk;
										$sub_price_setup = $vvvv["price_setup"];
										$sub_price_cycle = $vvvv["price_cycle"];
									}
									if ($v["configoptions"][$vvv["id"]] >= $vvvv["qty_minimum"] && $v["configoptions"][$vvv["id"]] <= $vvvv["qty_maximum"]) {
										$sub_price_setup = $vvvv["price_setup"];
										$sub_price_cycle = $vvvv["price_cycle"];
										$sub_id = $kkkk;
										break;
									}
								}
								if ($sub_id > 0) {
									$item_desc_name .= ": " . $v["configoptions"][$vvv["id"]];
									$sub_price_setup = $sub_price_setup < 0 ? 0 : $sub_price_setup;
									$sub_price_cycle = $sub_price_cycle < 0 ? 0 : $sub_price_cycle;
									if ($vvv["hidden"] != 1) {
										if (judgeQuantityStage($vvv["option_type"])) {
											$sum = quantityStagePrice($vvv["id"], $currency, $v["configoptions"][$vvv["id"]], $v["billingcycle"]);
											$price_cycle = bcadd($price_cycle, $sum[0]);
											$price_setup = bcadd($price_setup, $sum[1]);
											$config_base_sale = $sum[0] + $sum[1];
											$config_base_sale_setupfee = $sum[1];
										} else {
											if (intval($v["configoptions"][$vvv["id"]]) > 0) {
												$price_setup = bcadd($price_setup, $sub_price_setup);
											}
											$price_cycle = bcadd($price_cycle, bcmul($sub_price_cycle, $v["configoptions"][$vvv["id"]]));
											$config_base_sale = (intval($v["configoptions"][$vvv["id"]]) > 0 ? $sub_price_setup : 0) + bcmul($sub_price_cycle, $v["configoptions"][$vvv["id"]]);
											$config_base_sale_setupfee = intval($v["configoptions"][$vvv["id"]]) > 0 ? $sub_price_setup : 0;
										}
									}
									$product_item["product_config"][] = ["configid" => $vvv["id"], "optionid" => $sub_id, "qty" => $v["configoptions"][$vvv["id"]]];
								}
								$configoptions_base_sale[] = ["config_base_sale" => $config_base_sale, "config_base_sale_setupfee" => $config_base_sale_setupfee, "is_discount" => $vvv["is_discount"], "id" => $vvv["id"], "is_rebate" => $vvv["is_rebate"]];
							} else {
								$config_base_sale = 0;
								$config_base_sale_setupfee = 0;
								if (isset($vvv["sub"][$v["configoptions"][$vvv["id"]]])) {
									if ($vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_setup"] > 0 && $vvv["hidden"] != 1) {
										$price_setup = bcadd($price_setup, $vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_setup"]);
										$config_base_sale += $vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_setup"];
										$config_base_sale_setupfee = $vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_setup"];
									}
									if ($vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_cycle"] > 0 && $vvv["hidden"] != 1) {
										$price_cycle = bcadd($price_cycle, $vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_cycle"]);
										$config_base_sale += $vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_cycle"];
									}
									if (strpos($vvv["sub"][$v["configoptions"][$vvv["id"]]]["option_name"], "|") !== false) {
										$item_desc_name .= ": " . substr($vvv["sub"][$v["configoptions"][$vvv["id"]]]["option_name"], strpos($vvv["sub"][$v["configoptions"][$vvv["id"]]]["option_name"], "|"));
									} else {
										$item_desc_name .= $vvv["sub"][$v["configoptions"][$vvv["id"]]]["option_name"];
									}
									$product_item["product_config"][] = ["configid" => $vvv["id"], "optionid" => $v["configoptions"][$vvv["id"]], "qty" => judgeYesNo($vvv["option_type"]) ? 1 : 0];
									$configoptions_base_sale[] = ["config_base_sale" => $config_base_sale, "config_base_sale_setupfee" => $config_base_sale_setupfee, "is_discount" => $vvv["is_discount"], "id" => $vvv["id"], "is_rebate" => $vvv["is_rebate"]];
								}
							}
							$item_desc_name = str_replace("|", " ", $item_desc_name);
							if (empty($vvv["hidden"])) {
								$item_desc_home[] = $item_desc_name;
							}
							$item_desc[] = $item_desc_name;
						}
					}
				}
				if ($product["api_type"] == "zjmf_api" && $product["upstream_version"] > 0 && $product["upstream_price_type"] == "percent") {
					$price_setup = bcmul($price_setup, $product["upstream_price_value"]) / 100;
					$price_cycle = bcmul($price_cycle, $product["upstream_price_value"]) / 100;
					$product_base_sale = bcmul($product_base_sale, $product["upstream_price_value"]) / 100;
					$config_base_sale_setupfee = bcmul($config_base_sale_setupfee, $product["upstream_price_value"]) / 100;
					$product_base_sale_price = $product_base_sale - $config_base_sale_setupfee;
					foreach ($configoptions_base_sale as &$m) {
						$m["config_base_sale"] = bcmul($m["config_base_sale"], $product["upstream_price_value"]) / 100;
						$m["config_base_sale_setupfee"] = bcmul($m["config_base_sale_setupfee"], $product["upstream_price_value"]) / 100;
					}
					$product_rebate_price = bcmul($product_rebate_price, $product["upstream_price_value"]) / 100;
					$product_rebate_setupfee = bcmul($product_rebate_setupfee, $product["upstream_price_value"]) / 100;
				}
				if ($product["api_type"] == "resource" && function_exists("resourceUserGradePercent")) {
					$percent = resourceUserGradePercent($uid, $product["id"]) / 100;
					$price_setup = bcmul($price_setup, $percent);
					$price_cycle = bcmul($price_cycle, $percent);
					$product_base_sale = bcmul($product_base_sale, $percent);
					$config_base_sale_setupfee = bcmul($config_base_sale_setupfee, $percent);
					$product_base_sale_price = $product_base_sale - $config_base_sale_setupfee;
					foreach ($configoptions_base_sale as &$m) {
						$m["config_base_sale"] = bcmul($m["config_base_sale"], $percent);
						$m["config_base_sale_setupfee"] = bcmul($m["config_base_sale_setupfee"], $percent);
					}
					$product_rebate_price = bcmul($product_rebate_price, $percent);
					$product_rebate_setupfee = bcmul($product_rebate_setupfee, $percent);
				}
				$param = $this->request->param();
				if (isset($param["resource_percent_value"])) {
					$resource_percent_value = $param["resource_percent_value"];
					$price_setup = bcmul($price_setup, $resource_percent_value);
					$price_cycle = bcmul($price_cycle, $resource_percent_value);
					$product_base_sale = bcmul($product_base_sale, $resource_percent_value);
					$config_base_sale_setupfee = bcmul($config_base_sale_setupfee, $resource_percent_value);
					$product_base_sale_price = $product_base_sale - $config_base_sale_setupfee;
					foreach ($configoptions_base_sale as &$m) {
						$m["config_base_sale"] = bcmul($m["config_base_sale"], $resource_percent_value);
						$m["config_base_sale_setupfee"] = bcmul($m["config_base_sale_setupfee"], $resource_percent_value);
					}
					$product_rebate_price = bcmul($product_rebate_price, $resource_percent_value);
					$product_rebate_setupfee = bcmul($product_rebate_setupfee, $resource_percent_value);
				}
				$product_total_price = bcadd($price_setup, $price_cycle);
				if ($product_total_price < 0) {
					$product_total_price = 0;
				}
				if ($price_setup > 0) {
					$product_item["invoices_items"][] = ["uid" => $uid, "type" => "setup", "description" => "初装费", "description2" => "初装费", "amount" => $price_setup, "due_time" => $nextduedate, "payment" => $payment];
				}
				$product_item["invoices_items"][] = ["uid" => $uid, "type" => "host", "description" => implode("\n", $item_desc), "description2" => implode("\n", $item_desc_home) ?? "", "amount" => $price_cycle, "due_time" => $nextduedate, "payment" => $payment];
				$flag = getSaleProductUser($v["pid"], $uid);
				if ($flag) {
					$config_total = 0;
					$config_total_setupfee = 0;
					$config_total_price = 0;
					$userdiscount = 0;
					if ($flag["type"] == 1) {
						$bates = $flag["bates"];
						$userdiscount += (1 - $bates / 100) * ($product_rebate_price + $product_rebate_setupfee);
						foreach ($configoptions_base_sale as &$mm) {
							if ($mm["is_rebate"] || !$edition) {
								$userdiscount += (1 - $bates / 100) * $mm["config_base_sale"];
								$mm["config_base_sale"] = bcmul($bates / 100, $mm["config_base_sale"]);
								$mm["config_base_sale_setupfee"] = bcmul($bates / 100, $mm["config_base_sale_setupfee"]);
							}
							$config_total += $mm["config_base_sale"];
							$config_total_setupfee += $mm["config_base_sale_setupfee"];
							$config_total_price += $mm["config_base_sale"] - $mm["config_base_sale_setupfee"];
						}
						$product_base_sale = bcmul($bates / 100, $product_base_sale);
						$product_base_sale_setupfee = bcmul($bates / 100, $product_base_sale_setupfee);
						$product_base_sale_price = $product_base_sale - $product_base_sale_setupfee;
					} elseif ($flag["type"] == 2) {
						$bates = $flag["bates"];
						$product_total_rebate_price = $product_total_price;
						$product_base_sale = $product_base_sale / $product_total_price * ($product_total_price - $bates);
						$product_base_sale_setupfee = $product_base_sale_setupfee / $product_total_price * ($product_total_price - $bates);
						$product_base_sale_price = $product_base_sale - $product_base_sale_setupfee;
						foreach ($configoptions_base_sale as &$mm) {
							if ($mm["is_rebate"] || !$edition) {
								$mm["config_base_sale"] = $mm["config_base_sale"] / $product_total_price * ($product_total_price - $bates);
								$mm["config_base_sale_setupfee"] = $mm["config_base_sale_setupfee"] / $product_total_price * ($product_total_price - $bates);
							} else {
								$product_total_rebate_price = $product_total_rebate_price - $mm["config_base_sale"];
							}
							$config_total += $mm["config_base_sale"];
							$config_total_setupfee += $mm["config_base_sale_setupfee"];
							$config_total_price += $mm["config_base_sale"] - $mm["config_base_sale_setupfee"];
						}
						$userdiscount = $bates < $product_total_rebate_price ? $bates : $product_total_rebate_price;
					}
					$userdiscount = $userdiscount > 0 ? $userdiscount : 0;
					$product_item["invoices_items"][] = ["uid" => $uid, "type" => "discount", "description" => "客戶折扣", "description2" => "客戶折扣", "amount" => "-" . $userdiscount, "due_time" => $nextduedate, "payment" => $payment];
					$product_item["flag"] = 1;
					$product_item["flag_cycle"] = $v["billingcycle"];
					$product_total_price_sale = bcadd($product_base_sale, $config_total);
					$product_total_price_sale_setupfee = bcadd($product_base_sale_setupfee, $config_total_setupfee);
					$product_total_price_sale_price = bcadd($product_base_sale_price, $config_total_price);
					$total = $total + $product_total_price_sale * $qty;
				} else {
					$product_item["flag"] = 0;
					$product_item["flag_cycle"] = $v["billingcycle"];
					$product_total_price_sale = $product_total_price;
					$product_total_price_sale_setupfee = $price_setup;
					$product_total_price_sale_price = $price_cycle;
					$total = bcadd($total, $product_total_price * $qty);
				}
				if (!empty($promo) && $product_total_price_sale > 0) {
					if ($flag && $promo["is_discount"] == 0) {
						$product_item["promoid"] = 0;
					} else {
						if ((empty($promo["appliesto"][0]) || in_array($v["pid"], $promo["appliesto"])) && (empty($promo["cycles"]) || in_array($v["billingcycle"], explode(",", $promo["cycles"])))) {
							if ($promo["type"] == "percent") {
								$promo_value = $promo["value"] > 100 ? 100 : ($promo["value"] > 0 ? $promo["value"] : 0);
								$discount_pricing = $discount_recurring = 0;
								$discount_pricing += $product_base_sale * (1 - $promo_value / 100);
								$discount_recurring += $product_base_sale_price * (1 - $promo_value / 100);
								foreach ($configoptions_base_sale as $h) {
									if ($h["is_discount"] == 1) {
										$discount_pricing += $h["config_base_sale"] * (1 - $promo_value / 100);
										$discount_recurring += ($h["config_base_sale"] - $h["config_base_sale_setupfee"]) * (1 - $promo_value / 100);
									}
								}
								if ($promo["recurring"] > 0) {
									$product_total_price_sale_price = bcsub($product_total_price_sale_price, $discount_recurring);
								}
							} elseif ($promo["type"] == "fixed") {
								$discount_pricing = $product_total_price_sale < $promo["value"] ? $product_total_price_sale : $promo["value"];
								if ($promo["recurring"] > 0) {
									$product_total_price_sale_price = $product_total_price_sale_price - $promo["value"] > 0 ? bcsub($product_total_price_sale_price, $promo["value"]) : 0;
								}
							} elseif ($promo["type"] == "override") {
								if ($product_total_price_sale < $promo["value"]) {
									$discount_pricing = $product_total_price_sale;
								} else {
									$discount_pricing = $product_total_price_sale - $promo["value"];
								}
								if ($promo["recurring"] > 0) {
									$product_total_price_sale_price = $product_total_price_sale < $promo["value"] ? $product_total_price_sale : $promo["value"];
								}
							} elseif ($promo["type"] == "free") {
								$discount_pricing = $product_total_price_sale_setupfee;
							} else {
								$discount_pricing = 0;
							}
							$discount_pricing = $discount_pricing > 0 ? $discount_pricing : 0;
							$product_total_price_sale = bcsub($product_total_price_sale, $discount_pricing, 2) > 0 ? bcsub($product_total_price_sale, $discount_pricing, 2) : 0;
							if ($promo["one_time"] == 1) {
								if (empty($one_time)) {
									$qty = 1;
									$total = bcsub($total, $discount_pricing * $qty);
									$product_item["invoices_items"][] = ["uid" => $uid, "type" => "promo", "description" => promoCodeDesc($promo), "description2" => promoCodeDesc($promo) ?? "", "amount" => "-" . $discount_pricing, "due_time" => $nextduedate, "payment" => $payment, "one_time" => 1];
									$one_time = true;
								}
							} else {
								$total = bcsub($total, $discount_pricing * $qty);
								$product_item["invoices_items"][] = ["uid" => $uid, "type" => "promo", "description" => promoCodeDesc($promo), "description2" => promoCodeDesc($promo) ?? "", "amount" => "-" . $discount_pricing, "due_time" => $nextduedate, "payment" => $payment];
							}
							$product_item["promoid"] = $promo["id"];
						}
					}
				} else {
					$product_item["promoid"] = 0;
				}
				$product_item["firstpaymentamount"] = $product_total_price_sale > 0 ? $product_total_price_sale : 0;
				$product_item["amount"] = $product_total_price_sale_price > 0 ? $product_total_price_sale_price : 0;
				$product_items[] = $product_item;
			}
		}
		if (!empty($product_error)) {
			$result["status"] = 406;
			$result["msg"] = implode("\n", $product_error);
			return jsons($result);
		}
		$total = $total > 0 ? $total : 0;
		$subtotal = $total;
		$invoices_data = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "paid_time" => 0, "last_capture_attempt" => 0, "subtotal" => $subtotal, "credit" => 0, "tax" => 0, "tax2" => 0, "total" => $total, "taxrate" => 0, "taxrate2" => 0, "status" => "Unpaid", "payment" => $payment, "notes" => "", "type" => "product"];
		$order_data = ["uid" => $uid, "ordernum" => cmf_get_order_sn(), "status" => "Pending", "create_time" => time(), "update_time" => 0, "amount" => $total, "payment" => $payment];
		if (!empty($promo)) {
			$order_data["promo_code"] = $promo["code"];
			$order_data["promo_type"] = $promo["type"];
			$order_data["promo_value"] = $promo["value"];
		}
		$create_after_order = [];
		$create_after_pay = [];
		$all_host = [];
		if (request()->is_api == 1) {
			$downstream_data = input("post.");
			$is_downstream = (strpos($downstream_data["downstream_url"], "https://") === 0 || strpos($downstream_data["downstream_url"], "http://") === 0) && strlen($downstream_data["downstream_token"]) == 32 && is_numeric($downstream_data["downstream_id"]);
		}
		\think\Db::startTrans();
		try {
			if (!empty($create_invoice)) {
				$invoiceid = \think\Db::name("invoices")->insertGetId($invoices_data);
				if (empty($invoiceid)) {
					throw new \Exception(lang("CART_SETTLE_INCOICES_ERROR"));
				}
			}
			$invoiceid = intval($invoiceid);
			$order_data["invoiceid"] = $invoiceid;
			if ($pos_param["notes"]) {
				$order_data["notes"] = trim($pos_param["notes"]);
			}
			$orderid = \think\Db::name("orders")->insertGetId($order_data);
			$hids = [];
			foreach ($product_items as $k => $v) {
				$qtys = $v["qty"];
				if ($v["billingcycle"] == "onetime") {
					$v["amount"] = 0;
					$v["nextduedate"] = 0;
				}
				$invoices_items = $v["invoices_items"];
				$product_config = $v["product_config"];
				$customfields = $v["customfields"];
				unset($v["invoices_items"]);
				unset($v["product_config"]);
				unset($v["customfields"]);
				unset($v["qty"]);
				$v["orderid"] = $orderid;
				$pid = $v["productid"];
				$rule = \think\Db::name("products")->field("host,password")->where("id", $pid)->find();
				$host_rule = json_decode($rule["host"], true);
				$host = $v["host"];
				$password = $v["password"];
				unset($v["host"]);
				$v["password"] = empty($password) ? "" : cmf_encrypt($password);
				$r = \think\Db::name("products")->field("name,stock_control,qty,auto_setup,api_type")->where("id", $v["productid"])->find();
				if ($r["stock_control"] == 1 && $r["qty"] < $qtys) {
					throw new \Exception("产品{$r["name"]}库存不足");
				}
				if (empty($v["payment"])) {
					$v["payment"] = $default_payment ?? "";
				}
				if ($r["api_type"] == "resource") {
					$v["agent_grade"] = resourceUserGradePercent($uid, $v["productid"]);
					$price_model = \think\Db::name("res_products")->where("productid", $v["productid"])->value("price_type");
					if ($price_model == "handling") {
						$v["handling"] = floatval(configuration("shd_resource_handling_model"));
					}
				}
				for ($i = 0; $i < $qtys; $i++) {
					if ($qtys > 1) {
						$v["domain"] = generateHostName($host_rule["prefix"], $host_rule["rule"], $host_rule["show"]);
					} else {
						$v["domain"] = empty($host) ? generateHostName($host_rule["prefix"], $host_rule["rule"], $host_rule["show"]) : $host;
					}
					if ($param["agent_client"]) {
						$v["agent_client"] = intval($param["agent_client"]);
					}
					$hostid = \think\Db::name("host")->insertGetId($v);
					$h = [];
					$h["hid"] = $hostid;
					$h["billingcycle"] = $v["billingcycle"];
					$hids[] = $h;
					if ($r["auto_setup"] == "order") {
						$create_after_order[] = $hostid;
					} elseif ($r["auto_setup"] == "payment") {
						$create_after_pay[] = $hostid;
					}
					$all_host[] = $hostid;
					if (!empty($invoices_items)) {
						foreach ($invoices_items as $kk => $vv) {
							$invoices_items[$kk]["invoice_id"] = $invoiceid;
							$invoices_items[$kk]["rel_id"] = $hostid;
							if ($vv["one_time"] == 1) {
								unset($vv["one_time"]);
								$vv["invoice_id"] = $invoiceid;
								$vv["rel_id"] = $hostid;
								\think\Db::name("invoice_items")->insert($vv);
								unset($invoices_items[$kk]);
							}
						}
						\think\Db::name("invoice_items")->insertAll($invoices_items);
					}
					if (!empty($product_config)) {
						foreach ($product_config as $kk => $vv) {
							$product_config[$kk]["relid"] = $hostid;
						}
						\think\Db::name("host_config_options")->insertAll($product_config);
					}
					if (!empty($customfields)) {
						foreach ($customfields as $kk => $vv) {
							$customfields[$kk]["relid"] = $hostid;
							$customfields[$kk]["create_time"] = time();
						}
						\think\Db::name("customfieldsvalues")->insertAll($customfields);
					}
				}
				\think\Db::name("products")->where("id", $v["productid"])->where("stock_control", 1)->setDec("qty", $qtys);
			}
			\think\Db::name("cart_session")->where("uid", $uid)->update(["cart_data" => $new_cart_data, "update_time" => time()]);
			if (!empty($promo)) {
				\think\Db::name("promo_code")->where("id", $promo["id"])->setInc("used");
			}
			foreach ($products as $key => $v) {
				if ($v["groupid"] != 0) {
					$ng = \think\Db::name("nav_group_user")->where("uid", $uid)->where("groupid", $v["groupid"])->find();
					if (empty($ng)) {
						$data = ["groupid" => $v["groupid"], "uid" => $uid ?? 0, "is_show" => 1];
						$ng = \think\Db::name("nav_group_user")->insert($data);
					} else {
						if ($ng["is_show"] == 0) {
							$ng = \think\Db::name("nav_group_user")->where("uid", $uid)->where("groupid", $v["groupid"])->update(["is_show" => 1]);
						}
					}
				}
			}
			if (count($all_host) == 1) {
				\think\Db::name("invoices")->where("id", $invoiceid)->update(["url" => "servicedetail?id=" . $all_host[0]]);
			} else {
				if (count($all_host) > 1) {
					$menu = new \app\common\logic\Menu();
					$fpid = \think\Db::name("host")->where("id", $all_host[0])->value("productid");
					$url = $menu->proGetNavId(intval($fpid))["url"] ?: "";
					\think\Db::name("invoices")->where("id", $invoiceid)->update(["url" => $url]);
				}
			}
			if ($is_downstream) {
				$downstream_create = \think\Db::name("host")->whereLike("stream_info", "%" . $downstream_data["downstream_token"] . "%")->find();
				if (!empty($downstream_create)) {
					$result = [];
					$result["status"] = 1001;
					$result["msg"] = lang("BUY_SUCCESS");
					$result["data"]["hostid"] = [$downstream_create["id"]];
					return jsons($result);
				} else {
					$stream_info = [];
					$stream_info["downstream_url"] = $downstream_data["downstream_url"];
					$stream_info["downstream_token"] = $downstream_data["downstream_token"];
					$stream_info["downstream_id"] = $downstream_data["downstream_id"];
					\think\Db::name("host")->where("id", \intval($all_host[0]))->update(["stream_info" => json_encode($stream_info)]);
				}
			}
			if ($invoiceid == 0) {
				active_logs(sprintf($this->lang["Cart_home_settle_success1"], $orderid), $uid);
				active_logs(sprintf($this->lang["Cart_home_settle_success1"], $orderid), $uid, "", 2);
			} else {
				active_logs(sprintf($this->lang["Cart_home_settle_success"], $invoiceid, $orderid), $uid);
				active_logs(sprintf($this->lang["Cart_home_settle_success"], $invoiceid, $orderid), $uid, "", 2);
			}
			\think\Db::commit();
			$result["status"] = 200;
			$result["msg"] = lang("CART_SETTLE_INCOICES_OK");
		} catch (\Exception $e) {
			$result["status"] = 406;
			$result["msg"] = $e->getMessage();
			\think\Db::rollback();
		}
		if ($result["status"] != 200) {
			return jsons($result);
		}
		$curl_multi_data = [];
		if ($subtotal != 0) {
			foreach ($hids as $h) {
				if ($h["billingcycle"] != "free") {
					$arr_admin = ["relid" => $h["hid"], "name" => "【管理员】新订单通知", "type" => "invoice", "sync" => true, "admin" => true, "ip" => get_client_ip6()];
					if (configuration("shd_allow_email_send_queue")) {
						\app\queue\job\SendMail::push($arr_admin);
					} else {
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async", "data" => $arr_admin];
					}
					$admin = getReceiveAdmin();
					foreach ($admin as $key => $value) {
						$arr_admin = ["relid" => $h["hid"], "name" => "【管理员】新订单通知", "type" => "invoice", "sync" => true, "admin" => true, "adminid" => $value["id"], "ip" => get_client_ip6()];
						if (configuration("shd_allow_email_send_queue")) {
							\app\queue\job\SendMail::push($arr_admin);
						} else {
							$curl_multi_data[count($curl_multi_data)] = ["url" => "async", "data" => $arr_admin];
						}
					}
					$arr_client = ["relid" => $h["hid"], "name" => "新订单通知", "type" => "invoice", "sync" => true, "admin" => false, "ip" => get_client_ip6()];
					if (configuration("shd_allow_email_send_queue")) {
						\app\queue\job\SendMail::push($arr_client);
					} else {
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async", "data" => $arr_client];
					}
				}
			}
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			foreach ($product_items as $k => $v) {
				$hostre = \think\Db::name("products")->field("name")->where("id", $v["productid"])->find();
				$sms = new \app\common\logic\Sms();
				$client = check_type_is_use($message_template_type[strtolower("New_Order_Notice")], $v["uid"], $sms);
				if ($client && $v["billingcycle"] != "free") {
					$b = config("billing_cycle");
					$params = ["product_name" => $hostre["name"], "product_binlly_cycle" => $b[$v["billingcycle"]], "product_price" => $v["amount"], "order_create_time" => date("Y-m-d H:i:s", $v["create_time"])];
					if ($client["phone_code"] == "+86" || $client["phone_code"] == "86") {
						$phone = $client["phonenumber"];
					} else {
						if (substr($client["phone_code"], 0, 1) == "+") {
							$phone = substr($client["phone_code"], 1) . "-" . $client["phonenumber"];
						} else {
							$phone = $client["phone_code"] . "-" . $client["phonenumber"];
						}
					}
					$arr = ["name" => $message_template_type[strtolower("New_Order_Notice")], "phone" => $phone, "params" => json_encode($params), "sync" => false, "uid" => $v["uid"], "delay_time" => 0, "is_market" => false];
					if (configuration("shd_allow_sms_send_queue")) {
						\app\queue\job\SendSms::push($arr);
					} else {
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async_sms", "data" => $arr];
					}
				}
			}
		}
		foreach ($create_after_order as $v) {
			$host_arr = ["hid" => $v, "ip" => get_client_ip6()];
			if (configuration("shd_allow_auto_create_queue")) {
				\app\queue\job\AutoCreate::push($host_arr);
			} else {
				$curl_multi_data[count($curl_multi_data)] = ["url" => "async_create", "data" => $host_arr];
			}
		}
		hook("shopping_cart_settle", ["orderid" => $orderid, "total" => $total, "invoiceid" => \intval($invoiceid), "hostid" => array_column($hids, "hid")]);
		if ($total == 0) {
			if (!empty($invoiceid)) {
				\think\Db::name("invoices")->where("id", $invoiceid)->update(["status" => "Paid", "paid_time" => time()]);
				$invoice_logic = new \app\common\logic\Invoices();
				$invoice_logic->processPaidInvoice($invoiceid);
			} else {
				foreach ($create_after_pay as $vv) {
					if (configuration("shd_allow_auto_create_queue")) {
						\app\queue\job\AutoCreate::push(["hid" => $vv, "ip" => get_client_ip6()]);
					} else {
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async_create", "data" => ["hid" => $vv, "ip" => get_client_ip6()]];
					}
				}
			}
			$result["status"] = 1001;
			$result["msg"] = lang("BUY_SUCCESS");
			$result["data"]["hostid"] = $all_host;
			$invoice_url = \think\Db::name("invoices")->where("id", $invoiceid)->find();
			$result["data"]["url"] = $invoice_url["url"] ?: "";
			asyncCurlMulti($curl_multi_data);
		} else {
			$result["status"] = 200;
			$result["data"]["invoiceid"] = $invoiceid;
			if ($is_downstream) {
				$result["data"]["hostid"] = $all_host;
			}
			asyncCurlMulti($curl_multi_data);
			return jsons($result);
		}
		return jsons($result);
	}
	/**
	 * @title 添加产品到购物车
	 * @description 接口说明:添加产品到购物车
	 * @author 萧十一郎
	 * @url /cart/add_to_shop
	 * @method POST
	 * @param  .name:pid type:number require:1 default: orther: desc:产品ID
	 * @param  .name:billingcycle type:string require:1  default: orther: desc:产品周期
	 * @param  .name:qty type:string require:1  default: orther: desc:产品数量
	 * @param  .name:serverid type:number require:0 default: orther: desc:服务器可用区ID
	 * @param  .name:configoption type:array require:1 default: orther: desc:产品配置数组
	 * @param  .name:customfield type:array require:1 default: orther: desc:产品自定义字段数组
	 * @param  .name:currencyid type:array require:1 default: orther: desc:货币ID
	 * @param  .name:os[id] type:array require:1 default: orther: desc:值：操作系统name
	 * @param  .name:host type:string require:0 default: orther: desc:主机名
	 * @param  .name:password type:string require:0 default: orther: desc:密码
	 * @param  .name:hostid type:int require:0 default: orther: desc:应用产品ID(当产品为开发者应用时,此字段必传)
	 * @param  .name:checkout type:int require:0 default: orther: desc:1表示直接结算,0加到购物车
	 * @return  i:当前产品在购物车的位置0,1,2……
	 */
	public function addToShop(\think\Request $request)
	{
		$uid = request()->uid;
		if (!buyProductMustBindPhone($uid)) {
			return jsons(["status" => 400, "msg" => lang("CART_ADDTOSHOP_PHONE_ERROR")]);
		}
		if ($request->isPost()) {
			$rule = ["pid" => "require|number", "serverid" => "number", "configoption" => "array", "customfield" => "array", "currecncyid" => "number", "qty" => "number", "os" => "array", "hostid" => "integer", "checkout" => "number|in:0,1"];
			$msg = ["pid.require" => lang("CART_ADDTOSHOP_VERIFY_PID_REQUIRE"), "pid.number" => lang("CART_ADDTOSHOP_VERIFY_PID_NUMBER"), "serverid.number" => lang("CART_ADDTOSHOP_VERIFY_SERVERID_REQUIRE"), "configoption.array" => lang("CART_ADDTOSHOP_VERIFY_CONFIG_ARRAY"), "customfield.array" => lang("CART_ADDTOSHOP_VERIFY_CUSTOM_ARRAY"), "qty.number" => lang("CART_ADDTOSHOP_VERIFY_QTY_NUMBER"), "os.array" => lang("CART_ADDTOSHOP_VERIFY_OS_ARRAY"), "hostid.integer" => lang("CART_ADDTOSHOP_VERIFY_HOSTID_INTEGER")];
			$param = $request->param();
			$validate = new \think\Validate($rule, $msg);
			$result = $validate->check($param);
			if (!$result) {
				return jsons(["status" => 406, "msg" => $validate->getError()]);
			}
			$pid = $param["pid"];
			$billingcycle = $param["billingcycle"];
			$serverid = $param["serverid"];
			$configoption = $param["configoption"];
			$customfield = $param["customfield"];
			$currencyid = $param["currencyid"];
			$checkout = intval($param["checkout"]);
			if (empty($billingcycle)) {
				$product_model = new \app\common\model\ProductModel();
				$billingcycle = $product_model->getProductCycle($pid, $currencyid, "", "", "", "", "", "", 1)[0]["billingcycle"] ?? "";
			}
			$qty = isset($param["qty"]) && intval($param["qty"]) > 0 ? intval($param["qty"]) : 1;
			$os = isset($param["os"]) ? $param["os"] : [];
			$shop = new \app\common\logic\Shop($uid);
			$product = \think\Db::name("products")->field("host,password,name,is_truename,stock_control,qty,zjmf_api_id,upstream_pid,api_type")->where("id", $pid)->find();
			if (!judgeOntrialNum($pid, $uid, $qty) && $billingcycle == "ontrial") {
				return jsons(["status" => 400, "msg" => lang("CART_ONTRIAL_NUM", [$product["name"]])]);
			}
			if (!empty($product["stock_control"]) && $product["qty"] <= 0) {
				return jsons(["msg" => lang("CART_SETTLE_PRO_STOCK_CONTROL", [$product["name"]]), "status" => 400]);
			}
			if (!empty($product["stock_control"]) && isset($param["is_api"]) && $param["is_api"] && $product["qty"] < ($param["qty"] ?? 1)) {
				return jsons(["msg" => lang("CART_SETTLE_PRO_STOCK_CONTROL", [$product["name"]]), "status" => 400]);
			}
			if (isset($param["is_api"]) && $param["is_api"]) {
				$prod = [];
				$cart_data["products"][] = ["pid" => $pid, "qty" => $qty];
				foreach ($cart_data["products"] as $k => $value) {
					$product = \think\Db::name("products")->field("id,name,is_truename,clientscount,api_type,upstream_pid,zjmf_api_id,pay_type")->where("id", $value["pid"])->find();
					$api = \think\Db::name("api_user_product")->field("ontrial,qty")->where("uid", $uid)->where("pid", $value["pid"])->find();
					if (!empty($api)) {
						$product["clientscount"] = intval($api["qty"]);
					}
					if ($product["api_type"] == "zjmf_api") {
						$res = zjmfCurl($product["zjmf_api_id"], "cart/ontrialmax", ["pid" => $product["upstream_pid"]], 5, "GET");
						if (!empty($res["data"])) {
							$product["clientscount"] = intval($res["data"]["product"]["qty"]);
						}
					}
					if (empty($prod[$value["pid"]])) {
						$prod[$value["pid"]]["name"] = $product["name"];
						$prod[$value["pid"]]["clientscount"] = $product["clientscount"];
						$prod[$value["pid"]]["qty"] = $value["qty"];
					} else {
						$prod[$value["pid"]]["qty"] += $value["qty"];
					}
					$pay_type = json_decode($product["pay_type"], true);
					$prod[$value["pid"]]["clientscount_rule"] = !getEdition() ? 0 : $pay_type["clientscount_rule"] ?? 0;
				}
				foreach ($prod as $k => $value) {
					if ($value["clientscount"] > 0) {
						$pay_ontrial_num_rule = $value["clientscount_rule"];
						$whereMap = [];
						if ($pay_ontrial_num_rule) {
							$whereMap["domainstatus"] = "Active";
						}
						$productcounbt = \think\Db::name("host")->field("id")->where("productid", $k)->where("uid", $uid)->where($whereMap)->count();
						if ($value["clientscount"] < $productcounbt + $value["qty"]) {
							return json(["status" => 400, "msg" => lang("CART_SETTLE_CLIENT_COUNT_ERROR", [$value["name"]])]);
						}
					}
				}
			}
			if ($product["api_type"] == "zjmf_api" || $product["api_type"] == "resource") {
				$result = zjmfCurl($product["zjmf_api_id"], "cart/stock_control", ["pid" => $product["upstream_pid"]], 30, "GET");
				if ($result["status"] == 200) {
					$upstream_data = $result["data"];
					if (empty($upstream_data["product"])) {
						return jsons(["status" => 400, "msg" => "商品缺货"]);
					}
					if ($upstream_data["product"]["hidden"] == 1) {
						\think\Db::name("products")->where("id", $pid)->update(["hidden" => 1]);
						return jsons(["status" => 400, "msg" => "商品不存在"]);
					}
					if ($upstream_data["product"]["stock_control"] && $upstream_data["product"]["qty"] <= 0) {
						return jsons(["status" => 400, "msg" => lang("CART_SETTLE_PRO_STOCK_CONTROL", [$product["name"]])]);
					}
				}
			}
			$host_data = json_decode($product["host"], true);
			$host = $param["host"];
			if ($host_data["show"] == 1 && isset($param["host"])) {
				$check_res = $shop->checkHostName($host, $pid);
				if ($check_res["status"] == 400) {
					return jsons($check_res);
				}
			}
			$password_data = json_decode($product["password"], true);
			$password = $param["password"];
			if ($password_data["show"] == 1 && isset($param["password"])) {
				$check_res2 = $shop->checkHostPassword($password, $pid);
				if ($check_res2["status"] == 400) {
					return jsons($check_res2);
				}
			}
			$developer_app = checkDeveloperApp($pid);
			if (!empty($developer_app)) {
				$hostid = intval($param["hostid"]);
				if (empty($hostid)) {
					return jsons(["status" => 400, "msg" => lang("CART_CHECK_DEVELOPER_APP_HOST_ERROR")]);
				}
				$exist = \think\Db::name("host")->where("uid", $uid)->where("id", $hostid)->find();
				if (empty($exist)) {
					return jsons(["status" => 400, "msg" => lang("CART_CHECK_EXIST_NOT_FOUND")]);
				}
			} else {
				$hostid = 0;
			}
			$res = $shop->addProduct($pid, $billingcycle, $serverid, $configoption, $customfield, $currencyid, $qty, $os, $host, $password, $hostid, $checkout);
			if ($res["status"] == "success") {
				if ($checkout == 1) {
					return jsons(["status" => 200, "msg" => lang("ADD SUCCESS"), "data" => ["i" => $res["i"]]]);
				} else {
					return jsons(["status" => 200, "msg" => lang("ADD SUCCESS")]);
				}
			} else {
				return jsons(["status" => 406, "msg" => $res["msg"]]);
			}
		}
	}
	/**
	 * @title 重新编辑产品到购物车--页面
	 * @description 接口说明:重新编辑产品到购物车--页面
	 * @author wyh
	 * @url /cart/edit_to_shop_page
	 * @method GET
	 * @param  .name:i type:number require:1 default: desc:产品在购物车位置标识
	 */
	public function editToshopPage()
	{
		$params = $this->request->param();
		$i = intval($params["i"]);
		if (!is_numeric($i)) {
			return jsons(["status" => 400, "msg" => lang("CART_EDIT_TOSHOPPAGE_I_ISNUM")]);
		}
		$uid = \request()->uid;
		$shop = new \app\common\logic\Shop($uid);
		$cart_data = $shop->getProductSession($i);
		$pid = $cart_data["pid"];
		$billingcycle = $cart_data["billingcycle"];
		$servers = \think\Db::name("products")->alias("p")->field("s.id,s.name,s.noc")->leftJoin("server_groups sg", "sg.id = p.server_group")->leftJoin("servers s", "s.gid = sg.id")->where("p.id", $pid)->select()->toArray();
		$serversfilter = [];
		foreach ($servers as $key => $server) {
			$serversfilter[$key]["id"] = $server["id"];
			$serversfilter[$key]["name"] = $server["name"];
			if (!empty($server["noc"])) {
				$noc = $this->imageaddress . $server["noc"];
				$serversfilter[$key]["noc"] = base64EncodeImage($noc);
			} else {
				$serversfilter[$key]["noc"] = "";
			}
		}
		$currencyid = priorityCurrency(intval($uid));
		$currency = get_currency();
		$customfields = new \app\common\logic\Customfields();
		$fields = $customfields->getCartCustomField($pid);
		$cart = new \app\common\logic\Cart();
		$product = $cart->getProductCycle($pid, $currencyid);
		$config_logic = new \app\common\logic\ConfigOptions();
		$alloption = $config_logic->getConfigInfo($pid);
		$alloption = $config_logic->configShow($alloption, $currencyid, $billingcycle);
		$alloption_ids = $alloption ? array_column($alloption, "id") : [];
		$cart_data_ids = $cart_data["configoptions"] ? array_keys($cart_data["configoptions"]) : [];
		if (array_intersect($alloption_ids, $cart_data_ids)) {
			foreach ($alloption as $key => $val) {
				if (!in_array($val["id"], $cart_data_ids)) {
					unset($alloption[$key]);
				}
			}
		}
		$alloption = $this->handleLinkAgeLevel($alloption);
		$alloption = $this->handleTreeArr($alloption);
		$developer_app = checkDeveloperApp($pid);
		if (!empty($developer_app)) {
			$hosts = \think\Db::name("host")->alias("a")->field("a.id,b.name")->leftJoin("products b", "a.productid = b.id")->leftJoin("customfields c", "c.relid = b.id")->where("c.type", "product")->where("a.domainstatus", "Active")->where(function (\think\db\Query $query) use($developer_app) {
				if ($developer_app["type"] == "finance") {
					$query->where("c.fieldname", "type_zjmffinance");
				} elseif ($developer_app["type"] == "cloud") {
					$query->where("c.fieldname", "type_zjmfcloud");
				} elseif ($developer_app["type"] == "dcim") {
					$query->where("c.fieldname", "dcim");
				}
			})->where("a.uid", $uid)->select()->toArray();
		}
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
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "servers" => $serversfilter, "currency" => $currency, "dafault_currencyid" => $currencyid, "product" => $product, "option" => $alloption, "custom_fields" => $fields, "config_options" => $cart_data["configoptions"], "custom_fields_value" => $cart_data["customfield"] ?? [], "billingcyle" => $billingcycle, "host" => $cart_data["host"] ?? "", "password" => $cart_data["password"] ?? "", "qty" => $cart_data["qty"], "hostid" => $cart_data["hostid"], "hosts" => $hosts ?? [], "links" => $links ?? [], "developer_app" => !empty($developer_app) ? 1 : 0]);
	}
	/**
	 * @title 重新编辑产品到购物车
	 * @description 接口说明:重新编辑产品到购物车
	 * @author 萧十一郎
	 * @url /cart/edit_to_shop
	 * @method POST
	 * @param  .name:i type:number require:1 default: desc:产品在购物车位置标识
	 * @param  .name:billingcycle type:string require:1 default:desc: 产品周期
	 * @param  .name:qty type:string require:1 default:desc: 产品数量
	 * @param  .name:serverid type:number require:0 default: desc:服务器可用区ID
	 * @param  .name:configoption type:array require:1 default: desc:产品配置数组
	 * @param  .name:customfield type:array require:1 default: desc:产品自定义字段数组
	 * @param  .name:currencyid type:int require:1 default: desc:货币ID
	 * @param  .name:os[id] type:array require:1 default: orther: desc:值：操作系统name
	 * @param  .name:hostid type:int require:0 default: orther: desc:应用产品ID(当产品为开发者应用时,此字段必传)
	 */
	public function editToShop(\think\Request $request)
	{
		if ($request->isPost()) {
			$rule = ["i" => "require|number", "billingcycle" => "require", "serverid" => "number", "configoption" => "array", "customfield" => "array", "currencyid" => "number", "qty" => "number", "os" => "array", "hostid" => "integer"];
			$msg = ["i.require" => lang("CART_EDIT_TOSHOP_VERIFY_I_REQUIRE"), "i.number" => lang("CART_EDIT_TOSHOP_VERIFY_I_NUMBER"), "billingcycle.require" => lang("CART_EDIT_TOSHOP_VERIFY_bill_REQUIRE"), "serverid.number" => lang("CART_EDIT_TOSHOP_VERIFY_SID_NUMBER"), "configoption.array" => lang("CART_EDIT_TOSHOP_VERIFY_CONF_ARRAY"), "customfield.array" => lang("CART_EDIT_TOSHOP_VERIFY_CUSTOM_ARRAY"), "qty.number" => lang("CART_ADDTOSHOP_VERIFY_QTY_NUMBER"), "os.array" => lang("CART_ADDTOSHOP_VERIFY_OS_ARRAY"), "hostid.integer" => lang("CART_ADDTOSHOP_VERIFY_HOSTID_INTEGER")];
			$param = $request->param();
			$validate = new \think\Validate($rule, $msg);
			$result = $validate->check($param);
			if (!$result) {
				return jsons(["status" => 406, "msg" => $validate->getError()]);
			}
			$i = \intval($param["i"]);
			$billingcycle = $param["billingcycle"];
			$serverid = $param["serverid"];
			$configoption = $param["configoption"];
			$customfield = $param["customfield"];
			$currencyid = $param["currencyid"];
			$qty = isset($param["qty"]) && intval($param["qty"]) ? intval($param["qty"]) : 1;
			$os = isset($param["os"]) ? $param["os"] : [];
			$uid = $request->uid;
			$shop = new \app\common\logic\Shop($uid);
			$cart_data = $shop->getShoppingCart();
			$pid = $cart_data["products"][intval($i)]["pid"];
			$product = \think\Db::name("products")->field("name")->where("id", $pid)->find();
			if (!judgeOntrialNum($pid, $uid, $qty, false, false, $i) && $billingcycle == "ontrial") {
				return jsons(["status" => 400, "msg" => lang("CART_ONTRIAL_NUM", [$product["name"]])]);
			}
			if (isset($param["host"])) {
				$host = $param["host"];
				$check_res = $shop->checkHostName($host, $pid);
				if ($check_res["status"] == 400) {
					return jsons($check_res);
				}
			} else {
				$host = "";
			}
			if (isset($param["password"])) {
				$password = $param["password"];
				$check_res2 = $shop->checkHostPassword($password, $pid);
				if ($check_res2["status"] == 400) {
					return jsons($check_res2);
				}
			} else {
				$password = "";
			}
			$developer_app = checkDeveloperApp($pid);
			if (!empty($developer_app)) {
				$hostid = intval($param["hostid"]);
				if (empty($hostid)) {
					return jsons(["status" => 400, "msg" => lang("CART_CHECK_DEVELOPER_APP_HOST_ERROR")]);
				}
				$exist = \think\Db::name("host")->where("uid", $uid)->where("id", $hostid)->find();
				if (empty($exist)) {
					return jsons(["status" => 400, "msg" => lang("CART_CHECK_EXIST_NOT_FOUND")]);
				}
			} else {
				$hostid = 0;
			}
			$res = $shop->editProduct($i, $billingcycle, $serverid, $configoption, $customfield, $currencyid, $qty, $os, $host, $password, $hostid);
			if ($res["status"] == "success") {
				return jsons(["status" => 200, "msg" => lang("ADD SUCCESS")]);
			} else {
				return jsons(["status" => 406, "msg" => $res["msg"]]);
			}
		}
	}
	/**
	 * @title 添加优惠码到购物车
	 * @description 接口说明:添加优惠码到购物车
	 * @author 萧十一郎
	 * @url /cart/add_promo
	 * @method POST
	 * @param  .name:promo type:string require:1 default: desc:优惠码代码
	 * @param  .name:currency type:number require:0 default: desc:货币id
	 * @param  .name:pos[] type:array require:0 default: desc:产品在购物车位置标识,i=0,1,2……自然数
	 * @return  promo:优惠码数据@
	 * @promo  promo_desc:优惠码描述
	 * @promo  promo_desc_str:优惠码描述(带优惠码)
	 * @promo  promo_price:优惠码抵扣价格
	 * @promo  promo_price_str:优惠码抵扣价格（带价格单位）
	 * @return  total_price:合计
	 * @return  total_desc:合计（带价格单位）
	 * @return  promo_waring_desc:可能存在的字段，当优惠码正确，当前列表却不适用优惠的时候会出现
	 *
	 */
	public function addPromoToShop(\think\Request $request)
	{
		if ($request->isPost()) {
			$promo = $request->param("promo");
			if (!is_string($promo)) {
				return jsons(["status" => 406, "msg" => lang("CART_ADD_PROMO_TO_SHOP_ERROR")]);
			}
			$currency = $request->param("currency");
			$uid = $request->uid;
			$shop = new \app\common\logic\Shop($uid);
			$res = $shop->addPromo($promo);
			if ($res["status"] != "success") {
				return jsons(["status" => 406, "msg" => $res["msg"]]);
			}
			$id = \think\Db::name("promo_code")->where("code", $promo)->value("id");
			$hook = hook_one("after_shop_add_promo", ["uid" => $uid, "id" => $id]);
			if (!empty($hook) && $hook["status"] != 200) {
				return jsons(["status" => 400, "msg" => $hook["msg"] ?: "优惠码不可用"]);
			}
			$param = $request->param();
			$pos = [];
			if (isset($param["pos"]) && is_array($param["pos"]) && !empty($param["pos"])) {
				$pos = $param["pos"];
			}
			$pagedata = $shop->getShopPageData($currency, $pos);
			if (!empty($pagedata["promo_error_desc"])) {
				return jsons(["status" => 406, "msg" => $pagedata["promo_error_desc"]]);
			}
			$returndata = [];
			if (isset($pagedata["promo_waring_desc"])) {
				$returndata["promo_waring_desc"] = $pagedata["promo_waring_desc"];
			}
			$returndata["promo"] = $pagedata["promo"];
			$returndata["total_price"] = $pagedata["total_price"];
			$returndata["total_desc"] = $pagedata["total_desc"];
			return jsons(["status" => 200, "msg" => lang("CART_ADD_PROMO_TO_SHOP_SUCCESS"), "data" => $returndata]);
		}
	}
	/**
	 * @title 移除优惠码
	 * @description 接口说明:移除优惠码
	 * @author 萧十一郎
	 * @url /cart/remove_promo
	 * @method POST
	 * @param  .name:currency type:number require:0 default: desc: 货币id
	 * @param  .name:pos[] type:array require:0 default: desc:产品在购物车位置标识,i=0,1,2……自然数
	 * @return  total_price:合计
	 * @return  total_desc:合计（带价格单位）
	 */
	public function removePromoToShop(\think\Request $request)
	{
		if ($request->isPost()) {
			$uid = $request->uid;
			$shop = new \app\common\logic\Shop($uid);
			$res = $shop->removePromo();
			if ($res["status"] != "success") {
				return json(["status" => 406, "msg" => $res["msg"]]);
			}
			$currency = $request->param["currency"];
			$param = $request->param();
			$pos = [];
			if (isset($param["pos"]) && is_array($param["pos"]) && !empty($param["pos"])) {
				$pos = $param["pos"];
			}
			$pagedata = $shop->getShopPageData($currency, $pos);
			$returndata = [];
			$returndata["total_price"] = $pagedata["total_price"];
			$returndata["total_desc"] = $pagedata["total_desc"];
			return json(["status" => 200, "msg" => lang("CART_ADD_PROMO_TO_SHOP_REMOVE"), "data" => $returndata]);
		}
	}
	public function getOptions($pid, $currencyid, $admin = false, $cycle = "")
	{
		if (!$admin) {
			$where = ["p.hidden", "=", 0];
		} else {
			$where = ["1=1"];
		}
		$configgroups = \think\Db::name("products")->alias("p")->field("pcg.id,pcg.name,pcg.description")->join("product_config_links pcl", "pcl.pid = p.id")->join("product_config_groups pcg", "pcg.id = pcl.gid")->where("p.id", $pid)->select()->toArray();
		$cart = new \app\common\logic\Cart();
		$alloption = [];
		foreach ($configgroups as $ckey => $configgroup) {
			if (!empty($configgroup)) {
				$gid = $configgroup["id"];
				$options = \think\Db::name("product_config_options")->where("gid", $gid)->order("order asc")->order("id ASC")->select()->toArray();
				foreach ($options as $okey => $option) {
					if (!empty($option)) {
						$cid = $option["id"];
						$option["option_name"] = explode("|", $option["option_name"])[1] ? explode("|", $option["option_name"])[1] : $option["option_name"];
						if ($option["option_type"] == 3) {
							$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", $cid)->order("sort_order ASC")->order("id ASC")->limit(1)->select()->toArray();
						} else {
							$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", $cid)->order("sort_order ASC")->order("id ASC")->select()->toArray();
						}
						if (in_array(strtolower($option["option_name"]), $this->allowSystem) || $option["option_type"] == 5) {
							unset($option["qty_minimum"]);
							unset($option["qty_maximum"]);
							unset($option["order"]);
							unset($option["hidden"]);
							$option["child"] = [];
							foreach ($suboptions as $subkey => $suboption) {
								if (!empty($suboption)) {
									$subid = $suboption["id"];
									if (!empty($cycle)) {
										$pricings = \think\Db::name("pricing")->field($cycle . " as fee")->field($cart->changeCycleToupfee($cycle) . " as setupfee")->where("type", "configoptions")->where("relid", $subid)->select();
									} else {
										$pricings = \think\Db::name("pricing")->where("type", "configoptions")->where("relid", $subid)->select();
									}
									$subprice = \think\Db::name("pricing")->where("type", "configoptions")->where("relid", $subid)->where("currency", $currencyid)->find();
									if (!empty($subprice)) {
										$replace = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, "."];
										$original_name = $suboption["option_name"] = explode("|", $suboption["option_name"])[1] ? explode("|", $suboption["option_name"])[1] : $suboption["option_name"];
										if (explode("^", $suboption["option_name"])[1]) {
											$os = explode("^", $suboption["option_name"])[0];
											$os = str_replace($replace, "", $os);
											$suboption["option_name"] = $os;
										} else {
											$os = $suboption["option_name"];
											$os = str_replace($replace, "", $os);
											$suboption["option_name"] = "os";
										}
										$os = strtolower(trim($os));
										$version = explode("^", $original_name)[1] ? explode("^", $original_name)[1] : $original_name;
										$icoName = implode("_", explode(" ", strtolower(trim($os))));
										${$subkey} = base64EncodeImage($this->osIco . $icoName . "." . $this->ext);
										$suboption["version"] = $version;
										unset($suboption["qty_minimum"]);
										unset($suboption["qty_maximum"]);
										unset($suboption["sort_order"]);
										unset($suboption["hidden"]);
										unset($suboption["config_id"]);
										unset($suboption["option_name"]);
										foreach ($pricings as $pkey => $pricing) {
											if (!empty($pricing)) {
												if (!isset($suboption["child"][$currencyid])) {
													$suboption["child"][$currencyid] = [];
												}
												$suboption["child"][$currencyid] = $pricing;
											}
										}
										if (!isset($option["child"][strtolower(trim($os))]["child"])) {
											$option["child"][strtolower(trim($os))]["child"] = [];
										}
										$option["child"][strtolower(trim($os))]["ico_url"] = ${$subkey};
										$option["child"][strtolower(trim($os))]["system"] = strtolower(trim($os));
										array_push($option["child"][strtolower(trim($os))]["child"], $suboption);
									}
								}
							}
						} else {
							if (judgeNoc($option["option_type"])) {
								foreach ($suboptions as $subkey => $suboption) {
									if (!empty($suboption)) {
										$subid = $suboption["id"];
										$original_name = $suboption["option_name"] = explode("|", $suboption["option_name"])[1] ? explode("|", $suboption["option_name"])[1] : $suboption["option_name"];
										if (explode("^", $original_name)[2]) {
											$tmp = explode("^", $original_name);
											$suboption["country_code"] = $tmp[0];
											$suboption["option_name"] = $tmp[1];
											$suboption["area"] = $tmp[2];
											$suboption["area_zh"] = $tmp[2];
										} elseif (explode("^", $original_name)[1]) {
											$tmp = explode("^", $original_name);
											$suboption["country_code"] = $tmp[0];
											$suboption["option_name"] = $tmp[1];
											$suboption["area"] = "";
											$suboption["area_zh"] = "";
										} else {
											$suboption["country_code"] = "";
											$suboption["option_name"] = "";
											$suboption["area"] = "";
											$suboption["area_zh"] = "";
										}
										if (!empty($cycle)) {
											$pricings = \think\Db::name("pricing")->field($cycle . " as fee")->field($cart->changeCycleToupfee($cycle) . " as setupfee")->where("type", "configoptions")->where("relid", $subid)->select();
										} else {
											$pricings = \think\Db::name("pricing")->where("type", "configoptions")->where("relid", $subid)->select();
										}
										if (!empty($pricings[0])) {
											foreach ($pricings as $pkey => $pricing) {
												if (!empty($pricing)) {
													if (!isset($suboption["child"][$currencyid])) {
														$suboption["child"][$currencyid] = [];
													}
													$suboption["child"][$currencyid] = $pricing;
												}
											}
											if (!isset($option["child"][$suboption["option_name"]]["area"])) {
												$option["child"][$suboption["option_name"]]["area"] = [];
											}
											$option["child"][$suboption["option_name"]]["country_code"] = $suboption["country_code"];
											array_push($option["child"][$suboption["option_name"]]["area"], $suboption);
										}
									}
								}
							} else {
								foreach ($suboptions as $subkey => $suboption) {
									if (!empty($suboption)) {
										$subid = $suboption["id"];
										$suboption["option_name"] = explode("|", $suboption["option_name"])[1] ? explode("|", $suboption["option_name"])[1] : $suboption["option_name"];
										if (!empty($cycle)) {
											$pricings = \think\Db::name("pricing")->field($cycle . " as fee")->field($cart->changeCycleToupfee($cycle) . " as setupfee")->where("type", "configoptions")->where("relid", $subid)->select();
										} else {
											$pricings = \think\Db::name("pricing")->where("type", "configoptions")->where("relid", $subid)->select();
										}
										if (!empty($pricings[0])) {
											foreach ($pricings as $pkey => $pricing) {
												if (!empty($pricing)) {
													if (!isset($suboption["child"][$currencyid])) {
														$suboption["child"][$currencyid] = [];
													}
													$suboption["child"][$currencyid] = $pricing;
												}
											}
											$option["child"][] = $suboption;
										}
									}
								}
							}
						}
						$option["sub"] = $option["child"];
						array_push($alloption, $option);
					}
				}
			}
		}
		$alloption = $this->handleLinkAgeLevel($alloption);
		$alloption = $this->handleTreeArr($alloption);
		return $alloption;
	}
	public function currencyPriority($currencyId = "", $uid = "")
	{
		if (!empty($currencyId)) {
			$currencyId = intval($currencyId);
			$currency = \think\Db::name("currencies")->where("id", $currencyId)->find();
		} else {
			$currency = \think\Db::name("clients")->field("currency")->where("id", $uid)->find();
			if (!empty($currency["currency"])) {
				$currency = \think\Db::name("currencies")->where("id", $currency["currency"])->find();
			} else {
				$currency = \think\Db::name("currencies")->where("default", 1)->find();
			}
		}
		$currency = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $currency);
		unset($currency["format"]);
		unset($currency["rate"]);
		unset($currency["default"]);
		return $currency;
	}
	/**
	 * @title 移除购物车中产品
	 * @description 接口说明:移除购物车中产品
	 * @author 萧十一郎
	 * @url /cart/remove_product
	 * @method POST
	 * @param  .name:i type:number require:1 default: desc:循环购物车列表时的key，移除后需要刷新页面
	 */
	public function removeProduct(\think\Request $request)
	{
		$param = $this->request->param();
		$i = $param["i"];
		$uid = $request->uid;
		if (!is_array($param["i"])) {
			$i = [intval($param["i"])];
		}
		$shop = new \app\common\logic\Shop($uid);
		$shop->removeProduct($i);
		return json(["status" => 200, "msg" => lang("CART_REMOVE_PRODUCT_SUCCESS")]);
	}
	/**
	 * @title 购物车检查结算页面
	 * @description 接口说明:会返回是否登录状态
	 * @author 萧十一郎
	 * @url /cart/check_page
	 * @method GET
	 * @param  .name:pos[] type:array require:0 default: desc:产品在购物车位置标识,i=0,1,2……自然数
	 * @return user_login:是否登录(true,false)
	 * @return  total_price:购物车合计
	 * @return  total_desc:购物车显示合计
	 * @return  gateway_list:网关列表@
	 * @gateway_list  id:网关id
	 * @gateway_list  name:网关
	 * @gateway_list  title:网关显示名
	 * @return user_info:用户信息@
	 * @user_info  credit:余额
	 */
	public function checkoutPage(\think\Request $request)
	{
		$uid = $request->uid;
		if (empty($uid)) {
			return json(["status" => 400, "msg" => lang("CART_CHECKOUT_PAGE_UID_ERROR")]);
		}
		$shop = new \app\common\logic\Shop($uid);
		$currency = getUserCurrency($uid);
		$currencyid = $currency["id"];
		$param = $request->param();
		$pos = [];
		if (isset($param["pos"]) && is_array($param["pos"]) && !empty($param["pos"])) {
			$pos = $param["pos"];
		}
		$pagedata = $shop->getShopPageData($currencyid, $pos);
		$returndata = [];
		$returndata["user_login"] = $uid ? true : false;
		$returndata["total_price"] = $pagedata["total_price"];
		$returndata["total_desc"] = $pagedata["total_desc"];
		$returndata["gateway_list"] = gateway_list("gateways");
		$user_info = \think\Db::name("clients")->field("id, credit,username")->where("id", $uid)->find();
		$returndata["user_info"] = $user_info;
		$returndata["user_info"]["credit_desc"] = $currency["prefix"] . $user_info["credit"] . $currency["suffix"];
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $returndata]);
	}
	/**
	 * 时间 2020-08-20
	 * @title 清空购物车
	 * @desc 清空购物车
	 * @url /cart/clear
	 * @method  POST
	 * @author hh
	 * @version v1
	 */
	public function clearCart(\think\Request $request)
	{
		$uid = $request->uid;
		$cart_data = \think\Db::name("cart_session")->where("uid", $uid)->value("cart_data");
		$cart_data = json_decode($cart_data, true)["products"] ?? [];
		\think\Db::name("cart_session")->where("uid", $uid)->update(["cart_data" => ""]);
		if (!empty($cart_data)) {
			$hook_data = [];
			foreach ($cart_data as $v) {
				$hook_data[] = ["pid" => $v["pid"], "billingcycle" => $v["billingcycle"], "num" => $v["num"]];
			}
			hook("shopping_cart_clear", ["data" => $hook_data]);
		}
		if ($request->is_api == 1) {
			$downstream_data = input("post.");
			$is_downstream = (strpos($downstream_data["downstream_url"], "https://") === 0 || strpos($downstream_data["downstream_url"], "http://") === 0) && strlen($downstream_data["downstream_token"]) == 32 && is_numeric($downstream_data["downstream_id"]);
			$downstream_create = \think\Db::name("host")->whereLike("stream_info", "%" . $downstream_data["downstream_token"] . "%")->find();
			if (!empty($downstream_create)) {
				$orders = \think\Db::name("orders")->field("status,invoiceid")->where("id", $downstream_create["orderid"])->find();
				if (!empty($orders["invoiceid"])) {
					$invoice_status = \think\Db::name("invoices")->where("id", $orders["invoiceid"])->value("status");
					if ($invoice_status == "Paid") {
						return json(["status" => 400, "msg" => "该订单已开通,请勿重新开通", "hostid" => $downstream_create["id"], "domainstatus" => $downstream_create["domainstatus"]]);
					} else {
						return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "invoiceid" => $orders["invoiceid"], "hostid" => $downstream_create["id"]]);
					}
				}
			}
		}
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 官网应用详情页，版本不要，版本不要，版本不要，版本不要，版本不要，版本不要！！！！！！！！！！
	 * @description 接口说明:官网应用详情页,版本不要，版本不要，版本不要，版本不要，版本不要，版本不要！！！！！！！！！！
	 * @author wyh
	 * @url /cart/app_detail
	 * @method GET
	 * @param  .name:id type:int require:1 default: desc:应用ID
	 * @return product:应用信息(编辑时才有)@
	 * @product name:应用名称
	 * @product info:应用简述
	 * @product type:应用类型
	 * @product description:应用描述
	 * @product instruction:应用说明
	 * @product icon:应用图标
	 * @product pay_type:销售方式
	 * @product pricing:销售价格
	 * @product unretired_time:发布时间
	 * @return currency:货币
	 * @return product_type:应用类型--所有
	 * @return developer:开发者信息@
	 * @developer name:开发者昵称
	 * @developer desc:简介
	 * @return relation_app:应用作者更多应用，与应用列表一样
	 */
	public function getDeveloperAppDetail()
	{
		$params = $this->request->param();
		$id = intval($params["id"]);
		$product = \think\Db::name("products")->field("name,info,type,description,instruction,icon,pay_type,retired,app_status,uuid,unretired_time,p_uid,app_type,version_description,app_version as version,app_score,app_images,app_file,app_pay_type")->where("id", $id)->where("p_uid", ">", 0)->find();
		if (empty($product)) {
			return jsons(["status" => 400, "msg" => lang("CART_DEVELOP_PRO_NOT_FOUND")]);
		}
		$product["version"] = $product["version"] != "" ? $product["version"] : "1.0.0";
		if (!empty($product["app_file"]) && file_exists(CMF_ROOT . "/public/upload/common/application/" . $product["app_file"])) {
			$size = filesize(CMF_ROOT . "/public/upload/common/application/" . $product["app_file"]);
			$product["filesize"] = dataUnitChange($size);
		} else {
			$product["filesize"] = "0B";
		}
		if ($this->request->uid) {
			$evaluation_like = \think\Db::name("evaluation_like")->where("uid", $this->request->uid)->select()->toArray();
			$evaluation_like = array_column($evaluation_like, "eid");
			$product["my_evaluation"] = \think\Db::name("evaluation")->alias("a")->field("b.id as uid,a.id,b.username,a.content,a.score,a.like_num,a.create_time")->leftJoin("clients b", "a.uid = b.id")->where("a.eid", 0)->where("a.rid", $id)->where("a.type", "products")->where("a.status", 1)->where("a.uid", $this->request->uid)->find();
			if (!empty($product["my_evaluation"]["id"])) {
				$product["my_evaluation"]["is_like"] = in_array($product["my_evaluation"]["id"], $evaluation_like);
				$product["my_evaluation"]["reply"] = \think\Db::name("evaluation")->alias("a")->field("b.id as uid,a.id,a.aid,b.username,a.content,a.create_time")->leftJoin("clients b", "a.uid = b.id")->where("a.eid", $product["my_evaluation"]["id"])->where("a.status", 1)->order("a.create_time", "asc")->select()->toArray();
			} else {
				$product["my_evaluation"] = [];
			}
			$app_favorite = \think\Db::name("app_favorite")->where("pid", $id)->where("uid", $this->request->uid)->find();
			$product["in_favorite"] = !empty($app_favorite) ? true : false;
		} else {
			$product["my_evaluation"] = [];
			$product["my_likes"] = [];
			$product["in_favorite"] = false;
			$evaluation_like = [];
		}
		$uid = $product["p_uid"];
		$currency = priorityCurrency($uid);
		$product = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $product);
		$product_paytype = config("product_paytype");
		$pay_type = json_decode($product["pay_type"], true);
		$pay_type = $pay_type["pay_type"];
		$product["pay_type"] = $pay_type ?? "";
		$product["pay_type_zh"] = $product_paytype[$pay_type] ?? "";
		if ($pay_type == "onetime") {
			$billingcycle = "onetime";
			$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $currency)->where("relid", $id)->find();
			if ($pricing["onetime"] < 0) {
				unset($pricing["onetime"]);
			}
		} else {
			$billingcycle = "monthly,annually";
			$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $currency)->where("relid", $id)->find();
			if ($pricing["monthly"] < 0) {
				unset($pricing["monthly"]);
			}
			if ($pricing["annually"] < 0) {
				unset($pricing["annually"]);
			}
		}
		$product["pricing"] = $pricing ?? [];
		$url = request()->domain() . config("app_file_url");
		$product["icon"] = isset($product["icon"][0]) ? array_map(function ($v) use($url) {
			return $url . $v;
		}, explode(",", $product["icon"])) : [];
		$product["app_images"] = isset($product["app_images"][0]) ? array_map(function ($v) use($url) {
			return $url . $v;
		}, explode(",", $product["app_images"])) : [];
		$developer = \think\Db::name("developer")->field("name,desc")->where("uid", $uid)->find();
		$relation_app = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", $uid)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.type", $product["type"])->withAttr("icon", function ($value) use($url) {
			$icon = explode(",", $value);
			foreach ($icon as &$vv) {
				$vv = $url . $vv;
			}
			return $icon;
		})->order("a.order", "asc")->page(1)->limit(8)->select()->toArray();
		foreach ($relation_app as &$v) {
			$v["app_score"] = \floatval($v["app_score"]);
			$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
			$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
			if ($v["pay_type"] == "onetime") {
				$billingcycle = "onetime";
			} elseif ($v["pay_type"] == "recurring") {
				$billingcycle = "monthly,annually";
			}
			$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
			if ($v["pay_type"] == "onetime") {
				$v["product_price"] = $pricing["onetime"];
				$v["billingcycle"] = "onetime";
				$v["billingcycle_zh"] = lang("ONETIME");
			} elseif ($v["pay_type"] == "recurring") {
				if ($pricing["annually"] >= 0) {
					$v["product_price"] = $pricing["annually"];
					$v["billingcycle"] = "annually";
					$v["billingcycle_zh"] = lang("ANNUALLY");
				} elseif ($pricing["monthly"] >= 0) {
					$v["product_price"] = $pricing["monthly"];
					$v["billingcycle"] = "monthly";
					$v["billingcycle_zh"] = lang("MONTHLY");
				}
			}
			$v["currency"] = getUserCurrency($v["uid"]);
		}
		$other_app = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_type", $product["app_type"])->where("a.type", $product["type"])->where("a.app_pay_type", $product["app_pay_type"])->withAttr("icon", function ($value) use($url) {
			$icon = explode(",", $value);
			foreach ($icon as &$vv) {
				$vv = $url . $vv;
			}
			return $icon;
		})->order("a.order", "asc")->page(1)->limit(8)->select()->toArray();
		foreach ($other_app as &$v) {
			$v["app_score"] = \floatval($v["app_score"]);
			$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
			$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
			if ($v["pay_type"] == "onetime") {
				$billingcycle = "onetime";
			} elseif ($v["pay_type"] == "recurring") {
				$billingcycle = "monthly,annually";
			}
			$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
			if ($v["pay_type"] == "onetime") {
				$v["product_price"] = $pricing["onetime"];
				$v["billingcycle"] = "onetime";
				$v["billingcycle_zh"] = lang("ONETIME");
			} elseif ($v["pay_type"] == "recurring") {
				if ($pricing["annually"] >= 0) {
					$v["product_price"] = $pricing["annually"];
					$v["billingcycle"] = "annually";
					$v["billingcycle_zh"] = lang("ANNUALLY");
				} elseif ($pricing["monthly"] >= 0) {
					$v["product_price"] = $pricing["monthly"];
					$v["billingcycle"] = "monthly";
					$v["billingcycle_zh"] = lang("MONTHLY");
				}
			}
			$v["currency"] = getUserCurrency($v["uid"]);
		}
		$product["app_version"] = \think\Db::name("app_version")->where("pid", $id)->order("version", "desc")->select()->toArray();
		$product["update_time"] = $product["app_version"][0]["create_time"] ?? $product["unretired_time"];
		$product["evaluation"] = \think\Db::name("evaluation")->alias("a")->field("b.id as uid,a.id,b.username,a.content,a.score,a.like_num,a.create_time")->leftJoin("clients b", "a.uid = b.id")->where("a.eid", 0)->where("a.rid", $id)->where("a.type", "products")->where("a.status", 1)->order("create_time", "desc")->limit(3)->select()->toArray();
		foreach ($product["evaluation"] as &$v) {
			$v["is_like"] = in_array($v["id"], $evaluation_like);
			$v["reply"] = \think\Db::name("evaluation")->alias("a")->field("b.id as uid,a.id,a.aid,b.username,a.content,a.create_time")->leftJoin("clients b", "a.uid = b.id")->where("a.eid", $v["id"])->where("a.status", 1)->order("a.create_time", "asc")->select()->toArray();
		}
		$product["evaluation_count"] = \think\Db::name("evaluation")->where("eid", 0)->where("rid", $id)->where("type", "products")->where("status", 1)->count();
		$product["purchases"] = \think\Db::name("host")->field("id")->where("productid", $id)->where("domainstatus", "Active")->count();
		$product["scores"]["one"] = \think\Db::name("evaluation")->where("score like '1%' AND rid='{$id}' AND type='products' AND eid=0 AND status=1")->count();
		$product["scores"]["two"] = \think\Db::name("evaluation")->where("score like '2%' AND rid='{$id}' AND type='products' AND eid=0 AND status=1")->count();
		$product["scores"]["three"] = \think\Db::name("evaluation")->where("score like '3%' AND rid='{$id}' AND type='products' AND eid=0 AND status=1")->count();
		$product["scores"]["four"] = \think\Db::name("evaluation")->where("score like '4%' AND rid='{$id}' AND type='products' AND eid=0 AND status=1")->count();
		$product["scores"]["five"] = \think\Db::name("evaluation")->where("score like '5%' AND rid='{$id}' AND type='products' AND eid=0 AND status=1")->count();
		$data = ["product" => $product ?? [], "developer" => $developer ?? [], "product_type" => config("developer_app_product_type"), "currency" => getUserCurrency($uid), "other_app" => $other_app ?? [], "relation_app" => $relation_app ?? []];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function indexAppHome()
	{
		$params = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$keywords = isset($params["keywords"]) ? trim($params["keywords"]) : "";
		$gid = \think\Db::name("product_groups")->where("order_frm_tpl", "uuid")->value("id");
		$url = request()->domain() . config("app_file_url");
		$count = \think\Db::name("products")->field("id,info,name,description,pay_type,icon,version_description,app_version")->where("gid", $gid)->where("hidden", 0)->where("retired", 0)->where("p_uid", ">", 0)->where(function (\think\db\Query $query) use($keywords) {
			if (!empty($keywords)) {
				$query->where("info", "like", "%{$keywords}%")->whereOr("name", "like", "%{$keywords}%");
			}
		})->count();
		$products = \think\Db::name("products")->field("id,info,name,description,pay_type,icon,version_description,app_version")->where("gid", $gid)->where("hidden", 0)->where("retired", 0)->where("p_uid", ">", 0)->where(function (\think\db\Query $query) use($keywords) {
			if (!empty($keywords)) {
				$query->where("info", "like", "%{$keywords}%")->whereOr("name", "like", "%{$keywords}%");
			}
		})->withAttr("icon", function ($value) use($url) {
			$icon = explode(",", $value);
			foreach ($icon as &$vv) {
				$vv = $url . $vv;
			}
			return $icon;
		})->order("order", "desc")->page($page)->limit($limit)->select()->toArray();
		$currency = \think\Db::name("currencies")->field("id,code,suffix,prefix")->where("default", 1)->find();
		$currencyid = $currency["id"];
		foreach ($products as &$v) {
			$v = array_map(function ($vvv) {
				return is_string($vvv) ? htmlspecialchars_decode($vvv, ENT_QUOTES) : $vvv;
			}, $v);
			$paytype = (array) json_decode($v["pay_type"]);
			$pricing = \think\Db::name("pricing")->where("type", "product")->where("relid", $v["id"])->where("currency", $currencyid)->find();
			if (!empty($paytype["pay_ontrial_status"])) {
				if ($pricing["ontrial"] >= 0) {
					$v["product_price"] = $pricing["ontrial"];
					$v["setup_fee"] = $pricing["ontrialfee"];
					$v["billingcycle"] = "ontrial";
					$v["billingcycle_zh"] = lang("ONTRIAL");
				} else {
					$v["product_price"] = number_format(0, 2);
					$v["setup_fee"] = number_format(0, 2);
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
				}
			}
			if ($paytype["pay_type"] == "free") {
				$v["product_price"] = number_format(0, 2);
				$v["setup_fee"] = number_format(0, 2);
				$v["billingcycle"] = "free";
				$v["billingcycle_zh"] = lang("FREE");
			} elseif ($paytype["pay_type"] == "onetime") {
				if ($pricing["onetime"] >= 0) {
					$v["product_price"] = $pricing["onetime"];
					$v["setup_fee"] = $pricing["osetupfee"];
					$v["billingcycle"] = "onetime";
					$v["billingcycle_zh"] = lang("ONETIME");
				} else {
					$v["product_price"] = number_format(0, 2);
					$v["setup_fee"] = number_format(0, 2);
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
				}
			} else {
				if (!empty($pricing) && $paytype["pay_type"] == "recurring") {
					if ($pricing["hour"] >= 0) {
						$v["product_price"] = $pricing["hour"];
						$v["setup_fee"] = $pricing["hsetupfee"];
						$v["billingcycle"] = "hour";
						$v["billingcycle_zh"] = lang("HOUR");
					} elseif ($pricing["day"] >= 0) {
						$v["product_price"] = $pricing["day"];
						$v["setup_fee"] = $pricing["dsetupfee"];
						$v["billingcycle"] = "day";
						$v["billingcycle_zh"] = lang("DAY");
					} elseif ($pricing["monthly"] >= 0) {
						$v["product_price"] = $pricing["monthly"];
						$v["setup_fee"] = $pricing["msetupfee"];
						$v["billingcycle"] = "monthly";
						$v["billingcycle_zh"] = lang("MONTHLY");
					} elseif ($pricing["quarterly"] >= 0) {
						$v["product_price"] = $pricing["quarterly"];
						$v["setup_fee"] = $pricing["qsetupfee"];
						$v["billingcycle"] = "quarterly";
						$v["billingcycle_zh"] = lang("QUARTERLY");
					} elseif ($pricing["semiannually"] >= 0) {
						$v["product_price"] = $pricing["semiannually"];
						$v["setup_fee"] = $pricing["ssetupfee"];
						$v["billingcycle"] = "semiannually";
						$v["billingcycle_zh"] = lang("SEMIANNUALLY");
					} elseif ($pricing["annually"] >= 0) {
						$v["product_price"] = $pricing["annually"];
						$v["setup_fee"] = $pricing["asetupfee"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					} elseif ($pricing["biennially"] >= 0) {
						$v["product_price"] = $pricing["biennially"];
						$v["setup_fee"] = $pricing["bsetupfee"];
						$v["billingcycle"] = "biennially";
						$v["billingcycle_zh"] = lang("BIENNIALLY");
					} elseif ($pricing["triennially"] >= 0) {
						$v["product_price"] = $pricing["triennially"];
						$v["setup_fee"] = $pricing["tsetupfee"];
						$v["billingcycle"] = "triennially";
						$v["billingcycle_zh"] = lang("TRIENNIALLY");
					} elseif ($pricing["fourly"] >= 0) {
						$v["product_price"] = $pricing["fourly"];
						$v["setup_fee"] = $pricing["foursetupfee"];
						$v["billingcycle"] = "fourly";
						$v["billingcycle_zh"] = lang("FOURLY");
					} elseif ($pricing["fively"] >= 0) {
						$v["product_price"] = $pricing["fively"];
						$v["setup_fee"] = $pricing["fivesetupfee"];
						$v["billingcycle"] = "fively";
						$v["billingcycle_zh"] = lang("FIVELY");
					} elseif ($pricing["sixly"] >= 0) {
						$v["product_price"] = $pricing["sixly"];
						$v["setup_fee"] = $pricing["sixsetupfee"];
						$v["billingcycle"] = "sixly";
						$v["billingcycle_zh"] = lang("SIXLY");
					} elseif ($pricing["sevenly"] >= 0) {
						$v["product_price"] = $pricing["sevenly"];
						$v["setup_fee"] = $pricing["sevensetupfee"];
						$v["billingcycle"] = "sevenly";
						$v["billingcycle_zh"] = lang("SEVENLY");
					} elseif ($pricing["eightly"] >= 0) {
						$v["product_price"] = $pricing["eightly"];
						$v["setup_fee"] = $pricing["eightsetupfee"];
						$v["billingcycle"] = "eightly";
						$v["billingcycle_zh"] = lang("EIGHTLY");
					} elseif ($pricing["ninely"] >= 0) {
						$v["product_price"] = $pricing["ninely"];
						$v["setup_fee"] = $pricing["ninesetupfee"];
						$v["billingcycle"] = "ninely";
						$v["billingcycle_zh"] = lang("NINELY");
					} elseif ($pricing["tenly"] >= 0) {
						$v["product_price"] = $pricing["tenly"];
						$v["setup_fee"] = $pricing["tensetupfee"];
						$v["billingcycle"] = "tenly";
						$v["billingcycle_zh"] = lang("TENLY");
					} else {
						$v["product_price"] = number_format(0, 2);
						$v["setup_fee"] = number_format(0, 2);
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_CONFIG_ERROR");
					}
				} else {
					$v["product_price"] = number_format(0, 2);
					$v["setup_fee"] = number_format(0, 2);
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
				}
			}
			if ($paytype["pay_type"] == "recurring" && in_array($v["type"], array_keys(config("developer_app_product_type")))) {
				if ($pricing["annually"] > 0) {
					$v["product_price"] = $pricing["annually"];
					$v["setup_fee"] = $pricing["asetupfee"];
					$v["billingcycle"] = "annually";
					$v["billingcycle_zh"] = lang("ANNUALLY");
				}
			}
			$v["product_price"] += $v["setup_fee"];
			$cart_logic = new \app\common\logic\Cart();
			$rebate_total = 0;
			$config_total = $cart_logic->getProductDefaultConfigPrice($v["id"], $currencyid, $v["billingcycle"], $rebate_total);
			$rebate_total = bcadd($v["product_price"], $rebate_total, 2);
			$v["product_price"] = bcadd($v["product_price"], $config_total, 2);
			if ($v["api_type"] == "zjmf_api" && $v["upstream_version"] > 0 && $v["upstream_price_type"] == "percent") {
				$v["product_price"] = bcmul($v["product_price"], $v["upstream_price_value"], 2) / 100;
				$rebate_total = bcmul($rebate_total, $v["upstream_price_value"], 2) / 100;
			}
			$flag = getSaleProductUser($v["id"], $uid);
			$v["sale_price"] = $v["bates"] = 0;
			if ($flag) {
				if ($flag["type"] == 1) {
					$bates = bcdiv($flag["bates"], 100, 2);
					$rebate = bcmul($rebate_total, 1 - $bates, 2) < 0 ? 0 : bcmul($rebate_total, 1 - $bates, 2);
					$v["sale_price"] = bcsub($v["product_price"], $rebate, 2) < 0 ? 0 : bcsub($v["product_price"], $rebate, 2);
					$v["bates"] = bcmul($v["product_price"], 1 - $bates, 2);
				} elseif ($flag["type"] == 2) {
					$bates = $flag["bates"];
					$rebate = $rebate_total < $bates ? $rebate_total : $bates;
					$v["sale_price"] = bcsub($v["product_price"], $rebate, 2) < 0 ? 0 : bcsub($v["product_price"], $rebate, 2);
					$v["bates"] = $bates;
				}
			}
			unset($v["pay_type"]);
		}
		$data = ["products" => $products, "count" => $count, "currency" => $currency];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 官网 应用列表
	 * @description 接口说明:官网 应用列表 --分页
	 * @author wyh
	 * @url /cart/index_app
	 * @method GET
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:keywords type:int require:0  other: desc:关键字搜索
	 * @return .products:此产品组下所有产品的信息@
	 * @products  id:应用ID
	 * @products  info:简述
	 * @products  name:名称
	 * @products  description:描述
	 * @products  icon:图标
	 * @products  product_price:产品价格
	 * @products  billingcycle:周期
	 * @products  billingcycle_zh:周期
	 */
	public function indexAppHomeOrigin()
	{
		$params = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$keywords = isset($params["keywords"]) ? trim($params["keywords"]) : "";
		$gid = \think\Db::name("product_groups")->where("order_frm_tpl", "uuid")->value("id");
		$url = request()->domain() . config("app_file_url");
		$count = \think\Db::name("products")->field("id,info,name,description,pay_type,icon,version_description,app_version")->where("gid", $gid)->where("hidden", 0)->where("retired", 0)->where("p_uid", ">", 0)->where(function (\think\db\Query $query) use($keywords) {
			if (!empty($keywords)) {
				$query->where("info", "like", "%{$keywords}%")->whereOr("name", "like", "%{$keywords}%");
			}
		})->count();
		$products = \think\Db::name("products")->field("id,info,name,description,pay_type,icon,version_description,app_version")->where("gid", $gid)->where("hidden", 0)->where("retired", 0)->where("p_uid", ">", 0)->where(function (\think\db\Query $query) use($keywords) {
			if (!empty($keywords)) {
				$query->where("info", "like", "%{$keywords}%")->whereOr("name", "like", "%{$keywords}%");
			}
		})->withAttr("icon", function ($value) use($url) {
			$icon = explode(",", $value);
			foreach ($icon as &$vv) {
				$vv = $url . $vv;
			}
			return $icon;
		})->order("order", "desc")->page($page)->limit($limit)->select()->toArray();
		$currency = \think\Db::name("currencies")->field("id,code,suffix,prefix")->where("default", 1)->find();
		$currencyid = $currency["id"];
		foreach ($products as &$v) {
			$v = array_map(function ($vvv) {
				return is_string($vvv) ? htmlspecialchars_decode($vvv, ENT_QUOTES) : $vvv;
			}, $v);
			$paytype = (array) json_decode($v["pay_type"]);
			$pricing = \think\Db::name("pricing")->where("type", "product")->where("relid", $v["id"])->where("currency", $currencyid)->find();
			if (!empty($paytype["pay_ontrial_status"])) {
				if ($pricing["ontrial"] >= 0) {
					$v["product_price"] = $pricing["ontrial"];
					$v["setup_fee"] = $pricing["ontrialfee"];
					$v["billingcycle"] = "ontrial";
					$v["billingcycle_zh"] = lang("ONTRIAL");
				} else {
					$v["product_price"] = number_format(0, 2);
					$v["setup_fee"] = number_format(0, 2);
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
				}
			}
			if ($paytype["pay_type"] == "free") {
				$v["product_price"] = number_format(0, 2);
				$v["setup_fee"] = number_format(0, 2);
				$v["billingcycle"] = "free";
				$v["billingcycle_zh"] = lang("FREE");
			} elseif ($paytype["pay_type"] == "hour") {
				if ($pricing["hour"] >= 0) {
					$v["product_price"] = $pricing["hour"];
					$v["setup_fee"] = $pricing["hsetupfee"];
					$v["billingcycle"] = "hour";
					$v["billingcycle_zh"] = lang("HOUR");
				} else {
					$v["product_price"] = number_format(0, 2);
					$v["setup_fee"] = number_format(0, 2);
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
				}
			} elseif ($paytype["pay_type"] == "day") {
				if ($pricing["day"] >= 0) {
					$v["product_price"] = $pricing["day"];
					$v["setup_fee"] = $pricing["dsetupfee"];
					$v["billingcycle"] = "day";
					$v["billingcycle_zh"] = lang("DAY");
				} else {
					$v["product_price"] = number_format(0, 2);
					$v["setup_fee"] = number_format(0, 2);
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
				}
			} elseif ($paytype["pay_type"] == "onetime") {
				if ($pricing["onetime"] >= 0) {
					$v["product_price"] = $pricing["onetime"];
					$v["setup_fee"] = $pricing["osetupfee"];
					$v["billingcycle"] = "onetime";
					$v["billingcycle_zh"] = lang("ONETIME");
				} else {
					$v["product_price"] = number_format(0, 2);
					$v["setup_fee"] = number_format(0, 2);
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
				}
			} elseif (!empty($pricing) && $paytype["pay_type"] == "recurring") {
				if ($pricing["monthly"] >= 0) {
					$v["product_price"] = $pricing["monthly"];
					$v["setup_fee"] = $pricing["msetupfee"];
					$v["billingcycle"] = "monthly";
					$v["billingcycle_zh"] = lang("MONTHLY");
				} elseif ($pricing["quarterly"] >= 0) {
					$v["product_price"] = $pricing["quarterly"];
					$v["setup_fee"] = $pricing["qsetupfee"];
					$v["billingcycle"] = "quarterly";
					$v["billingcycle_zh"] = lang("QUARTERLY");
				} elseif ($pricing["semiannually"] >= 0) {
					$v["product_price"] = $pricing["semiannually"];
					$v["setup_fee"] = $pricing["ssetupfee"];
					$v["billingcycle"] = "semiannually";
					$v["billingcycle_zh"] = lang("SEMIANNUALLY");
				} elseif ($pricing["annually"] >= 0) {
					$v["product_price"] = $pricing["annually"];
					$v["setup_fee"] = $pricing["asetupfee"];
					$v["billingcycle"] = "annually";
					$v["billingcycle_zh"] = lang("ANNUALLY");
				} elseif ($pricing["biennially"] >= 0) {
					$v["product_price"] = $pricing["biennially"];
					$v["setup_fee"] = $pricing["bsetupfee"];
					$v["billingcycle"] = "biennially";
					$v["billingcycle_zh"] = lang("BIENNIALLY");
				} elseif ($pricing["triennially"] >= 0) {
					$v["product_price"] = $pricing["triennially"];
					$v["setup_fee"] = $pricing["tsetupfee"];
					$v["billingcycle"] = "triennially";
					$v["billingcycle_zh"] = lang("TRIENNIALLY");
				} elseif ($pricing["fourly"] >= 0) {
					$v["product_price"] = $pricing["fourly"];
					$v["setup_fee"] = $pricing["foursetupfee"];
					$v["billingcycle"] = "fourly";
					$v["billingcycle_zh"] = lang("FOURLY");
				} elseif ($pricing["fively"] >= 0) {
					$v["product_price"] = $pricing["fively"];
					$v["setup_fee"] = $pricing["fivesetupfee"];
					$v["billingcycle"] = "fively";
					$v["billingcycle_zh"] = lang("FIVELY");
				} elseif ($pricing["sixly"] >= 0) {
					$v["product_price"] = $pricing["sixly"];
					$v["setup_fee"] = $pricing["sixsetupfee"];
					$v["billingcycle"] = "sixly";
					$v["billingcycle_zh"] = lang("SIXLY");
				} elseif ($pricing["sevenly"] >= 0) {
					$v["product_price"] = $pricing["sevenly"];
					$v["setup_fee"] = $pricing["sevensetupfee"];
					$v["billingcycle"] = "sevenly";
					$v["billingcycle_zh"] = lang("SEVENLY");
				} elseif ($pricing["eightly"] >= 0) {
					$v["product_price"] = $pricing["eightly"];
					$v["setup_fee"] = $pricing["eightsetupfee"];
					$v["billingcycle"] = "eightly";
					$v["billingcycle_zh"] = lang("EIGHTLY");
				} elseif ($pricing["ninely"] >= 0) {
					$v["product_price"] = $pricing["ninely"];
					$v["setup_fee"] = $pricing["ninesetupfee"];
					$v["billingcycle"] = "ninely";
					$v["billingcycle_zh"] = lang("NINELY");
				} elseif ($pricing["tenly"] >= 0) {
					$v["product_price"] = $pricing["tenly"];
					$v["setup_fee"] = $pricing["tensetupfee"];
					$v["billingcycle"] = "tenly";
					$v["billingcycle_zh"] = lang("TENLY");
				} else {
					$v["product_price"] = number_format(0, 2);
					$v["setup_fee"] = number_format(0, 2);
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_CONFIG_ERROR");
				}
			} else {
				$v["product_price"] = number_format(0, 2);
				$v["setup_fee"] = number_format(0, 2);
				$v["billingcycle"] = "";
				$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
			}
			if ($paytype["pay_type"] == "recurring" && in_array($v["type"], array_keys(config("developer_app_product_type")))) {
				if ($pricing["annually"] > 0) {
					$v["product_price"] = $pricing["annually"];
					$v["setup_fee"] = $pricing["asetupfee"];
					$v["billingcycle"] = "annually";
					$v["billingcycle_zh"] = lang("ANNUALLY");
				}
			}
			$v["product_price"] += $v["setup_fee"];
			$uid = \request()->uid;
			$cart_logic = new \app\common\logic\Cart();
			$rebate_total = 0;
			$config_total = $cart_logic->getProductDefaultConfigPrice($v["id"], $currencyid, $v["billingcycle"], $rebate_total);
			$rebate_total = bcadd($v["product_price"], $rebate_total, 2);
			$v["product_price"] = bcadd($v["product_price"], $config_total, 2);
			if ($v["api_type"] == "zjmf_api" && $v["upstream_version"] > 0 && $v["upstream_price_type"] == "percent") {
				$v["product_price"] = bcmul($v["product_price"], $v["upstream_price_value"], 2) / 100;
				$rebate_total = bcmul($rebate_total, $v["upstream_price_value"], 2) / 100;
			}
			$flag = getSaleProductUser($v["id"], $uid);
			$v["sale_price"] = $v["bates"] = 0;
			if ($flag) {
				if ($flag["type"] == 1) {
					$bates = bcdiv($flag["bates"], 100, 2);
					$rebate = bcmul($rebate_total, 1 - $bates, 2) < 0 ? 0 : bcmul($rebate_total, 1 - $bates, 2);
					$v["sale_price"] = bcsub($v["product_price"], $rebate, 2) < 0 ? 0 : bcsub($v["product_price"], $rebate, 2);
					$v["bates"] = bcmul($v["product_price"], 1 - $bates, 2);
				} elseif ($flag["type"] == 2) {
					$bates = $flag["bates"];
					$rebate = $rebate_total < $bates ? $rebate_total : $bates;
					$v["sale_price"] = bcsub($v["product_price"], $rebate, 2) < 0 ? 0 : bcsub($v["product_price"], $rebate, 2);
					$v["bates"] = $bates;
				}
			}
			unset($v["pay_type"]);
		}
		$data = ["products" => $products, "count" => $count, "currency" => $currency];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 应用商店（客户后台 应用）
	 * @description 接口说明:TODO 应用商店（客户后台 应用）
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:
	 * @list  id:编号
	 * @list  uid:用户id
	 * @list  ordernum：订单号
	 * @list  create_time:
	 * @list  username:
	 * @list  payment:付款方式
	 * @list  amount:总计
	 * @list  pay_status:付款状态
	 * @list  status:状态
	 * @author wyh
	 * @url cart/market_app
	 * @method get
	 */
	public function getMarketApp($type = "", $app_type = "")
	{
		return json(["status" => 400, "msg" => "应用商店升级中，预计5月升级完成"]);
		$params = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "a.id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		$keywords = isset($params["keywords"]) ? trim($params["keywords"]) : "";
		$type = !empty($type) ? $type : (isset($params["type"]) ? trim($params["type"]) : "");
		$app_type = !empty($app_type) ? $app_type : (isset($params["app_type"]) ? trim($params["app_type"]) : "");
		$pay_type = isset($params["pay_type"]) ? intval($params["pay_type"]) : 0;
		$url = request()->domain() . config("app_file_url");
		$product_paytype = config("product_paytype");
		if (empty($app_type)) {
			$hot = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where(function (\think\db\Query $query) use($type) {
				if (!empty($type)) {
					$query->where("a.type", $type);
				}
			})->where(function (\think\db\Query $query) use($pay_type) {
				if (!empty($pay_type)) {
					$query->where("a.app_pay_type", $pay_type);
				}
			})->where(function (\think\db\Query $query) use($keywords) {
				if (!empty($keywords)) {
					$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
				}
			})->withAttr("icon", function ($value) use($url) {
				$icon = explode(",", $value);
				foreach ($icon as &$vv) {
					$vv = $url . $vv;
				}
				return $icon;
			})->order("a.app_hot_order", "asc")->page(1)->limit(8)->select()->toArray();
			foreach ($hot as &$v) {
				$v["app_score"] = \floatval($v["app_score"]);
				$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
				$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
				if ($v["pay_type"] == "onetime") {
					$billingcycle = "onetime";
				} elseif ($v["pay_type"] == "recurring") {
					$billingcycle = "monthly,annually";
				}
				$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
				if ($v["pay_type"] == "onetime") {
					$v["product_price"] = $pricing["onetime"];
					$v["billingcycle"] = "onetime";
					$v["billingcycle_zh"] = lang("ONETIME");
				} elseif ($v["pay_type"] == "recurring") {
					if ($pricing["annually"] >= 0) {
						$v["product_price"] = $pricing["annually"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					} elseif ($pricing["monthly"] >= 0) {
						$v["product_price"] = $pricing["monthly"];
						$v["billingcycle"] = "monthly";
						$v["billingcycle_zh"] = lang("MONTHLY");
					}
				}
				$v["currency"] = getUserCurrency($v["uid"]);
			}
			$recommend = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_recommend_status", 1)->where(function (\think\db\Query $query) use($type) {
				if (!empty($type)) {
					$query->where("a.type", $type);
				}
			})->where(function (\think\db\Query $query) use($pay_type) {
				if (!empty($pay_type)) {
					$query->where("a.app_pay_type", $pay_type);
				}
			})->where(function (\think\db\Query $query) use($keywords) {
				if (!empty($keywords)) {
					$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
				}
			})->withAttr("icon", function ($value) use($url) {
				$icon = explode(",", $value);
				foreach ($icon as &$vv) {
					$vv = $url . $vv;
				}
				return $icon;
			})->order("a.app_recommend_order", "asc")->page(1)->limit(8)->select()->toArray();
			foreach ($recommend as &$v) {
				$v["app_score"] = \floatval($v["app_score"]);
				$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
				$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
				if ($v["pay_type"] == "onetime") {
					$billingcycle = "onetime";
				} elseif ($v["pay_type"] == "recurring") {
					$billingcycle = "monthly,annually";
				}
				$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
				if ($v["pay_type"] == "onetime") {
					$v["product_price"] = $pricing["onetime"];
					$v["billingcycle"] = "onetime";
					$v["billingcycle_zh"] = lang("ONETIME");
				} elseif ($v["pay_type"] == "recurring") {
					if ($pricing["annually"] >= 0) {
						$v["product_price"] = $pricing["annually"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					} elseif ($pricing["monthly"] >= 0) {
						$v["product_price"] = $pricing["monthly"];
						$v["billingcycle"] = "monthly";
						$v["billingcycle_zh"] = lang("MONTHLY");
					}
				}
				$v["currency"] = getUserCurrency($v["uid"]);
			}
			$addons = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_type", "addons")->where(function (\think\db\Query $query) use($type) {
				if (!empty($type)) {
					$query->where("a.type", $type);
				}
			})->where(function (\think\db\Query $query) use($pay_type) {
				if (!empty($pay_type)) {
					$query->where("a.app_pay_type", $pay_type);
				}
			})->where(function (\think\db\Query $query) use($keywords) {
				if (!empty($keywords)) {
					$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
				}
			})->withAttr("icon", function ($value) use($url) {
				$icon = explode(",", $value);
				foreach ($icon as &$vv) {
					$vv = $url . $vv;
				}
				return $icon;
			})->order("a.order", "asc")->page(1)->limit(8)->select()->toArray();
			foreach ($addons as &$v) {
				$v["app_score"] = \floatval($v["app_score"]);
				$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
				$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
				if ($v["pay_type"] == "onetime") {
					$billingcycle = "onetime";
				} elseif ($v["pay_type"] == "recurring") {
					$billingcycle = "monthly,annually";
				}
				$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
				if ($v["pay_type"] == "onetime") {
					$v["product_price"] = $pricing["onetime"];
					$v["billingcycle"] = "onetime";
					$v["billingcycle_zh"] = lang("ONETIME");
				} elseif ($v["pay_type"] == "recurring") {
					if ($pricing["annually"] >= 0) {
						$v["product_price"] = $pricing["annually"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					} elseif ($pricing["monthly"] >= 0) {
						$v["product_price"] = $pricing["monthly"];
						$v["billingcycle"] = "monthly";
						$v["billingcycle_zh"] = lang("MONTHLY");
					}
				}
				$v["currency"] = getUserCurrency($v["uid"]);
			}
			$gateways = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_type", "gateways")->where(function (\think\db\Query $query) use($type) {
				if (!empty($type)) {
					$query->where("a.type", $type);
				}
			})->where(function (\think\db\Query $query) use($pay_type) {
				if (!empty($pay_type)) {
					$query->where("a.app_pay_type", $pay_type);
				}
			})->where(function (\think\db\Query $query) use($keywords) {
				if (!empty($keywords)) {
					$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
				}
			})->withAttr("icon", function ($value) use($url) {
				$icon = explode(",", $value);
				foreach ($icon as &$vv) {
					$vv = $url . $vv;
				}
				return $icon;
			})->order("a.order", "asc")->page(1)->limit(8)->select()->toArray();
			foreach ($gateways as &$v) {
				$v["app_score"] = \floatval($v["app_score"]);
				$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
				$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
				if ($v["pay_type"] == "onetime") {
					$billingcycle = "onetime";
				} elseif ($v["pay_type"] == "recurring") {
					$billingcycle = "monthly,annually";
				}
				$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
				if ($v["pay_type"] == "onetime") {
					$v["product_price"] = $pricing["onetime"];
					$v["billingcycle"] = "onetime";
					$v["billingcycle_zh"] = lang("ONETIME");
				} elseif ($v["pay_type"] == "recurring") {
					if ($pricing["annually"] >= 0) {
						$v["product_price"] = $pricing["annually"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					} elseif ($pricing["monthly"] >= 0) {
						$v["product_price"] = $pricing["monthly"];
						$v["billingcycle"] = "monthly";
						$v["billingcycle_zh"] = lang("MONTHLY");
					}
				}
				$v["currency"] = getUserCurrency($v["uid"]);
			}
			$servers = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_type", "servers")->where(function (\think\db\Query $query) use($type) {
				if (!empty($type)) {
					$query->where("a.type", $type);
				}
			})->where(function (\think\db\Query $query) use($pay_type) {
				if (!empty($pay_type)) {
					$query->where("a.app_pay_type", $pay_type);
				}
			})->where(function (\think\db\Query $query) use($keywords) {
				if (!empty($keywords)) {
					$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
				}
			})->withAttr("icon", function ($value) use($url) {
				$icon = explode(",", $value);
				foreach ($icon as &$vv) {
					$vv = $url . $vv;
				}
				return $icon;
			})->order("a.order", "asc")->page(1)->limit(8)->select()->toArray();
			foreach ($servers as &$v) {
				$v["app_score"] = \floatval($v["app_score"]);
				$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
				$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
				if ($v["pay_type"] == "onetime") {
					$billingcycle = "onetime";
				} elseif ($v["pay_type"] == "recurring") {
					$billingcycle = "monthly,annually";
				}
				$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
				if ($v["pay_type"] == "onetime") {
					$v["product_price"] = $pricing["onetime"];
					$v["billingcycle"] = "onetime";
					$v["billingcycle_zh"] = lang("ONETIME");
				} elseif ($v["pay_type"] == "recurring") {
					if ($pricing["annually"] >= 0) {
						$v["product_price"] = $pricing["annually"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					} elseif ($pricing["monthly"] >= 0) {
						$v["product_price"] = $pricing["monthly"];
						$v["billingcycle"] = "monthly";
						$v["billingcycle_zh"] = lang("MONTHLY");
					}
				}
				$v["currency"] = getUserCurrency($v["uid"]);
			}
			$systems = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_type", "systems")->where(function (\think\db\Query $query) use($type) {
				if (!empty($type)) {
					$query->where("a.type", $type);
				}
			})->where(function (\think\db\Query $query) use($pay_type) {
				if (!empty($pay_type)) {
					$query->where("a.app_pay_type", $pay_type);
				}
			})->where(function (\think\db\Query $query) use($keywords) {
				if (!empty($keywords)) {
					$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
				}
			})->withAttr("icon", function ($value) use($url) {
				$icon = explode(",", $value);
				foreach ($icon as &$vv) {
					$vv = $url . $vv;
				}
				return $icon;
			})->order("a.order", "asc")->page(1)->limit(8)->select()->toArray();
			foreach ($systems as &$v) {
				$v["app_score"] = \floatval($v["app_score"]);
				$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
				$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
				if ($v["pay_type"] == "onetime") {
					$billingcycle = "onetime";
				} elseif ($v["pay_type"] == "recurring") {
					$billingcycle = "monthly,annually";
				}
				$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
				if ($v["pay_type"] == "onetime") {
					$v["product_price"] = $pricing["onetime"];
					$v["billingcycle"] = "onetime";
					$v["billingcycle_zh"] = lang("ONETIME");
				} elseif ($v["pay_type"] == "recurring") {
					if ($pricing["annually"] >= 0) {
						$v["product_price"] = $pricing["annually"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					} elseif ($pricing["monthly"] >= 0) {
						$v["product_price"] = $pricing["monthly"];
						$v["billingcycle"] = "monthly";
						$v["billingcycle_zh"] = lang("MONTHLY");
					}
				}
				$v["currency"] = getUserCurrency($v["uid"]);
			}
			$oauth = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_type", "oauth")->where(function (\think\db\Query $query) use($type) {
				if (!empty($type)) {
					$query->where("a.type", $type);
				}
			})->where(function (\think\db\Query $query) use($pay_type) {
				if (!empty($pay_type)) {
					$query->where("a.app_pay_type", $pay_type);
				}
			})->where(function (\think\db\Query $query) use($keywords) {
				if (!empty($keywords)) {
					$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
				}
			})->withAttr("icon", function ($value) use($url) {
				$icon = explode(",", $value);
				foreach ($icon as &$vv) {
					$vv = $url . $vv;
				}
				return $icon;
			})->order("a.order", "asc")->page(1)->limit(8)->select()->toArray();
			foreach ($oauth as &$v) {
				$v["app_score"] = \floatval($v["app_score"]);
				$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
				$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
				if ($v["pay_type"] == "onetime") {
					$billingcycle = "onetime";
				} elseif ($v["pay_type"] == "recurring") {
					$billingcycle = "monthly,annually";
				}
				$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
				if ($v["pay_type"] == "onetime") {
					$v["product_price"] = $pricing["onetime"];
					$v["billingcycle"] = "onetime";
					$v["billingcycle_zh"] = lang("ONETIME");
				} elseif ($v["pay_type"] == "recurring") {
					if ($pricing["annually"] >= 0) {
						$v["product_price"] = $pricing["annually"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					} elseif ($pricing["monthly"] >= 0) {
						$v["product_price"] = $pricing["monthly"];
						$v["billingcycle"] = "monthly";
						$v["billingcycle_zh"] = lang("MONTHLY");
					}
				}
				$v["currency"] = getUserCurrency($v["uid"]);
			}
			$templates = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_type", "templates")->where(function (\think\db\Query $query) use($type) {
				if (!empty($type)) {
					$query->where("a.type", $type);
				}
			})->where(function (\think\db\Query $query) use($pay_type) {
				if (!empty($pay_type)) {
					$query->where("a.app_pay_type", $pay_type);
				}
			})->where(function (\think\db\Query $query) use($keywords) {
				if (!empty($keywords)) {
					$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
				}
			})->withAttr("icon", function ($value) use($url) {
				$icon = explode(",", $value);
				foreach ($icon as &$vv) {
					$vv = $url . $vv;
				}
				return $icon;
			})->order("a.order", "asc")->page(1)->limit(8)->select()->toArray();
			foreach ($templates as &$v) {
				$v["app_score"] = \floatval($v["app_score"]);
				$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
				$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
				if ($v["pay_type"] == "onetime") {
					$billingcycle = "onetime";
				} elseif ($v["pay_type"] == "recurring") {
					$billingcycle = "monthly,annually";
				}
				$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
				if ($v["pay_type"] == "onetime") {
					$v["product_price"] = $pricing["onetime"];
					$v["billingcycle"] = "onetime";
					$v["billingcycle_zh"] = lang("ONETIME");
				} elseif ($v["pay_type"] == "recurring") {
					if ($pricing["annually"] >= 0) {
						$v["product_price"] = $pricing["annually"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					} elseif ($pricing["monthly"] >= 0) {
						$v["product_price"] = $pricing["monthly"];
						$v["billingcycle"] = "monthly";
						$v["billingcycle_zh"] = lang("MONTHLY");
					}
				}
				$v["currency"] = getUserCurrency($v["uid"]);
			}
			$data = ["hot" => $hot, "recommend" => $recommend, "addons" => $addons, "gateways" => $gateways, "servers" => $servers, "systems" => $systems, "oauth" => $oauth, "templates" => $templates];
		} else {
			if ($app_type == "hot") {
				$count = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where(function (\think\db\Query $query) use($type) {
					if (!empty($type)) {
						$query->where("a.type", $type);
					}
				})->where(function (\think\db\Query $query) use($pay_type) {
					if (!empty($pay_type)) {
						$query->where("a.app_pay_type", $pay_type);
					}
				})->where(function (\think\db\Query $query) use($keywords) {
					if (!empty($keywords)) {
						$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
					}
				})->count();
				$products = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_hot_order", "<=", 50)->where(function (\think\db\Query $query) use($type) {
					if (!empty($type)) {
						$query->where("a.type", $type);
					}
				})->where(function (\think\db\Query $query) use($pay_type) {
					if (!empty($pay_type)) {
						$query->where("a.app_pay_type", $pay_type);
					}
				})->where(function (\think\db\Query $query) use($keywords) {
					if (!empty($keywords)) {
						$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
					}
				})->withAttr("icon", function ($value) use($url) {
					$icon = explode(",", $value);
					foreach ($icon as &$vv) {
						$vv = $url . $vv;
					}
					return $icon;
				})->order("a.app_hot_order", "asc")->page($page)->limit($limit)->select()->toArray();
				foreach ($products as &$v) {
					$v["app_score"] = \floatval($v["app_score"]);
					$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
					$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
					if ($v["pay_type"] == "onetime") {
						$billingcycle = "onetime";
					} elseif ($v["pay_type"] == "recurring") {
						$billingcycle = "monthly,annually";
					}
					$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
					if ($v["pay_type"] == "onetime") {
						$v["product_price"] = $pricing["onetime"];
						$v["billingcycle"] = "onetime";
						$v["billingcycle_zh"] = lang("ONETIME");
					} elseif ($v["pay_type"] == "recurring") {
						if ($pricing["annually"] >= 0) {
							$v["product_price"] = $pricing["annually"];
							$v["billingcycle"] = "annually";
							$v["billingcycle_zh"] = lang("ANNUALLY");
						} elseif ($pricing["monthly"] >= 0) {
							$v["product_price"] = $pricing["monthly"];
							$v["billingcycle"] = "monthly";
							$v["billingcycle_zh"] = lang("MONTHLY");
						}
					}
					$v["currency"] = getUserCurrency($v["uid"]);
				}
				$data = [$app_type => $products, "count" => $count];
			} elseif ($app_type == "recommend") {
				$count = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where(function (\think\db\Query $query) use($type) {
					if (!empty($type)) {
						$query->where("a.type", $type);
					}
				})->where(function (\think\db\Query $query) use($pay_type) {
					if (!empty($pay_type)) {
						$query->where("a.app_pay_type", $pay_type);
					}
				})->where(function (\think\db\Query $query) use($keywords) {
					if (!empty($keywords)) {
						$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
					}
				})->count();
				$products = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_recommend_status", 1)->where(function (\think\db\Query $query) use($type) {
					if (!empty($type)) {
						$query->where("a.type", $type);
					}
				})->where(function (\think\db\Query $query) use($pay_type) {
					if (!empty($pay_type)) {
						$query->where("a.app_pay_type", $pay_type);
					}
				})->where(function (\think\db\Query $query) use($keywords) {
					if (!empty($keywords)) {
						$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
					}
				})->withAttr("icon", function ($value) use($url) {
					$icon = explode(",", $value);
					foreach ($icon as &$vv) {
						$vv = $url . $vv;
					}
					return $icon;
				})->order("a.app_recommend_order", "asc")->page($page)->limit($limit)->select()->toArray();
				foreach ($products as &$v) {
					$v["app_score"] = \floatval($v["app_score"]);
					$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
					$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
					if ($v["pay_type"] == "onetime") {
						$billingcycle = "onetime";
					} elseif ($v["pay_type"] == "recurring") {
						$billingcycle = "monthly,annually";
					}
					$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
					if ($v["pay_type"] == "onetime") {
						$v["product_price"] = $pricing["onetime"];
						$v["billingcycle"] = "onetime";
						$v["billingcycle_zh"] = lang("ONETIME");
					} elseif ($v["pay_type"] == "recurring") {
						if ($pricing["annually"] >= 0) {
							$v["product_price"] = $pricing["annually"];
							$v["billingcycle"] = "annually";
							$v["billingcycle_zh"] = lang("ANNUALLY");
						} elseif ($pricing["monthly"] >= 0) {
							$v["product_price"] = $pricing["monthly"];
							$v["billingcycle"] = "monthly";
							$v["billingcycle_zh"] = lang("MONTHLY");
						}
					}
					$v["currency"] = getUserCurrency($v["uid"]);
				}
				$data = [$app_type => $products, "count" => $count];
			} else {
				$count = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_type", $app_type)->where(function (\think\db\Query $query) use($type) {
					if (!empty($type)) {
						$query->where("a.type", $type);
					}
				})->where(function (\think\db\Query $query) use($pay_type) {
					if (!empty($pay_type)) {
						$query->where("a.app_pay_type", $pay_type);
					}
				})->where(function (\think\db\Query $query) use($keywords) {
					if (!empty($keywords)) {
						$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
					}
				})->count();
				$products = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_type", $app_type)->where(function (\think\db\Query $query) use($type) {
					if (!empty($type)) {
						$query->where("a.type", $type);
					}
				})->where(function (\think\db\Query $query) use($pay_type) {
					if (!empty($pay_type)) {
						$query->where("a.app_pay_type", $pay_type);
					}
				})->where(function (\think\db\Query $query) use($keywords) {
					if (!empty($keywords)) {
						$query->where("a.info", "like", "%{$keywords}%")->whereOr("a.name", "like", "%{$keywords}%");
					}
				})->withAttr("icon", function ($value) use($url) {
					$icon = explode(",", $value);
					foreach ($icon as &$vv) {
						$vv = $url . $vv;
					}
					return $icon;
				})->order("a.order", "asc")->page($page)->limit($limit)->select()->toArray();
				foreach ($products as &$v) {
					$v["app_score"] = \floatval($v["app_score"]);
					$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
					$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
					if ($v["pay_type"] == "onetime") {
						$billingcycle = "onetime";
					} elseif ($v["pay_type"] == "recurring") {
						$billingcycle = "monthly,annually";
					}
					$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
					if ($v["pay_type"] == "onetime") {
						$v["product_price"] = $pricing["onetime"];
						$v["billingcycle"] = "onetime";
						$v["billingcycle_zh"] = lang("ONETIME");
					} elseif ($v["pay_type"] == "recurring") {
						if ($pricing["annually"] >= 0) {
							$v["product_price"] = $pricing["annually"];
							$v["billingcycle"] = "annually";
							$v["billingcycle_zh"] = lang("ANNUALLY");
						} elseif ($pricing["monthly"] >= 0) {
							$v["product_price"] = $pricing["monthly"];
							$v["billingcycle"] = "monthly";
							$v["billingcycle_zh"] = lang("MONTHLY");
						}
					}
					$v["currency"] = getUserCurrency($v["uid"]);
				}
				$data = [$app_type => $products, "count" => $count];
			}
		}
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 应用排行榜
	 * @description 接口说明:应用排行榜
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @author xj
	 * @url cart/app_ranking_list
	 * @method get
	 */
	public function appRankingList()
	{
		return json(["status" => 400, "msg" => "应用商店升级中，预计5月升级完成"]);
		$params = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$type = isset($params["type"]) ? trim($params["type"]) : "";
		$url = request()->domain() . config("app_file_url");
		$product_paytype = config("product_paytype");
		$total = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where(function (\think\db\Query $query) use($type) {
			if (!empty($type)) {
				$query->where("a.type", $type);
			}
		})->withAttr("icon", function ($value) use($url) {
			$icon = explode(",", $value);
			foreach ($icon as &$vv) {
				$vv = $url . $vv;
			}
			return $icon;
		})->order("a.app_hot_heat", "desc")->page($page)->limit($limit)->select()->toArray();
		foreach ($total as &$v) {
			$v["app_score"] = \floatval($v["app_score"]);
			$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
			$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
			if ($v["pay_type"] == "onetime") {
				$billingcycle = "onetime";
			} elseif ($v["pay_type"] == "recurring") {
				$billingcycle = "monthly,annually";
			}
			$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
			if ($v["pay_type"] == "onetime") {
				$v["product_price"] = $pricing["onetime"];
				$v["billingcycle"] = "onetime";
				$v["billingcycle_zh"] = lang("ONETIME");
			} elseif ($v["pay_type"] == "recurring") {
				if ($pricing["annually"] >= 0) {
					$v["product_price"] = $pricing["annually"];
					$v["billingcycle"] = "annually";
					$v["billingcycle_zh"] = lang("ANNUALLY");
				} elseif ($pricing["monthly"] >= 0) {
					$v["product_price"] = $pricing["monthly"];
					$v["billingcycle"] = "monthly";
					$v["billingcycle_zh"] = lang("MONTHLY");
				}
			}
			$v["currency"] = getUserCurrency($v["uid"]);
		}
		$charge = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_pay_type", 0)->where(function (\think\db\Query $query) use($type) {
			if (!empty($type)) {
				$query->where("a.type", $type);
			}
		})->withAttr("icon", function ($value) use($url) {
			$icon = explode(",", $value);
			foreach ($icon as &$vv) {
				$vv = $url . $vv;
			}
			return $icon;
		})->order("a.app_hot_heat", "desc")->page($page)->limit($limit)->select()->toArray();
		foreach ($charge as &$v) {
			$v["app_score"] = \floatval($v["app_score"]);
			$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
			$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
			if ($v["pay_type"] == "onetime") {
				$billingcycle = "onetime";
			} elseif ($v["pay_type"] == "recurring") {
				$billingcycle = "monthly,annually";
			}
			$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
			if ($v["pay_type"] == "onetime") {
				$v["product_price"] = $pricing["onetime"];
				$v["billingcycle"] = "onetime";
				$v["billingcycle_zh"] = lang("ONETIME");
			} elseif ($v["pay_type"] == "recurring") {
				if ($pricing["annually"] >= 0) {
					$v["product_price"] = $pricing["annually"];
					$v["billingcycle"] = "annually";
					$v["billingcycle_zh"] = lang("ANNUALLY");
				} elseif ($pricing["monthly"] >= 0) {
					$v["product_price"] = $pricing["monthly"];
					$v["billingcycle"] = "monthly";
					$v["billingcycle_zh"] = lang("MONTHLY");
				}
			}
			$v["currency"] = getUserCurrency($v["uid"]);
		}
		$free = \think\Db::name("products")->alias("a")->field("b.id as uid,a.id,a.uuid,c.name as nickname,a.name,a.type,a.info,a.description,a.pay_type,a.icon,a.app_score,a.app_type,a.version_description,a.app_version,a.unretired_time,b.currency")->leftJoin("clients b", "a.p_uid = b.id")->leftJoin("developer c", "b.id = c.uid")->where("a.p_uid", ">", 0)->where("a.app_status", 1)->where("a.retired", 0)->where("a.hidden", 0)->where("a.app_pay_type", 1)->where(function (\think\db\Query $query) use($type) {
			if (!empty($type)) {
				$query->where("a.type", $type);
			}
		})->withAttr("icon", function ($value) use($url) {
			$icon = explode(",", $value);
			foreach ($icon as &$vv) {
				$vv = $url . $vv;
			}
			return $icon;
		})->order("a.app_hot_heat", "desc")->page($page)->limit($limit)->select()->toArray();
		foreach ($free as &$v) {
			$v["app_score"] = \floatval($v["app_score"]);
			$v["pay_type"] = json_decode($v["pay_type"], true)["pay_type"] ?? "";
			$v["pay_type_zh"] = $product_paytype[$v["pay_type"]] ?? "";
			if ($v["pay_type"] == "onetime") {
				$billingcycle = "onetime";
			} elseif ($v["pay_type"] == "recurring") {
				$billingcycle = "monthly,annually";
			}
			$pricing = \think\Db::name("pricing")->field($billingcycle)->where("type", "product")->where("currency", $v["currency"])->where("relid", $v["id"])->find();
			if ($v["pay_type"] == "onetime") {
				$v["product_price"] = $pricing["onetime"];
				$v["billingcycle"] = "onetime";
				$v["billingcycle_zh"] = lang("ONETIME");
			} elseif ($v["pay_type"] == "recurring") {
				if ($pricing["annually"] >= 0) {
					$v["product_price"] = $pricing["annually"];
					$v["billingcycle"] = "annually";
					$v["billingcycle_zh"] = lang("ANNUALLY");
				} elseif ($pricing["monthly"] >= 0) {
					$v["product_price"] = $pricing["monthly"];
					$v["billingcycle"] = "monthly";
					$v["billingcycle_zh"] = lang("MONTHLY");
				}
			}
			$v["currency"] = getUserCurrency($v["uid"]);
		}
		$data = ["total" => $total, "charge" => $charge, "free" => $free];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 应用评论
	 * @description 接口说明:应用评论
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @author xj
	 * @url cart/app/:id/evaluation
	 * @method get
	 * @param   .name:page type:int require:0 desc:页数 default:1
	 * @param   .name:limit type:int require:0 desc:每页条数 default:10
	 * @param   .name:orderby type:string require:0 desc:排序(create_time,score) default:create_time
	 * @param   .name:score type:string require:0 desc:查询分数(1,2,3,4,5)
	 */
	public function appEvaluation($id)
	{
		$params = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "a.create_time";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		$score = isset($params["score"]) ? trim($params["score"]) : "";
		if ($this->request->uid) {
			$evaluation_like = \think\Db::name("evaluation_like")->where("uid", $this->request->uid)->select()->toArray();
			$evaluation_like = array_column($evaluation_like, "eid");
		} else {
			$evaluation_like = [];
		}
		$evaluation = \think\Db::name("evaluation")->alias("a")->field("b.id as uid,a.id,b.username,a.content,a.score,a.like_num,a.create_time")->leftJoin("clients b", "a.uid = b.id")->where("a.eid", 0)->where("a.rid", $id)->where("a.type", "products")->where("a.status", 1)->where(function (\think\db\Query $query) use($score) {
			if (!empty($score)) {
				$query->where("a.score", "like", "{$score}%");
			}
		})->order($order, "desc")->page($page)->limit($limit)->select()->toArray();
		foreach ($evaluation as $key => &$value) {
			$value["is_like"] = in_array($value["id"], $evaluation_like);
			$value["reply"] = \think\Db::name("evaluation")->alias("a")->field("b.id as uid,a.id,a.aid,b.username,a.content,a.create_time")->leftJoin("clients b", "a.uid = b.id")->where("a.eid", $value["id"])->where("a.status", 1)->order("a.create_time", "asc")->select()->toArray();
		}
		$data = ["evaluation" => $evaluation];
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function setConfToShopData($data, $req)
	{
		$cart_data = $data["cart_products"];
		$shop = new \app\common\logic\Shop(request()->uid);
		$conf = $shop->getShoppingCart();
		$conf = $conf["products"] ?? [];
		$currency = getUserCurrency(request()->uid);
		foreach ($cart_data as $key => &$val) {
			$val["conf"] = $conf[$key];
			$param = [$currency, $val["productid"], $val["conf"]];
			$val["conf_child"] = $this->getProductDetail($param);
		}
		$data["cart_products"] = $cart_data;
		return $data;
	}
	public function getProductDetail($param)
	{
		list($currency, $pid, $confs) = $param;
		$data = [];
		$conf = $confs["configoptions"];
		$options = \think\Db::name("product_config_options")->whereIn("id", array_keys($conf))->where("hidden", 0)->select()->toArray();
		foreach ($options as $key => $val) {
			if (empty($val)) {
				continue;
			}
			$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", $val["id"])->where("id", $conf[$val["id"]])->where("hidden", 0)->find();
			$config_show = ["option_type" => $val["option_type"], "name" => explode("|", $val["option_name"])[1] ? explode("|", $val["option_name"])[1] : $val["option_name"]];
			if ($val["option_type"] == 3) {
				if ($conf[$val["id"]] == 1) {
					$config_show["sub_name"] = "是";
				} else {
					$conf[$val["id"]] = 0;
					$config_show["sub_name"] = "否";
				}
			} else {
				if (judgeQuantity($val["option_type"])) {
					$qty_minimum = $val["qty_minimum"];
					if (!empty($conf[$val["id"]])) {
						$config_show["sub_name"] = $conf[$val["id"]] . $val["unit"];
					} else {
						$config_show["sub_name"] = $qty_minimum . $val["unit"];
					}
				} else {
					$config_show["sub_name"] = explode("|", $suboptions["option_name"])[1] ? explode("|", $suboptions["option_name"])[1] : $suboptions["option_name"];
					$pos = strpos($config_show["sub_name"], "^");
					if ($pos !== false) {
						$sub_arr = explode("^", $config_show["sub_name"]);
						$config_show["sub_name"] = $sub_arr[1];
						if ($val["option_type"] == 5) {
							$config_show["os_group"] = $sub_arr[0];
						} elseif ($val["option_type"] == 12) {
							$config_show["code"] = $sub_arr[0];
						}
					}
				}
			}
			$data[] = $config_show;
		}
		return $data;
	}
	/**
	 * @time 2021-12-09
	 * @title 购物车商品列表(拉取缓存)
	 * @description 购物车商品列表(拉取缓存)
	 * @url cart/prolist
	 * @method  GET
	 * @author wyh
	 * @return fgs:商品信息@
	 */
	public function proList()
	{
		$uid = request()->uid;
		$logic = new \app\common\logic\Product();
		$lists = $logic->getListCache();
		if (empty($lists)) {
			$logic->updateListCache();
			$lists = $logic->getListCache();
		}
		$gid = 0;
		if ($uid) {
			$gid = \think\Db::name("clients")->where("id", $uid)->value("groupid");
		}
		$fgs = \think\Db::name("product_first_groups")->field("id,name")->where("hidden", 0)->order("order", "asc")->order("id", "asc")->select()->toArray();
		foreach ($fgs as &$fg) {
			$gs = \think\Db::name("product_groups")->field("id,name,headline,tagline,order,gid,order_frm_tpl,tpl_type")->where("hidden", 0)->where("order_frm_tpl", "<>", "uuid")->where(function (\think\db\Query $query) use($fg) {
				$query->where("gid", $fg["id"]);
			})->order("order", "asc")->select()->toArray();
			foreach ($gs as &$g) {
				$tmp = array_filter($lists, function ($v) use($g) {
					if ($v["gid"] != $g["id"]) {
						return false;
					}
					return true;
				});
				$filter = [];
				foreach ($tmp as $v) {
					if ($v["gid"] = $g["id"]) {
						if ($gid && isset($v["cgs"][$gid])) {
							$v["sale_price"] = $v["cgs"][$gid]["sale_price"];
							$v["bates"] = $v["cgs"][$gid]["bates"];
						}
						unset($v["cgs"]);
						$filter[] = $v;
					}
				}
				$g["products"] = $filter;
			}
			$fg["group"] = $gs;
		}
		$data = ["fgs" => $fgs];
		return json(["status" => 200, "msg" => "请求成功", "data" => $data]);
	}
}