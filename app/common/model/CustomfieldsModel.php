<?php

namespace app\common\model;

class CustomfieldsModel extends \think\Model
{
	public function deleteCustomfields($relid, $type = "product")
	{
		$fields = $this->field("id")->where("relid", $relid)->where("type", $type)->select()->toArray();
		if (!empty($fields)) {
			$where_in = array_column($fields, "id");
			$this->whereIn("id", $where_in)->delete();
			\think\Db::name("customfieldsvalues")->whereIn("fieldid", $where_in)->delete();
		}
		return true;
	}
	public function getCustomfields($relid, $type = "product", $field = "*")
	{
		return $this->field($field)->where("relid", $relid)->where("type", $type)->order("sortorder", "asc")->select()->toArray();
	}
	/**
	 * 获取自定义字段的值和自定义字段
	 * @Author huanghao
	 * @date   2019-12-01
	 * @param  int $relid 自定义字段relid
	 * @param  int $value_relid 自定义字段值relid
	 * @param  string $type 自定义字段类型
	 * @return array
	 */
	public function getCustomValue($relid, $value_relid, $type = "ticket", $user_type = "admin")
	{
		if (empty($value_relid)) {
			$value_relid = "\"\"";
		}
		$data = \think\Db::name("customfields")->field("a.id,a.fieldname,a.fieldtype,a.description,a.fieldoptions,a.required,a.regexpr,b.value,b.id vid")->alias("a")->leftJoin("customfieldsvalues b", "a.id=b.fieldid AND b.relid=" . $value_relid)->where("a.relid", $relid)->where("a.type", $type);
		if ($user_type != "admin") {
			$data = $data->where("adminonly", "<>", 0);
		}
		$data = $data->order("a.sortorder", "asc")->select()->toArray();
		foreach ($data as $k => $v) {
			$data[$k]["value"] = $v["value"] ?: "";
			$data[$k]["vid"] = $v["vid"] ?: 0;
		}
		return $data;
	}
	/**
	 * 获取某种类型的所有自定义字段+相应值(没有则定义为'')
	 * @Author wyh
	 * @date   2020-04-27
	 * @param  int $relid 客户id或者产品ID或者工单ID
	 * @param  string $type 自定义字段类型
	 * @param  string $admin 0|1是否只显示在管理端
	 * @return array
	 */
	public function getCustomFieldValue($relid, $type = "client", $admin = 0)
	{
		$customs = \think\Db::name("customfields")->field("id,fieldname,fieldtype,description,fieldoptions,regexpr,required,sortorder")->where("type", $type)->where("adminonly", $admin)->select()->toArray();
		$client_customs = \think\Db::name("customfields")->alias("a")->field("a.id,b.value")->leftJoin("customfieldsvalues b", "a.id = b.fieldid")->where("a.type", $type)->where("a.adminonly", $admin)->where("b.relid", $relid)->select()->toArray();
		$client_customs_value = [];
		foreach ($customs as $key => $custom) {
			if (!empty($client_customs[0])) {
				foreach ($client_customs as $client_custom) {
					if ($client_custom["id"] == $custom["id"]) {
						$custom["value"] = $client_custom["value"] ?? "";
					}
				}
			} else {
				$custom["value"] = "";
			}
			$client_customs_value[$key] = $custom;
		}
		return $client_customs_value;
	}
	/**
	 * 更新或插入自定义字段的值
	 * @Author huanghao
	 * @date   2019-12-01
	 * @return [type]     [description]
	 */
	public function updateCustomValue($relid, $value_relid, $update = [], $type = "ticket")
	{
		$data = $this->getCustomValue($relid, $value_relid, $type);
		if (!empty($update)) {
			$time = time();
			foreach ($data as $v) {
				if (isset($update[$v["id"]])) {
					if (!empty($v["regexpr"]) && !preg_match("/" . str_replace("/", "\\/", $v["regexpr"]) . "/", $update[$v["id"]])) {
						continue;
					}
					if ($v["fieldname"] == "select" && !in_array($update[$v["id"]], explode(",", $v["fieldoptions"]))) {
						continue;
					}
					$hook_res = hook("custom_field_save", ["fieldid" => $v["id"], "relid" => $value_relid, "value" => $update[$v["id"]]]);
					if (!empty($hook_res)) {
						foreach ($hook_res as $vv) {
							if (isset($vv["value"])) {
								$update[$v["id"]] = \strval($vv["value"]);
							}
						}
					}
					if ($v["vid"] > 0) {
						\think\Db::name("customfieldsvalues")->where("id", $v["vid"])->update(["value" => $update[$v["id"]], "update_time" => $time]);
					} else {
						\think\Db::name("customfieldsvalues")->insert(["fieldid" => $v["id"], "relid" => $value_relid, "value" => $update[$v["id"]], "create_time" => $time, "update_time" => 0]);
					}
				}
			}
		}
		return true;
	}
	/**
	 * 删除自定义字段所有值
	 * @Author huanghao
	 * @date   2019-12-01
	 * @param  int       $relid       [description]
	 * @param  [type]     $value_relid [description]
	 * @param  string     $type        [description]
	 * @return [type]                  [description]
	 */
	public function deleteCustomValue($relid, $value_relid, $type = "ticket")
	{
		$data = $this->getCustomValue($relid, $value_relid, $type);
		return \think\Db::name("customfieldsvalues")->whereIn("id", array_column($data, "vid"))->delete();
	}
	/**
	 * 验证自定义字段
	 * @Author wyh
	 * @date   2019-12-01
	 * @param  $customfields:自定义字段列表
	 * @param  $value:数组 ['id' => 'value'];id为字段ID，value为提交的值
	 */
	public function check($customfields, $value = [])
	{
		foreach ($customfields as $k => $v) {
			if (!empty($v["required"]) && empty($value[$v["id"]])) {
				return ["status" => "error", "msg" => $v["fieldname"] . "必须"];
			}
			if ($value[$v["id"]] && !empty($v["regexpr"]) && !preg_match("/" . str_replace("/", "\\/", $v["regexpr"]) . "/", $value[$v["id"]])) {
				return ["status" => "error", "msg" => $v["fieldname"] . "格式错误"];
			}
			if ($v["fieldname"] == "dropdown" && !in_array($value[$v["id"]], explode(",", $v["fieldoptions"]))) {
				return ["status" => "error", "msg" => $v["fieldname"] . "格式错误"];
			}
		}
		return ["status" => "success"];
	}
}