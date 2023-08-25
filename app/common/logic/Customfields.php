<?php

namespace app\common\logic;

class Customfields
{
	public function add($relid, $type = "product", $param = [])
	{
		$dec = "";
		if (!empty($param["addfieldname"])) {
			$add_field = ["type" => $type, "relid" => $relid, "fieldname" => $param["addfieldname"], "fieldtype" => $param["addfieldtype"] ?: "text", "description" => $param["addcustomfielddesc"] ?: "", "fieldoptions" => $param["addfieldoptions"] ?: "", "regexpr" => $param["addregexpr"] ?: "", "adminonly" => $param["addadminonly"] ?: "", "required" => $param["addrequired"] ?: "", "showorder" => $param["addshoworder"] ?: "", "sortorder" => $param["addsortorder"] ?: 0, "create_time" => time(), "showdetail" => $param["addshowdetail"] ?: 0];
			$re = \think\Db::name("customfields")->insertGetId($add_field);
			$dec = "添加自定义字段“" . $param["addfieldname"] . "” - #ID:" . $re;
		}
		return ["status" => "success", "dec" => $dec];
	}
	public function edit($relid, $type = "product", $param = [])
	{
		$validate = new \app\common\validate\CustomfieldsValidate();
		$dec = "";
		$customfieldname = $param["customfieldname"];
		if (is_array($customfieldname) && !empty($customfieldname)) {
			$customfields = \think\Db::name("customfields")->where("relid", $relid)->where("type", $type)->select()->toArray();
			$custom_id_arr = array_column($customfields, "id");
			foreach ($customfieldname as $k => $v) {
				if (in_array($k, $custom_id_arr)) {
					$update = ["fieldname" => $param["customfieldname"][$k], "fieldtype" => $param["customfieldtype"][$k] ?: "text", "description" => $param["customfielddesc"][$k] ?: "", "fieldoptions" => $param["customfieldoptions"][$k] ?: "", "regexpr" => $param["customfieldregexpr"][$k] ?: "", "adminonly" => $param["customadminonly"][$k] ?: "", "required" => $param["customrequired"][$k] ?: "", "showorder" => $param["customshoworder"][$k] ?: "", "sortorder" => $param["customsortorder"][$k] ?: 0, "showdetail" => $param["customshowdetail"][$k] ?: 0, "update_time" => time()];
					foreach ($customfields as $k1 => $v1) {
						if ($k == $v1["id"]) {
							$dec1 = "";
							$dec .= "修改自定义字段:#ID" . $k . "，";
							if ($update["fieldname"] != $v1["fieldname"]) {
								$dec1 .= "字段名称由“" . $v1["fieldname"] . "”改为“" . $update["fieldname"] . "”，";
							}
							if ($update["fieldtype"] != $v1["fieldtype"]) {
								$dec1 .= "字段类型由“" . $v1["fieldtype"] . "”改为“" . $update["fieldtype"] . "”，";
							}
							if ($update["description"] != $v1["description"]) {
								$dec1 .= "字段描述由“" . $v1["description"] . "”改为“" . $update["description"] . "”，";
							}
							if ($update["fieldoptions"] != $v1["fieldoptions"]) {
								$dec1 .= "字段选项由“" . $v1["fieldoptions"] . "”改为“" . $update["fieldoptions"] . "”，";
							}
							if ($update["regexpr"] != $v1["regexpr"]) {
								$dec1 .= "字段验证由“" . $v1["regexpr"] . "”改为“" . $update["regexpr"] . "”，";
							}
							if ($update["adminonly"] != $v1["adminonly"]) {
								if ($update["adminonly"] == 1) {
									$dec1 .= "管理员可见由“关闭”改为“开启”，";
								} else {
									$dec1 .= "管理员可见由“开启”改为“关闭”，";
								}
							}
							if ($update["required"] != $v1["required"]) {
								if ($update["required"] == 1) {
									$dec1 .= "是否必填由“关闭”改为“开启”，";
								} else {
									$dec1 .= "是否必填由“开启”改为“关闭”，";
								}
							}
							if ($update["showorder"] != $v1["showorder"]) {
								if ($update["required"] == 1) {
									$dec1 .= "订单/账单上显示由“关闭”改为“开启”，";
								} else {
									$dec1 .= "订单/账单上显示由“开启”改为“关闭”，";
								}
							}
							if ($update["sortorder"] != $v1["sortorder"]) {
								$dec1 .= "排序由“" . $v1["sortorder"] . "”改为“" . $update["sortorder"] . "”，";
							}
							if ($update["showdetail"] != $v1["showdetail"]) {
								if ($update["showdetail"] == 1) {
									$dec1 .= "产品内页显示由“关闭”改为“开启”，";
								} else {
									$dec1 .= "产品内页显示由“开启”改为“关闭”，";
								}
							}
							if (empty($dec1)) {
								$dec1 .= "未做任何修改，";
							}
							$dec .= $dec1;
						}
					}
					\think\Db::name("customfields")->where("id", $k)->update($update);
				}
			}
		}
		return ["status" => "success", "dec" => $dec];
	}
	public function getOne($fieldid, $field = "*")
	{
		return \think\Db::name("customfields")->field($field)->where("id", $fieldid)->find();
	}
	public function getAll($relid, $type = "product", $field = "*")
	{
		return \think\Db::name("customfields")->field($field)->where("relid", $relid)->where("type", $type)->order("sortorder", "asc")->select()->toArray();
	}
	public function deleteOne($fieldid)
	{
		$fieldid = intval($fieldid);
		$field = \think\Db::name("customfields")->field("id")->where("id", $fieldid)->find();
		if (!empty($field)) {
			\think\Db::name("customfields")->where("id", $fieldid)->delete();
			\think\Db::name("customfieldsvalues")->where("fieldid", $fieldid)->delete();
		}
		return ["status" => "success"];
	}
	public function deleteAll($relid, $type = "product")
	{
		$fields_array = \think\Db::name("customfields")->field("id")->where("relid", $relid)->where("type", $type)->select()->toArray();
		if (!empty($fields_array)) {
			$where_in = array_column($fields_array, "id");
			\think\Db::name("customfields")->whereIn("id", $where_in)->delete();
			\think\Db::name("customfieldsvalues")->whereIn("fieldid", $where_in)->delete();
		}
		return ["status" => "success"];
	}
	public function saveValue($relid, $customfield_param = [])
	{
		if (is_array($customfield_param) && !empty($customfield_param)) {
			foreach ($customfield_param as $k => $v) {
				$update = [];
				$k = intval($k);
				$field = \think\Db::name("customfields")->where("id", $k)->find();
				if (!empty($field)) {
					$update["fieldid"] = $k;
					$update["relid"] = $relid;
					$fieldtype = $field["fieldtype"];
					if ($field["required"] == 1 && empty($v)) {
						$errormsg = $field["fieldname"] . " 字段不能为空<br>";
						continue;
					}
					$update["value"] = $v;
					if ($fieldtype == "dropdown") {
						$fieldoptions = $field["fieldoptions"];
						if (!empty($fieldoptions)) {
							$fieldoptions_array = explode(",", $fieldoptions);
							if (in_array($v, $fieldoptions_array)) {
								$update["value"] = $v;
							} else {
								$update["value"] = "";
							}
						}
					}
					if (!empty($field["regexpr"]) && !empty($v)) {
						$regexpr = $field["regexpr"];
						$status = preg_match(\strval($regexpr), \strval($v));
						if (!$status) {
							$errormsg = $field["fieldname"] . " 字段不符合规则";
							continue;
						}
					}
					$fieldvalue = \think\Db::name("customfieldsvalues")->field("id")->where("fieldid", $k)->where("relid", $relid)->find();
					if (!empty($fieldvalue["id"])) {
						\think\Db::name("customfieldsvalues")->where("id", $fieldvalue["id"])->update($update);
					} else {
						\think\Db::name("customfieldsvalues")->insert($update);
					}
				}
			}
		}
		if (!empty($errormsg)) {
			return ["status" => "error", "msg" => $errormsg];
		}
		return ["status" => "success"];
	}
	public function getCustomField($pid)
	{
		$custom_data = \think\Db::name("customfields")->field("id,fieldname,fieldtype,fieldoptions,regexpr")->where("type", "product")->where("relid", $pid)->order("sortorder desc")->select()->toArray();
		$custom_config_data = [];
		if (!empty($custom_data)) {
			foreach ($custom_data as $key => $value) {
				$custom_config_data[$key] = $value;
				if ($value["fieldtype"] == "dropdown") {
					$custom_option = $value["fieldoptions"];
					$custom_config_data[$key]["dropdown_option"] = explode(",", $custom_option) ?: [];
				}
			}
		}
		return $custom_config_data;
	}
	public function getCartCustomField($pid, $is_admin = 0)
	{
		$custom_data = \think\Db::name("customfields")->field("id,fieldname,description,fieldtype,fieldoptions,regexpr,required")->where("type", "product")->where("relid", $pid);
		if (!$is_admin) {
			$custom_data->where("showorder", 1);
		}
		$custom_data = $custom_data->order("sortorder desc")->select()->toArray();
		$custom_config_data = [];
		if (!empty($custom_data)) {
			foreach ($custom_data as $key => $value) {
				$custom_config_data[$key] = $value;
				if ($value["fieldtype"] == "dropdown") {
					$custom_option = $value["fieldoptions"];
					if ($value["required"] == 1) {
						$custom_config_data[$key]["dropdown_option"] = explode(",", $custom_option) ?: [];
					} else {
						$custom_config_data[$key]["dropdown_option"] = explode(",", $custom_option) ?: [];
						array_unshift($custom_config_data[$key]["dropdown_option"], lang("NULL"));
					}
				}
			}
		}
		return $custom_config_data;
	}
	public function saveCustomField($value_relid, $customfield = [], $type = "product", $new_productid = 0, $product_info = [])
	{
		if ($type == "product") {
			$field_relid = \think\Db::name("host")->where("id", $value_relid)->value("productid");
		}
		$pid = \think\Db::name("host")->where("id", $value_relid)->value("productid");
		if ($pid != $new_productid && $new_productid > 0) {
			$old = \think\Db::name("customfields")->where("type", $type)->where("relid", $field_relid)->select()->toArray();
			$oldcustomfieldsvalues = \think\Db::name("customfieldsvalues")->whereIn("fieldid", array_column($old, "id"))->where("relid", $value_relid)->select()->toArray();
			$new = \think\Db::name("customfields")->where("type", $type)->where("relid", $new_productid)->select()->toArray();
			$old_con = [];
			foreach ($old as $v) {
				$val_md5 = $v["fieldname"] . $v["fieldtype"] . $v["description"] . $v["fieldoptions"] . $v["regexpr"] . $v["adminonly"] . $v["required"] . $v["showorder"] . $v["showinvoice"] . $v["showdetail"];
				$config = md5($val_md5);
				$old_con[$config] = $v["id"];
			}
			$new_con = [];
			$customfieldsvalues = [];
			foreach ($new as $v) {
				$val_md5 = $v["fieldname"] . $v["fieldtype"] . $v["description"] . $v["fieldoptions"] . $v["regexpr"] . $v["adminonly"] . $v["required"] . $v["showorder"] . $v["showinvoice"] . $v["showdetail"];
				$config = md5($val_md5);
				$fieldsvalues = [];
				$old_id = $old_con[$config];
				if (!empty($old_id)) {
					$value = "";
					foreach ($oldcustomfieldsvalues as $val) {
						if ($val["fieldid"] == $old_id && $val["relid"] == $value_relid) {
							$value = $val["value"];
						}
					}
					$fieldsvalues["fieldid"] = $v["id"];
					$fieldsvalues["relid"] = $value_relid;
					$fieldsvalues["value"] = $value;
					$fieldsvalues["create_time"] = time();
					$fieldsvalues["update_time"] = time();
				}
				if (count($fieldsvalues) > 0) {
					$customfieldsvalues[] = $fieldsvalues;
				}
			}
			\think\Db::name("customfieldsvalues")->where("relid", $value_relid)->delete();
			if (count($customfieldsvalues) > 0) {
				\think\Db::name("customfieldsvalues")->insertAll($customfieldsvalues);
			}
		} else {
			$customfields_data = \think\Db::name("customfields")->field("id,fieldoptions")->where("type", $type)->where("relid", $field_relid)->select()->toArray();
			$customfields_ids = array_column($customfields_data, "id");
			foreach ($customfield as $key => $value) {
				if (in_array($key, $customfields_ids)) {
					$exist_data = \think\Db::name("customfieldsvalues")->where("fieldid", $key)->where("relid", $value_relid)->find();
					if (!empty($exist_data)) {
						if ($exist_data["value"] != $value) {
							\think\Db::name("customfieldsvalues")->where("fieldid", $key)->where("relid", $value_relid)->update(["value" => $value, "update_time" => time()]);
						}
					} else {
						$icustom_data = ["fieldid" => $key, "relid" => $value_relid, "value" => $value, "update_time" => time()];
						\think\Db::name("customfieldsvalues")->insert($icustom_data);
					}
				}
			}
		}
		return "success";
	}
	public function getClientCustomField()
	{
		$custom_data = \think\Db::name("customfields")->field("id,fieldname,description,fieldtype,fieldoptions,regexpr,required")->where("type", "client")->where("relid", 0)->where("adminonly", 0)->where("showorder", 1)->order("sortorder desc")->select()->toArray();
		$custom_config_data = [];
		if (!empty($custom_data)) {
			foreach ($custom_data as $key => $value) {
				$custom_config_data[$key] = $value;
				if ($value["fieldtype"] == "dropdown") {
					$custom_option = $value["fieldoptions"];
					if ($value["required"] == 1) {
						$custom_config_data[$key]["dropdown_option"] = explode(",", $custom_option) ?: [];
					} else {
						$custom_config_data[$key]["dropdown_option"] = explode(",", $custom_option) ?: [];
						array_unshift($custom_config_data[$key]["dropdown_option"], lang("NULL"));
					}
				}
			}
		}
		return $custom_config_data;
	}
}