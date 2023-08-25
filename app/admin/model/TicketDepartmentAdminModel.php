<?php

namespace app\admin\model;

class TicketDepartmentAdminModel
{
	/**
	 * 获取当前管理员可用部门
	 * @Author huanghao
	 * @date   2019-11-30
	 * @return array
	 */
	public function getAllow()
	{
		$admin = cmf_get_current_admin_id();
		if (empty($admin)) {
			return [];
		}
		$result = \think\Db::name("ticket_department_admin")->field("dptid")->where("admin_id", $admin)->select()->toArray();
		return array_column($result, "dptid");
	}
	/**
	 * 管理员是否有对应部门权限
	 * @Author huanghao
	 * @date   2019-11-30
	 * @param  integer $dptid 部门id
	 * @return bool
	 */
	public function check($dptid = 0)
	{
		$admin = cmf_get_current_admin_id();
		if (empty($admin)) {
			return false;
		}
		if ($admin == 1) {
			return true;
		}
		$result = \think\Db::name("ticket_department_admin")->where("admin_id", $admin)->where("dptid", $dptid)->find();
		return !empty($result);
	}
}