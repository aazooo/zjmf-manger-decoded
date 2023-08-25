<?php

namespace app\admin\controller;

/**
 * @title 可配置选项组
 * @description 接口说明
 */
class ConfigOptionsController extends AdminBaseController
{
	/**
	* @title 可选项配置组列表(本地已测试)
	* @description 接口说明:可选项配置组列表
	* @author wyh
	* @url /admin/options/groups_list
	* @method GET
	* @param .name:page type:int require:1 default:1 other: desc:第几页
	* @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	* @param .name:order type:string require:1 default:10 other: desc:排序字段
	* @param .name:order_method type:int require:1 default:10 other: desc:ASC,DESC
	* @param .name:keyword type:string require:0 default:1 other: desc:按关键字搜索
	@param .name:type type:string require:0 default:1 other: desc:产品类型normal
	@param .name:keywords type:string require:0 default:1 other: desc:按组名搜索
	* @return .total:总数
	* @return .totalPage:总页数
	* @return .list:可配置选项组信息
	* @return .list.id:可配置选项组ID
	* @return .list.name:可配置选项组名称
	* @return .list.description:可配置选项组描述
	*/
	public function groupsList()
	{
		$params = $this->request->param();
		$order = isset($params["order"][0]) ? trim($params["order"]) : "id";
		$sort = isset($params["sort"][0]) ? trim($params["sort"]) : "desc";
		$keywords = isset($params["keywords"]) ? trim($params["keywords"]) : "";
		$list = \think\Db::name("product_config_groups")->where("global", 1)->where(function (\think\db\Query $query) use($keywords) {
			if (!empty($keywords)) {
				$query->where("name", "like", "%{$keywords}%");
			}
		})->order($order, $sort)->field("id,name,description")->select()->toArray();
		$listFilter = [];
		foreach ($list as $key => $value) {
			$products = \think\Db::name("product_config_links")->alias("a")->field("b.id,b.name,b.type")->leftJoin("products b", "a.pid = b.id")->where("a.gid", $value["id"])->where(function (\think\db\Query $query) use($value) {
			})->select()->toArray();
			foreach ($products as $k => $product) {
				$products[$k]["name"] = "<a class=\"el-link el-link--primary is-underline\" href=\"#/edit-product?id=" . $product["id"] . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $product["name"] . "</span></a>";
			}
			$value["products"] = implode(",", array_column($products, "name"));
			$listFilter[] = $value;
		}
		$product_type = config("product_type");
		unset($product_type["dcim"]);
		unset($product_type["dcimcloud"]);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "list" => $listFilter, "type" => $product_type]);
	}
	public function searchPage()
	{
		$product_type = config("product_type");
		unset($product_type["dcim"]);
		unset($product_type["dcimcloud"]);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "type" => $product_type]);
	}
	/**
	 * @title 创建可选项配置组页面(本地已测试)
	 * @description 接口说明:创建可选项配置组页面
	 * @author wyh
	 * @url /admin/options/create_groups
	 * @method GET
	 * @param  .name:type type:number require:1 desc:产品组分类1=通用2=裸金属3=魔方云
	 * @return .products:产品信息
	 * @return .products.pg_name:产品组名称
	 * @return .products.p_name:产品名称
	 * @return .products.p_id:
	 * @return .products.link:展示方式
	 */
	public function createGroups()
	{
		$param = $this->request->param();
		$where = [];
		if (isset($param["type"]) && $param["type"] > 0 && $param["type"] < 4) {
			$where[] = ["pg.type", "=", \intval($param["type"])];
		}
		$result = \think\Db::name("products")->alias("p")->field("pg.name as pg_name,p.name as p_name,p.id as p_id,CONCAT(pg.name,\"：\",p.name) as link,pg.type")->leftJoin("product_groups pg", "p.gid = pg.id")->where($where)->select();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "products" => $result]);
	}
	/**
	 * @title 创建可选项配置组页面提交(本地已测试)
	 * @description 接口说明:创建可选项配置组页面提交
	 * @author wyhcheckSaveLinkAgeLevel
	 * @url /admin/options/create_groups_post
	 * @method POST
	 * @param .name:name type:string require:1 default:1
	 * other: desc:可选项配置组名称
	 * @param .name:description type:string require:1 default:1 other: desc:可选项配置组描述
	 * @param .name:products.p_id type:int require:0 default:1 other: desc:产品ID,多选
	 * @param .name:global type:int require:1 default:1 other: desc:是否全局配置项组,是1,0否,
	 * @return .gid:可选项配置组ID(前端需要根据gid判断是执行添加还是编辑)
	 */
	public function createGroupsPost()
	{
		if ($this->request->isPost()) {
			$params = $this->request->only(["p_id", "name", "description", "global"]);
			$products = $params["p_id"];
			unset($params["p_id"]);
			$data = array_map("trim", $params);
			\think\Db::startTrans();
			try {
				$groupid = \think\Db::name("product_config_groups")->insertGetId($data);
				if (!empty($products) && is_array($products)) {
					$products = \think\Db::name("products")->field("id")->whereIn("id", $products)->select()->toArray();
					$products = array_column($products, "id");
					$insert = [];
					foreach ($products as $product) {
						$insert[] = ["gid" => $groupid, "pid" => $product];
					}
					\think\Db::name("product_config_links")->insertAll($insert);
					\think\Db::name("products")->whereIn("id", $products)->setInc("location_version");
					(new \app\common\logic\Product())->updateCache($products);
					foreach ($products as $pid) {
						$res_array = hook("product_edit", ["pid" => $pid]);
						foreach ($res_array as $res) {
							if ($res["is_resource"] && $res["status"] != 200) {
								throw new \think\Exception("可选项配置组同步至资源池失败,失败原因:" . $res["msg"]);
							}
						}
					}
					active_log(sprintf($this->lang["Configoption_admin_createGroupsPost"], $groupid));
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
			}
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS"), "groupid" => $groupid]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 编辑可配置选项组页面 Configurable Option Groups页面(完成)
	 * @description 接口说明:编辑可配置选项组页面
	 * @author wyh
	 * @url /admin/options/edit_groups/:gid
	 * @method GET
	 * @param .name:gid type:string require:1 default:1 other: desc:可选项配置组ID
	 * @param  .name:type type:number require:1 desc:产品组分类1=通用2=裸金属3=魔方云
	 * @return .group:可选项配置组信息
	 * @return .group.name:可选项配置组名称
	 * @return .group.description:可选项配置组描述
	 * @return .group.global:是否全局配置项组：1是、0否
	 * @return .pids:已经选择的产品组--产品ID
	 * @return .product:产品信息
	 * @return .product.pg_name:产品组名称
	 * @return .product.p_name:产品名称
	 * @return .product.p_id:产品ID
	 * @return .product.link:展示方式
	 * @return .options:可选项配置信息
	 * @return .options.option_name:可选项配置名称
	 * @return .options.option_type:可选项配置类型,1默认Dropdown,2radio,3yes/no,4quantity
	 * @return .options.order:可选项配置排序默认0
	 * @return .options.hidden:可选项配置是否显示：0默认显示，1隐藏
	 * @return .options.upgrade:可选项配置是否可以升降级：1是，0否
	 * @return .options.is_discount:可选项配置是否可以用于折扣：1是，0否
	 * @return edition:0免费版，1专业版
	 */
	public function editGroups()
	{
		$params = $this->request->param();
		$where = [];
		if (isset($params["type"]) && $params["type"] > 0 && $params["type"] < 4) {
			$where[] = ["pg.type", "=", \intval($params["type"])];
		}
		$gid = intval($params["gid"]);
		if ($gid) {
			$groups = \think\Db::name("product_config_groups")->where("id", $gid)->find();
			$groupsfilter = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $groups);
			$pids = \think\Db::name("product_config_links")->field("pid")->where("gid", $gid)->select();
			$prolink = \think\Db::name("products")->alias("p")->field("pg.name as pg_name,p.name as p_name,p.id as p_id,CONCAT(pg.name,\"：\",p.name) as link,pg.type")->leftJoin("product_groups pg", "p.gid = pg.id")->where($where)->select();
			$options = \think\Db::name("product_config_options")->where("gid", $gid)->where("linkage_pid", 0)->where("linkage_top_pid", 0)->select();
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "group" => $groupsfilter, "pid" => $pids, "product" => $prolink, "options" => $options, "edition" => getEdition()]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
	}
	/**
	* @title 编辑可配置选项组页面提交(完成)
	* @description 接口说明:编辑可配置选项组页面提交
	* @author wyh
	* @url /admin/options/edit_groups_post
	* @method POST
	* @param .name:gid type:string require:1 default:1 other: desc:可选项配置组ID
	* @param .name:name type:string require:0 default:1 other: desc:可选项配置组名称
	* @param .name:description type:string require:0 default:1 other: desc:可选项配置组描述
	* @param .name:productlinks[] type:int require:0 default:1 other: desc:产品ID（多选框）
	* @param .name:order[] type:int require:0 default:1 other: desc:可配置选项排序,如：order[9],order[10]
	* @param .name:hidden[] type:int require:0 default:1 other: desc:
	* @param .name:upgrade[] type:int require:0 default:1 other: desc:是否升级：1默认是，0否
	@param .name:down[] type:int require:0 default:1 other: desc:是否降级：1是，0否默认
	* @param .name:is_discount[] type:int require:0 default:1 other: desc:是否应用优惠：1默认是，0否
	* @param .name:is_rebate[] type:int require:0 default:1 other: desc:是否折扣：1默认是，0否
	*/
	public function editGroupsPost()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$gid = isset($param["gid"]) ? intval($param["gid"]) : "";
			if (!$gid) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$pcg = \think\Db::name("product_config_groups")->where("id", $gid)->find();
			$dec = "";
			$optiongroup["name"] = $param["name"];
			if ($optiongroup["name"] != $pcg["name"]) {
				$dec .= " -组名" . $pcg["name"] . "修改为" . $optiongroup["name"];
			}
			$optiongroup["description"] = $param["description"];
			if ($optiongroup["description"] != $pcg["description"]) {
				$dec .= " -描述" . $pcg["description"] . "修改为" . $optiongroup["description"];
			}
			$optiongroupfilter = array_map("trim", $optiongroup);
			if (isset($param["order"]) && is_array($param["order"])) {
				$orders = $param["order"];
			} else {
				$orders = [];
			}
			if (isset($param["upgrade"]) && is_array($param["upgrade"])) {
				$upgrades = $param["upgrade"];
			} else {
				$upgrades = [];
			}
			$product = \think\Db::name("product_config_links")->alias("a")->field("b.api_type")->leftJoin("products b", "a.pid=b.id")->leftJoin("product_config_groups c", "a.gid=c.id")->where("a.gid", $gid)->where("c.global", 0)->find();
			if ($product["api_type"] == "zjmf_api" && $pcg["upstream_id"] > 0) {
				$product = \think\Db::name("products")->alias("a")->leftJoin("product_config_links b", "a.id = b.pid")->where("b.gid", $gid)->find();
				$param["productlinks"] = [];
				if ($product["upstream_price_type"] == "custom") {
					$location_options = \think\Db::name("product_config_options")->where("gid", $gid)->select()->toArray();
					$res = getZjmfUpstreamProductConfig($product["zjmf_api_id"], $product["upstream_pid"]);
					if ($res["status"] == 200) {
						$config_groups = $res["data"]["config_groups"] ?? [];
						if (!empty($config_groups)) {
							foreach ($config_groups as $config_group) {
								$options1 = $config_group["options"];
								foreach ($options1 as $option) {
									foreach ($location_options as $location_option) {
										if ($location_option["upstream_id"] == $option["id"] && $option["upgrade"] == 0) {
											$upgrades[$location_option["id"]] = 0;
										}
									}
								}
							}
						}
					}
				} else {
					return jsonrule(["status" => 400, "msg" => lang("不可更改配置项")]);
				}
			}
			\think\Db::startTrans();
			try {
				\think\Db::name("product_config_groups")->where("id", $gid)->update($optiongroupfilter);
				$pcg2 = \think\Db::name("product_config_links")->where("gid", $gid)->select()->toArray();
				if ($pcg["upstream_id"] == 0) {
					\think\Db::name("product_config_links")->where("gid", $gid)->delete();
					if (!empty($param["productlinks"]) && is_array($param["productlinks"])) {
						$pids = $param["productlinks"];
						$insert = [];
						$gids = "";
						$gids1 = "";
						foreach ($pids as $key => $value) {
							$insert[] = ["gid" => $gid, "pid" => $value];
							if ($key == 0) {
								$gids .= $value;
							} else {
								$gids .= "," . $value;
							}
						}
						foreach ($pcg2 as $key => $value) {
							if ($key == 0) {
								$gids1 .= $value["pid"];
							} else {
								$gids1 .= "," . $value["pid"];
							}
						}
						\think\Db::name("product_config_links")->insertAll($insert);
						if ($gids != $gids1) {
							$product = \think\Db::name("products")->field("name")->where("id", "in", $gids)->select()->toArray();
							$product1 = \think\Db::name("products")->field("name")->where("id", "in", $gids1)->select()->toArray();
							$pname = "";
							$pname1 = "";
							foreach ($product as $key => $value) {
								if ($key == 0) {
									$pname .= $value["name"];
								} else {
									$pname .= "," . $value["name"];
								}
							}
							foreach ($product1 as $key => $value) {
								if ($key == 0) {
									$pname1 .= $value["name"];
								} else {
									$pname1 .= "," . $value["name"];
								}
							}
							$dec .= "指定产品由“" . $pname1 . "”改为“" . $pname . "”，";
						}
					}
				}
				foreach ($orders as $key => $value) {
					$options["order"] = is_numeric($value) ? ceil($value) : 0;
					$options["hidden"] = !empty($param["hidden"][$key]) ? 1 : 0;
					$options["upgrade"] = $upgrades[$key] ? 1 : 0;
					$options["is_discount"] = $param["is_discount"][$key] ? 1 : 0;
					$options["is_rebate"] = isset($param["is_rebate"]) ? intval($param["is_rebate"][$key]) : 1;
					$pco = \think\Db::name("product_config_options")->field("option_name,order,hidden,upgrade,is_discount,option_type")->where("id", intval($key))->find();
					if ($pco["option_type"] == 20) {
						\think\Db::name("product_config_options")->where("linkage_top_pid", intval($key))->update($options);
					}
					\think\Db::name("product_config_options")->where("id", intval($key))->update($options);
					if ($pco["order"] != $options["order"]) {
						$dec .= " - " . $pco["name"] . " -排序 " . $pco["order"] . "修改为 " . $options["order"];
					}
					if ($pco["hidden"] != $options["hidden"]) {
						if ($options["hidden"] == 1) {
							$dec .= $pco["name"] . "由“隐藏”改为“显示”，";
						} else {
							$dec .= $pco["name"] . "由“显示”改为“隐藏”，";
						}
					}
					if ($pco["upgrade"] != $options["upgrade"]) {
						if ($options["upgrade"] == 1) {
							$dec .= $pco["name"] . "升级由“关闭”改为“开启”，";
						} else {
							$dec .= $pco["name"] . "升级由“开启”改为“关闭”，";
						}
					}
					if ($pco["is_discount"] != $options["upgrade"]) {
						if ($options["is_discount"] == 1) {
							$dec .= $pco["name"] . "使用折扣由“关闭”改为“开启”，";
						} else {
							$dec .= $pco["name"] . "使用折扣由“开启”改为“关闭”，";
						}
					}
				}
				if (empty($dec)) {
					$dec .= "没有任何修改";
				}
				active_log(sprintf($this->lang["Configoption_admin_editGroupsPost"], $gid, $dec));
				unset($dec);
				if (!empty($param["productlinks"]) && is_array($param["productlinks"])) {
					$pids = $param["productlinks"];
					\think\Db::name("products")->whereIn("id", $pids)->setInc("location_version");
					(new \app\common\logic\Product())->updateCache($pids);
				}
				foreach ($pids as $pid) {
					$res_array = hook("product_edit", ["pid" => $pid]);
					foreach ($res_array as $res) {
						if ($res["is_resource"] && $res["status"] != 200) {
							throw new \think\Exception("可配置选项组同步至资源池失败,失败原因:" . $res["msg"]);
						}
					}
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
			}
			return jsonrule(["status" => 200, "msg" => lang("EDIT SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 添加可配置选项Configurable Options页面(完成已测)
	 * @description 接口说明:添加可配置选项Configurable Options页面(完成已测)
	 * @author wyh
	 * @url /admin/options/add_options_page
	 * @method GET
	 * @param .name:gid type:int require:1 default:1 other: desc:可选项配置组ID
	 * @param .name:pid type:int require:0 default:1 other: desc:产品ID
	 */
	public function addOptionsPage()
	{
		$param = $this->request->param();
		$gid = isset($param["gid"]) ? intval($param["gid"]) : "";
		if (!$gid) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$currency = new \app\common\logic\Currencies();
		$currencies = $currency->getCurrencies("id,code");
		$pid = isset($param["pid"]) ? intval($param["pid"]) : "";
		$pay_type_recurring = [];
		if (!empty($pid)) {
			$pay_type = \think\Db::name("products")->where("id", $pid)->value("pay_type");
			$pay_type = json_decode($pay_type, true);
			$paytype = $pay_type["pay_type"] ?? "";
			$pay_ontrial_status = $pay_type["pay_ontrial_status"] ?? 0;
			if ($paytype == "onetime") {
				$field = ["osetupfee", "onetime"];
			} elseif ($paytype == "free") {
				$field = [];
			} elseif ($paytype == "recurring") {
				foreach ($currencies as $v) {
					$product_pricing = \think\Db::name("pricing")->where("type", "product")->where("currency", $v["id"])->where("relid", $pid)->find();
					$price_type = config("price_type");
					foreach ($price_type as $vv) {
						if ($product_pricing[$vv[0]] >= 0 && $vv[0] != "onetime" && $vv[0] != "free") {
							$field[] = $vv[0];
							$field[] = $vv[1];
							$pay_type_recurring[] = $vv[0];
						}
					}
				}
			}
			if ($pay_ontrial_status) {
				$field[] = "ontrialfee";
				$field[] = "ontrial";
			}
			$data = ["paytype" => $paytype, "pay_type_recurring" => $pay_type_recurring, "pay_ontrial_status" => $pay_ontrial_status, "cycle" => $field, "type" => config("configurable_option_type_name")];
		} else {
			$data = ["paytype" => "", "pay_type_recurring" => $pay_type_recurring, "pay_ontrial_status" => "", "cycle" => [], "type" => config("configurable_option_type_name")];
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 添加可配置选项Configurable Options页面(完成已测)
	 * @description 接口说明:添加可配置选项
	 * @author wyh
	 * @url /admin/options/add_options
	 * @method POST
	 * @param .name:gid type:int require:1 default:1 other: desc:可选项配置组ID
	 * @param .name:option_name type:string require:1 default:1 other: desc:可选项配置名称
	 * @param .name:option_type type:tinyint require:1 default:1 other: desc:可选项配置类型：1默认Dropdown,2radio,3yes/no,4quantity
	 * @param .name:addoptionname type:string require:1 default:1 other: desc:子项名称
	 * @param .name:addsortorder type:tinyint require:1 default:1 other: desc:排序默认为0
	 * @param .name:addhidden type:tinyint require:1 default:1 other: desc:1隐藏
	 * @param .name:notes type:string require:0 default:1 other: desc:备注
	 * @param .name:qty_stage type:string require:0 default:1 other: desc:数量阶梯
	 * @return cid:可配置项ID（页面跳转至编辑页）
	 */
	public function addOptions()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$gid = isset($param["gid"]) ? intval($param["gid"]) : "";
			if (!$gid) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$pco = \think\Db::name("product_config_groups")->where("id", $gid)->find();
			if ($pco["upstream_id"] > 0) {
				return jsonrule(["status" => 400, "msg" => lang("上游配置项组,不可添加配置项")]);
			}
			$validate = new \app\admin\validate\ConfigOptionsValidate();
			$option["gid"] = $gid;
			$option["option_name"] = $param["option_name"];
			$option["option_type"] = $param["option_type"];
			$option["notes"] = $param["notes"];
			if (!getEdition() && $param["qty_stage"] > 1) {
				return jsonrule(["status" => 400, "msg" => "此功能仅专业版可用"]);
			}
			$option["qty_stage"] = isset($param["qty_stage"]) ? intval($param["qty_stage"]) : 0;
			if (!$validate->scene("add_config_option")->check($option)) {
				return jsonrule(["status" => 400, "msg" => $validate->getError()]);
			}
			$optionfilter = array_map("trim", $option);
			$suboption = [];
			$suboption["option_name"] = trim($param["addoptionname"]);
			if (!$validate->scene("add_config_option_sub")->check($suboption)) {
				return jsonrule(["status" => 400, "msg" => $validate->getError()]);
			}
			\think\Db::startTrans();
			try {
				$optionfilter["linkage_pid"] = $param["linkage_pid"] ?: 0;
				$cid = \think\Db::name("product_config_options")->insertGetId($optionfilter);
				if ($optionfilter["linkage_pid"] == 0) {
					$update_option["linkage_level"] = "0-" . $cid;
					$update_option["linkage_top_pid"] = 0;
				} else {
					$linkage_top_data = \think\Db::name("product_config_options")->where("id", $optionfilter["linkage_pid"])->find();
					$update_option["linkage_top_pid"] = $linkage_top_data["linkage_top_pid"];
					if ($linkage_top_data["linkage_pid"] == 0) {
						$update_option["linkage_top_pid"] = $linkage_top_data["id"] ?? 0;
					}
					$update_option["linkage_level"] = $linkage_top_data["linkage_level"] . "-" . $cid;
				}
				\think\Db::name("product_config_options")->where("id", $cid)->update($update_option);
				$suboption["config_id"] = $cid;
				$suboption["sort_order"] = isset($param["addsortorder"]) && !empty($param["addsortorder"]) ? ceil($param["addsortorder"]) : 0;
				$suboption["hidden"] = isset($param["addhidden"]) && !empty($param["addhidden"]) ? 1 : 0;
				$sub_id = \think\Db::name("product_config_options_sub")->insertGetId($suboption);
				$pids = $this->updateProductVersion($gid, "groups");
				$currencies = (new \app\common\logic\Currencies())->getCurrencies("id");
				foreach ($currencies as $currency) {
					$pricing_logic = new \app\common\logic\Pricing();
					$pricing_logic::addToConfig($sub_id, "configoptions", $currency["id"]);
				}
				foreach ($pids as $pid) {
					$res_array = hook("product_edit", ["pid" => $pid]);
					foreach ($res_array as $res) {
						if ($res["is_resource"] && $res["status"] != 200) {
							throw new \think\Exception("配置项同步至资源池失败,失败原因:" . $res["msg"]);
						}
					}
				}
				\think\Db::commit();
				active_log(sprintf($this->lang["Configoption_admin_add"], $cid));
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
			}
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS"), "cid" => $cid]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 删除可配置选项的子选项
	 * @description 接口说明:删除可配置选项的子选项
	 * @author wyh
	 * @url /admin/options/delete_sub_options/:subid
	 * @method GET
	 * @param .name:subid type:string require:1 default:1 other: desc:子配置选项ID
	 */
	public function deleteSubOptions()
	{
		$params = $this->request->param();
		$subid = intval($params["subid"]);
		if (!$subid) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$pcos = \think\Db::name("product_config_options_sub")->where("id", $subid)->find();
		if ($pcos["upstream_id"] > 0) {
			return jsonrule(["status" => 400, "msg" => lang("上游配置子项,不可删除")]);
		}
		if ($subid) {
			\think\Db::startTrans();
			try {
				$pids = $this->updateProductVersion($subid, "options_sub");
				$sub_ids = \think\Db::name("product_config_options_sub")->where("id", $subid)->whereOr("linkage_level", "like", "%-" . $subid . "-%")->column("id");
				$sub_ids = $sub_ids ?: [0];
				\think\Db::name("product_config_options_sub")->whereIn("id", $sub_ids)->delete();
				\think\Db::name("pricing")->where("type", "configoptions")->whereIn("relid", $sub_ids)->delete();
				foreach ($pids as $pid) {
					$res_array = hook("product_edit", ["pid" => $pid]);
					foreach ($res_array as $res) {
						if ($res["is_resource"] && $res["status"] != 200) {
							throw new \think\Exception("同步至资源池失败,失败原因:" . $res["msg"]);
						}
					}
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
			}
			return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 删除可配置选项
	 * @description 接口说明:删除可配置选项
	 * @author wyh
	 * @url /admin/options/delete_options/:cid
	 * @method GET
	 * @param .name:cid type:string require:1 default:1 other: desc:可配置选项ID
	 */
	public function deleteOptions()
	{
		$params = $this->request->param();
		$cid = intval($params["cid"]);
		if ($cid) {
			$pco = \think\Db::name("product_config_options")->where("id", $cid)->find();
			if ($pco["upstream_id"] > 0) {
				return jsonrule(["status" => 400, "msg" => lang("上游配置项,不可删除")]);
			}
			$pricing = new \app\common\logic\Pricing();
			\think\Db::startTrans();
			try {
				$pids = $this->updateProductVersion($cid, "options");
				\think\Db::name("product_config_options")->where("id", $cid)->delete();
				$subids = \think\Db::name("product_config_options_sub")->field("id")->where("config_id", $cid)->select();
				if (!empty($subids)) {
					foreach ($subids as $key => $value) {
						$pricing->delete("configoptions", $value);
					}
				}
				\think\Db::name("product_config_options_sub")->where("config_id", $cid)->delete();
				active_log(sprintf($this->lang["Configoption_admin_delete"], $cid));
				foreach ($pids as $pid) {
					$res_array = hook("product_edit", ["pid" => $pid]);
					foreach ($res_array as $res) {
						if ($res["is_resource"] && $res["status"] != 200) {
							throw new \think\Exception("同步至资源池失败,失败原因:" . $res["msg"]);
						}
					}
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
			}
			return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
	}
	/**
	 * @title 删除可配置选项组
	 * @description 接口说明:删除可配置选项组
	 * @author wyh
	 * @url /admin/options/delete_groups/:gid
	 * @method GET
	 * @param .name:gid type:string require:1 default:1 other: desc:可选项配置组ID
	 */
	public function deleteGroups()
	{
		$params = $this->request->param();
		$gid = intval($params["gid"]);
		if ($gid) {
			\think\Db::startTrans();
			try {
				$pids = $this->updateProductVersion($gid, "groups");
				\think\Db::name("product_config_groups")->where("id", $gid)->delete();
				\think\Db::name("product_config_links")->where("gid", $gid)->delete();
				$cids = \think\Db::name("product_config_options")->field("id")->where("gid", $gid)->select();
				if (!empty($cids)) {
					foreach ($cids as $cid) {
						$subids = \think\Db::name("product_config_options_sub")->field("id")->where("config_id", $cid["id"])->select();
						if (!empty($subids)) {
							foreach ($subids as $subid) {
								\think\Db::name("pricing")->where("type", "configoptions")->where("relid", $subid["id"])->delete();
							}
						}
						\think\Db::name("product_config_options_sub")->where("config_id", $cid["id"])->delete();
					}
					\think\Db::name("product_config_options")->where("gid", $gid)->delete();
				}
				active_log(sprintf($this->lang["Configoption_admin_delete"], $gid));
				foreach ($pids as $pid) {
					$res_array = hook("product_edit", ["pid" => $pid]);
					foreach ($res_array as $res) {
						if ($res["is_resource"] && $res["status"] != 200) {
							throw new \think\Exception("同步至资源池失败,失败原因:" . $res["msg"]);
						}
					}
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
			}
			return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
	}
	/**
	 * @title 复制可配置选项组页面
	 * @description 接口说明:复制可配置选项组页面
	 * @author wyh
	 * @url /admin/options/duplicate_groups
	 * @method GET
	 * @return .groups:所有可选项配置组的信息
	 */
	public function duplicateGroups()
	{
		$result = \think\Db::name("product_config_groups")->alias("pcg")->field("id,name,description,CONCAT(name,\"--\",description) as links")->where("global", 1)->select();
		if ($result) {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "groups" => $result]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 复制可配置选项组页面提交
	 * @description 接口说明:复制可配置选项组页面提交
	 * @author wyh
	 * @url /admin/options/duplicate_groups_post
	 * @method POST
	 * @param .name:gid type:int require:1 default:1 other: desc:可选项配置组ID
	 * @param .name:newname type:string require:1 default:1 other: desc:新的可选项配置组名称
	 */
	public function duplicateGroupsPost()
	{
		if ($this->request->isPost()) {
			$data = array_map("trim", $this->request->param());
			$gid = isset($data["gid"]) ? intval($data["gid"]) : "";
			if (!$gid) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$oldgroup = \think\Db::name("product_config_groups")->where("id", $gid)->find();
			if (empty($oldgroup)) {
				return jsonrule(["status" => 400, "msg" => lang("CONFIG_OPITON_GROUP_CAN_NOT_FIND")]);
			}
			$newgroup = [];
			$newgroup["description"] = $oldgroup["description"];
			$newname = isset($data["newname"]) ? trim($data["newname"]) : "";
			if (!$newname) {
				return jsonrule(["status" => 400, "msg" => lang("CONFIG_OPITON_GROUP_NAME_REQUIRE")]);
			}
			$newgroup["name"] = $newname;
			$newgroup["global"] = $oldgroup["global"];
			\think\Db::startTrans();
			try {
				$newgroupid = \think\Db::name("product_config_groups")->insertGetId($newgroup);
				$oldpids = \think\Db::name("product_config_links")->field("pid")->where("gid", $gid)->select();
				if (!empty($oldpids)) {
					foreach ($oldpids as $oldpid) {
						$newlinks["gid"] = $newgroupid;
						$newlinks["pid"] = $oldpid["pid"];
						\think\Db::name("product_config_links")->insertGetId($newlinks);
					}
				}
				$oldoptions = \think\Db::name("product_config_options")->where("gid", $gid)->select()->toArray();
				if (!empty($oldoptions[0])) {
					foreach ($oldoptions as $oldoption) {
						$newoption["gid"] = $newgroupid;
						$newoption["option_name"] = $oldoption["option_name"];
						$newoption["option_type"] = $oldoption["option_type"];
						$newoption["qty_minimum"] = $oldoption["qty_minimum"];
						$newoption["qty_maximum"] = $oldoption["qty_maximum"];
						$newoption["order"] = $oldoption["order"];
						$newoption["hidden"] = $oldoption["hidden"];
						$newoption["upgrade"] = $oldoption["upgrade"];
						$newcid = \think\Db::name("product_config_options")->insertGetId($newoption);
						$oldcid = $oldoption["id"];
						$oldsuboptions = \think\Db::name("product_config_options_sub")->where("config_id", $oldcid)->select()->toArray();
						if (!empty($oldsuboptions[0])) {
							foreach ($oldsuboptions as $oldsuboption) {
								$newsuboption["config_id"] = $newcid;
								$newsuboption["option_name"] = $oldsuboption["option_name"];
								$newsuboption["sort_order"] = $oldsuboption["sort_order"];
								$newsuboption["hidden"] = $oldsuboption["hidden"];
								$newsuboption["qty_minimum"] = $oldsuboption["qty_minimum"];
								$newsuboption["qty_maximum"] = $oldsuboption["qty_maximum"];
								$newsubid = \think\Db::name("product_config_options_sub")->insertGetId($newsuboption);
								$oldsubid = $oldsuboption["id"];
								$oldpricings = \think\Db::name("pricing")->field("id,relid", true)->where("type", "configoptions")->where("relid", $oldsubid)->select()->toArray();
								if (!empty($oldpricings[0])) {
									foreach ($oldpricings as $oldpricing) {
										$newpricing = $oldpricing;
										$newpricing["relid"] = $newsubid;
										\think\Db::name("pricing")->insertGetId($newpricing);
									}
								}
							}
						}
					}
				}
				active_log(sprintf($this->lang["Configoption_admin_duplicateGroupsPost"], $gid));
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("DUPLICATE FAIL")]);
			}
			return jsonrule(["status" => 200, "msg" => lang("DUPLICATE SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 输出系统自带接口有操作系统配置选项的产品
	 * @description 接口说明:编辑可配置项页面
	 * @author xiong
	 * @url /admin/options/config_options_check_os
	 * @method GET
	 * @return .pid:产品ID
	 * @return .name:产品名称
	 * @return .type:产品类型
	 */
	public function configOptionsCheckOs()
	{
		$microtime = microtime(true);
		$products = \think\Db::name("products")->alias("a")->leftJoin("servers s", "s.gid=a.server_group")->leftJoin("product_config_links b", "a.id=b.pid")->leftJoin("shd_product_config_options c", "b.gid=c.gid")->field("a.id as pid,a.name,s.server_type as type")->where("(s.server_type='dcimcloud' OR s.server_type='dcim') AND s.gid>0 AND a.server_group>0 AND c.option_type=5 AND (a.api_type='normal' OR a.api_type='')")->order("a.id", "desc")->select()->toArray();
		return jsonrule(["status" => 200, "products" => $products]);
	}
	/**
	 * @title 拉取系统自带接口操作系统并同步
	 * @description 接口说明:编辑可配置项页面
	 * @author xiong
	 * @url /admin/options/config_options_check_os/:pid
	 * @method POST
	 * @param .pid:产品ID
	 */
	public function configOptionsOs()
	{
		$params = $this->request->param();
		$pid = isset($params["pid"]) ? intval($params["pid"]) : "";
		if (!$pid) {
			return jsonrule(["status" => 400, "pid" => $pid, "msg" => lang("ID_ERROR")]);
		}
		$products = \think\Db::name("products")->alias("a")->leftJoin("product_config_links b", "a.id=b.pid")->leftJoin("shd_product_config_options c", "b.gid=c.gid")->field("a.server_group,c.id as cid,a.api_type,a.type")->where(["a.id" => $pid, "c.option_type" => 5])->find();
		$cid = $products["cid"];
		if (empty($cid)) {
			return jsonrule(["status" => 400, "pid" => $pid, "msg" => lang("ID_ERROR")]);
		}
		if ($products["type"] != "dcim" && $products["type"] != "dcimcloud" || $products["api_type"] != "normal" && $products["api_type"] != "") {
			return jsonrule(["status" => 400, "pid" => $pid, "msg" => "只有本地接口（魔方云或者DCIM）才能拉取"]);
		}
		$server_info = \think\Db::name("servers")->field("id,server_type")->where("gid", $products["server_group"])->where("server_type='dcimcloud' OR server_type='dcim'")->find();
		if ($server_info["server_type"] == "dcim") {
			$dcim = new \app\common\logic\Dcim();
			$dcim->setUrl($server_info["id"]);
			$getOs_name = $dcim->getFormatOs();
		} elseif ($server_info["server_type"] == "dcimcloud") {
			$dcimcloud = new \app\common\logic\DcimCloud();
			$dcimcloud->setUrl($server_info["id"]);
			$getOs = $dcimcloud->getOs();
			$getOs_name = array_column($getOs, "name");
		}
		if (!$getOs_name[0]) {
			return jsonrule(["status" => 400, "pid" => $pid, "msg" => lang("拉取操作系统失败,拉取数据为空")]);
			exit;
		}
		if (is_array($getOs_name)) {
			$config_options_sub = \think\Db::name("product_config_options_sub")->field("id,option_name,hidden")->where("config_id", $cid)->select()->toArray();
			foreach ($getOs_name as $os) {
				list($key) = explode("|", $os);
				$getOs_name_new[$key] = $os;
			}
			$hidden = array_column($config_options_sub, "hidden", "id");
			$config_options_sub_name = array_column($config_options_sub, "option_name", "id");
			$sub_name_diff = array_diff($config_options_sub_name, $getOs_name_new);
			$getOs_name_diff = array_diff($getOs_name_new, $config_options_sub_name);
			$num_update = 0;
			$num_insert = 0;
			foreach ($hidden as $hidden_k => $hidden_v) {
				if ($hidden_v == 1 && in_array($config_options_sub_name[$hidden_k], $getOs_name)) {
					\think\Db::name("product_config_options_sub")->where("id", $hidden_k)->update(["hidden" => 0]);
					$num_update++;
				}
			}
			if (is_array($sub_name_diff)) {
				foreach ($sub_name_diff as $sub_k => $sub_diff) {
					list($id) = explode("|", $sub_diff);
					if ($hidden[$sub_k] == 1) {
						if ($getOs_name_new[$id]) {
							\think\Db::name("product_config_options_sub")->where("id", $sub_k)->update(["option_name" => $getOs_name_new[$id], "hidden" => 0]);
							$num_update++;
						}
					} else {
						if ($getOs_name_new[$id]) {
							\think\Db::name("product_config_options_sub")->where("id", $sub_k)->update(["option_name" => $getOs_name_new[$id], "hidden" => 0]);
						} else {
							\think\Db::name("product_config_options_sub")->where("id", $sub_k)->update(["option_name" => $sub_diff, "hidden" => 1]);
						}
						$num_update++;
					}
				}
			}
			if (is_array($getOs_name_diff)) {
				$pricing = [];
				foreach ($config_options_sub_name as $os_k => $os) {
					list($key) = explode("|", $os);
					$config_options_sub_name_new[$key] = $os_k;
				}
				foreach ($getOs_name_diff as $kk => $vv) {
					if (!$config_options_sub_name_new[$kk]) {
						$config_option_sub = ["config_id" => $cid, "option_name" => $vv, "qty_minimum" => 0, "qty_maximum" => 0];
						$sub_id = \think\Db::name("product_config_options_sub")->insertGetId($config_option_sub);
						$pricing[] = ["type" => "configoptions", "relid" => $sub_id, "monthly" => 0];
						$num_insert++;
					}
				}
				if (!empty($pricing)) {
					$currency = \think\Db::name("currencies")->column("id");
					foreach ($currency as $v) {
						foreach ($pricing as $kk => $vv) {
							$pricing[$kk]["currency"] = $v;
						}
						\think\Db::name("pricing")->insertAll($pricing);
					}
				}
			}
		}
		if ($num_update == 0 && $num_insert == 0) {
			return jsonrule(["status" => 200, "pid" => $pid, "msg" => lang("CONFIG_GETOS_SUCCESS_NONUM")]);
		} else {
			$desc = lang("CONFIG_GETOS_ACTIVE_LOG", ["cid" => $cid, "num_update" => $num_update, "num_insert" => $num_insert]);
			active_log($desc);
			return jsonrule(["status" => 200, "pid" => $pid, "msg" => lang("CONFIG_GETOS_SUCCESS_NUM", ["num_update" => $num_update, "num_insert" => $num_insert])]);
			exit;
		}
	}
	/**
	 * @title 编辑可配置项页面
	 * @description 接口说明:编辑可配置项页面
	 * @author wyh
	 * @url /admin/options/edit_config/:cid
	 * @method GET
	 * @param .name:cid type:int require:1 default:1 other: desc:可选项配置ID
	 * @param .name:pid type:int require:1 default:1 other: desc:产品ID
	 * @param .name:option_type type:int require:1 default:5 other: desc:配置项类型操作系统
	 * @return .option:可配置选项信息 unit 单位
	 * @return .suboptions:所有子项信息
	 * @return .pricingsall:所有子项价格信息
	 */
	public function editConfig()
	{
		$params = $this->request->param();
		$linkAgeList = [];
		$cid = isset($params["cid"]) ? intval($params["cid"]) : "";
		if (!$cid) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$currency = new \app\common\logic\Currencies();
		$currencies = $currency->getCurrencies("id,code");
		$price_type = config("price_type");
		if (isset($params["pid"])) {
			$pid = intval($params["pid"]);
			$productstype = \think\Db::name("products")->field("type,pay_type")->where("id", $pid)->find();
			$pay_type = json_decode($productstype["pay_type"], true);
			$paytype = $pay_type["pay_type"] ?? "";
			$pay_ontrial_status = $pay_type["pay_ontrial_status"] ?? 0;
			$products_type = $productstype["type"];
			$field = "";
			$pay_type_recurring = [];
			if ($paytype == "onetime") {
				$field .= ",p.osetupfee,p.onetime";
			} elseif ($paytype == "free") {
			} elseif ($paytype == "recurring") {
				foreach ($currencies as $v) {
					$product_pricing = \think\Db::name("pricing")->where("type", "product")->where("currency", $v["id"])->where("relid", $pid)->find();
					foreach ($price_type as $vv) {
						if ($product_pricing[$vv[0]] >= 0 && $vv[0] != "onetime" && $vv[0] != "free") {
							$field .= ",p." . $vv[1] . ",p." . $vv[0];
							$pay_type_recurring[] = $vv[0];
						}
					}
				}
			}
			if ($pay_ontrial_status) {
				$field .= ",p.ontrialfee,p.ontrial";
			}
		} else {
			$field = ",p.*";
		}
		$option = \think\Db::name("product_config_options")->where("id", $cid)->find();
		$optiontype = $option["option_type"];
		if ($optiontype == 3) {
			$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", $cid)->order("sort_order ASC")->order("id ASC")->limit(1)->select();
			$suboptionsfilter = [];
			foreach ($suboptions as $key => $suboption) {
				$subid = $suboption["id"];
				$pricings = \think\Db::name("pricing")->alias("p")->field("c.code,p.currency" . $field)->leftJoin("currencies c", "c.id = p.currency")->where("p.type", "configoptions")->where("p.relid", $subid)->select();
				$suboption["child"] = $pricings;
				$suboption["cost"] = [];
				$suboptionsfilter[$key] = $suboption;
			}
		} else {
			$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", $cid)->order("sort_order ASC")->order("id ASC")->select();
			$suboptionsfilter = [];
			foreach ($suboptions as $key => $suboption) {
				$subid = $suboption["id"];
				$pricings = \think\Db::name("pricing")->alias("p")->field("c.code,p.currency" . $field)->leftJoin("currencies c", "c.id = p.currency")->where("p.type", "configoptions")->where("p.relid", $subid)->select();
				$suboption["child"] = $pricings;
				$suboption["cost"] = [];
				$suboptionsfilter[$key] = $suboption;
			}
		}
		$is_upstream_percent_product = false;
		if ($option["upstream_id"] > 0) {
			$product = \think\Db::name("products")->alias("a")->field("a.upstream_price_type,a.api_type,a.zjmf_api_id,a.upstream_pid")->leftJoin("product_config_links b", "a.id = b.pid")->leftJoin("product_config_groups c", "b.gid = c.id")->leftJoin("product_config_options d", "d.gid = c.id")->where("d.id", $cid)->find();
			if (!empty($product)) {
				$res = getZjmfUpstreamProductConfig($product["zjmf_api_id"], $product["upstream_pid"]);
				if ($res["status"] == 200) {
					$upstream_product = $res["data"]["products"];
					if ($upstream_product["api_type"] == "zjmf_api" && $upstream_product["upstream_pid"] > 0 && $upstream_product["upstream_price_type"] == "percent") {
						$is_upstream_percent_product = true;
					}
					$config_groups = $res["data"]["config_groups"];
					foreach ($config_groups as $config_group) {
						$options = $config_group["options"];
						foreach ($options as $v) {
							if ($v["id"] == $option["upstream_id"]) {
								$upstream_option = $v;
							}
						}
					}
					unset($config_groups);
				}
				if ($upstream_option) {
					$subs = $upstream_option["sub"];
					foreach ($subs as $kk => $vv) {
						foreach ($suboptionsfilter as $kkk => $vvv) {
							if ($vv["id"] == $vvv["upstream_id"]) {
								$upstream_config_pricings = $vv["pricings"];
								if ($is_upstream_percent_product) {
									foreach ($upstream_config_pricings as $k4 => $v4) {
										foreach ($price_type as $v5) {
											$upstream_config_pricings[$k4][$v5[0]] = $v4[$v5[0]] * $upstream_product["upstream_price_value"] / 100;
											$upstream_config_pricings[$k4][$v5[1]] = $v4[$v5[1]] * $upstream_product["upstream_price_value"] / 100;
										}
									}
								}
								$suboptionsfilter[$kkk]["cost"] = $upstream_config_pricings;
							}
						}
					}
					unset($subs);
					unset($upstream_option);
				}
			}
		}
		$this->request->sub_id = 0;
		$linkAgeList = $this->getLinkAgeList($cid);
		unset($is_upstream_percent_product);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "currencies" => $currencies, "option" => $option ?? "", "suboptions" => $suboptionsfilter, "products_type" => $products_type, "pay_type" => $paytype, "pay_type_recurring" => $pay_type_recurring, "pay_ontrial_status" => $pay_ontrial_status, "type" => config("configurable_option_type_name"), "product" => $product ?? [], "link_age_list" => $linkAgeList]);
	}
	/**
	 * @title 获取下一级列表
	 * @description 接口说明:获取下一级列表
	 * @author xue
	 * @url /admin/options/getNextLinkAgeList
	 * @method get
	 * @param .name:id type:int require:1 default:0 other: desc:子项id
	 */
	public function getNextLinkAgeList()
	{
		try {
			$param = $this->request->param();
			$config_id = \think\Db::name("product_config_options_sub")->where("linkage_pid", $param["id"])->value("config_id");
			if (!$config_id) {
				return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["sub_pid" => $param["id"]]]);
			}
			$option_data = \think\Db::name("product_config_options")->where("id", $config_id)->find();
			$option_sub = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->order("sort_order", "desc")->select()->toArray();
			$option_sub = array_map(function ($v) {
				$v["disable"] = false;
				return $v;
			}, $option_sub);
			$option_data["sub_son"] = $option_sub;
			$option_data["sub_pid"] = $param["id"];
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $option_data]);
		} catch (\think\Exception $e) {
			return jsonrule(["status" => 400, "msg" => $e->getMessage(), "data" => ["sub_pid" => $param["id"]]]);
		}
	}
	protected function getLinkAgeList($cid)
	{
		$configOptions = new \app\common\logic\ConfigOptions();
		$list = $configOptions->getLinkAgeList($this->request);
		$arr = ["cid" => $cid];
		if ($this->request->sub_id) {
			$arr["sub_id"] = $this->request->sub_id;
		}
		$list = $configOptions->setLinkAgeListDefaultVal($list, $arr);
		if ($this->request->get_focus) {
			$sub_data = \think\Db::name("product_config_options_sub")->where("id", $this->request->sub_id)->value("linkage_level");
			if (!$sub_data) {
				return $list;
			}
			$level_len = count(explode("-", $sub_data));
			if (count($list) < $level_len) {
				$list[] = [];
			}
		}
		return $list;
	}
	/**
	 * @title 编辑可配置项页面提交
	 * @description 接口说明:编辑可配置项页面提交
	 * @author wyh
	 * @url /admin/options/edit_config_post
	 * @method POST
	 * @param .name:cid type:int require:1 default:1 other: desc:可选项配置ID
	 * @param .name:configoptionname type:int require:1 default:1 other: desc:可配置项名称
	 * @param .name:configoptiontype type:int require:1 default:1 other: desc:类型
	 * @param .name:notes type:string require:0 default:1 other: desc:备注
	 * @param .name:configqtyminimum type:int require:0 default:1 other: desc:当类型为4时，最小值
	 * @param .name:configqtymaximum type:int require:0 default:1 other: desc:当类型为4时，最大值
	 * @param .name:optionname[子项ID] type:int require:0 default:1 other: desc:子项名称
	 * @param .name:sortorder[子项ID] type:int require:0 default:1 other: desc:子项排序
	 * @param .name:qtyminimum[子项ID] type:int require:0 default:1 other: desc:子项最小值
	 * @param .name:qtymaximum[子项ID] type:int require:0 default:1 other: desc:子项最大值
	 * @param .name:price[货币ID][子项ID][osetupfee-tenly] type:int require:0 default:1 other: desc:价格数据
	 * @param .name:addoptionname type:int require:0 default:1 other: desc:添加的子项名称
	 * @param .name:addsortorder type:int require:0 default:1 other: desc:添加的子项排序
	 * @param .name:addhidden type:int require:0 default:1 other: desc:添加的子项:1隐藏，0否
	 * @param .name:is_discount type:int require:0 default:1 other: desc:可配置项折扣:1开启，0否
	 * @param .name:qty_stage type:int require:0 default:1 other: desc:当配置项为数量类型时，需要填写此字段
	 * @param .name:unit type:int require:0 default:1 other: desc:单位:配置项类型为1 2 3 5 12时不需要单位
	 */
	public function editConfigPost()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$validate = new \app\admin\validate\ConfigOptionsValidate();
			$cid = isset($param["cid"]) ? intval($param["cid"]) : "";
			if (!$cid) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			if ($param["configoptiontype"] == 20 && !getEdition()) {
				return jsonrule(["status" => 400, "msg" => "该功能仅专业版可用！"]);
			}
			$dec = "";
			$pco = \think\Db::name("product_config_options")->where("id", $cid)->find();
			$option["option_name"] = $param["configoptionname"] ?? "";
			if ($option["option_name"] != $pco["option_name"]) {
				$dec .= "配置项名称" . $pco["option_name"] . "改为" . $option["option_name"];
			}
			$option["option_type"] = $param["configoptiontype"] ?? "";
			$option["notes"] = $param["notes"] ?? "";
			$cot = config("configurable_option_type");
			$cot1 = config("configurable_option_type_name");
			$cot1 = array_column($cot1, "name_zh", "name");
			if ($option["option_type"] != $pco["option_type"]) {
				$dec .= "配置项类型" . $cot1[$cot[$pco["option_type"]]] . "改为" . $cot1[$cot[$option["option_type"]]];
			}
			$qtyminimum = isset($param["qtyminimum"]) ? $param["qtyminimum"] : [];
			$qtyminimum = array_values($qtyminimum);
			$qtymaximum = isset($param["qtymaximum"]) ? $param["qtymaximum"] : [];
			$qtymaximum = array_values($qtymaximum);
			$arr = array_merge($qtyminimum, $qtymaximum);
			$option["qty_minimum"] = min($arr) ? intval(min($arr)) : 0;
			$option["qty_maximum"] = max($arr) ? intval(max($arr)) : 0;
			$option["is_discount"] = isset($param["is_discount"]) ? $param["is_discount"] : 0;
			if (!getEdition() && $param["qty_stage"] > 1) {
				return jsonrule(["status" => 400, "msg" => "此功能仅专业版可用"]);
			}
			$option["qty_stage"] = isset($param["qty_stage"]) ? intval($param["qty_stage"]) : 0;
			$option["unit"] = isset($param["unit"]) ? $param["unit"] : "";
			$option["senior"] = isset($param["senior"]) ? intval($param["senior"]) : 0;
			if (!$validate->scene("config_option")->check($option)) {
				return jsonrule(["status" => 400, "msg" => $validate->getError()]);
			}
			$option = array_map("trim", $option);
			\think\Db::startTrans();
			try {
				if ($pco["upstream_id"] == 0) {
					\think\Db::name("product_config_options")->where("id", $cid)->update($option);
					if (isset($param["optionname"]) && is_array($param["optionname"])) {
						$optionname = $param["optionname"];
						$sortorder = isset($param["sortorder"]) ? $param["sortorder"] : 0;
						$qtyminimum = isset($param["qtyminimum"]) ? $param["qtyminimum"] : 0;
						$qtymaximum = isset($param["qtymaximum"]) ? $param["qtymaximum"] : 0;
						$hidden = isset($param["hidden"]) ? $param["hidden"] : 0;
						$qty_arr = [];
						if (is_array($qtyminimum) && is_array($qtymaximum)) {
							foreach ($qtyminimum as $k => $v) {
								foreach ($qtymaximum as $kk => $vv) {
									if ($k == $kk) {
										$qty_arr[$v] = $vv;
									}
								}
							}
							ksort($qty_arr);
							$key = array_keys($qty_arr);
							$value = array_values($qty_arr);
							$flag = true;
							for ($i = 0; $i < count($qty_arr) - 1; $i++) {
								if ($key[$i + 1] <= $value[$i]) {
									$flag = false;
								}
							}
							if (!$flag) {
								return jsonrule(["status" => 400, "msg" => "最大值最小值配置错误"]);
							}
						}
						foreach ($optionname as $key => $value) {
							$pcos = \think\Db::name("product_config_options_sub")->where("id", $key)->find();
							$suboption["option_name"] = trim($value);
							if ($pcos["option_name"] != $suboption["option_name"]) {
								$dec .= "配置子项名称由“" . $pcos["option_name"] . "”改为“" . $suboption["option_name"] . "”，";
							}
							$suboption["sort_order"] = isset($sortorder[$key]) ? ceil($sortorder[$key]) : 0;
							if ($pcos["sort_order"] != $suboption["sort_order"]) {
								$dec .= "配置子项排序由“" . $pcos["sort_order"] . "”改为“" . $suboption["sort_order"] . "”，";
							}
							$suboption["qty_minimum"] = isset($qtyminimum[$key]) ? floor($qtyminimum[$key]) : 0;
							if ($pcos["qty_minimum"] != $suboption["qty_minimum"]) {
								$dec .= "配置子项最小由“" . $pcos["qty_minimum"] . "”改为“" . $suboption["qty_minimum"] . "”，";
							}
							$suboption["qty_maximum"] = isset($qtymaximum[$key]) ? floor($qtymaximum[$key]) : 0;
							if ($pcos["qty_maximum"] != $suboption["qty_maximum"]) {
								$dec .= "配置子项最大由“" . $pcos["qty_maximum"] . "”改为“" . $suboption["qty_maximum"] . "”，";
							}
							$suboption["hidden"] = isset($hidden[$key]) ? intval($hidden[$key]) : 0;
							if ($pcos["hidden"] != $suboption["hidden"]) {
								if ($pcos["hidden"] == 1) {
									$dec .= "配置子项“显示”改为“隐藏”，";
								} else {
									$dec .= "配置子项“隐藏”改为“显示”，";
								}
							}
							if ($dec == "") {
								$dec .= "没有任何修改";
							}
							if (!$validate->scene("config_suboption")->check($suboption)) {
								return jsonrule(["status" => 400, "msg" => $validate->getError()]);
							}
							\think\Db::name("product_config_options_sub")->where("id", $key)->update($suboption);
						}
					}
					if (isset($param["addoptionname"]) && !empty($param["addoptionname"])) {
						if ($option["option_type"] == 3) {
							$yesno = \think\Db::name("product_config_options_sub")->where("config_id", $cid)->count();
							if ($yesno > 1) {
								return jsonrule(["status" => 400, "msg" => lang("CONFIG_YES_NO_ONLY_ONE")]);
							}
						}
						$isadd["config_id"] = $cid;
						$isadd["option_name"] = trim($param["addoptionname"]);
						$isadd["sort_order"] = isset($param["addsortorder"]) ? ceil($param["addsortorder"]) : 0;
						$isadd["hidden"] = isset($param["addhidden"]) ? intval($param["addhidden"]) : 0;
						$sub_id = \think\Db::name("product_config_options_sub")->insertGetId($isadd);
						$currencies = (new \app\common\logic\Currencies())->getCurrencies("id");
						foreach ($currencies as $currency) {
							$pricing_logic = new \app\common\logic\Pricing();
							$pricing_logic::addToConfig($sub_id, "configoptions", $currency["id"]);
						}
					}
				}
				$product = \think\Db::name("products")->alias("a")->leftJoin("product_config_links b", "a.id = b.pid")->leftJoin("product_config_groups c", "b.gid = c.id")->leftJoin("product_config_options d", "d.gid = c.id")->where("d.id", $cid)->find();
				if ($pco["upstream_id"] > 0 && $product["upstream_price_type"] == "percent") {
				} else {
					if (isset($param["price"]) && is_array($param["price"])) {
						$pricing = $param["price"];
						foreach ($pricing as $currency_id => $temp_value) {
							foreach ($temp_value as $suboptionid => $value) {
								$price = new \app\common\logic\Pricing();
								$data = $price->getUpdateOrInsertData($value);
								$pricings = \think\Db::name("pricing")->where("type", "configoptions")->where("currency", $currency_id)->where("relid", $suboptionid)->find();
								if (!empty($pricings)) {
									\think\Db::name("pricing")->where("type", "configoptions")->where("currency", $currency_id)->where("relid", $suboptionid)->update($data);
								} else {
									$data["type"] = "configoptions";
									$data["currency"] = $currency_id;
									$data["relid"] = $suboptionid;
									\think\Db::name("pricing")->insertGetId($data);
								}
							}
						}
					}
				}
				if (empty($dec)) {
					$dec .= "没有任何修改";
				}
				active_log(sprintf($this->lang["Configoption_admin_editConfigPost"], $cid, $dec));
				unset($dec);
				$pids = $this->updateProductVersion($cid, "options");
				foreach ($pids as $pid) {
					$res_array = hook("product_edit", ["pid" => $pid]);
					foreach ($res_array as $res) {
						if ($res["is_resource"] && $res["status"] != 200) {
							throw new \think\Exception("配置项同步至资源池失败,失败原因:" . $res["msg"]);
						}
					}
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
			}
			return jsonrule(["status" => 200, "msg" => lang("EDIT SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	private function updateProductVersion($id, $type = "groups")
	{
		if ($type == "options") {
			$groups = \think\Db::name("product_config_groups")->alias("a")->field("b.pid")->leftJoin("product_config_links b", "a.id = b.gid")->leftJoin("product_config_options c", "a.id = c.gid")->where("c.id", $id)->select()->toArray();
		} elseif ($type == "options_sub") {
			$groups = \think\Db::name("product_config_groups")->alias("a")->field("b.pid")->leftJoin("product_config_links b", "a.id = b.gid")->leftJoin("product_config_options c", "a.id = c.gid")->leftJoin("product_config_options_sub d", "c.id = d.config_id")->where("d.id", $id)->select()->toArray();
		} else {
			$groups = \think\Db::name("product_config_groups")->alias("a")->field("b.pid")->leftJoin("product_config_links b", "a.id = b.gid")->where("a.id", $id)->select()->toArray();
		}
		$pids = array_column($groups, "pid");
		\think\Db::name("products")->whereIn("id", $pids)->setInc("location_version");
		(new \app\common\logic\Product())->updateCache($pids);
		return $pids ?: [];
	}
	/**
	 * @title 可配置项（层级联动）
	 * @description 接口说明:可配置项（层级联动）
	 * @author xue
	 * @url /admin/options/saveLinkAgeLevel
	 * @method POST
	 * @param .name:gid type:int require:1 default:1 other: desc:gid
	 * @param .name:option_name type:string require:1 default:1 other: desc:可选项配置名称
	 * @param .name:option_type type:tinyint require:1 default:1 other: desc:可选项配置类型：1默认Dropdown,2radio,3yes/no,4quantity
	 * @param .name:linkage_pid type:int require:1 default:0 other: desc:配置组pid
	 * @param .name:is_discount type:int require:0 default:1 other: desc:可配置项折扣:1开启，0否
	 * @param .name:option_id type:tinyint require:1 default:0 other: desc:可选项配置组ID(没有就不传)
	 * @param .name:notes type:string require:0 default:1 other: desc:备注
	 * @param .name:qty_stage type:int require:0 default:1 other: desc:当配置项为数量类型时，需要填写此字段
	 * @param .name:unit type:int require:0 default:1 other: desc:单位:配置项类型为1 2 3 5 12时不需要单位
	 * @param .name:option_sub_name type:string require:1 default:1 other: desc:子项名称
	 * @param .name:sort_order type:tinyint require:0 default:0 other: desc:排序
	 * @param .name:sub_linkage_pid type:int require:1 default:0 other: desc:子项对应pid
	 * @param .name:hidden type:int require:0 default:0 other: desc:是否隐藏 默认0 不隐藏
	 * @param .name:sub_option_id type:tinyint require:1 default:0 other: desc:子项ID(没有就不传)
	 */
	public function saveLinkAgeLevel()
	{
		try {
			$param = $this->request->param();
			throwEditionError();
			$this->checkSaveLinkAgeLevel($param);
			$param["option_id"] = $this->saveConfigOption();
			$option_sub_data = ["option_name" => $param["option_sub_name"], "qty_minimum" => 0, "qty_maximum" => 0, "hidden" => $param["hidden"] ?: 0, "config_id" => $param["option_id"], "linkage_pid" => $param["sub_linkage_pid"] ?: 0];
			if ($param["sub_option_id"]) {
				\think\Db::name("product_config_options_sub")->where("id", $param["sub_option_id"])->update($option_sub_data);
			} else {
				$param["sub_option_id"] = \think\Db::name("product_config_options_sub")->insertGetId($option_sub_data);
				$currencies = (new \app\common\logic\Currencies())->getCurrencies("id");
				foreach ($currencies as $currency) {
					\app\common\logic\Pricing::addToConfig($param["sub_option_id"], "configoptions", $currency["id"]);
				}
			}
			if ($option_sub_data["linkage_pid"] == 0) {
				$sub_update_option["linkage_level"] = "0-" . $param["sub_option_id"];
			} else {
				$sub_linkage_top_data = \think\Db::name("product_config_options_sub")->where("id", $option_sub_data["linkage_pid"])->find();
				$sub_update_option["linkage_top_pid"] = $sub_linkage_top_data["linkage_top_pid"];
				if ($sub_linkage_top_data["linkage_pid"] == 0) {
					$sub_update_option["linkage_top_pid"] = $sub_linkage_top_data["id"];
				}
				$sub_update_option["linkage_level"] = $sub_linkage_top_data["linkage_level"] . "-" . $param["sub_option_id"];
			}
			\think\Db::name("product_config_options_sub")->where("id", $param["sub_option_id"])->update($sub_update_option);
			return jsonrule(["status" => 200, "msg" => "success", "option_id" => $param["option_id"], "sub_option_id" => $param["sub_option_id"]]);
		} catch (\Throwable $e) {
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 修改单个配置项
	 * @description 接口说明:修改单个配置项
	 * @author xue
	 * @url /admin/options/saveConfigOptionInfo
	 * @method POST
	 * @param .name:gid type:int require:1 default:1 other: desc:gid
	 * @param .name:option_name type:string require:1 default:1 other: desc:可选项配置名称
	 * @param .name:option_type type:tinyint require:1 default:1 other: desc:可选项配置类型：1默认Dropdown,2radio,3yes/no,4quantity
	 * @param .name:linkage_pid type:int require:1 default:0 other: desc:配置组pid
	 * @param .name:is_discount type:int require:0 default:1 other: desc:可配置项折扣:1开启，0否
	 * @param .name:option_id type:tinyint require:1 default:0 other: desc:可选项配置组ID(没有就不传)
	 * @param .name:notes type:string require:0 default:1 other: desc:备注
	 * @param .name:qty_stage type:int require:0 default:1 other: desc:当配置项为数量类型时，需要填写此字段
	 * @param .name:unit type:int require:0 default:1 other: desc:单位:配置项类型为1 2 3 5 12时不需要单位
	 * @param .name:senior type:int require:0 default:0 other: desc:高级
	 *
	 */
	public function saveConfigOptionInfo()
	{
		try {
			$param = $this->request->param();
			throwEditionError();
			if (!$param["gid"]) {
				throw new \think\Exception(lang("ID_ERROR"));
			}
			$pco = \think\Db::name("product_config_groups")->where("id", $param["gid"])->find();
			if ($pco["upstream_id"] > 0) {
				throw new \think\Exception("上游配置项组,不可添加配置项");
			}
			if (!$param["option_name"]) {
				throw new \think\Exception("请填写配置选项名称");
			}
			$param["option_id"] = $this->saveConfigOption();
			return jsonrule(["status" => 200, "msg" => "success", "option_id" => $param["option_id"]]);
		} catch (\Throwable $e) {
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	private function saveConfigOption()
	{
		$param = $this->request->param();
		$option_data = ["gid" => $param["gid"], "option_name" => $param["option_name"], "option_type" => $param["option_type"] ?: 1, "notes" => $param["notes"] ?: "", "qty_stage" => $param["qty_stage"] ?: 0, "linkage_pid" => $param["linkage_pid"] ?: 0, "is_discount" => $param["is_discount"] ?: 0, "unit" => $param["unit"] ?: "", "senior" => $param["senior"] ?: 0, "upgrade" => 0];
		if ($param["option_id"]) {
			$this->updateProductVersion($param["option_id"], "options");
			\think\Db::name("product_config_options")->where("id", $param["option_id"])->update($option_data);
		} else {
			$param["option_id"] = \think\Db::name("product_config_options")->insertGetId($option_data);
			$this->updateProductVersion($param["gid"], "groups");
		}
		if ($option_data["linkage_pid"] == 0) {
			$update_option["linkage_level"] = "0-" . $param["option_id"];
		} else {
			$linkage_top_data = \think\Db::name("product_config_options")->where("id", $option_data["linkage_pid"])->find();
			$update_option["linkage_top_pid"] = $linkage_top_data["linkage_top_pid"];
			if ($linkage_top_data["linkage_pid"] == 0) {
				$update_option["linkage_top_pid"] = $linkage_top_data["id"];
			}
			$update_option["linkage_level"] = $linkage_top_data["linkage_level"] . "-" . $param["option_id"];
		}
		\think\Db::name("product_config_options")->where("id", $param["option_id"])->update($update_option);
		return $param["option_id"];
	}
	/**
	 * @title 层级联动排序
	 * @description 接口说明:层级联动排序
	 * @author xue
	 * @url /admin/options/saveLinkAgeOrder
	 * @method POST
	 * @param .name:sub_ids type:string|array require:1 default:'' other: desc:排好序的id（1,2,3,4）
	 */
	public function saveLinkAgeOrder()
	{
		try {
			$param = $this->request->param();
			throwEditionError();
			if (!$param["sub_ids"]) {
				throw new \think\Exception("排序数据不存在");
			}
			$param["sub_ids"] = is_array($param["sub_ids"]) ? $param["sub_ids"] : explode(",", $param["sub_ids"]);
			$count = count($param["sub_ids"]);
			foreach ($param["sub_ids"] as $key => $val) {
				\think\Db::name("product_config_options_sub")->where("id", $val)->update(["sort_order" => $key]);
			}
			return jsonrule(["status" => 200, "msg" => "success"]);
		} catch (\Throwable $e) {
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 层级联动删除
	 * @description 接口说明:层级联动删除
	 * @author xue
	 * @url /admin/options/delLinkAgeSub
	 * @method POST
	 * @param .name:sub_id type:int require:1 default:0 other: desc:要删除的id
	 */
	public function delLinkAgeSub()
	{
		try {
			$param = $this->request->param();
			throwEditionError();
			if (!$param["sub_id"]) {
				throw new \think\Exception(lang("ID_ERROR"));
			}
			$sub_data = \think\Db::name("product_config_options_sub")->where("id", $param["sub_id"])->find();
			if ($sub_data["upstream_id"] > 0) {
				throw new \think\Exception("上游配置子项,不可删除");
			}
			$this->updateProductVersion($param["sub_id"], "options_sub");
			$sub_ids = \think\Db::name("product_config_options_sub")->where("id", $param["sub_id"])->whereOr("linkage_level", "like", "%-" . $param["sub_id"] . "-%")->column("id");
			$sub_ids = $sub_ids ?: [0];
			\think\Db::name("product_config_options_sub")->whereIn("id", $sub_ids)->delete();
			\think\Db::name("pricing")->where("type", "configoptions")->whereIn("relid", $sub_ids)->delete();
			return jsonrule(["status" => 200, "msg" => "success"]);
		} catch (\Throwable $e) {
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	private function checkSaveLinkAgeLevel($param)
	{
		if (!$param["gid"]) {
			throw new \think\Exception(lang("ID_ERROR"));
		}
		$pco = \think\Db::name("product_config_groups")->where("id", $param["gid"])->find();
		if ($pco["upstream_id"] > 0) {
			throw new \think\Exception("上游配置项组,不可添加配置项");
		}
		if (!$param["option_name"]) {
			throw new \think\Exception("请填写配置选项名称");
		}
		$validate = new \app\admin\validate\ConfigOptionsValidate();
		if (!$validate->scene("add_config_option_sub")->check(["option_name" => $param["option_sub_name"]])) {
			throw new \think\Exception($validate->getError()[0]);
		}
	}
}