<?php

namespace app\common\model;

class ClientModel extends \think\Model
{
	protected $pk = "id";
	protected $name = "clients";
	public function userList($field = "*")
	{
		$param = \request()->only(["limit", "page"]);
		$page = isset($param["page"]) ?: config("page");
		$limit = isset($param["limit"]) ?: config("limit");
		return $this::field($field)->page($page)->limit($limit)->select()->toArray();
	}
	public function clientList($field = "*", $str = "")
	{
		if (!empty($str)) {
			return $this->field($field)->where("status", 1)->where("id", "in", $str)->select()->toArray();
		} else {
			return $this->field($field)->where("status", 1)->select()->toArray();
		}
	}
	public function checkclient($uname, $where = [])
	{
		$user_info = db("clients")->field("id,email,username,companyname,phonenumber")->where("username|companyname|email|phonenumber", "like", "%" . $uname . "%");
		if (!empty($where)) {
			$user_info->where($where);
		}
		$user_info = $user_info->limit(30)->select()->toArray();
		return $user_info;
	}
}