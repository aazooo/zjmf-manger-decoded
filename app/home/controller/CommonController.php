<?php

namespace app\home\controller;

/**
 * @title 公共数据
 * @description 公共数据
 */
class CommonController extends \cmf\controller\HomeBaseController
{
	protected function initialize()
	{
		parent::initialize();
	}
	/**
	 * @title 用户目录
	 * @description 接口说明:用户目录
	 * @author lgd
	 * @url /navindex
	 * @method GET
	 * @return  data:导航列表@
	 * @data  id:产品分类ID
	 * @data  groupname:产品分类name
	 * @data  fa_icon:图标
	 */
	public function index()
	{
		$uid = $this->request->uid;
		$ngu = \think\Db::name("nav_group")->alias("ng")->join("nav_group_user ngu", "ng.id=ngu.groupid")->field("ng.*")->where("ngu.is_show", 1)->where("ngu.uid", $uid)->order("ng.order", "asc")->order("ng.id", "asc")->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $ngu]);
	}
	/**
	 * @title 用户可添加目录
	 * @description 接口说明:用户可添加目录
	 * @author lgd
	 * @url /addindex_page
	 * @method GET
	 * @return  data:导航列表@
	 * @data  id:产品分类ID
	 * @data  groupname:产品分类name
	 * @data  fa_icon:图标
	 */
	public function addindexPage()
	{
		$ng = \think\Db::name("nav_group")->order("order", "asc")->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $ng]);
	}
	/**
	 * @title 用户添加目录
	 * @description 接口说明:用户添加目录
	 * @author lgd
	 * @url /addindex_post
	 * @method Post
	 * @param name:id type:int  require:0  default: other: desc:分类id
	 */
	public function addindexPost()
	{
		$uid = $this->request->uid;
		updateUgp($uid);
		$param = $this->request->param();
		$ngr = \think\Db::name("nav_group_user")->where("uid", $uid)->where("is_show", 0)->where("groupid", $param["id"])->find();
		if (empty($ngr)) {
			return jsonrule(["status" => 400, "msg" => lang("COMMON_DIR_IS_ADD")]);
		}
		$data = ["is_show" => 1];
		$ng = \think\Db::name("nav_group_user")->where("uid", $uid)->where("groupid", $param["id"])->update($data);
		active_logs(sprintf($this->lang["Common_home_addindexPost"], $uid, $param["id"]), $uid);
		active_logs(sprintf($this->lang["Common_home_addindexPost"], $uid, $param["id"]), $uid, "", 2);
		if ($ng) {
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
		}
	}
	/**
	 * @title 用户目录删除
	 * @description 接口说明:删除
	 * @author lgd
	 * @url /addindex_del
	 * @method Post
	 * @param name:id type:int  require:0  default: other: desc:分类id
	 */
	public function addindexDel()
	{
		$uid = $this->request->uid;
		$param = $this->request->param();
		$ngr = \think\Db::name("nav_group_user")->where("uid", $uid)->where("groupid", $param["id"])->find();
		if (empty($ngr)) {
			return jsonrule(["status" => 400, "msg" => lang("COMMON_PARAM_ERROR")]);
		}
		$data = ["is_show" => 0];
		$ng = \think\Db::name("nav_group_user")->where("uid", $uid)->where("groupid", $param["id"])->update($data);
		active_logs(sprintf($this->lang["Common_home_addindexDel"], $uid, $param["id"]), $uid);
		active_logs(sprintf($this->lang["Common_home_addindexDel"], $uid, $param["id"]), $uid, "", 2);
		if ($ng) {
			return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
	}
}