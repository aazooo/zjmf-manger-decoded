<?php

namespace app\admin\controller;

/**
 * @title 后台优惠码
 * @description 接口说明
 */
class PromoCodeController extends AdminBaseController
{
	/**
	 * @title 添加优惠码页面
	 * @description 接口说明:添加优惠码页面
	 * @author wyh
	 * @url admin/add_promo_code/page
	 * @method GET
	 * @return type:percent百分比,fixed固定金额,override置换价格,free免费安装
	 * @return products:产品列表@
	 * @products  pid:产品ID
	 * @products  gname: 产品组名称
	 * @products  pname: 产品名称
	 * @return cycles:结算周期
	 * @return config_options:可配置选项@
	 * @config_options id:可配置选ID
	 * @config_options option_name:可配置项名称
	 * @config_options name:可配置选项组名称
	 **/
	public function addPage()
	{
		$param = $this->request->param();
		$order = isset($param["order"][0]) ? trim($param["order"]) : "b.id";
		$sort = isset($param["sort"][0]) ? trim($param["sort"]) : "DESC";
		$groups = get_product_groups();
		foreach ($groups as $k => $v) {
			$groupid = $v["id"];
			$groups[$k]["product"] = db("products")->field("id,name,type")->withAttr("type", function ($value) {
				return config("product_type")[$value];
			})->where("gid", $groupid)->where("retired", 0)->order("order", "asc")->select()->toArray();
		}
		$configoptions = \think\Db::name("product_config_options")->alias("a")->field("a.id,a.option_name,b.name")->leftJoin("product_config_groups b", "a.gid = b.id")->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "type" => config("promo_code_type"), "products" => $groups, "cycles" => config("coupon_cycle_promo"), "config_options" => $configoptions, "type_upgrade" => config("promo_code_type_upgrade")]);
	}
	/**
	 * @title 添加优惠码
	 * @description 添加优惠码
	 * @author huanghao
	 * @url         admin/add_promo_code
	 * @method      POST
	 * @time        2019-11-22
	 * @param       .name:code type:string require:1 default: other: desc:优惠码
	 * @param       .name:type type:string require:0 default:percent other: desc:percent百分比,fixed固定金额,override置换价格,free免费安装
	 * @param       .name:recurring type:int require:0 default:0 other: desc:是否循环优惠 0不是 1是
	 * @param       .name:recurfor type:int require:0 default:0 other: desc:循环优惠重复执行次数
	 * @param       .name:value type:float require:0 default:0 other: desc:价值
	 * @param       .name:appliesto type:array require:0 default: other: desc:适用的产品id
	 * @param       .name:requires type:array require:0 default: other: desc:需要的产品id
	 * @param       .name:requires_exist type:int require:0 default:0 other: desc:也可以用于账户中现有的产品
	 * @param       .name:cycles type:array require:0 default: other: desc:结算周期
	 * @param       .name:start_time type:date require:0 default: other: desc:开始日期
	 * @param       .name:expiration_time type:date require:0 default: other: desc:失效日期
	 * @param       .name:max_times type:int require:0 default: other: desc:最大使用次数
	 * @param       .name:lifelong type:int require:0 default: other: desc:终身优惠
	 * @param       .name:one_time type:int require:0 default: other: desc:一次性
	 * @param       .name:only_new_client type:int require:0 default: other: desc:仅适用于新用户
	 * @param       .name:only_old_client type:int require:0 default: other: desc:仅用于老客户
	 * @param       .name:once_per_client type:int require:0 default: other: desc:每个用户只能使用一次
	 * @param       .name:upgrades type:int require:0 default: other: desc:启用产品升级优惠（1启用，0禁用）
	 * @param       .name:upgrade_type type:string require:0 default: other: desc:product产品,option可配置选项
	 * @param       .name:upgrade_value type:float require:0 default: other: desc:升级优惠值
	 * @param       .name:upgrade_value_type type:string require:0 default:percent other: desc:percent百分比,fixed固定金额
	 * @param       .name:upgrade_options type:array require:0 default: other: desc:升级配置选项id
	 * @param       .name:notes type:string require:0 default: other: desc:管理员备注
	 * @param       .name:is_discount type:string require:0 default: other: desc:那么下单时享有客户折扣的同时，也可使用优惠码
	 */
	public function add()
	{
		$params = $this->request->param();
		$rule = ["code" => "require", "type" => "in:percent,fixed,override,free", "value" => "float", "recurfor" => "number", "start_time" => "number", "expiration_time" => "number"];
		$msg = ["code.require" => lang("PROMO_CODE_REQUIRE"), "type.in" => lang("PROMO_CODE_TYPE_ERROR"), "value.float" => lang("PROMO_CODE_VALUE_ERROR"), "recurfor.number" => lang("PROMO_CODE_CYCLE_TIMES_ERROR"), "start_time.number" => lang("PROMO_CODE_START_TIME_FORMAT_ERROR"), "expiration_time.number" => lang("PROMO_CODE_EXPIRE_TIME_FORMAT_ERROR")];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$data = [];
		$code_exist = \think\Db::name("promo_code")->where("code", $params["code"])->find();
		if (!empty($code_exist)) {
			$result["status"] = 406;
			$result["msg"] = lang("PROMO_CODE_ALLREADY_EXIST");
			return jsonrule($result);
		}
		if (isset($params["cycles"]) && !empty($params["cycles"]) && is_array($params["cycles"])) {
			$cycles = array_filter($params["cycles"], function ($x) {
				return in_array($x, array_keys(config("coupon_cycle_promo")));
			});
			$data["cycles"] = implode(",", $cycles);
		} else {
			$data["cycles"] = "";
		}
		if (!empty($params["appliesto"]) && is_array($params["appliesto"])) {
			$params["appliesto"] = array_filter($params["appliesto"], function ($x) {
				return is_numeric($x) && $x > 0;
			});
			if (!empty($params["appliesto"])) {
				$appliesto = \think\Db::name("products")->field("id")->whereIn("id", $params["appliesto"])->select()->toArray();
				$params["appliesto"] = array_column($appliesto, "id") ?: [];
			}
		} else {
			$params["appliesto"] = [];
		}
		if (!empty($params["requires"]) && is_array($params["requires"])) {
			$params["requires"] = array_filter($params["requires"], function ($x) {
				return is_numeric($x) && $x > 0;
			});
			if (!empty($params["requires"])) {
				$requires = \think\Db::name("products")->field("id")->whereIn("id", $params["requires"])->select()->toArray();
				$params["requires"] = array_column($requires, "id") ?: [];
			}
		} else {
			$params["requires"] = [];
		}
		$data["code"] = $params["code"];
		$data["start_time"] = $params["start_time"];
		$data["expiration_time"] = $params["expiration_time"];
		$data["appliesto"] = implode(",", $params["appliesto"]);
		$data["requires"] = implode(",", $params["requires"]);
		$data["type"] = !empty($params["type"]) ? $params["type"] : "percent";
		$data["value"] = $params["value"] ?? "";
		$data["recurring"] = !empty($params["recurring"]) ? 1 : 0;
		$data["recurfor"] = !empty($params["recurring"]) && !empty($params["recurfor"]) ? intval($params["recurfor"]) : 0;
		$data["requires_exist"] = !empty($params["requires_exist"]) ? 1 : 0;
		$data["max_times"] = !empty($params["max_times"]) ? intval($params["max_times"]) : 0;
		$data["lifelong"] = !empty($params["lifelong"]) ? 1 : 0;
		$data["one_time"] = !empty($params["one_time"]) ? 1 : 0;
		$data["only_new_client"] = !empty($params["only_new_client"]) ? 1 : 0;
		$data["only_old_client"] = !empty($params["only_old_client"]) ? 1 : 0;
		$data["once_per_client"] = !empty($params["once_per_client"]) ? 1 : 0;
		$data["upgrades"] = !empty($params["upgrades"]) ? 1 : 0;
		$data["notes"] = $params["notes"] ?: "";
		$data["is_discount"] = $params["is_discount"] ?: 0;
		if (!empty($data["upgrades"])) {
			$upgrades = ["upgrade_type" => "", "upgrade_value" => 0.0, "upgrade_value_type" => "percent", "upgrade_options" => []];
			if (!empty($params["upgrade_type"]) && in_array($params["upgrade_type"], ["product", "option"])) {
				$upgrades["upgrade_type"] = $params["upgrade_type"];
			}
			if (is_numeric($params["upgrade_value"]) && $params["upgrade_value"] > 0) {
				$upgrades["upgrade_value"] = $params["upgrade_value"];
			}
			if (!empty($params["upgrade_value_type"]) && in_array($params["upgrade_value_type"], ["percent", "fixed"])) {
				$upgrades["upgrade_value_type"] = $params["upgrade_value_type"];
			}
			if (!empty($params["upgrade_options"]) && is_array($params["upgrade_options"])) {
				$upgrade_options = array_filter($params["upgrade_options"], function ($x) {
					return is_numeric($x) && $x > 0;
				});
				if (!empty($upgrade_options)) {
					$upgrade_options = \think\Db::name("product_config_options")->field("id")->whereIn("id", $upgrade_options)->select()->toArray();
					$upgrades["upgrade_options"] = array_column($upgrade_options, "id") ?: [];
				}
			}
			$data["upgrade_config"] = serialize($upgrades);
		}
		$r = \think\Db::name("promo_code")->insertGetId($data);
		if ($r) {
			active_log(lang("PROMO_CODE_ADD_SUCCESS", [$data["code"], $r]));
			$result["status"] = 200;
			$result["msg"] = lang("ADD SUCCESS");
			return jsonrule($result);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
		}
	}
	/**
	 * @title 编辑优惠码页面
	 * @description 接口说明:编辑优惠码页面
	 * @author wyh
	 * @url admin/save_promo_code/page
	 * @method GET
	 * @param  .name:id type:int require:1 default: other: desc:优惠码id
	 * @return type:percent百分比,fixed固定金额,override置换价格,free免费安装
	 * @return products:产品列表@
	 * @products  pid:产品ID
	 * @products  gname: 产品组名称
	 * @products  pname: 产品名称
	 * @return cycles:结算周期
	 * @return config_options:可配置选项@
	 * @config_options id:可配置选ID
	 * @config_options option_name:可配置项名称
	 * @config_options name:可配置选项组名称
	 **/
	public function savePage()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$promo_code = \think\Db::name("promo_code")->where("id", $id)->find();
		if (!empty($promo_code["cycles"])) {
			$promo_code["cycles"] = explode(",", $promo_code["cycles"]);
		}
		$fun = function ($value) {
			return intval($value);
		};
		if (!empty($promo_code["appliesto"])) {
			$promo_code["appliesto"] = array_map($fun, explode(",", $promo_code["appliesto"]));
		} else {
			$promo_code["appliesto"] = [];
		}
		if (!empty($promo_code["requires"])) {
			$promo_code["requires"] = array_map($fun, explode(",", $promo_code["requires"]));
		} else {
			$promo_code["requires"] = [];
		}
		$promo_code["upgrade_type"] = "";
		$promo_code["upgrade_value"] = 0.0;
		$promo_code["upgrade_value_type"] = "percent";
		$promo_code["upgrade_options"] = [];
		if (!empty($promo_code["upgrades"])) {
			if (!empty($promo_code["upgrade_config"])) {
				$upgrade_config = unserialize($promo_code["upgrade_config"]);
				$promo_code["upgrade_type"] = $upgrade_config["upgrade_type"];
				$promo_code["upgrade_value"] = $upgrade_config["upgrade_value"];
				$promo_code["upgrade_value_type"] = $upgrade_config["upgrade_value_type"];
				$promo_code["upgrade_options"] = $upgrade_config["upgrade_options"];
			}
		}
		unset($promo_code["upgrade_config"]);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "promo_code" => $promo_code, "type" => config("promo_code_type"), "cycles" => config("coupon_cycle_promo"), "type_upgrade" => config("promo_code_type_upgrade")]);
	}
	/**
	 * @title 编辑优惠码
	 * @description 编辑优惠码
	 * @author huanghao
	 * @url         admin/save_promo_code
	 * @method      POST
	 * @time        2019-11-22
	 * @param       .name:id type:int require:1 default: other: desc:优惠码id
	 * @param       .name:code type:string require:1 default: other: desc:优惠码
	 * @param       .name:type type:string require:0 default: other: desc:percent百分比,fixed固定金额,override置换价格,free免费安装
	 * @param       .name:recurring type:int require:0 default: other: desc:是否循环优惠 0不是 1是
	 * @param       .name:recurfor type:int require:0 default: other: desc:循环优惠重复执行次数
	 * @param       .name:value type:float require:0 default: other: desc:价值
	 * @param       .name:appliesto type:array require:0 default: other: desc:适用的产品id
	 * @param       .name:requires type:array require:0 default: other: desc:需要的产品id
	 * @param       .name:requires_exist type:int require:0 default: other: desc:也可以用于账户中现有的产品
	 * @param       .name:cycles type:array require:0 default: other: desc:结算周期
	 * @param       .name:start_time type:date require:0 default: other: desc:开始日期
	 * @param       .name:expiration_time type:date require:0 default: other: desc:失效日期
	 * @param       .name:max_times type:int require:0 default: other: desc:最大使用次数
	 * @param       .name:lifelong type:int require:0 default: other: desc:终身优惠
	 * @param       .name:one_time type:int require:0 default: other: desc:一次性
	 * @param       .name:only_new_client type:int require:0 default: other: desc:仅适用于新用户
	 * @param       .name:only_old_client type:int require:0 default: other: desc:仅用于老客户
	 * @param       .name:once_per_client type:int require:0 default: other: desc:每个用户只能使用一次
	 * @param       .name:upgrades type:int require:0 default: other: desc:启用产品升级优惠
	 * @param       .name:upgrade_type type:string require:0 default: other: desc:product产品,option可配置选项
	 * @param       .name:upgrade_value type:float require:0 default: other: desc:升级优惠值
	 * @param       .name:upgrade_value_type type:string require:0 default: other: desc:percent百分比,fixed固定金额
	 * @param       .name:upgrade_options type:array require:0 default: other: desc:升级配置选项id
	 * @param       .name:notes type:string require:0 default: other: desc:管理员备注
	 * @param       .name:is_discount type:string require:0 default: other: desc:那么下单时享有客户折扣的同时，也可使用优惠码
	 */
	public function save()
	{
		$params = input("post.");
		$id = $params["id"];
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$rule = ["code" => "require", "type" => "in:percent,fixed,override,free", "value" => "float", "recurfor" => "number", "start_time" => "number", "expiration_time" => "number"];
		$msg = ["code.require" => lang("PROMO_CODE_REQUIRE"), "type.in" => lang("PROMO_CODE_TYPE_ERROR"), "value.float" => lang("PROMO_CODE_VALUE_ERROR"), "recurfor.number" => lang("PROMO_CODE_CYCLE_TIMES_ERROR"), "start_time.number" => lang("PROMO_CODE_START_TIME_FORMAT_ERROR"), "expiration_time.number" => lang("PROMO_CODE_EXPIRE_TIME_FORMAT_ERROR")];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$data = [];
		$code_info = \think\Db::name("promo_code")->where("id", $params["id"])->find();
		if (empty($code_info)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$code_exist = \think\Db::name("promo_code")->where("code", $params["code"])->where("id", "<>", $id)->find();
		if (!empty($code_exist)) {
			$result["status"] = 406;
			$result["msg"] = lang("PROMO_CODE_ALLREADY_EXIST");
			return jsonrule($result);
		}
		if (isset($params["cycles"]) && !empty($params["cycles"]) && is_array($params["cycles"])) {
			$cycles = array_filter($params["cycles"], function ($x) {
				return in_array($x, array_keys(config("coupon_cycle_promo")));
			});
			$data["cycles"] = implode(",", $cycles);
		} else {
			$data["cycles"] = "";
		}
		if (isset($params["appliesto"])) {
			if (!empty($params["appliesto"]) && is_array($params["appliesto"])) {
				$params["appliesto"] = array_filter($params["appliesto"], function ($x) {
					return is_numeric($x) && $x > 0;
				});
				if (!empty($params["appliesto"])) {
					$appliesto = \think\Db::name("products")->field("id")->whereIn("id", $params["appliesto"])->select()->toArray();
					$params["appliesto"] = array_column($appliesto, "id") ?: [];
				}
			} else {
				$params["appliesto"] = [];
			}
		}
		if (isset($params["requires"])) {
			if (!empty($params["requires"]) && is_array($params["requires"])) {
				$params["requires"] = array_filter($params["requires"], function ($x) {
					return is_numeric($x) && $x > 0;
				});
				if (!empty($params["requires"])) {
					$requires = \think\Db::name("products")->field("id")->whereIn("id", $params["requires"])->select()->toArray();
					$params["requires"] = array_column($requires, "id") ?: [];
				}
			} else {
				$params["requires"] = [];
			}
		}
		$dec = "";
		$data["code"] = $params["code"];
		$data["value"] = $params["value"];
		if ($data["code"] != $code_info["code"]) {
			$dec .= "优惠码由“" . $code_info["code"] . "”改为“" . $data["code"] . "”，";
		}
		if ($data["value"] != $code_info["value"]) {
			$dec .= "价值由“" . $code_info["value"] . "”改为“" . $data["value"] . "”，";
		}
		if (isset($params["start_time"])) {
			$data["start_time"] = $params["start_time"];
			if ($data["start_time"] != $code_info["start_time"]) {
				$dec .= "开始时间由“" . date("Y-m-d", $code_info["start_time"]) . "”改为“" . date("Y-m-d", $data["start_time"]) . "”，";
			}
		}
		if (isset($params["expiration_time"])) {
			$data["expiration_time"] = $params["expiration_time"];
			if ($data["expiration_time"] != $code_info["expiration_time"]) {
				$dec .= "失效时间由“" . date("Y-m-d", $code_info["expiration_time"]) . "”改为“" . date("Y-m-d", $data["expiration_time"]) . "”，";
			}
		}
		if (isset($params["appliesto"])) {
			$data["appliesto"] = implode(",", $params["appliesto"]);
			$product = \think\Db::name("products")->field("name")->where("id", $code_info["appliesto"])->find();
			$product1 = \think\Db::name("products")->field("name")->where("id", $data["appliesto"])->find();
			if ($data["appliesto"] != $code_info["appliesto"]) {
				$dec .= "适用的产品由“" . $product["name"] . "”改为“" . $product1["name"] . "”，";
			}
		}
		if (isset($params["requires"])) {
			$data["requires"] = implode(",", $params["requires"]);
			$product = \think\Db::name("products")->field("name")->where("id", $code_info["requires"])->find();
			$product1 = \think\Db::name("products")->field("name")->where("id", $data["requires"])->find();
			if ($data["requires"] != $code_info["requires"]) {
				$dec .= "需要的产品由“" . $product["name"] . "”改为“" . $product1["name"] . "”，";
			}
		}
		if (isset($params["requires_exist"])) {
			$data["requires_exist"] = !empty($params["requires_exist"]) ? 1 : 0;
			if ($data["requires_exist"] != $code_info["requires_exist"]) {
				if ($data["requires_exist"] == 1) {
					$dec .= "用于账户中的产品由“关闭”改为“开启”，";
				} else {
					$dec .= "用于账户中的产品由“开启”改为“关闭”，";
				}
			}
		}
		if (isset($params["max_times"])) {
			$data["max_times"] = !empty($params["max_times"]) ? intval($params["max_times"]) : 0;
			if ($data["max_times"] != $code_info["max_times"]) {
				$dec .= "最大使用次数由“" . $code_info["max_times"] . "”改为“" . $data["max_times"] . "”，";
			}
		}
		if (isset($params["notes"])) {
			$data["notes"] = $params["notes"] ?: "";
			if ($data["notes"] != $code_info["notes"]) {
				$dec .= "管理员备注由“" . $code_info["notes"] . "”改为“" . $data["notes"] . "”，";
			}
		}
		if (isset($params["cycles"])) {
			if ($params["cycles"] != $code_info["cycles"]) {
				$cycle1 = explode(",", $code_info["cycles"]);
				$cou = config("coupon_cycle_promo");
				$arr = [];
				$arr1 = [];
				foreach ($params["cycles"] as $key => $value) {
					$arr[] = $cou[$value];
				}
				foreach ($cycle1 as $key => $value) {
					$arr1[] = $cou[$value];
				}
				$arr = implode(",", $arr);
				$arr1 = implode(",", $arr1);
				$dec .= "结算周期由“" . $arr1 . "”改为“" . $arr . "”，";
			}
		}
		if (isset($params["lifelong"])) {
			$data["lifelong"] = !empty($params["lifelong"]) ? 1 : 0;
			if ($data["lifelong"] != $code_info["lifelong"]) {
				if ($data["lifelong"] == 1) {
					$dec .= "终身优惠由“关闭”改为“开启”，";
				} else {
					$dec .= "终身优惠由“开启”改为“关闭”，";
				}
			}
		}
		if (isset($params["one_time"])) {
			$data["one_time"] = !empty($params["one_time"]) ? 1 : 0;
			if ($data["one_time"] != $code_info["one_time"]) {
				if ($data["one_time"] == 1) {
					$dec .= "一次性由“关闭”改为“开启”，";
				} else {
					$dec .= "一次性由“开启”改为“关闭”，";
				}
			}
		}
		if (isset($params["only_new_client"])) {
			$data["only_new_client"] = !empty($params["only_new_client"]) ? 1 : 0;
			if ($data["only_new_client"] != $code_info["only_new_client"]) {
				if ($data["only_new_client"] == 1) {
					$dec .= "新注册用户由“关闭”改为“开启”，";
				} else {
					$dec .= "新注册用户由“开启”改为“关闭”，";
				}
			}
		}
		if (isset($params["only_old_client"])) {
			$data["only_old_client"] = !empty($params["only_old_client"]) ? 1 : 0;
			if ($data["only_old_client"] != $code_info["only_old_client"]) {
				if ($data["only_old_client"] == 1) {
					$dec .= "现有的用户由“关闭”改为“开启”，";
				} else {
					$dec .= "现有的用户由“开启”改为“关闭”，";
				}
			}
		}
		if (isset($params["once_per_client"])) {
			$data["once_per_client"] = !empty($params["once_per_client"]) ? 1 : 0;
			if ($data["once_per_client"] != $code_info["once_per_client"]) {
				if ($data["once_per_client"] == 1) {
					$dec .= "用户只能使用一次由“关闭”改为“开启”，";
				} else {
					$dec .= "用户只能使用一次由“开启”改为“关闭”，";
				}
			}
		}
		if (isset($params["upgrades"])) {
			$data["upgrades"] = !empty($params["upgrades"]) ? 1 : 0;
			if ($data["upgrades"] != $code_info["upgrades"]) {
				if ($data["upgrades"] == 1) {
					$dec .= "产品升级优惠由“关闭”改为“开启”，";
				} else {
					$dec .= "产品升级优惠由“开启”改为“关闭”，";
				}
			}
		}
		if (isset($params["is_discount"])) {
			$data["is_discount"] = !empty($params["is_discount"]) ? 1 : 0;
			if ($data["is_discount"] != $code_info["is_discount"]) {
				if ($data["upgrades"] == 1) {
					$dec .= "下单时享有客户折扣的同时，也可使用优惠码由“关闭”改为“开启”，";
				} else {
					$dec .= "下单时享有客户折扣的同时，也可使用优惠码由“开启”改为“关闭”，";
				}
			}
		}
		if (isset($params["type"])) {
			$data["type"] = !empty($params["type"]) ? $params["type"] : "percent";
		}
		if (isset($params["value"])) {
			$data["value"] = $params["value"];
		}
		if (isset($params["recurring"])) {
			$data["recurring"] = !empty($params["recurring"]) ? 1 : 0;
		}
		if (isset($params["recurfor"])) {
			$data["recurfor"] = (!empty($data["recurring"]) || !isset($data["recurring"]) && !empty($code_info["recurring"])) && !empty($params["recurfor"]) ? intval($params["recurfor"]) : 0;
		}
		if (!empty($data["upgrades"]) || !isset($params["upgrades"]) && !empty($code_info["upgrades"])) {
			$old_upgrades = unserialize($code_info["upgrade_config"]);
			$upgrades = ["upgrade_type" => "", "upgrade_value" => 0.0, "upgrade_value_type" => "percent", "upgrade_options" => []];
			if (isset($params["upgrade_type"])) {
				if (!empty($params["upgrade_type"]) && in_array($params["upgrade_type"], ["product", "option"])) {
					$upgrades["upgrade_type"] = $params["upgrade_type"];
				}
			} else {
				if (!empty($old_upgrades)) {
					$upgrades["upgrade_type"] = $old_upgrades["upgrade_type"];
				}
			}
			if (isset($params["upgrade_value"])) {
				if (is_numeric($params["upgrade_value"]) && $params["upgrade_value"] > 0) {
					$upgrades["upgrade_value"] = $params["upgrade_value"];
				}
			} else {
				if (!empty($old_upgrades)) {
					$upgrades["upgrade_value"] = $old_upgrades["upgrade_value"];
				}
			}
			if (isset($params["upgrade_value_type"])) {
				if (!empty($params["upgrade_value_type"]) && in_array($params["upgrade_value_type"], ["percent", "fixed"])) {
					$upgrades["upgrade_value_type"] = $params["upgrade_value_type"];
				}
			} else {
				if (!empty($old_upgrades)) {
					$upgrades["upgrade_value_type"] = $old_upgrades["upgrade_value_type"];
				}
			}
			if (isset($params["upgrade_options"])) {
				if (!empty($params["upgrade_options"]) && is_array($params["upgrade_options"])) {
					$upgrade_options = array_filter($params["upgrade_options"], function ($x) {
						return is_numeric($x) && $x > 0;
					});
					if (!empty($upgrade_options)) {
						$upgrade_options = \think\Db::name("product_config_options")->field("id")->whereIn("id", $upgrade_options)->select()->toArray();
						$upgrades["upgrade_options"] = array_column($upgrade_options, "id") ?: [];
					}
				}
			} else {
				if (!empty($old_upgrades)) {
					$upgrades["upgrade_options"] = $old_upgrades["upgrade_options"];
				}
			}
			$data["upgrade_config"] = serialize($upgrades);
		} else {
			$data["upgrade_config"] = "";
		}
		$r = \think\Db::name("promo_code")->where("id", $params["id"])->update($data);
		if ($r) {
			if (empty($dec)) {
				$dec .= "未做任何修改";
			}
			active_log(sprintf("促销优惠码修改" . $params["id"] . " " . $dec));
			unset($dec);
		}
		$result["status"] = 200;
		$result["msg"] = lang("UPDATE SUCCESS");
		return jsonrule($result);
	}
	/**
	 * @title 删除优惠码
	 * @description 删除优惠码
	 * @author huanghao
	 * @url         admin/delete_promo_code
	 * @method      POST
	 * @time        2019-11-22
	 * @param       .name:id type:int require:1 default: other: desc:优惠码id
	 */
	public function delete()
	{
		$id = intval(input("post.id"));
		$exist = \think\Db::name("promo_code")->field("id,code")->where("id", $id)->find();
		if (empty($exist)) {
			$result["status"] = 200;
			$result["msg"] = lang("PROMO_CODE_ALLREADY_DELETE");
			return jsonrule($result);
		}
		\think\Db::name("promo_code")->where("id", $id)->delete();
		active_log(lang("PROMO_CODE_DELETE_SUCCESS", [$exist["code"], $exist["id"]]));
		$result["status"] = 200;
		$result["msg"] = lang("DELETE SUCCESS");
		return jsonrule($result);
	}
	/**
	 * @title 优惠码立即过期
	 * @description 优惠码立即过期
	 * @author huanghao
	 * @url         admin/expired_promo_code
	 * @method      POST
	 * @time        2019-11-22
	 * @param       .name:id type:int require:1 default: other: desc:优惠码id
	 */
	public function expireImmediately()
	{
		$id = intval(input("post.id"));
		$exist = \think\Db::name("promo_code")->field("id,expiration_time,code")->where("id", $id)->find();
		if (empty($exist)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		if ($exist["expiration_time"] > 0 && $exist["expiration_time"] < time()) {
			$result["status"] = 200;
			$result["msg"] = lang("PROMO_CODE_HAS_EXPIRED");
			return jsonrule($result);
		}
		\think\Db::name("promo_code")->where("id", $id)->update(["expiration_time" => strtotime("-1 day")]);
		active_log(lang("PROMO_CODE_EXPIRED_IMMEDIATELY", [$exist["code"], $exist["id"]]));
		$result["status"] = 200;
		$result["msg"] = lang("SUCCESS MESSAGE");
		return jsonrule($result);
	}
	/**
	 * @title 获取优惠码列表
	 * @description 获取优惠码列表:数据量少，返回所有数据，前端分页
	 * @author huanghao
	 * @url         admin/list_promo_code
	 * @method      GET
	 * @time        2019-11-22
	 * @param       .name:type type:string require:0 default:active other: desc:all全部,expired过期,active未过期
	 * @return      .id:优惠码id
	 * @return      .code:优惠码
	 * @return      .type:优惠码类型,percent百分比,fixed固定金额,override置换价格,free免费安装
	 * @return      .recurring:循环优惠 0否 1是
	 * @return      .max_times:最多使用次数 0无限制
	 * @return      .used:已使用次数
	 * @return      .start_time:开始时间
	 * @return      .expiration_time:失效时间
	 */
	public function getList()
	{
		$type = input("get.type");
		$db = \think\Db::name("promo_code")->field("id,code,type,value,recurring,max_times,used,start_time,expiration_time");
		$now = time();
		if ($type == "all") {
		} elseif ($type == "expired") {
			$db = $db->where("(expiration_time>0 AND expiration_time<{$now}) OR (max_times>0 && used>=max_times)");
		} else {
			$db = $db->where("(expiration_time=0 OR expiration_time>{$now}) AND (max_times=0 || used<max_times)");
		}
		$data = $db->order("id", "DESC")->select()->toArray();
		foreach ($data as $k => $v) {
			$data[$k]["start_time"] = $v["start_time"] == 0 ? "-" : date("Y-m-d H:i:s", $v["start_time"]);
			$data[$k]["expiration_time"] = $v["expiration_time"] == 0 ? "-" : date("Y-m-d H:i:s", $v["expiration_time"]);
		}
		$result["status"] = 200;
		$result["data"] = $data;
		$result["msg"] = lang("SUCCESS MESSAGE");
		return jsonrule($result);
	}
	/**
	 * @title 自动生成优惠码
	 * @description 自动生成优惠码
	 * @author wyh
	 * @url         admin/auto_promo_code
	 * @method      GET
	 * @time        2020-04-08
	 * @return      rand:优惠码
	 */
	public function autoPromoCode()
	{
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "rand" => randStr()]);
	}
}