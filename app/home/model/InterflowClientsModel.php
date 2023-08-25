<?php

namespace app\home\model;

class InterflowClientsModel extends \think\Model
{
	protected $autoWriteTimestamp = true;
	protected $updateTime = "update_time";
	protected $createTime = "create_time";
	protected $dateFormat = "Y/m/d H:i";
	protected $readonly = ["create_time"];
	public function saveData($data)
	{
		$is_update_id = $data["id"];
		$data_ins["i_type"] = $data["i_type"];
		$data_ins["uid"] = $data["uid"];
		$data_ins["i_account"] = $data["i_account"];
		$data_ins["security_verify"] = $data["security_verify"];
		$data_ins["security_code"] = $data["security_code"];
		$data_ins["verify_code"] = $data["verify_code"];
		$is_update = $this->find(["id", $is_update_id]) ? true : false;
		if ($is_update) {
			$data_ins["id"] = $is_update_id;
			return $this->data($data_ins, true)->isUpdate(true)->force(true)->save();
		}
		return $this->data($data_ins, true)->isUpdate(false)->save();
	}
	public function getAll($where_field, $value, $where = [], $field = "*")
	{
		return $this->where($where_field, $value)->field($field)->where($where)->select();
	}
	public function getRows($where_field, $value, $where = [], $field = "*")
	{
		return $this->where($where_field, $value)->field($field)->where($where)->find();
	}
	public function deleteData($where_field, $value, $where = [])
	{
		return $this->where($where_field, $value)->where($where)->delete();
	}
}