<?php

namespace app\admin\controller;

/**
 * @title 管理员喜好
 * @description 接口说明: 管理员喜好(xue)
 */
class UserTastesController extends AdminBaseController
{
	protected $systemColumn = ["id", "uid"];
	public function index()
	{
		return $this->tryCatch(function () {
			$uid = cmf_get_current_admin_id();
			return \think\Db::name("user_tastes")->where("uid", $uid)->find();
		});
	}
	/**
	 * @title 管理员喜好修改
	 * @description 接口说明:管理员喜好修改
	 * @author xue
	 * @url admin/tastes/editUserTanstes
	 * @method POST
	 */
	public function editUserTanstes()
	{
		return $this->tryCatch(function () {
			return \think\Db::transaction(function () {
				$uid = cmf_get_current_admin_id();
				$this->updateCommon();
				return \think\Db::name("user_tastes")->field($this->systemColumn, true)->where("uid", $uid)->find();
			});
		});
	}
	private function updateCommon()
	{
		$params = $this->request->param();
		$uid = cmf_get_current_admin_id();
		$data = \think\Db::name("user_tastes")->where("uid", $uid)->find();
		if (!$data) {
			\think\Db::name("user_tastes")->insert(["uid" => $uid]);
		}
		$column = \think\Db::query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME = 'shd_user_tastes';");
		$column = array_column($column, "COLUMN_NAME");
		$tastes_column = array_diff($column, $this->systemColumn);
		$update_data = [];
		foreach ($params as $key => $val) {
			if (!in_array($key, $tastes_column)) {
				continue;
			}
			$update_data[$key] = $val;
		}
		$update_data && \think\Db::name("user_tastes")->where(["uid" => $uid])->update($update_data);
	}
	private function tryCatch(\Closure $closure)
	{
		try {
			return $this->toJson(call_user_func($closure));
		} catch (\Throwable $exception) {
			return $this->errorJson($exception);
		}
	}
	private function getLimit()
	{
		$limit = max(1, $this->request->limit);
		if ($limit > 50) {
			$limit = 50;
		}
		return intval($limit);
	}
	private function errorJson(\Throwable $exception)
	{
		return json(["status" => 406, "msg" => $exception->getMessage()]);
	}
	private function toJson($result)
	{
		return json(["status" => 200, "msg" => "Success", "data" => $result]);
	}
}