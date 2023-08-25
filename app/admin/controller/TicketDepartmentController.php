<?php

namespace app\admin\controller;

/**
 * @title 后台工单部门
 * @description 接口说明
 */
class TicketDepartmentController extends AdminBaseController
{
	protected $custom_param_type = ["dropdown" => "下拉", "password" => "密码", "text" => "文本框", "tickbox" => "选项框", "textarea" => "文本域"];
	/**
	 * @title 添加新部门页面
	 * @description 添加新部门页面
	 * @author huanghao
	 * @url         admin/get_ticket_department
	 * @method      GET
	 * @time        2019-11-25
	 *  @return id:管理员id
	 *  @return user_login:用户名
	 *  @return user_nickname:姓名
	 */
	public function addPage()
	{
		$admin_list = \think\Db::name("user")->field("id,user_login,user_nickname")->select()->toArray();
		$zjmf_finance_api = \think\Db::name("zjmf_finance_api")->field("id,name")->where("status", 1)->select()->toArray();
		return jsonrule(["status" => 200, "data" => ["admin_list" => $admin_list, "zjmf_finance_api" => $zjmf_finance_api]]);
	}
	/**
	 * @title 添加新部门
	 * @description 添加新工单部门
	 * @author huanghao
	 * @url         admin/add_ticket_department
	 * @method      POST
	 * @time        2019-11-25
	 * @param       .name:name type:string require:1 default: other: desc:部门名称
	 * @param       .name:description type:string require:0 default: other: desc:描述
	 * @param       .name:email type:string require:1 default: other: desc:邮件地址
	 * @param       .name:admins type:array require:0 default: other: desc:已指派的管理员
	 * @param       .name:only_reg_client type:int require:0 default:0 other: desc:仅客户
	 * @param       .name:feedback_request type:int require:0 default:0 other: desc:工单评分
	 * @param       .name:hidden type:int require:0 default:0 other: desc:隐藏
	 * @param       .name:host type:string require:0 default: other: desc:主机名
	 * @param       .name:port type:string require:0 default: other: desc:POP3端口
	 * @param       .name:login type:string require:0 default: other: desc:邮件地址
	 * @param       .name:password type:string require:0 default: other: desc:邮箱密码
	 * @param       .name:is_product_order type:int require:0 default: other: desc:开启产品订单
	 * @param       .name:is_open_auto_reply type:int require:0 default: other: desc:开启自动回复
	 * @param       .name:minutes type:int require:0 default: other: desc:时间
	 * @param       .name:time_type type:int require:0 default:m other: desc:单位m分钟s秒
	 * @param       .name:bz type:string require:0 default: other: desc:内容
	 * @param       .name:is_related_upstream type:int require:0 default: other: desc:关联上游(0:否1:是)
	 * @param       .name:is_certifi type:int require:0 default: other: desc:是否实名认证,1是,0否
	 */
	public function add()
	{
		$params = input("post.");
		$rule = ["name" => "require", "email" => "email"];
		$msg = ["name.require" => "部门名称不能为空", "email.email" => "邮件地址格式错误"];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$data["name"] = $params["name"];
		$data["description"] = $params["description"] ?: "";
		$data["email"] = $params["email"];
		$data["only_reg_client"] = !empty($params["only_reg_client"]) ? 1 : 0;
		$data["only_client_open"] = !empty($params["only_client_open"]) ? 1 : 0;
		$data["no_auto_reply"] = !empty($params["no_auto_reply"]) ? 1 : 0;
		$data["feedback_request"] = !empty($params["feedback_request"]) ? 1 : 0;
		$data["is_certifi"] = !empty($params["is_certifi"]) ? 1 : 0;
		$data["hidden"] = !empty($params["hidden"]) ? 1 : 0;
		$data["host"] = $params["host"] ?: "";
		$data["port"] = $params["port"] ?: "";
		$data["login"] = $params["login"] ?: "";
		$data["password"] = cmf_encrypt($params["password"]);
		$data["is_product_order"] = !empty($params["is_product_order"]) ? $params["is_product_order"] : 0;
		$data["is_open_auto_reply"] = !empty($params["is_open_auto_reply"]) ? $params["is_open_auto_reply"] : 0;
		$data["minutes"] = !empty($params["minutes"]) ? $params["minutes"] : 0;
		$data["time_type"] = !empty($params["time_type"]) ? $params["time_type"] : 0;
		$data["bz"] = !empty($params["bz"]) ? $params["bz"] : 0;
		$data["is_related_upstream"] = !empty($params["is_related_upstream"]) ? $params["is_related_upstream"] : 0;
		$max_order = \think\Db::name("ticket_department")->field("order")->order("order", "desc")->find();
		$data["order"] = \intval($max_order["order"]) + 1;
		$r = \think\Db::name("ticket_department")->insertGetId($data);
		if ($r) {
			if (!empty($params["admins"]) && is_array($params["admins"])) {
				$params["admins"] = array_filter($params["admins"], function ($x) {
					return is_numeric($x) && $x > 0;
				});
				if (!empty($params["admins"])) {
					$admins = \think\Db::name("user")->field("id")->whereIn("id", $params["admins"])->select()->toArray();
					$insert = [];
					foreach ($admins as $k => $v) {
						$insert[] = ["admin_id" => $v["id"], "dptid" => $r];
					}
					if (!empty($insert)) {
						\think\Db::name("ticket_department_admin")->insertAll($insert);
					}
				}
			}
			if (!empty($params["upstreams"]) && is_array($params["upstreams"]) && $params["is_related_upstream"] == 1) {
				$insert = [];
				foreach ($params["upstreams"] as $k => $v) {
					$insert[] = ["api_id" => $k, "upstream_dptid" => $v, "dptid" => $r];
				}
				if (!empty($insert)) {
					\think\Db::name("ticket_department_upstream")->insertAll($insert);
				}
			}
			active_log(sprintf($this->lang["TicketDepartment_admin_add"], $r, $data["name"]));
		}
		$result["status"] = 200;
		$result["msg"] = "添加成功";
		return jsonrule($result);
	}
	/**
	 * @title 修改工单部门
	 * @description 修改工单部门
	 * @author huanghao
	 * @url         admin/save_ticket_department
	 * @method      POST
	 * @time        2019-11-25
	 * @param       .name:id type:int require:1 default: other: desc:部门id
	 * @param       .name:name type:string require:1 default: other: desc:部门名称
	 * @param       .name:description type:string require:0 default: other: desc:描述
	 * @param       .name:email type:string require:1 default: other: desc:邮件地址
	 * @param       .name:admins type:array require:0 default: other: desc:已指派的管理员
	 * @param       .name:only_reg_client type:int require:0 default:0 other: desc:仅客户
	 * @param       .name:feedback_request type:int require:0 default:0 other: desc:工单评分
	 * @param       .name:hidden type:int require:0 default:0 other: desc:隐藏
	 * @param       .name:host type:string require:0 default: other: desc:主机名
	 * @param       .name:port type:string require:0 default: other: desc:POP3端口
	 * @param       .name:login type:string require:0 default: other: desc:邮件地址
	 * @param       .name:password type:string require:0 default: other: desc:邮箱密码
	 * @param       .name:addfieldname type:string require:0 default: other: desc:新增自定义字段名称
	 * @param       .name:addsortorder type:int require:0 default: other: desc:新增自定义字段排序
	 * @param       .name:addfieldtype type:string require:0 default: other: desc:新增自定义字段类型
	 * @param       .name:addcfdesc type:string require:0 default: other: desc:新增自定义字段描述
	 * @param       .name:addregexpr type:string require:0 default: other: desc:新增自定义字段验证
	 * @param       .name:addfieldoptions type:string require:0 default: other: desc:新增自定义字段Select Options
	 * @param       .name:addadminonly type:string require: default: other: desc:新增自定义字段仅管理员可见
	 * @param       .name:addrequired type:string require: default: other: desc:新增自定义字段仅管理员必填
	 * @param       .name:customfieldname type:array require:0 default: other: desc:修改自定义字段名称
	 * @param       .name:customsortorder type:array require:0 default: other: desc:修改自定义字段排序
	 * @param       .name:customfieldtype type:array require:0 default: other: desc:修改自定义字段类型
	 * @param       .name:customcfdesc type:array require:0 default: other: desc:修改自定义字段描述
	 * @param       .name:customregexpr type:array require:0 default: other: desc:修改自定义字段验证
	 * @param       .name:customfieldoptions type:array require:0 default: other: desc:修改自定义字段Select Options
	 * @param       .name:customadminonly type:array require: default: other: desc:修改自定义字段仅管理员可见
	 * @param       .name:customrequired type:array require: default: other: desc:修改自定义字段仅管理员必填
	 * @param       .name:is_product_order type:int require:0 default: other: desc:开启产品订单
	 * @param       .name:is_open_auto_reply type:int require:0 default: other: desc:开启自动回复
	 * @param       .name:minutes type:int require:0 default: other: desc:时间
	 * @param       .name:time_type type:int require:0 default:m other: desc:单位m分钟s秒
	 * @param       .name:bz type:string require:0 default: other: desc:内容
	 * @param       .name:is_related_upstream type:int require:0 default: other: desc:关联上游(0:否1:是)
	 * @param       .name:is_certifi type:int require:0 default: other: desc:是否实名认证,1是,0否
	 */
	public function save()
	{
		$params = $this->request->param();
		$rule = ["name" => "require", "email" => "email"];
		$msg = ["name.require" => "部门名称不能为空", "email.email" => "邮件地址格式错误"];
		$des = "";
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$id = intval($params["id"]);
		$info = \think\Db::name("ticket_department")->where("id", $id)->find();
		if (empty($info)) {
			$result["status"] = 406;
			$result["msg"] = "工单部门id错误";
			return jsonrule($result);
		}
		$data["name"] = $params["name"];
		if (!empty($data["name"]) && $data["name"] != $info["name"]) {
			$des .= "部门名称由“" . $info["name"] . "”改为“" . $data["name"] . "”，";
		}
		$data["email"] = $params["email"];
		if (!empty($data["email"]) && $data["email"] != $info["email"]) {
			$des .= "邮箱由“" . $info["email"] . "”改为“" . $data["email"] . "”，";
		}
		if (isset($params["admins"])) {
			if (!empty($params["admins"]) && is_array($params["admins"])) {
				$params["admins"] = array_filter($params["admins"], function ($x) {
					return is_numeric($x) && $x > 0;
				});
				\think\Db::name("ticket_department_admin")->where("dptid", $id)->delete();
				if (!empty($params["admins"])) {
					$admins = \think\Db::name("user")->field("id")->whereIn("id", $params["admins"])->select()->toArray();
					$insert = [];
					foreach ($admins as $k => $v) {
						$insert[] = ["admin_id" => $v["id"], "dptid" => $id];
					}
					if (!empty($insert)) {
						\think\Db::name("ticket_department_admin")->insertAll($insert);
					}
				}
			} else {
				\think\Db::name("ticket_department_admin")->where("dptid", $id)->delete();
			}
		}
		if (!empty($params["upstreams"]) && is_array($params["upstreams"]) && $params["is_related_upstream"] == 1) {
			\think\Db::name("ticket_department_upstream")->where("dptid", $id)->delete();
			$insert = [];
			foreach ($params["upstreams"] as $k => $v) {
				$insert[] = ["api_id" => $k, "upstream_dptid" => $v, "dptid" => $id];
			}
			if (!empty($insert)) {
				\think\Db::name("ticket_department_upstream")->insertAll($insert);
			}
		} else {
			\think\Db::name("ticket_department_upstream")->where("dptid", $id)->delete();
		}
		$data["description"] = $params["description"];
		if (isset($params["description"]) && $data["description"] != $info["description"]) {
			$des .= "描述由“" . $info["description"] . " 改为:" . $data["description"] . "”，";
		}
		$data["is_product_order"] = !empty($params["is_product_order"]) ? $params["is_product_order"] : 0;
		if (isset($params["is_product_order"]) && $data["is_product_order"] != $info["is_product_order"]) {
			if ($data["is_product_order"] == 1) {
				$des .= " 需要激活产品由“关闭”改为“开启”";
			} else {
				$des .= " 需要激活产品由“开启”改为“关闭”";
			}
		}
		$data["is_open_auto_reply"] = !empty($params["is_open_auto_reply"]) ? $params["is_open_auto_reply"] : 0;
		if (isset($params["is_open_auto_reply"]) && $data["is_open_auto_reply"] != $info["is_open_auto_reply"]) {
			if ($data["is_open_auto_reply"] == 1) {
				$des .= "自动回复由“关闭”改为“开启”";
			} else {
				$des .= "自动回复由“开启”改为“关闭”";
			}
		}
		$data["minutes"] = !empty($params["minutes"]) ? $params["minutes"] : 0;
		if (isset($params["minutes"]) && $data["minutes"] != $info["minutes"]) {
			if ($data["minutes"] != $info["minutes"]) {
				$des .= "时间由“" . $info["minutes"] . "”改为“" . $data["minutes"] . "”，";
			}
		}
		$arr = ["秒", "分钟"];
		$data["time_type"] = !empty($params["time_type"]) ? $params["time_type"] : 0;
		if (isset($params["time_type"]) && $data["time_type"] != $info["time_type"]) {
			if ($data["time_type"] != $info["time_type"]) {
				$des .= "时间类型由“" . $arr[$info["time_type"]] . "”改为“" . $arr[$data["time_type"]] . "”，";
			}
		}
		$data["bz"] = !empty($params["bz"]) ? $params["bz"] : 0;
		if (isset($params["bz"]) && $data["bz"] != $info["bz"]) {
			if ($data["bz"] != $info["bz"]) {
				$des .= "自动回复内容由“" . $info["bz"] . "”改为“" . $data["bz"] . "”，";
			}
		}
		$data["feedback_request"] = !empty($params["feedback_request"]) ? 1 : 0;
		if (isset($params["feedback_request"]) && $data["feedback_request"] != $info["feedback_request"]) {
			if ($data["feedback_request"] == 1) {
				$des .= "工单评分由“关闭”改为“开启”";
			} else {
				$des .= "工单评分由“开启”改为“关闭”";
			}
		}
		$data["hidden"] = !empty($params["hidden"]) ? 1 : 0;
		if (isset($params["hidden"]) && $data["hidden"] != $info["hidden"]) {
			if ($data["hidden"] == 1) {
				$des .= "由“隐藏”改为“显示”";
			} else {
				$des .= "由“显示”改为“隐藏”";
			}
		}
		$data["is_related_upstream"] = !empty($params["is_related_upstream"]) ? $params["is_related_upstream"] : 0;
		if (isset($params["is_related_upstream"]) && $data["is_related_upstream"] != $info["is_related_upstream"]) {
			if ($data["is_related_upstream"] == 1) {
				$des .= "关联上游部门由“关闭”改为“开启”";
			} else {
				$des .= "关联上游部门由“开启”改为“关闭”";
			}
		}
		if (isset($params["host"])) {
			$data["host"] = $params["host"] ?: "";
		}
		if (isset($params["port"])) {
			$data["port"] = $params["port"] ?: "";
		}
		if (isset($params["login"])) {
			$data["login"] = $params["login"] ?: "";
		}
		if (isset($params["password"])) {
			$old_password = cmf_decrypt($info["password"]);
			if ($params["password"] == str_repeat("*", strlen($old_password))) {
			} else {
				$data["password"] = cmf_encrypt($params["password"]);
			}
		}
		$data["is_certifi"] = !empty($params["is_certifi"]) ? 1 : 0;
		$r = \think\Db::name("ticket_department")->where("id", $id)->update($data);
		if (is_array($params["customfieldname"]) && !empty($params["customfieldname"])) {
			$customfields = model("Customfields")->getCustomfields($id, "ticket", "id");
			$old_id = array_column($customfields, "id");
			foreach ($params["customfieldname"] as $k => $v) {
				if (in_array($k, $old_id)) {
					$update["fieldname"] = $v;
					$update["update_time"] = time();
					if (isset($params["customfieldtype"][$k])) {
						$update["fieldtype"] = $params["customfieldtype"][$k] ?: "text";
					}
					if (isset($params["customcfdesc"][$k])) {
						$update["description"] = $params["customcfdesc"][$k] ?: "";
					}
					if (isset($params["customfieldoptions"][$k])) {
						$update["fieldoptions"] = $params["customfieldoptions"][$k] ?: "";
					}
					if (isset($params["customregexpr"][$k])) {
						$update["regexpr"] = $params["customregexpr"][$k] ?: "";
					}
					if (isset($params["customadminonly"][$k])) {
						$update["adminonly"] = $params["customadminonly"][$k] ?: "";
					}
					if (isset($params["customrequired"][$k])) {
						$update["required"] = $params["customrequired"][$k] ?: "";
					}
					if (isset($params["customsortorder"][$k])) {
						$update["sortorder"] = $params["customsortorder"][$k] ?: "";
					}
					\think\Db::name("customfields")->where("id", $k)->update($update);
				}
			}
		}
		if (!empty($params["addfieldname"])) {
			$add_field = ["type" => "ticket", "relid" => $id, "fieldname" => $params["addfieldname"], "fieldtype" => $params["addfieldtype"] ?: "text", "description" => $params["addcfdesc"] ?: "", "fieldoptions" => $params["addfieldoptions"] ?: "", "regexpr" => $params["addregexpr"] ?: "", "adminonly" => $params["addadminonly"] ?: "", "required" => $params["addrequired"] ?: "", "sortorder" => $params["addsortorder"] ?: 0, "create_time" => time()];
			\think\Db::name("customfields")->insert($add_field);
		}
		if (empty($desc)) {
			$des .= "没有任何修改";
		}
		active_log(sprintf($this->lang["TicketDepartment_admin_save"], $r, $des));
		$result["status"] = 200;
		$result["msg"] = "修改成功";
		return jsonrule($result);
	}
	/**
	 * @title 删除工单部门
	 * @description 删除工单部门
	 * @author huanghao
	 * @url         admin/delete_ticket_department
	 * @method      POST
	 * @time        2019-11-25
	 * @param       .name:id type:int require:1 default: other: desc:部门id
	 * @return      
	 */
	public function delete()
	{
		$id = intval(input("post.id"));
		$info = \think\Db::name("ticket_department")->field("id,name")->where("id", $id)->find();
		if (empty($info)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return jsonrule($result);
		}
		$is_use = \think\Db::name("ticket")->where("dptid", $id)->count();
		if (!empty($is_use)) {
			$result["status"] = 406;
			$result["msg"] = "有工单使用该部门,不能删除";
			return jsonrule($result);
		}
		model("Customfields")->deleteCustomfields($id, "ticket");
		\think\Db::name("ticket_department_admin")->where("dptid", $id)->delete();
		$r = \think\Db::name("ticket_department")->where("id", $id)->delete();
		if ($r) {
			active_log("删除工单部门成功,名称:{$info["name"]},ID:{$id}");
		}
		$result["status"] = 200;
		$result["msg"] = "删除成功";
		return jsonrule($result);
	}
	/**
	 * @title 向后排序
	 * @description 和后一位交换排序
	 * @author huanghao
	 * @url         admin/movedown_ticket_department
	 * @method      POST
	 * @time        2019-11-25
	 * @param       .name:id type:int require:1 default: other: desc:部门id
	 * @return      
	 */
	public function moveDown()
	{
		$id = intval(input("post.id"));
		$info = \think\Db::name("ticket_department")->field("id,order")->where("id", $id)->find();
		if (empty($info)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return jsonrule($result);
		}
		$next = \think\Db::name("ticket_department")->field("id,order")->where("order", ">", $info["order"])->order("order", "asc")->find();
		if (empty($next)) {
			$result["status"] = 200;
			$result["msg"] = "操作成功";
			return jsonrule($result);
		}
		\think\Db::startTrans();
		try {
			$r1 = \think\Db::name("ticket_department")->where("id", $id)->update(["order" => $next["order"]]);
			$r2 = \think\Db::name("ticket_department")->where("id", $next["id"])->update(["order" => $info["order"]]);
			if ($r1 && $r2) {
				\think\Db::commit();
				$result["status"] = 200;
				$result["msg"] = "操作成功";
			} else {
				\think\Db::rollback();
				$result["status"] = 200;
				$result["msg"] = "操作失败";
			}
		} catch (\Exception $e) {
			\think\Db::rollback();
			$result["status"] = 200;
			$result["msg"] = "操作失败";
		}
		return jsonrule($result);
	}
	/**
	 * @title 向前排序
	 * @description 和前一位交换排序
	 * @author huanghao
	 * @url         admin/moveup_ticket_department
	 * @method      POST
	 * @time        2019-11-25
	 * @param       .name:id type:int require:1 default: other: desc:部门id
	 * @return      
	 */
	public function moveUp()
	{
		$id = intval(input("post.id"));
		$info = \think\Db::name("ticket_department")->field("id,order")->where("id", $id)->find();
		if (empty($info)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return jsonrule($result);
		}
		$prev = \think\Db::name("ticket_department")->field("id,order")->where("order", "<", $info["order"])->order("order", "desc")->find();
		if (empty($prev)) {
			$result["status"] = 200;
			$result["msg"] = "操作成功";
			return jsonrule($result);
		}
		\think\Db::startTrans();
		try {
			$r1 = \think\Db::name("ticket_department")->where("id", $id)->update(["order" => $prev["order"]]);
			$r2 = \think\Db::name("ticket_department")->where("id", $prev["id"])->update(["order" => $info["order"]]);
			if ($r1 && $r2) {
				\think\Db::commit();
				$result["status"] = 200;
				$result["msg"] = "操作成功";
			} else {
				\think\Db::rollback();
				$result["status"] = 200;
				$result["msg"] = "操作失败";
			}
		} catch (\Exception $e) {
			\think\Db::rollback();
			$result["status"] = 200;
			$result["msg"] = "操作失败";
		}
		return jsonrule($result);
	}
	/**
	 * @title 工单部门列表
	 * @description 获取工单部门列表
	 * @author huanghao
	 * @url         admin/list_ticket_department
	 * @method      GET
	 * @time        2019-11-25
	 * @param       
	 * @return      .id:工单部门id
	 * @return      .name:部门名称
	 * @return      .description:描述
	 * @return      .email:邮件地址
	 * @return      .hidden:是否隐藏
	 * @return      .order:排序值
	 */
	public function getList()
	{
		$data = \think\Db::name("ticket_department")->field("id,name,description,email,hidden,order,is_open_auto_reply as auto_reply")->order("order", "DESC")->select()->toArray();
		$result["status"] = 200;
		$result["data"] = $data;
		return jsonrule($result);
	}
	/**
	 * @title 部门详情
	 * @description 工单部门详情
	 * @author huanghao
	 * @url         admin/list_ticket_department/:id
	 * @method      GET
	 * @time        2019-11-25
	 * @param       .name:id type:int require:1 default: other: desc:部门id
	 * @return      .id:部门id
	 * @return      .name:部门名称
	 * @return      .description:描述
	 * @return      .email:邮件地址
	 * @return      .only_reg_client:仅客户
	 * @return      .only_client_open:仅管道回复
	 * @return      .no_auto_reply:无自动回复
	 * @return      .hidden:隐藏?
	 * @return      .host:主机名
	 * @return      .port:POP3端口
	 * @return      .login:邮件地址
	 * @return      .password:邮箱密码
	 * @return      .feedback_request:反馈请求
	 * @return      .is_certifi:是否实名认证,1是,0否
	 * @return      .admins:已指派的管理员
	 * @return      .customfields.id:自定义字段id
	 * @return      .customfields.fieldname:自定义字段名称
	 * @return      .customfields.fieldtype:自定义字段类型
	 * @return      .customfields.description:自定义字段描述
	 * @return      .customfields.regexpr:自定义字段验证
	 * @return      .customfields.fieldoptions:自定义字段select options
	 * @return      .customfields.adminonly:自定义字段仅管理员可见
	 * @return      .customfields.required:自定义字段是否必填
	 * @return      .customfields.sortorder:自定义字段是否排序
	 */
	public function getDetail($id)
	{
		$id = intval($id);
		$data = \think\Db::name("ticket_department")->where("id", $id)->find();
		if (empty($data)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return jsonrule($result);
		}
		$department_admin = \think\Db::name("ticket_department_admin")->where("dptid", $id)->select()->toArray();
		$data["admins"] = array_column($department_admin, "admin_id") ?: [];
		unset($data["order"]);
		$data["password"] = cmf_decrypt($data["password"]);
		$ticket_department_upstream = \think\Db::name("ticket_department_upstream")->where("dptid", $id)->select()->toArray();
		$data["upstreams"] = array_column($ticket_department_upstream, "upstream_dptid", "api_id") ?: [];
		$custom_list = \think\Db::name("customfields")->where("relid", $id)->where("type", "ticket")->order("id", "desc");
		$custom_count = $custom_list->count();
		if ($custom_count) {
			$custom_list = $custom_list->page($this->page, $this->limit)->select()->toArray();
			foreach ($custom_list as $key => $val) {
				$custom_list[$key]["fieldname_zn"] = $this->custom_param_type[$val["fieldname"]] ?? "";
			}
			$data["customfields"] = ["count" => $custom_count, "list" => $custom_list];
		} else {
			$data["customfields"] = ["count" => 0, "list" => []];
		}
		$result["status"] = 200;
		$result["data"] = $data;
		return jsonrule($result);
	}
	/**
	 * @title 获取自定义字段类型
	 * @description 获取自定义字段类型
	 * @author xue
	 * @url         admin/get_custom_param_type
	 * @method      GET
	 * @time        2021-3-29
	 */
	public function getCustomParamType()
	{
		return jsonrule(["status" => 200, "data" => $this->custom_param_type]);
	}
	/**
	 * @title 添加自定义字段
	 * @description 添加自定义字段
	 * @author xue
	 * @url         admin/add_ticket_custom_param
	 * @method      GET
	 * @time        2021-3-29
	 * @param       .name:fieldname type:string require:1 default: '': desc:字段名称
	 * @param       .name:fieldtype type:string require:1 default: '': desc:字段类型
	 * @param       .name:description type:string require:1 default: '': desc:字段描述
	 * @param       .name:ticketId type:int require:1 default: '': desc:工单ID
	 * @param       .name:fieldoptions type:string require:0 default: '': desc:下拉选项内容，逗号隔开
	 */
	public function addTicketCustomParam()
	{
		try {
			$params = $this->request->param();
			$rule = ["fieldname" => "require", "fieldtype" => "require", "description" => "require", "ticketId" => "require"];
			$msg = ["fieldname.require" => "字段名称不能为空", "fieldtype.require" => "字段类型不能为空", "description.require" => "字段描述不能为空", "ticketId.require" => "工单部门ID不能为空"];
			$validate = new \think\Validate($rule, $msg);
			$validate_result = $validate->check($params);
			if (!$validate_result) {
				return jsonrule(["status" => 406, "msg" => $validate->getError()]);
			}
			$model = \think\Db::name("customfields")->where(["fieldname" => $params["fieldname"], "type" => "ticket"])->find();
			if ($model) {
				throw new \think\Exception("字段名称已存在!");
			}
			if ($params["fieldtype"] == "dropdown" && trim($params["fieldoptions"]) == "") {
				throw new \think\Exception("请输入下拉选项!");
			}
			if (!\think\Db::name("ticket_department")->find($params["ticketId"])) {
				throw new \think\Exception("该工单部门不存在!");
			}
			$data = ["type" => "ticket", "relid" => $params["ticketId"], "fieldname" => $params["fieldname"], "fieldtype" => $params["fieldtype"], "description" => $params["description"], "fieldoptions" => $params["fieldoptions"] ?: "", "regexpr" => $params["regexpr"] ?: "", "adminonly" => $params["adminonly"] ?: "", "required" => $params["required"] ?: "", "sortorder" => $params["sortorder"] ?: 0, "create_time" => time()];
			\think\Db::name("customfields")->insert($data);
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		} catch (\Throwable $e) {
			return jsonrule(["status" => 406, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 获取修改自定义字段的值
	 * @description 获取修改自定义字段的值
	 * @author xue
	 * @url         admin/get_ticket_param_val
	 * @method      GET
	 * @time        2021-3-29
	 * @param       .name:fieldId type:int require:1 default: '': desc:自定义字段ID
	 */
	public function getTicketParamVal()
	{
		try {
			$params = $this->request->param();
			if (!$params["fieldId"]) {
				throw new \think\Exception("自定义字段id不能为空");
			}
			$model = \think\Db::name("customfields")->find($params["fieldId"]);
			if (!$model) {
				throw new \think\Exception("数据不存在!");
			}
			return jsonrule(["status" => 200, "msg" => "success", "data" => $model]);
		} catch (\Throwable $e) {
			return jsonrule(["status" => 406, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 修改自定义字段
	 * @description 修改自定义字段
	 * @author xue
	 * @url         admin/edit_ticket_custom_param
	 * @method      GET
	 * @time        2021-3-29
	 * @param       .name:fieldname type:string require:1 default: '': desc:字段名称
	 * @param       .name:fieldtype type:string require:1 default: '': desc:字段类型
	 * @param       .name:description type:string require:1 default: '': desc:字段描述
	 * @param       .name:ticketId type:int require:1 default: '': desc:工单ID
	 * @param       .name:fieldoptions type:string require:0 default: '': desc:下拉选项内容，逗号隔开
	 * @param       .name:fieldId type:int require:1 default: '': desc:自定义字段ID
	 */
	public function editTicketCustomParam()
	{
		try {
			$params = $this->request->param();
			$rule = ["fieldname" => "require", "fieldtype" => "require", "description" => "require", "ticketId" => "require", "fieldId" => "require"];
			$msg = ["fieldname.require" => "字段名称不能为空", "fieldtype.require" => "字段类型不能为空", "description.require" => "字段描述不能为空", "ticketId.require" => "工单部门ID不能为空", "fieldId.require" => "自定义字段id不能为空"];
			$validate = new \think\Validate($rule, $msg);
			$validate_result = $validate->check($params);
			if (!$validate_result) {
				return jsonrule(["status" => 406, "msg" => $validate->getError()]);
			}
			$model = \think\Db::name("customfields")->where(["fieldname" => $params["fieldname"], "type" => "ticket"])->where("id", "<>", $params["fieldId"])->find();
			if ($model) {
				throw new \think\Exception("字段名称已存在!");
			}
			if ($params["fieldtype"] == "dropdown" && trim($params["fieldoptions"]) == "") {
				throw new \think\Exception("请输入下拉选项!");
			}
			if (!\think\Db::name("ticket_department")->find($params["ticketId"])) {
				throw new \think\Exception("该工单部门不存在!");
			}
			$data = ["type" => "ticket", "relid" => $params["ticketId"], "fieldname" => $params["fieldname"], "fieldtype" => $params["fieldtype"], "description" => $params["description"], "fieldoptions" => $params["fieldoptions"] ?: "", "regexpr" => $params["regexpr"] ?: "", "adminonly" => $params["adminonly"] ?: "", "required" => $params["required"] ?: "", "sortorder" => $params["sortorder"] ?: 0, "create_time" => time()];
			\think\Db::name("customfields")->where("id", $params["fieldId"])->update($data);
			return jsonrule(["status" => 200, "msg" => "success"]);
		} catch (\Throwable $e) {
			return jsonrule(["status" => 406, "msg" => $e->getMessage()]);
		}
	}
	/**
	 * @title 删除自定义字段
	 * @description 删除自定义字段
	 * @author xue
	 * @url         admin/del_ticket_custom_param
	 * @method      GET
	 * @time        2021-3-29
	 * @param       .name:fieldId type:int require:1 default: '': desc:自定义字段ID
	 */
	public function delTicketCustomParam()
	{
		try {
			$params = $this->request->param();
			if (!$params["fieldId"]) {
				throw new \think\Exception("自定义字段id不能为空");
			}
			\think\Db::startTrans();
			\think\Db::name("customfieldsvalues")->where("fieldid", $params["fieldId"])->delete();
			\think\Db::name("customfields")->delete($params["fieldId"]);
			\think\Db::commit();
			return jsonrule(["status" => 200, "msg" => "删除成功"]);
		} catch (\Throwable $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 406, "msg" => $e->getMessage()]);
		}
	}
}