<?php

namespace app\admin\controller;

/**
 * @title 管理员管理
 * @description 接口说明:管理员管理
 */
class UserController extends AdminBaseController
{
	/**
	 * @title 管理员列表
	 * @description 管理员列表
	 * @author wyh
	 * @url         admin/admin
	 * @method      GET
	 * @time        2020-04-03
	 * @param       .name:page type:int require:0 default:1 other: desc:页数
	 * @param       .name:search type:string require:0 default: other: desc:搜索
	 * @param       .name:limit type:int require:0 default:50 other: desc:每页条数
	 * @return      page:当前页数
	 * @return      limit:每页条数
	 * @return      count:总条数
	 * @return      max_page:总页数
	 * @return      list:管理员列表@
	 * @list        id:管理员用户id
	 * @list        user_login:管理员用户名
	 * @list        list.user_nickname:管理员姓名
	 * @list        list.user_email:邮箱
	 * @list        list.create_time:创建时间
	 * @list        list.user_status:状态0禁用1可用
	 * @list        list.last_login_time:上次登录时间
	 * @list        list.last_login_ip:上次登录ip
	 * @list        list.role:管理员角色
	 * @list        list.dept:工单部门
	 * @list        is_sale:是否销售0=默认1=是
	 * @list        sale_is_use:销售是否启用0=默认1=启用
	 */
	public function adminList()
	{
		$page = input("get.page", 1, "intval");
		$search = input("get.search", "");
		$limit = input("get.limit", 10, "intval");
		$page = $page >= 1 ? $page : config("page");
		$limit = $limit >= 1 ? $limit : config("limit");
		$params = $this->request->param();
		$order = isset($params["order"][0]) ? trim($params["order"]) : "a.id";
		$sort = isset($params["sort"][0]) ? trim($params["sort"]) : "DESC";
		$count = \think\Db::name("user")->where("user_nickname LIKE '%{$search}%' OR user_login LIKE '%{$search}%'")->count();
		$data = \think\Db::name("user")->alias("a")->leftJoin("role_user b", "b.user_id = a.id")->leftJoin("role c", "c.id = b.role_id")->field("a.id,a.user_login,a.user_nickname,a.user_email,a.create_time,a.user_status,a.last_login_time,a.last_login_ip,c.name as role,a.is_sale,a.sale_is_use")->where("user_nickname LIKE '%{$search}%' OR user_login LIKE '%{$search}%'")->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		foreach ($data as $k => $v) {
			$data[$k]["dept"] = implode("/", admin_dept($v["id"]));
		}
		$res["status"] = 200;
		$res["msg"] = lang("SUCCESS MESSAGE");
		$res["count"] = $count;
		$res["list"] = $data;
		return jsonrule($res);
	}
	/**
	 * @title 管理员添加页面
	 * @description 接口说明:
	 * @author wyh
	 * @url admin/create_page
	 * @method GET
	 * @throws
	 * @return roles：角色信息@
	 * @roles  id:角色ID
	 * @roles  name:角色名称
	 * @return depts：部门@
	 * @depts  id:部门ID
	 * @depts  name:部门名称
	 * @return lang：语言
	 */
	public function createPage()
	{
		$roles = \think\Db::name("role")->field("id,name")->where("status", 1)->select()->toArray();
		$depts = \think\Db::name("ticket_department")->field("id,name")->where("hidden", 0)->select()->toArray();
		$lang1 = get_language_list();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "roles" => $roles, "depts" => $depts, "lang" => $lang1]);
	}
	/**
	 * @title 管理员添加
	 * @description 接口说明:
	 * @author wyh
	 * @url admin/admin
	 * @method POST
	 * @throws
	 * @param  .name:role_id type:int require:1 default:1 other: desc:管理员角色ID(单选)
	 * @param  .name:user_login type:string require:1 default:1 other: desc:管理员用户名
	 * @param  .name:user_nickname type:string require:1 default:1 other: desc:管理员姓名
	 * @param  .name:user_email type:string require:1 default:1 other: desc:邮箱
	 * @param  .name:user_pass type:string require:1 default:1 other: desc:密码
	 * @param  .name:signature type:string require:1 default:1 other: desc:签名
	 * @param  .name:user_status type:int require:1 default:1 other: desc:管理员状态0:禁用,1:正常
	 * @param  .name:language type:string require:1 default:1 other: desc:语言
	 * @param  .name:dept_id[] type:array require:1 default:1 other: desc:部门ID(多选)
	 * @param  .name:is_sale type:int require:0 default:0 other: desc:是否销售0=默认1=是
	 * @param  .name:sale_is_use type:int require:0 default:0 other: desc:销售是否启用0=默认1=启用
	 * @param .name:only_mine type:string require:0 default:0 other: desc:只能查看自己的销售人员;
	 * @param .name:code type:string require:0 default:0 other: desc:验证码
	 * @param .name:is_receive type:string require:0 default:0 other: desc:是否接收业务类邮件;
	 */
	public function create()
	{
		if ($this->request->isPost()) {
			$params = $this->request->param();
			$rule = ["user_login" => "require|max:15", "user_nickname" => "require|max:15", "user_pass" => "require|alphaDash|max:32|min:8", "role_id" => "require|number", "user_status" => "require|in:0,1", "signature" => "max:255", "language" => "require"];
			$msg = ["user_login.require" => "用户名不能为空", "user_nickname.require" => "姓名不能为空", "user_pass.require" => "密码不能为空", "user_pass.max" => "密码长度为8-32位!", "user_pass.min" => "密码长度为8-32位!", "role_id.require" => "没有指定角色"];
			$validate = new \think\Validate($rule, $msg);
			$validate_result = $validate->check($params);
			if (!$validate_result) {
				return jsonrule(["status" => 406, "msg" => $validate->getError()]);
			}
			if (preg_match("/[^0-9a-zA-z]/i", $params["user_login"])) {
				return jsonrule(["status" => 400, "msg" => lang("用户名只能由字母和数字组成")]);
			}
			$role_id = $params["role_id"];
			$is_role = \think\Db::name("role")->where("id", $role_id)->count();
			if ($is_role < 1) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$exist = \think\Db::name("user")->where("user_login", $params["user_login"])->find();
			if (!empty($exist)) {
				return jsonrule(["status" => 406, "msg" => "用户名已存在"]);
			}
			$insert["user_login"] = $params["user_login"];
			$insert["user_pass"] = cmf_password($params["user_pass"]);
			$insert["user_email"] = $params["user_email"] ?: "";
			$insert["user_type"] = 1;
			$insert["user_status"] = $params["user_status"] ?? 1;
			$insert["user_nickname"] = $params["user_nickname"] ?: "";
			$insert["create_time"] = time();
			$insert["is_sale"] = $params["is_sale"] ?? 0;
			$insert["sale_is_use"] = $params["sale_is_use"] ?? 0;
			$insert["only_mine"] = $params["only_mine"] ?? 0;
			$insert["signature"] = \strval($params["signature"]);
			$insert["language"] = array_key_exists($params["language"], get_language_list()) ? $params["language"] : "zh-cn";
			$insert["is_receive"] = $params["is_receive"] ?: 0;
			$res = secondVerifyResultAdmin("create_admin");
			if ($res["status"] != 200) {
				return jsonrule($res);
			}
			\think\Db::startTrans();
			try {
				$aid = \think\Db::name("user")->insertGetId($insert);
				\think\Db::name("RoleUser")->insert(["role_id" => $role_id, "user_id" => $aid]);
				if (!empty($params["dept_id"]) && is_array($params["dept_id"])) {
					$depts = $params["dept_id"];
					$insert_dept = [];
					foreach ($depts as $dept) {
						$insert_dept[] = ["admin_id" => $aid, "dptid" => $dept];
					}
					\think\Db::name("ticket_department_admin")->insertAll($insert_dept);
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				active_log_final(sprintf($this->lang["User_admin_create_fail"]));
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("ADD FAIL") . $e->getMessage()]);
			}
			active_log_final(sprintf($this->lang["User_admin_create_success"], $aid));
			hook("add_admin", ["adminid" => $aid]);
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 管理员编辑显示
	 * @description 接口说明:管理员编辑显示
	 * @author wyh
	 * @url admin/admin/:id
	 * @method GET
	 * @throws
	 * @return roles：角色信息@
	 * @roles  id:角色ID
	 * @roles  name:角色名称
	 * @return depts：部门@
	 * @depts  id:部门ID
	 * @depts  name:部门名称
	 * @return lang：语言
	 * @return user:用户@
	 * @user  user_login:管理员用户名
	 * @user  user_status:状态（0：禁用，1：启用）
	 * @user  user_pass:密码
	 * @user  user_nickname:昵称
	 * @user  user_email:邮件
	 * @user  signature:签名
	 * @user  language:语言
	 * @user  language:语言
	 * @user  is_sale:是否销售0=默认1=是
	 * @user  sale_is_use:销售是否启用0=默认1=启用
	 * @user  role_id:角色ID
	 * @return dept_select:已选部门ID
	 */
	public function updatePage()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$roles = \think\Db::name("role")->field("id,name")->where("status", 1)->select()->toArray();
		$depts = \think\Db::name("ticket_department")->field("id,name")->select()->toArray();
		$lang = get_language_list();
		$user = \think\Db::name("user")->field("id,user_login,user_status,user_login,user_pass,user_nickname,user_email,signature,language,sale_is_use,is_sale,only_mine,is_receive")->where("id", $id)->find();
		$dept_select = \think\Db::name("ticket_department_admin")->where("admin_id", $id)->column("dptid");
		$role_id = \think\Db::name("role_user")->where("user_id", $id)->value("role_id");
		$user["role_id"] = $role_id;
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "roles" => $roles, "depts" => $depts, "lang" => $lang, "user" => $user, "dept_select" => $dept_select]);
	}
	/**
	 * @title 管理员编辑
	 * @description 接口说明:管理员编辑
	 * @author wyh
	 * @url admin/admin/update
	 * @method POST
	 * @throws
	 * @param  .name:id type:int require:1 default:1 other: desc:管理员ID
	 * @param  .name:role_id type:int require:1 default:1 other: desc:管理员角色ID(单选)
	 * @param  .name:user_login type:string require:1 default:1 other: desc:管理员用户名
	 * @param  .name:user_nickname type:string require:1 default:1 other: desc:管理员姓名
	 * @param  .name:user_email type:string require:1 default:1 other: desc:邮箱
	 * @param  .name:user_pass type:string require:0 default:1 other: desc:密码(不改，就不传)
	 * @param  .name:signature type:string require:1 default:1 other: desc:签名
	 * @param  .name:user_status type:int require:1 default:1 other: desc:管理员状态0:禁用,1:正常
	 * @param  .name:language type:string require:1 default:1 other: desc:语言
	 * @param  .name:is_sale type:int require:0 default:0 other: desc:是否销售0=默认1=是
	 * @param  .name:sale_is_use type:int require:0 default:0 other: desc:销售是否启用0=默认1=启用
	 * @param  .name:dept_id[] type:array require:1 default:1 other: desc:部门ID(多选)
	 * @param .name:only_mine type:string require:0 default:0 other: desc:只能查看自己的销售人员;
	 * @param .name:is_receive type:string require:0 default:0 other: desc:是否接收业务类邮件;
	 */
	public function update()
	{
		if ($this->request->isPost()) {
			$params = $this->request->param();
			$uid = isset($params["id"]) ? intval($params["id"]) : "";
			if (!$uid) {
				return jsonrule(["status" => 400, "msg" => "ID_ERROR"]);
			}
			$rule = ["user_login" => "require|max:15", "user_nickname" => "require|max:15", "role_id" => "require", "user_status" => "require|in:0,1", "signature" => "max:255", "language" => "require"];
			$msg = ["user_login.require" => "用户名不能为空", "user_nickname.require" => "姓名不能为空", "role_id.require" => "没有指定角色"];
			$validate = new \think\Validate($rule, $msg);
			$validate_result = $validate->check($params);
			if (!$validate_result) {
				return jsonrule(["status" => 406, "msg" => $validate->getError()]);
			}
			if (preg_match("/[^0-9a-zA-z]/i", $params["user_login"])) {
				return jsonrule(["status" => 400, "msg" => lang("用户名只能由字母和数字组成")]);
			}
			$role_id = $params["role_id"];
			$is_role = \think\Db::name("role")->where("id", $role_id)->count();
			if ($is_role < 1) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$exist1 = \think\Db::name("user")->where("id", "<>", $uid)->where("user_login", $params["user_login"])->find();
			if (!empty($exist1)) {
				return jsonrule(["status" => 406, "msg" => "用户名已存在"]);
			}
			$exist2 = \think\Db::name("role_user")->where("user_id", "=", $uid)->find();
			$exist = \think\Db::name("user")->where("id", "=", $uid)->find();
			$dec = "";
			$rolename = \think\Db::name("role")->field("name")->where("id", $params["role_id"])->find();
			$rolename1 = \think\Db::name("role")->field("name")->where("id", $exist2["role_id"])->find();
			if ($rolename1["name"] != "超级管理员") {
				if (!empty($params["role_id"]) && $params["role_id"] != $exist["role_id"]) {
					if ($rolename1["name"] != $rolename["name"]) {
						$dec .= "角色由“" . $rolename1["name"] . "”改为“" . $rolename["name"] . "”，";
					}
				}
			}
			$insert["is_sale"] = $params["is_sale"] ?? 0;
			if (!empty($params["is_sale"]) && $params["is_sale"] != $exist["is_sale"]) {
				if ($params["is_sale"] == 1) {
					$dec .= "销售由“关闭”改为“开启”，";
				} else {
					$dec .= "销售由“开启”改为“关闭”，";
				}
			}
			if ($params["is_sale"] == 0) {
				$params["sale_is_use"] = 0;
				$params["only_mine"] = 0;
			}
			$insert["sale_is_use"] = $params["sale_is_use"] ?? 0;
			if (!empty($params["sale_is_use"]) && $params["sale_is_use"] != $exist["sale_is_use"]) {
				if ($params["sale_is_use"] == 1) {
					$dec .= "由“禁用”改为“启佣”，";
				} else {
					$dec .= "由“启用”改为“禁言”，";
				}
			}
			$insert["only_mine"] = $params["only_mine"] ?? 0;
			if (!empty($params["only_mine"]) && $params["only_mine"] != $exist["only_mine"]) {
				if ($params["only_mine"] == 1) {
					$dec .= "只能查看自己旗下的客户由“关闭”改为“开启”，";
				} else {
					$dec .= "只能查看自己旗下的客户由“开启”改为“关闭”，";
				}
			}
			$insert["user_login"] = $params["user_login"];
			if (!empty($params["user_login"]) && $params["user_login"] != $exist["user_login"]) {
				$dec .= "用户名由“" . $exist["user_login"] . "”改为“" . $params["user_login"] . "”，";
			}
			$insert["user_email"] = $params["user_email"] ?: "";
			if (!empty($params["user_email"]) && $params["user_email"] != $exist["user_email"]) {
				$dec .= "邮箱由“" . $exist["user_email"] . "”改为“" . $params["user_email"] . "”，";
			}
			$insert["user_type"] = 1;
			$insert["user_status"] = $params["user_status"] ?? 1;
			if (!empty($params["user_status"]) && $params["user_status"] != $exist["user_status"]) {
				if ($params["user_status"] == 1) {
					$dec .= "状态户由“禁用”改为“启用”，";
				} else {
					$dec .= "状态户由“启用”改为“禁用”，";
				}
			}
			$insert["user_nickname"] = $params["user_nickname"] ?: "";
			if (!empty($params["user_nickname"]) && $params["user_nickname"] != $exist["user_nickname"]) {
				$dec .= "用户昵称由“" . $exist["user_nickname"] . "”改为“" . $params["user_nickname"] . "”，";
			}
			$insert["signature"] = $params["signature"];
			if (!empty($params["signature"]) && $params["signature"] != $exist["signature"]) {
				$dec .= "个性签名由“" . $exist["signature"] . "”改为“" . $params["signature"] . "”，";
			}
			$insert["language"] = $params["language"];
			if (!empty($params["language"]) && $params["language"] != $exist["language"]) {
				$dec .= "语言由“" . $exist["language"] . "”改为“" . $params["language"] . "”，";
			}
			$insert["is_receive"] = $params["is_receive"];
			if (!empty($params["is_receive"]) && $params["is_receive"] != $exist["is_receive"]) {
				if ($params["is_receive"] == 1) {
					$dec .= "是否接收业务类邮件由“关闭”改为“开启”，";
				} else {
					$dec .= "是否接收业务类邮件由“开启”改为“关闭”，";
				}
			}
			if (isset($params["user_pass"][0])) {
				$pass_len = strlen($params["user_pass"]);
				if ($pass_len < 8 || $pass_len > 32) {
					return jsonrule(["status" => 406, "msg" => "密码长度为8-32位!"]);
				}
				$insert["user_pass"] = cmf_password($params["user_pass"]);
			}
			if ($dec == "") {
				$dec = "什么也没修改";
			}
			if (cmf_get_current_admin_id() == $uid && $insert["user_status"] == 0) {
				return jsonrule(["status" => 400, "msg" => lang("您不能关闭自己的账号")]);
			}
			$res = secondVerifyResultAdmin("edit_admin");
			if ($res["status"] != 200) {
				return jsonrule($res);
			}
			\think\Db::startTrans();
			try {
				$users = \think\Db::name("user")->where("id", $uid)->find();
				$role_users = \think\Db::name("role_user")->field("role_id")->where("user_id", $uid)->find();
				\think\Db::name("user")->where("id", $uid)->update($insert);
				\think\Db::name("role_user")->where("user_id", $uid)->delete();
				\think\Db::name("RoleUser")->insert(["role_id" => $role_id, "user_id" => $uid]);
				if (!empty($params["dept_id"]) && is_array($params["dept_id"])) {
					$depts = $params["dept_id"];
					$insert_dept = [];
					foreach ($depts as $dept) {
						$insert_dept[] = ["admin_id" => $uid, "dptid" => $dept];
					}
					\think\Db::name("ticket_department_admin")->where("admin_id", $uid)->delete();
					\think\Db::name("ticket_department_admin")->insertAll($insert_dept);
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("UPDATE FAIL")]);
			}
			if (isset($params["user_pass"][0])) {
				if ($exist["user_pass"] != cmf_password($params["user_pass"])) {
					$token = md5(uniqid()) . md5(uniqid());
					\think\Db::name("user_token")->where("user_id", $uid)->update(["token" => $token]);
				}
			}
			active_log_final(sprintf($this->lang["User_admin_edit_page_success"], $uid, $dec), $uid);
			unset($dec);
			hook("edit_admin", ["adminid" => $uid]);
			return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 管理员删除
	 * @description 接口说明:管理员删除
	 * @author wyh
	 * @url admin/admin/:id/
	 * @method delete
	 * @throws
	 * @param  .name:id type:int require:1 default:1 other: desc:管理员ID
	 */
	public function delete()
	{
		$id = $this->request->param("id", 0, "intval");
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		if ($id == 1) {
			active_log_final(sprintf($this->lang["User_admin_delete_fail_admin"], $id));
			return jsonrule(["status" => 400, "msg" => "不可能的删除"]);
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("user")->delete($id);
			\think\Db::name("role_user")->where("user_id", $id)->delete();
			\think\Db::name("ticket_department_admin")->where("admin_id", $id)->delete();
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			active_log_final(sprintf($this->lang["User_admin_delete_fail"], $id, lang("DELETE FAIL")));
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
		active_log_final(sprintf($this->lang["User_admin_delete_success"], $id));
		hook("delete_admin", ["adminid" => $id]);
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 停用管理员
	 * @description 接口说明:停用管理员
	 * @author wyh
	 * @url admin/ban/:id/
	 * @method get
	 * @throws
	 * @param  .name:id type:int require:1 default:1 other: desc:管理员ID
	 */
	public function ban()
	{
		$id = $this->request->param("id", 0, "intval");
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		if ($id == 1) {
			active_log_final(sprintf($this->lang["User_admin_ban_fail_admin"], $id));
			return jsonrule(["status" => 400, "msg" => "不可能停用"]);
		}
		$result = \think\Db::name("user")->where(["id" => $id, "user_type" => 1])->setField("user_status", "0");
		if ($result) {
			active_log_final(sprintf($this->lang["User_admin_ban_success"], $id));
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		} else {
			active_log_final(sprintf($this->lang["User_admin_ban_fail"], $id, lang("FAIL MESSAGE")));
			return jsonrule(["status" => 400, "msg" => lang("FAIL MESSAGE")]);
		}
	}
	/**
	 * @title 启用管理员
	 * @description 接口说明:启用管理员
	 * @author wyh
	 * @url admin/cancel_ban/:id/
	 * @method get
	 * @throws
	 * @param  .name:id type:int require:1 default:1 other: desc:管理员ID
	 */
	public function cancelBan()
	{
		$id = $this->request->param("id", 0, "intval");
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$result = \think\Db::name("user")->where(["id" => $id, "user_type" => 1])->setField("user_status", "1");
		if ($result) {
			active_log_final(sprintf($this->lang["User_admin_cancelBan_success"], $id));
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		} else {
			active_log_final(sprintf($this->lang["User_admin_cancelBan_fail"], $id, lang("FAIL MESSAGE")));
			return jsonrule(["status" => 400, "msg" => lang("FAIL MESSAGE")]);
		}
	}
	/**
	 * @title 管理员修改自己信息(页面)
	 * @description 接口说明:管理员修改自己信息
	 * @author wyh
	 * @url admin/user/edit_self_info_page
	 * @method GET
	 * @throws
	 * @return user:用户信息@
	 * @user user_login:用户名
	 * @user user_nickname:真实姓名
	 * @user user_email:邮箱
	 * @user language:语言
	 * @return lang:语言列表@
	 */
	public function editSelfInfoPage()
	{
		$id = cmf_get_current_admin_id();
		$user = \think\Db::name("user")->field("id,user_login,user_nickname,user_email,language")->where("id", $id)->find();
		$lang = get_language_list();
		$data = ["user" => $user, "lang" => $lang];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	* @title 管理员修改自己信息
	* @description 接口说明:管理员修改自己信息
	* @author wyh
	* @url admin/user/edit_self_info
	* @method POST
	* @throws
	* @param  .name:id type:string require:1 default:1 other: desc:管理员ID
	* @param  .name:user_login type:string require:1 default:1 other: desc:用户名
	  @param  .name:user_nickname type:string require:1 default:1 other: desc:真实姓名
	* @param  .name:user_email type:string require:1 default:1 other: desc:邮箱
	* @param  .name:language type:string require:1 default:1 other: desc:语言
	* @param  .name:original_pass type:string require:0 default:1 other: desc:原密码(不修改，此参数不传)
	* @param  .name:user_pass type:string require:0 default:1 other: desc:新密码(不修改，此参数不传)
	* @param  .name:re_user_pass type:string require:0 default:1 other: desc:确认密码(不修改，此参数不传)
	*/
	public function editSelfInfo()
	{
		if ($this->request->isPost()) {
			$params = $this->request->param();
			$uid = cmf_get_current_admin_id();
			if ($uid != $params["id"]) {
				return jsonrule(["status" => 400, "msg" => "非法操作"]);
			}
			$rule = ["user_login" => "max:15", "user_nickname" => "max:15", "user_email" => "require|email", "language" => "require", "user_pass" => "max:20", "re_user_pass" => "max:20|confirm:user_pass"];
			$msg = ["user_email.require" => "邮箱不能为空", "language.require" => "语言不能为空", "re_user_pass.confirm" => "两次密码不一致"];
			$validate = new \think\Validate($rule, $msg);
			$validate_result = $validate->check($params);
			if (!$validate_result) {
				return jsonrule(["status" => 400, "msg" => $validate->getError()]);
			}
			if (preg_match("/[^0-9a-zA-z]/i", $params["user_login"])) {
				return jsonrule(["status" => 400, "msg" => lang("用户名只能由字母和数字组成")]);
			}
			$exist1 = \think\Db::name("user")->where("id", "<>", $uid)->where("user_login", $params["user_login"])->find();
			if (!empty($exist1)) {
				return jsonrule(["status" => 400, "msg" => "用户名已存在"]);
			}
			$exist = \think\Db::name("user")->where("id", "=", $uid)->find();
			$dec = "";
			$insert = ["user_login" => $params["user_login"], "user_email" => $params["user_email"], "language" => $params["language"]];
			if (isset($params["user_pass"][0])) {
				if (empty($params["original_pass"][0])) {
					return jsonrule(["status" => 400, "msg" => "请输入原密码"]);
				}
				if (!cmf_compare_password($params["original_pass"], $exist["user_pass"])) {
					return jsonrule(["status" => 400, "msg" => "原密码错误"]);
				}
				$insert["user_pass"] = cmf_password($params["user_pass"]);
			}
			if (!empty($params["user_email"]) && $params["user_email"] != $exist["user_email"]) {
				$dec .= "邮箱" . $exist["user_email"] . "改为" . $params["user_email"];
			}
			if (!empty($params["user_login"]) && $params["user_login"] != $exist["user_login"]) {
				$dec .= " - 用户名" . $exist["user_login"] . "改为" . $params["user_login"];
			}
			if (!empty($params["language"]) && $params["language"] != $exist["language"]) {
				$dec .= " - 语言" . $exist["language"] . "改为" . $params["language"];
			}
			$res = secondVerifyResultAdmin("modify_password");
			if ($res["status"] != 200) {
				return jsonrule($res);
			}
			\think\Db::startTrans();
			try {
				\think\Db::name("user")->where("id", $uid)->update($insert);
				if (isset($params["user_pass"][0])) {
					if ($exist["user_pass"] != cmf_password($params["user_pass"])) {
						$dec .= " - 修改了密码";
						$token = md5(uniqid()) . md5(uniqid());
						\think\Db::name("user_token")->where("user_id", $uid)->update(["token" => $token]);
					}
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("UPDATE FAIL")]);
			}
			if (!empty($dec)) {
				active_log_final(sprintf($this->lang["User_admin_edit_page_success"], $uid, $dec), $uid);
			}
			unset($dec);
			hook("edit_admin", ["adminid" => $uid]);
			return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
	}
	/**
	 * @title 黑名单列表
	 * @description 接口说明:黑名单列表
	 * @author wyh
	 * @url admin/user/get_black_list
	 * @method GET
	 * @throws
	 * @return list:黑名单@
	 * @list id:id
	 * @list ip:ip
	 * @list create_time:创建时间
	 * @list type:类型
	 * @list username:用户名
	 */
	public function getBlackList()
	{
		$lists = \think\Db::name("blacklist")->select()->toArray();
		$type = config("black_list_type");
		foreach ($lists as $k => $v) {
			$lists[$k]["ip"] = long2ip($v["ip"]);
			$lists[$k]["type"] = $type[$v["type"]];
		}
		$data = ["list" => $lists];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 移除黑名单
	 * @description 接口说明:移除黑名单
	 * @author wyh
	 * @url admin/user/remove_black_list
	 * @method POST
	 * @throws
	 * @param  .name:id type:string require:1 default:1 other: desc:ID
	 */
	public function removeBlackList()
	{
		$params = $this->request->param();
		$id = intval($params["id"]);
		$exist = \think\Db::name("blacklist")->where("id", $id)->find();
		if (empty($exist)) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$name = $exist["username"];
		$key = "admin_user_login_error_num_" . $name;
		$disable_login_key = "admin_user_disable_login_key_" . $name;
		\think\facade\Cache::rm($key);
		\think\facade\Cache::rm($disable_login_key);
		\think\Db::name("blacklist")->where("id", $id)->delete();
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
}