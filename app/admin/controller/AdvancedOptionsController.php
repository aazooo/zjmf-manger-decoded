<?php

namespace app\admin\controller;

/**
 * @title 高级可配置项
 * @description 高级可配置项
 * @author wyh 1340863150@qq.com
 * @time 2020-11-02
 */
class AdvancedOptionsController extends AdminBaseController
{
	private $relation = ["seq", "sneq"];
	/**
	 * @title 高级配置页面
	 * @description 接口说明:高级配置页面(考虑是否是上游配置项,自动创建)
	 * @param .name:pid type:int require:1 default:1 other: desc:产品ID
	 * @author wyh
	 * @url /admin/advanced_options/page
	 * @return options:配置项@
	 * @options  id:id
	 * @options  option_name:名称
	 * @options  option_type:类型:这里可能需要根据类型判断显示,具体可以问问黄
	 * @options  sub_options:子项@
	 * @sub_options  id:子项id
	 * @sub_options  option_name:子项名称
	 * @sub_options  qty_minimum:最小值(仅类型为数量使用)
	 * @sub_options  qty_maximum:最大值
	 * @return  link:已关联高级配置@
	 * @link  id:条件id,对应创建编辑的->条件关联id
	 * @link  config_id:条件配置项id
	 * @link  relation:条件关系:eq相等，neq不相等(前端直接显示传，后端不返回)
	 * @link  sub_id:条件子项id,返回数组@
	 * @sub_id  qty_minimum:子项最小值
	 * @sub_id  qty_maximum:子项最大值
	 * @link  result:结果数据@
	 * @result  id:结果id,对应创建编辑的->结果关联id
	 * @result  config_id:结果配置项id
	 * @result  relation:结果关系
	 * @result  sub_id:结果子项id,返回数组
	 * @method GET
	 */
	public function page()
	{
		$params = $this->request->param();
		$pid = intval($params["pid"]);
		$products = \think\Db::name("products")->where("id", $pid)->find();
		if (empty($products)) {
			return jsonrule(["status" => 400, "msg" => "产品不存在"]);
		}
		$options = \think\Db::name("product_config_options")->alias("a")->field("a.id,a.option_name,a.option_type")->leftJoin("product_config_links b", "b.gid = a.gid")->leftJoin("product_config_groups c", "a.gid = c.id")->where("b.pid", $pid)->withAttr("option_name", function ($value) {
			return !empty(explode("|", $value)[1]) ? explode("|", $value)[1] : $value;
		})->order("a.order", "asc")->order("a.id", "asc")->select()->toArray();
		foreach ($options as &$option) {
			$sub_options = \think\Db::name("product_config_options_sub")->field("id,option_name,qty_minimum,qty_maximum")->where("config_id", $option["id"])->withAttr("option_name", function ($value) {
				return !empty(explode("|", $value)[1]) ? explode("|", $value)[1] : $value;
			})->order("sort_order", "asc")->order("id", "asc")->select()->toArray();
			$option["sub_options"] = $sub_options ?: [];
		}
		$ids = array_column($options, "id");
		$tmp = \think\Db::name("product_config_options_links")->field("id,config_id,relation,sub_id")->whereIn("config_id", $ids)->where("type", "condition")->where("relation_id", 0)->withAttr("sub_id", function ($value) {
			return json_decode($value, true);
		})->select()->toArray();
		foreach ($tmp as &$v) {
			$result = \think\Db::name("product_config_options_links")->field("id,config_id,relation,sub_id")->where("relation_id", $v["id"])->where("type", "result")->withAttr("sub_id", function ($value) {
				return json_decode($value, true);
			})->select()->toArray();
			$v["result"] = $result ?: [];
		}
		$data = ["options" => $options ?: [], "link" => $tmp ?: []];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 高级配置创建、编辑
	 * @description 接口说明:高级配置创建、编辑(考虑是否是上游配置项,自动创建)
	 * @param .name:link[条件关联id][config_id] type:array require:0 default:1 other: desc:配置项id,以下link参数(非必填都是表示参数都可以不传)
	 * @param .name:link[条件关联id][relation] type:array require:0 default:1 other: desc:条件
	 * @param .name:link[条件关联id][sub_id][子项ID][qty_minimum] type:array require:0 default:1 other: desc:子项最小值
	 * @param .name:link[条件关联id][sub_id][子项ID][qty_maximum] type:array require:0 default:1 other: desc:子项最大值
	 * @param .name:link[条件关联id][result][结果关联id][config_id] type:array require:0 default:1 other: desc:结果 配置项id
	 * @param .name:link[条件关联id][result][结果关联id][relation] type:array require:0 default:1 other: desc:结果 条件
	 * @param .name:link[条件关联id][result][结果关联id][sub_id][子项id][qty_minimum] type:array require:0 default:1 other: desc:结果 子项最小值,非数量传0
	 * @param .name:link[条件关联id][new_result][0][config_id] type:array require:0 default:1 other: desc: 新结果 配置项id
	 * @param .name:link[条件关联id][new_result][0][relation] type:array require:0 default:1 other: desc: 新结果 条件
	 * @param .name:link[条件关联id][new_result][0][sub_id][子项id][qty_minimum] type:array require:0 default:1 other: desc: 新结果 子项最小值
	 * @param .name:link[条件关联id][new_result][0][sub_id][子项id][qty_maximum] type:array require:0 default:1 other: desc: 新结果 子项最大值
	 * @param .name:new_cid type:int require:0 default:1 other: desc:新增配置项ID
	 * @param .name:new_relation type:string require:0 default:1 other: desc:条件
	 * @param .name:new_subid[子项id][qty_minimum] type:array require:0 default:1 other: desc:子项最小值,非数量传0
	 * @param .name:new_subid[子项id][qty_maximum] type:array require:0 default:1 other: desc:子项最大值,非数量传0
	 * @param .name:result[0开始的自然数][new_cid] type:array require:0 default:1 other: desc:新增 结果 配置项id
	 * @param .name:result[0][new_relation] type:array require:0 default:1 other: desc:新增 结果 条件ID
	 * @param .name:result[0][new_subid][子项ID][qty_minimum] type:array require:0 default:1 other: desc:新增 结果 子项ID最小值,非数量传0
	 * @param .name:result[0][new_subid][子项ID][qty_maximum] type:array require:0 default:1 other: desc:新增 结果 子项ID最大值,非数量传0
	 * @author wyh
	 * @url /admin/advanced_options/create
	 * @method POST
	 */
	public function create()
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
		if (!in_array("seniorConfig", $app)) {
			return jsonrule(["status" => 400, "msg" => "免费版该功能不可用"]);
		}
		$params = $this->request->param();
		$relation = $this->relation;
		$uniq_arr = [];
		if (isset($params["link"]) && !empty($params["link"])) {
			$link = $params["link"];
			if (!is_array($link) || empty($link)) {
				return jsonrule(["status" => 400, "msg" => "参数错误"]);
			}
			foreach ($link as $j => $h) {
				if (!is_array($h) || empty($h)) {
					return jsonrule(["status" => 400, "msg" => "参数错误"]);
				}
				if (!in_array($h["relation"], $relation)) {
					return jsonrule(["status" => 400, "msg" => "条件只能为等于或者不等于"]);
				}
				$tmp1 = \think\Db::name("product_config_options_links")->where("id", $j)->find();
				$tmp2 = \think\Db::name("product_config_options")->where("id", $h["config_id"])->find();
				if (empty($tmp1) || empty($tmp2)) {
					return jsonrule(["status" => 400, "msg" => "参数错误"]);
				}
				foreach ($h["sub_id"] as $jj => $hh) {
					$tmp3 = \think\Db::name("product_config_options_sub")->where("config_id", $h["config_id"])->where("id", $jj)->find();
					if (empty($tmp3)) {
						return jsonrule(["status" => 400, "msg" => "参数错误"]);
					}
					if (judgeQuantity($tmp2["option_type"])) {
						$hh_qty_minimum = $hh["qty_minimum"];
						$hh_qty_maximum = $hh["qty_maximum"];
						if ($hh_qty_maximum < $hh_qty_minimum || $hh_qty_minimum < $tmp3["qty_minimum"] || $tmp3["qty_maximum"] < $hh_qty_maximum) {
							return jsonrule(["status" => 400, "msg" => "参数错误"]);
						}
					}
				}
				$tmp_result = $h["result"];
				if (!is_array($tmp_result) || empty($tmp_result)) {
					return jsonrule(["status" => 400, "msg" => "参数错误"]);
				}
				foreach ($tmp_result as $jjj => $hhh) {
					if ($hhh["config_id"] == $h["config_id"]) {
						return jsonrule(["status" => 400, "msg" => "条件和结果配置项不可一样"]);
					}
					if (!in_array($hhh["relation"], $relation)) {
						return jsonrule(["status" => 400, "msg" => "条件只能为等于或者不等于"]);
					}
					$tmp4 = \think\Db::name("product_config_options_links")->where("id", $jjj)->find();
					$tmp5 = \think\Db::name("product_config_options")->where("id", $hhh["config_id"])->find();
					if (empty($tmp4) || empty($tmp5)) {
						return jsonrule(["status" => 400, "msg" => "参数错误"]);
					}
					foreach ($hhh["sub_id"] as $jjjj => $hhhh) {
						$tmp55 = \think\Db::name("product_config_options_sub")->where("config_id", $hhh["config_id"])->where("id", $jjjj)->find();
						if (empty($tmp55)) {
							return jsonrule(["status" => 400, "msg" => "参数错误"]);
						}
						if (judgeQuantity($tmp5["option_type"])) {
							$hhhh_qty_minimum = $hhhh["qty_minimum"];
							$hhhh_qty_maximum = $hhhh["qty_maximum"];
							if ($hhhh_qty_maximum < $hhhh_qty_minimum || $hhhh_qty_minimum < $tmp55["qty_minimum"] || $tmp55["qty_maximum"] < $hhhh_qty_maximum) {
								return jsonrule(["status" => 400, "msg" => "参数错误"]);
							}
						}
					}
				}
				if (isset($h["new_result"]) && is_array($h["new_result"]) && !empty($h["new_result"])) {
					$new_result_tmp = $h["new_result"];
					foreach ($new_result_tmp as $jjjjj => $hhhhh) {
						if ($hhhhh["config_id"] == $h["config_id"]) {
							return jsonrule(["status" => 400, "msg" => "条件和结果配置项不可一样"]);
						}
						if (!in_array($hhhhh["relation"], $relation)) {
							return jsonrule(["status" => 400, "msg" => "条件只能为等于或者不等于"]);
						}
						$new_tmp1 = \think\Db::name("product_config_options")->where("id", $hhhhh["config_id"])->find();
						if (empty($new_tmp1)) {
							return jsonrule(["status" => 400, "msg" => "参数错误"]);
						}
						foreach ($hhhhh["sub_id"] as $j6 => $h6) {
							$new_tmp2 = \think\Db::name("product_config_options_sub")->where("config_id", $hhhhh["config_id"])->where("id", $j6)->find();
							if (empty($new_tmp2)) {
								return jsonrule(["status" => 400, "msg" => "参数错误"]);
							}
							if (judgeQuantity($new_tmp1["option_type"])) {
								$h6_qty_minimum = $h6["qty_minimum"];
								$h6_qty_maximum = $h6["qty_maximum"];
								if ($h6_qty_maximum < $h6_qty_minimum || $h6_qty_minimum < $new_tmp2["qty_minimum"] || $new_tmp2["qty_maximum"] < $h6_qty_maximum) {
									return jsonrule(["status" => 400, "msg" => "参数错误"]);
								}
							}
						}
					}
				}
				ksort($h["sub_id"]);
				$tmp_arr = ["config_id" => $h["config_id"], "relation" => $h["relation"], "sub_id" => json_encode($h["sub_id"])];
				$uniq_arr[] = implode(",", $tmp_arr);
			}
		}
		if (isset($params["new_cid"]) && !empty($params["new_cid"])) {
			if (empty($params["new_relation"]) || empty($params["new_subid"]) || empty($params["result"]) || !is_array($params["result"]) || !is_array($params["new_subid"])) {
				return jsonrule(["status" => 400, "msg" => "参数错误"]);
			}
			$new_cid = intval($params["new_cid"]);
			$tmp6 = \think\Db::name("product_config_options")->where("id", $new_cid)->find();
			if (!in_array($params["new_relation"], $relation)) {
				return jsonrule(["status" => 400, "msg" => "条件只能为等于或者不等于"]);
			}
			if (empty($tmp6)) {
				return jsonrule(["status" => 400, "msg" => "参数错误"]);
			}
			$new_tmp_subid = $params["new_subid"];
			ksort($new_tmp_subid);
			foreach ($new_tmp_subid as $u => $w) {
				$tmp7 = \think\Db::name("product_config_options_sub")->where("config_id", $new_cid)->where("id", $u)->find();
				if (empty($tmp7)) {
					return jsonrule(["status" => 400, "msg" => "参数错误"]);
				}
				if (judgeQuantity($tmp6["option_type"])) {
					$w_qty_minimum = $w["qty_minimum"];
					$w_qty_maximum = $w["qty_maximum"];
					if ($w_qty_maximum < $w_qty_minimum || $w_qty_minimum < $tmp7["qty_minimum"] || $tmp7["qty_maximum"] < $w_qty_maximum) {
						return jsonrule(["status" => 400, "msg" => "参数错误"]);
					}
				}
			}
			$new_tmp_result = $params["result"];
			foreach ($new_tmp_result as $ww) {
				if ($ww["new_cid"] == $new_cid) {
					return jsonrule(["status" => 400, "msg" => "条件和结果配置项不可一样"]);
				}
				$tmp8 = \think\Db::name("product_config_options")->where("id", $ww["new_cid"])->find();
				if (empty($tmp8)) {
					return jsonrule(["status" => 400, "msg" => "参数错误"]);
				}
				if (!in_array($ww["new_relation"], $relation)) {
					return jsonrule(["status" => 400, "msg" => "条件只能为等于或者不等于"]);
				}
				foreach ($ww["new_subid"] as $uuu => $www) {
					$tmp9 = \think\Db::name("product_config_options_sub")->where("config_id", $ww["new_cid"])->where("id", $uuu)->find();
					if (empty($tmp9)) {
						return jsonrule(["status" => 400, "msg" => "参数错误"]);
					}
					if (judgeQuantity($tmp8["option_type"])) {
						$www_qty_minimum = $www["qty_minimum"];
						$www_qty_maximum = $www["qty_maximum"];
						if ($www_qty_maximum < $www_qty_minimum || $www_qty_minimum < $tmp9["qty_minimum"] || $tmp9["qty_maximum"] < $www_qty_maximum) {
							return jsonrule(["status" => 400, "msg" => "参数错误"]);
						}
					}
				}
			}
			$tmp_arr2 = ["config_id" => $new_cid, "relation" => $params["new_relation"], "sub_id" => json_encode($new_tmp_subid)];
			$uniq_arr[] = implode(",", $tmp_arr2);
		}
		$arr = array_unique($uniq_arr);
		if (count($arr) != count($uniq_arr)) {
			return jsonrule(["status" => 400, "msg" => "条件不可完全一样"]);
		}
		$pids = [];
		\think\Db::startTrans();
		try {
			if ($link) {
				foreach ($link as $k => $v) {
					$groups = \think\Db::name("product_config_groups")->alias("a")->field("b.pid")->leftJoin("product_config_links b", "a.id = b.gid")->leftJoin("product_config_options c", "a.id = c.gid")->where("c.id", $v["config_id"])->select()->toArray();
					$pids = array_merge($pids, array_column($groups, "pid"));
					ksort($v["sub_id"]);
					$sub_id = json_encode($v["sub_id"]);
					\think\Db::name("product_config_options_links")->where("id", $k)->update(["config_id" => $v["config_id"], "relation" => $v["relation"], "sub_id" => $sub_id]);
					$result = $v["result"];
					foreach ($result as $kk => $vv) {
						ksort($vv["sub_id"]);
						$res_sub_id = json_encode($vv["sub_id"]);
						\think\Db::name("product_config_options_links")->where("id", $kk)->update(["config_id" => $vv["config_id"], "relation" => $vv["relation"], "sub_id" => $res_sub_id]);
					}
					if ($new_result_tmp) {
						foreach ($new_result_tmp as $kkk => $vvv) {
							ksort($vvv["sub_id"]);
							$sub_id = json_encode($vvv["sub_id"]);
							\think\Db::name("product_config_options_links")->insert(["config_id" => $vvv["config_id"], "sub_id" => $sub_id, "relation" => $vvv["relation"], "type" => "result", "relation_id" => $k]);
						}
					}
				}
			}
			if ($new_cid) {
				ksort($params["new_subid"]);
				$new_sub_id = json_encode($params["new_subid"]);
				$insert = ["config_id" => intval($params["new_cid"]), "relation" => $params["new_relation"], "type" => "condition", "sub_id" => $new_sub_id, "relation_id" => 0];
				$new_id = \think\Db::name("product_config_options_links")->insertGetId($insert);
				$new_result = $params["result"];
				foreach ($new_result as $value) {
					ksort($value["new_subid"]);
					$new_res_sub_id = json_encode($value["new_subid"]);
					$res_insert = ["config_id" => $value["new_cid"], "relation" => $value["new_relation"], "type" => "result", "relation_id" => $new_id, "sub_id" => $new_res_sub_id];
					$uniq = \think\Db::name("product_config_options_links")->where("config_id", $value["new_cid"])->where("sub_id", $new_res_sub_id)->where("relation", $value["new_relation"])->where("type", "result")->where("relation_id", $new_id)->find();
					if (empty($uniq)) {
						\think\Db::name("product_config_options_links")->insert($res_insert);
					}
				}
			}
			$pids = array_unique($pids);
			foreach ($pids as $pid) {
				$res_array = hook("product_edit", ["pid" => $pid]);
				foreach ($res_array as $res) {
					if ($res["is_resource"] && $res["status"] != 200) {
						throw new \think\Exception("高级配置项同步至资源池失败,失败原因:" . $res["msg"]);
					}
				}
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("EDIT FAIL") . $e->getMessage()]);
		}
		\think\Db::name("products")->whereIn("id", $pids)->setInc("location_version");
		return jsonrule(["status" => 200, "msg" => lang("EDIT SUCCESS")]);
	}
	/**
	 * @title 删除条件
	 * @description 接口说明:删除条件
	 * @param .name:id type:int require:1 default:1 other: desc:条件ID
	 * @author wyh
	 * @url /admin/advanced_options/deletecondition
	 * @method DELETE
	 */
	public function deleteCondition()
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
		if (!in_array("seniorConfig", $app)) {
			return jsonrule(["status" => 400, "msg" => "免费版该功能不可用"]);
		}
		$params = $this->request->param();
		$id = intval($params["id"]);
		\think\Db::startTrans();
		try {
			\think\Db::name("product_config_options_links")->where("id", $id)->whereOr("relation_id", $id)->delete();
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 删除结果
	 * @description 接口说明:删除结果
	 * @param .name:id type:int require:1 default:1 other: desc:结果id
	 * @author wyh
	 * @url /admin/advanced_options/deleteresult
	 * @method DELETE
	 */
	public function deleteResult()
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
		if (!in_array("seniorConfig", $app)) {
			return jsonrule(["status" => 400, "msg" => "免费版该功能不可用"]);
		}
		$params = $this->request->param();
		$id = intval($params["id"]);
		\think\Db::startTrans();
		try {
			\think\Db::name("product_config_options_links")->where("id", $id)->where("type", "result")->delete();
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 添加条件
	 * @description 接口说明:添加条件
	 * @param .name:config_id type:int require:1 default:1 other: desc:配置项id
	 * @param .name:relation type:string require:1 default:1 other: desc:条件关系
	 * @param .name:sub_id[子项id][qty_minimum] type:array require:1 default:1 other: desc:子项最小值,非数量传0
	 * @param .name:sub_id[子项id][qty_maximum] type:array require:1 default:1 other: desc:子项最大值
	 * @param .name:result[0][config_id] type:array require:1 default:1 other: desc:结果 配置项id
	 * @param .name:result[0][relation] type:array require:1 default:1 other: desc:结果 条件关系
	 * @param .name:result[0][sub_id][子项id][qty_minimum] type:array require:1 default:1 other: desc:结果 子项最小值,非数量传0
	 * @param .name:result[0][sub_id][子项id][qty_maximum] type:array require:1 default:1 other: desc:结果 子项最大值,非数量传0
	 * @author wyh
	 * @url /admin/advanced_options/addcondition
	 * @method POST
	 */
	public function addCondition()
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
		if (!in_array("seniorConfig", $app)) {
			return jsonrule(["status" => 400, "msg" => "免费版该功能不可用"]);
		}
		$params = $this->request->param();
		$config_id = intval($params["config_id"]);
		$tmp2 = \think\Db::name("product_config_options")->where("id", $config_id)->find();
		if (empty($tmp2)) {
			return jsonrule(["status" => 400, "msg" => "配置项不存在"]);
		}
		$relation = $this->relation;
		if (!in_array($params["relation"], $relation)) {
			return jsonrule(["status" => 400, "msg" => "条件只能为等于或者不等于"]);
		}
		if (!is_array($params["sub_id"]) || empty($params["sub_id"])) {
			return jsonrule(["status" => 400, "msg" => "参数错误"]);
		}
		$sub_id = $params["sub_id"];
		ksort($sub_id);
		foreach ($sub_id as $k => $v) {
			$tmp3 = \think\Db::name("product_config_options_sub")->where("id", $k)->find();
			if (empty($tmp3)) {
				return jsonrule(["status" => 400, "msg" => "参数错误"]);
			}
			if (judgeQuantity($tmp2["option_type"])) {
				$qty_minimum = $v["qty_minimum"];
				$qty_maximum = $v["qty_maximum"];
				if ($qty_maximum < $qty_minimum || $qty_minimum < $tmp3["qty_minimum"] || $tmp3["qty_maximum"] < $qty_maximum) {
					return jsonrule(["status" => 400, "msg" => "参数错误"]);
				}
			}
		}
		if (!is_array($params["result"]) || empty($params["result"])) {
			return jsonrule(["status" => 400, "msg" => "参数错误"]);
		}
		$result = $params["result"];
		foreach ($result as $vv) {
			$tmp4 = \think\Db::name("product_config_options")->where("id", $vv["config_id"])->find();
			if (empty($tmp4)) {
				return jsonrule(["status" => 400, "msg" => "结果配置项不存在"]);
			}
			if (!in_array($vv["relation"], $relation)) {
				return jsonrule(["status" => 400, "msg" => "结果的条件只能为等于或者不等于"]);
			}
			if (!is_array($vv["sub_id"]) || empty($vv["sub_id"])) {
				return jsonrule(["status" => 400, "msg" => "结果参数错误"]);
			}
			$res_sub_id = $vv["sub_id"];
			foreach ($res_sub_id as $kkk => $vvv) {
				$tmp5 = \think\Db::name("product_config_options_sub")->where("id", $kkk)->find();
				if (empty($tmp5)) {
					return jsonrule(["status" => 400, "msg" => "结果参数错误"]);
				}
				if (judgeQuantity($tmp4["option_type"])) {
					$qty_minimum = $v["qty_minimum"];
					$qty_maximum = $v["qty_maximum"];
					if ($qty_maximum < $qty_minimum || $qty_minimum < $tmp5["qty_minimum"] || $tmp5["qty_maximum"] < $qty_maximum) {
						return jsonrule(["status" => 400, "msg" => "结果参数错误"]);
					}
				}
			}
		}
		$uniq = \think\Db::name("product_config_options_links")->where("config_id", $config_id)->where("sub_id", json_encode($sub_id))->where("relation", $params["relation"])->where("relation_id", 0)->where("type", "condition")->find();
		if (!empty($uniq)) {
			return jsonrule(["status" => 400, "msg" => "条件不可完全一样"]);
		}
		\think\Db::startTrans();
		try {
			$insert = ["config_id" => $config_id, "sub_id" => json_encode($sub_id), "relation" => $params["relation"] ?: "seq", "type" => "condition", "relation_id" => 0];
			$condition_id = \think\Db::name("product_config_options_links")->insertGetId($insert);
			foreach ($result as $value) {
				\think\Db::name("product_config_options_links")->insert(["config_id" => $value["config_id"], "sub_id" => json_encode($value["sub_id"]), "relation" => $value["relation"] ?: "seq", "type" => "result", "relation_id" => $condition_id]);
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
	}
	/**
	 * @title 添加结果
	 * @description 接口说明:添加结果
	 * @param .name:id type:int require:1 default:1 other: desc:条件id
	 * @param .name:config_id type:int require:1 default:1 other: desc:配置项id
	 * @param .name:relation type:string require:1 default:1 other: desc:条件关系
	 * @param .name:sub_id[子项id][qty_minimum] type:int require:1 default:1 other: desc:子项最小值,非数量传0
	 * @param .name:sub_id[子项id][qty_maximum] type:int require:1 default:1 other: desc:子项最大值
	 * @author wyh
	 * @url /admin/advanced_options/addresult
	 * @method POST
	 */
	public function addResult()
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
		if (!in_array("seniorConfig", $app)) {
			return jsonrule(["status" => 400, "msg" => "免费版该功能不可用"]);
		}
		$params = $this->request->param();
		$id = intval($params["id"]);
		$tmp = \think\Db::name("product_config_options_links")->where("id", $id)->find();
		if (empty($tmp)) {
			return jsonrule(["status" => 400, "msg" => "条件不存在"]);
		}
		$config_id = intval($params["config_id"]);
		if ($tmp["config_id"] == $config_id) {
			return jsonrule(["status" => 400, "msg" => "结果配置项不能与条件配置项相同"]);
		}
		$tmp2 = \think\Db::name("product_config_options")->where("id", $config_id)->find();
		if (empty($tmp2)) {
			return jsonrule(["status" => 400, "msg" => "配置项不存在"]);
		}
		$relation = $this->relation;
		if (!in_array($params["relation"], $relation)) {
			return jsonrule(["status" => 400, "msg" => "条件只能为等于或者不等于"]);
		}
		$sub_id = $params["sub_id"];
		if (!is_array($sub_id) || empty($sub_id)) {
			return jsonrule(["status" => 400, "msg" => "参数错误"]);
		}
		foreach ($sub_id as $k => $v) {
			$tmp3 = \think\Db::name("product_config_options_sub")->where("id", $k)->find();
			if (empty($tmp3)) {
				return jsonrule(["status" => 400, "msg" => "参数错误"]);
			}
			if (judgeQuantity($tmp2["option_type"])) {
				$qty_minimum = $v["qty_minimum"];
				$qty_maximum = $v["qty_maximum"];
				if ($qty_maximum < $qty_minimum || $qty_minimum < $tmp3["qty_minimum"] || $tmp3["qty_maximum"] < $qty_maximum) {
					return jsonrule(["status" => 400, "msg" => "参数错误"]);
				}
			}
		}
		ksort($sub_id);
		$sub_id = json_encode($sub_id);
		$uniq = \think\Db::name("product_config_options_links")->where("config_id", $config_id)->where("sub_id", $sub_id)->where("relation", $params["relation"])->where("type", "result")->where("relation_id", $id)->find();
		if (empty($uniq)) {
			$res = \think\Db::name("product_config_options_links")->insert(["config_id" => $config_id, "relation" => $params["relation"], "type" => "result", "relation_id" => $id, "sub_id" => $sub_id]);
			if ($res) {
				return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
			} else {
				return jsonrule(["status" => 400, "msg" => lang("FAIL MESSAGE")]);
			}
		} else {
			return jsonrule(["status" => 400, "msg" => "不可添加相同结果"]);
		}
	}
}