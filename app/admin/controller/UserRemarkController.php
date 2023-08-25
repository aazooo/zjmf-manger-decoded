<?php

namespace app\admin\controller;

class UserRemarkController extends AdminBaseController
{
	/**
	 * 显示资源列表
	 *
	 * @return \think\Response
	 */
	public function index()
	{
		$page = input("page") ?? config("page_num");
		$size = input("size") ?? config("page_size");
		$res = db("remark")->page($page, $size)->order("stick", "desc")->order("id", "desc")->select();
		return jsonrule(["data" => $res, "status" => 200, "msg" => "ok"]);
	}
	/**
	 * 显示创建资源表单页.
	 *
	 * @return \think\Response
	 */
	public function create()
	{
	}
	/**
	 * 保存新建的资源
	 *
	 * @param \think\Request $request
	 * @return \think\Response
	 */
	public function save(\think\Request $request)
	{
		$data = ["remark" => input("remark", "", "htmlspecialchars"), "stick" => input("stick/d"), "uid" => input("uid/d"), "admin_id" => cmf_get_current_admin_id(), "create_time" => time()];
		db("remark")->insert($data);
		return jsonrule(["status" => 201, "msg" => "ok"], 201);
	}
	/**
	 * 显示指定的资源
	 *
	 * @param int $id
	 * @return \think\Response
	 */
	public function read($id)
	{
		$res = db("remark")->where("id", $id)->find();
		return jsonrule(["data" => $res, "status" => 200, "msg" => "ok"]);
	}
	/**
	 * 显示编辑资源表单页.
	 *
	 * @param int $id
	 * @return \think\Response
	 */
	public function edit($id)
	{
	}
	/**
	 * 保存更新的资源
	 *
	 * @param \think\Request $request
	 * @param int $id
	 * @return \think\Response
	 */
	public function update(\think\Request $request, $id)
	{
		$data = ["id" => $id, "remark" => input("remark", "", "htmlspecialchars"), "stick" => input("stick/d")];
		$res = db("remark")->update($data);
		if ($res) {
			return jsonrule(["status" => 203, "msg" => "ok"], 203);
		} else {
			return jsonrule(["status" => 400, "msg" => "error"], 400);
		}
	}
	/**
	 * 删除指定资源
	 *
	 * @param int $id
	 * @return \think\Response
	 */
	public function delete($id)
	{
		db("remark")->where("id", $id)->delete();
		return jsonrule(["status" => 204, "msg" => "ok"], 204);
	}
}