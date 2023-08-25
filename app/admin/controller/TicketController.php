<?php

namespace app\admin\controller;

/**
 * @title 后台工单
 * @description 接口说明
 */
class TicketController extends GetUserController
{
	/**
	 * @title 新建工单页面
	 * @param  .name:tid type:int require:1 default: other: desc:工单id
	 * @description 新建工单页面
	 * @return      .client.id:用户id
	 * @return      .client.username:用户名
	 * @return      .client.email:邮箱
	 * @return      .client.companyname:公司名
	 * @return      .department.id:部门id
	 * @return      .department.name:部门名称
	 * @return      .custom_arr 自定义参数列表
	 * @author huanghao
	 * @url         admin/add_ticket_page
	 * @method      GET
	 * @time        2019-11-27
	 */
	public function createPage()
	{
		$params = input("get.");
		$id = intval($params["tid"]);
		$ticket = \think\Db::name("ticket")->field("uid,id")->where("tid", $id)->find();
		$data["client"] = \think\Db::name("clients")->field("id,username,email,companyname")->where("id", $ticket["uid"])->find();
		$allow = model("TicketDepartmentAdmin")->getAllow();
		$data["department"] = \think\Db::name("ticket_department")->field("id,name")->where("hidden", 0)->whereIn("id", $allow)->select()->toArray();
		$custom_arr = \think\Db::name("customfieldsvalues")->field("A.value,B.fieldname")->alias("A")->join("customfields B", "A.fieldid = B.id")->where("A.relid", $ticket["id"])->select()->toArray();
		$data["custom_arr"] = $custom_arr;
		$data["user"] = \think\Db::name("user")->find(cmf_get_current_admin_id());
		$result["status"] = 200;
		$result["msg"] = lang("SUCCESS MESSAGE");
		$result["data"] = $data;
		return jsonrule($result);
	}
	/**
	 * @title 新建工单
	 * @description 新建工单
	 * @param       .name:uid type:int require:0 default: other: desc:用户id
	 * @param       .name:name type:string require:0 default: other: desc:用户名称
	 * @param       .name:email type:string require:0 default: other: desc:邮箱地址
	 * @param       .name:send type:int require:0 default:0 other: desc:是否发送邮件
	 * @param       .name:cc type:string require:0 default: other: desc:抄送收件人
	 * @param       .name:title type:string require:1 default: other: desc:主题
	 * @param       .name:dptid type:int require:1 default: other: desc:部门id
	 * @param       .name:priority type:string require:0 default:medium other: desc:优先级high高,medium中,low低
	 * @param       .name:content type:string require:1 default: other: desc:内容
	 * @param       .name:attachment type:file&array require:0 default: other: desc:附件
	 * @author huanghao
	 * @url         admin/add_ticket
	 * @method      POST
	 * @time        2019-11-27
	 */
	public function add()
	{
		$params = input("post.");
		$rule = ["title" => "require", "dptid" => "require", "priority" => "in:high,medium,low", "content" => "require|length:0,10000", "email" => "email"];
		$msg = ["title.require" => lang("TICKET_TITLE_EMPTY"), "dptid.require" => lang("TICKET_DEPARTMENT_EMPTY"), "priority.in" => lang("TICKET_PRIORITY_ERROR"), "content.require" => lang("TICKET_CONTENT_EMPTY"), "email.email" => lang("EMAIL_FORMAT_ERROR")];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$params["dptid"] = intval($params["dptid"]);
		$data = [];
		if (!empty($params["uid"])) {
			$r = \think\Db::name("clients")->where("id", $params["uid"])->where("status", 1)->find();
			if (empty($r)) {
				$result["status"] = 406;
				$result["msg"] = lang("ID_ERROR");
				return jsonrule($result);
			}
			$params["name"] = $r["username"];
			$params["email"] = $r["email"];
			$data["uid"] = $params["uid"];
			$data["name"] = "";
			$data["email"] = "";
		} else {
			if (empty($params["name"]) || empty($params["email"])) {
				$result["status"] = 406;
				$result["msg"] = lang("TICKET_MUST_HAVE_USER");
				return jsonrule($result);
			}
			$data["name"] = $params["name"];
			$data["email"] = $params["email"];
		}
		if (!model("TicketDepartmentAdmin")->check($params["dptid"])) {
			$result["status"] = 406;
			$result["msg"] = lang("您没有此部门权限");
			return jsonrule($result);
		}
		$upload = new \app\common\logic\Upload();
		foreach ($params["attachment"] as $v) {
			$tmp = $upload->moveTo($v, config("ticket_attachments"));
			if (isset($tmp["error"])) {
				return jsonrule(["status" => 400, "msg" => $tmp["error"]]);
			}
		}
		$admin = cmf_get_current_admin_id();
		$admin_info = \think\Db::name("user")->field("id,user_login")->where("id", $admin)->find();
		$data["dptid"] = $params["dptid"];
		$data["attachment"] = implode(",", $params["attachment"]);
		$data["create_time"] = time();
		$data["title"] = $params["title"];
		$data["content"] = model("Ticket")->parse($params["content"], ["name" => $params["name"], "email" => $params["email"]]);
		$data["status"] = 1;
		$data["admin_id"] = cmf_get_current_admin_id();
		$data["priority"] = !empty($params["priority"]) ? $params["priority"] : "medium";
		$data["admin"] = $admin_info["user_login"] ?: "";
		$data["client_unread"] = 1;
		$data["tid"] = model("Ticket")->getTid();
		$data["c"] = cmf_random_str(8, "lower_upper_number");
		$data["cc"] = $params["cc"] ?: "";
		$data["last_reply_time"] = time();
		$r = \think\Db::name("ticket")->insertGetId($data);
		if ($r) {
			if (!empty($params["send"])) {
				$email = new \app\common\logic\Email();
				$email->is_admin = true;
				$email->sendEmailBase($r, "工单创建成功", "support");
			}
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$sms = new \app\common\logic\Sms();
			$client = check_type_is_use($message_template_type[strtolower("Support_Ticket_Opened")], $data["uid"], $sms);
			if ($client) {
				$params = ["ticket_createtime" => date("Y-m-d H:i:s", time()), "ticketnumber_tickettitle" => $data["title"]];
				$sms->sendSms($message_template_type[strtolower("Support_Ticket_Opened")], $client["phone_code"] . $client["phonenumber"], $params, false, $data["uid"]);
			}
			model("SystemLog")->log(lang("TICKET_ADD_SUCCESS_ADMIN", [$data["tid"]]), "ticket", $r);
			$data["attachment"] = array_filter(explode(",", $data["attachment"]));
			foreach ($data["attachment"] as $k => $v) {
				$data["attachment"][$k] = config("ticket_attachments") . $v;
			}
			$hook_data = ["ticketid" => $r, "tid" => $data["tid"], "uid" => \intval($data["uid"]), "dptid" => $data["dptid"], "dptname" => \think\Db::name("ticket_department")->where("id", $data["dptid"])->value("name"), "title" => html_entity_decode($data["title"], ENT_QUOTES), "content" => html_entity_decode($data["content"], ENT_QUOTES), "priority" => $data["priority"], "attachment" => $data["attachment"] ?: []];
			hook("ticket_open_admin", $hook_data);
		}
		$result["status"] = 200;
		$result["msg"] = lang("ADD SUCCESS");
		$result["data"] = $r;
		if (!empty($params["uid"])) {
			active_log(sprintf($this->lang["Ticket_admin_add"], $params["uid"], $r, $data["title"]), $params["uid"]);
		} else {
			active_log(sprintf($this->lang["Ticket_admin_add1"], $r, $data["title"]));
		}
		return jsonrule($result);
	}
	/**
	 * @title 工单列表
	 * @description 工单列表
	 * @param       .name:tid type:string require:0 default: other: desc:tid
	 * @param       .name:email type:string require:0 default: other: desc:邮件地址
	 * @param       .name:content type:string require:0 default: other: desc:主题/内容
	 * @param       .name:priority type:string require:0 default:all other: desc:优先级
	 * @param       .name:dptid type:int require:0 default: other: desc:部门id
	 * @param       .name:uid type:int require:0 default: other: desc:客户id
	 * @param       .name:status type:string require:0 default:all other: desc:状态
	 * @param       .name:limit type:int require:0 default:10 other: desc:条数
	 * @param       .name:page type:int require:0 default:1 other: desc:页数
	 * @return      .limit:每页条数
	 * @return      .page:当前页数
	 * @return      .sum:总条数
	 * @return      .max_page:最大页数
	 * @return      .list.id:工单id
	 * @return      .list.tid:工单tid
	 * @return      .list.uid:发起工单的用户id
	 * @return      .list.title:工单标题
	 * @return      .list.status:工单状态
	 * @return      .list.last_reply_time:最后回复时间戳
	 * @return      .list.flag_admin:标记的管理员名称
	 * @return      .list.department_name:部门名称
	 * @return      .list.user_name:发起工单的用户名
	 * @return      .list.format_time:格式化的最后回复时间
	 * @author huanghao
	 * @url         admin/list_ticket
	 * @method      GET
	 * @time        2019-11-27
	 */
	public function getList()
	{
		$params = $this->request->param();
		$limit = input("get.limit", 50, "int");
		$page = input("get.page", 1, "int");
		$order = isset($params["order"][0]) ? trim($params["order"]) : "a.last_reply_time";
		$sort = isset($params["sort"][0]) ? trim($params["sort"]) : "DESC";
		$where[] = ["merged_ticket_id", "=", "0"];
		$tmp = model("TicketDepartmentAdmin")->getAllow();
		if (isset($params["dptid"][0]) && in_array($params["dptid"], $tmp)) {
			$where[] = ["dptid", "=", $params["dptid"], "AND"];
		} else {
			$where[] = ["dptid", "in", $tmp, "AND"];
		}
		if (isset($params["uid"][0]) && $params["uid"] > 0) {
			$where[] = ["a.uid", "=", \intval($params["uid"]), "AND"];
		}
		if (isset($params["priority"][0])) {
			$where[] = ["priority", "=", $params["priority"], "AND"];
		}
		if (isset($params["email"][0])) {
			$where[] = ["email", "=", $params["email"], "AND"];
		}
		if (isset($params["content"][0])) {
			$tmp = sprintf("%%%s%%", $params["content"]);
			$where[] = ["content|a.title", "like", $tmp, "OR"];
		}
		if (isset($params["tid"][0]) && $params["tid"] > 0) {
			$where[] = ["tid", "=", \intval($params["tid"]), "AND"];
		}
		if (isset($params["status"]) && $params["status"] > 0) {
			if ($params["status"] == 1) {
				$params["status"] = [1, 3];
			}
			$params["status"] = is_array($params["status"]) ? $params["status"] : [$params["status"]];
			$where[] = ["a.status", "in", $params["status"], "AND"];
		} else {
			if (!isset($params["status"]) || $params["status"] == "pending") {
				$where[] = ["a.status", "in", [1, 3, 5], "AND"];
			}
		}
		$data = \think\Db::name("ticket")->alias("a")->field("a.id,a.tid,a.uid,a.admin_unread,a.title,a.name,a.status,a.create_time,a.is_deliver,a.is_receive,a.handle,t.color as statusColor,t.title as status_title,a.last_reply_time,b.user_login flag_admin,c.name department_name,d.username user_name,e.user_login handle_name")->leftJoin("user b", "a.flag=b.id")->leftJoin("ticket_department c", "a.dptid=c.id")->leftJoin("clients d", "a.uid=d.id")->leftJoin("ticket_status t", "a.status = t.id")->leftJoin("user e", "a.handle=e.id")->where($where)->page($page)->order($order, $sort)->limit($limit)->select()->toArray();
		$count = \think\Db::name("ticket as a")->leftJoin("clients d", "a.uid=d.id")->where($where)->count();
		foreach ($data as $k => $v) {
			$data[$k]["title"] = html_entity_decode($v["title"], ENT_QUOTES);
			if ($v["status"] == 3 && $v["is_receive"] == 1) {
				$ticket_reply = \think\Db::name("ticket_reply")->where("tid", $v["id"])->order("id", "desc")->find();
				if ($ticket_reply["is_receive"] == 1 && $ticket_reply["source"] == 0) {
					$data[$k]["deliver_status"] = 1;
					$data[$k]["deliver_status_title"] = "下游已回复";
				}
			} else {
				if ($v["status"] == 2 && $v["is_deliver"] == 1) {
					$ticket_reply = \think\Db::name("ticket_reply")->where("tid", $v["id"])->order("id", "desc")->find();
					if ($ticket_reply["is_receive"] == 1 && $ticket_reply["source"] == 1) {
						$data[$k]["deliver_status"] = 2;
						$data[$k]["deliver_status_title"] = "上游已回复";
					}
				}
			}
			$data[$k]["user_name"] = !empty($v["uid"]) ? $v["user_name"] : $v["name"];
			$data[$k]["format_time"] = date("Y-m-d H:i:s", $v["last_reply_time"]);
			$data[$k]["flag_admin"] = $v["flag_admin"] ?: "";
			unset($data[$k]["name"]);
		}
		$status = \think\Db::name("ticket_status")->select()->toArray();
		$result["status"] = 200;
		$result["msg"] = lang("SUCCESS MESSAGE");
		$result["data"] = ["limit" => $limit, "ticket_status" => $status, "page" => $page, "sum" => $count, "max_page" => ceil($count / $limit), "list" => $data];
		return jsonrule($result);
	}
	/**
	 * @title 获取客户工单列表
	 * @description 获取客户工单列表
	 * @param .name:uid type:int require:1 default: other: desc:用户id
	 * @param .name:hostid type:int require:1 default: other: desc:产品id
	 * @param .name:order type:string require:1 default:last_reply_time other: desc:排序字段
	 * @param .name:sort type:int require:1 default:DESC other: desc:排序类型
	 * @param .name:page type:int require:1 default:1 other: desc:页码
	 * @param .name:limit type:int require:1 default:10 other: desc:分页条数
	 * @return ticket_data:工单列表数据@
	 * @ticket_data  id:工单id
	 * @ticket_data  tid:工单号
	 * @ticket_data  dptid:部门id
	 * @ticket_data  title:工单名
	 * @ticket_data  status:状态
	 * @ticket_data  create_time:创建时间
	 * @ticket_data  depart_name:工单名
	 * @ticket_data  last_replay:上次回复
	 * @return  opened_this_month:这个月打开工单
	 * @return  opened_last_month:上个月打开工单
	 * @return  opened_this_year:今年打开工单
	 * @return  opened_last_year:去年打开工单
	 * @author 萧十一郎
	 * @url         admin/client_ticket
	 * @method      GET
	 */
	public function getClientTicketPage()
	{
		$params = $this->request->param();
		if (empty($params["uid"])) {
			return jsonrule(["status" => 406, "msg" => "用户不能为空"]);
		}
		$order = isset($params["order"][0]) ? trim($params["order"]) : "last_reply_time";
		$sort = isset($params["sort"][0]) ? trim($params["sort"]) : "DESC";
		$page = max($params["page"], 1);
		$limit = max($params["limit"], 10);
		$where = $this->get_search($params);
		$ticket_data = \think\Db::name("ticket")->field("t.id,t.tid,t.dptid,t.title,t.status,t.create_time,t.last_reply_time,d.name as depart_name")->alias("t")->leftJoin("ticket_department d", "d.id=t.dptid")->where($where)->page($page, $limit)->order($order, $sort)->select()->toArray();
		$returndata = [];
		$opened_this_month = mktime(0, 0, 0, date("m"), 0, date("Y"));
		$returndata["opened_this_month"] = \think\Db::name("ticket")->where(array_merge($where, [["create_time", ">=", $opened_this_month]]))->count();
		if (date("m") != 1) {
			$opened_last_month = mktime(0, 0, 0, date("m") - 1, 0, date("Y"));
		} else {
			$opened_last_month = mktime(0, 0, 0, 12, 0, date("Y") - 1);
		}
		$returndata["opened_last_month"] = \think\Db::name("ticket")->where(array_merge($where, [["create_time", "<", $opened_this_month], ["create_time", ">=", $opened_last_month]]))->count();
		$opened_this_year = mktime(0, 0, 0, 0, 0, date("Y"));
		$returndata["opened_this_year"] = \think\Db::name("ticket")->where(array_merge($where, [["create_time", ">=", $opened_this_year]]))->count();
		$opened_last_year = mktime(0, 0, 0, 0, 0, date("Y") - 1);
		$returndata["opened_last_year"] = \think\Db::name("ticket")->where(array_merge($where, [["create_time", "<", $opened_this_year], ["create_time", ">=", $opened_last_year]]))->count();
		$count = \think\Db::name("ticket")->where($where)->count();
		$returndata["meta"] = ["total" => $count, "total_page" => ceil($count / $limit), "page" => $page, "limit" => $limit];
		$status = \think\Db::name("ticket_status")->select()->toArray();
		$tmp = array_column($status, null, "id");
		foreach ($ticket_data as $key => $val) {
			$tid = $val["id"];
			$ticket_data[$key]["status"] = $tmp[$val["status"]];
			$replay_data = \think\Db::name("ticket_reply as t")->field("create_time")->where("tid", $tid)->order("create_time", "DESC")->find();
			if (empty($replay_data)) {
				$ticket_data[$key]["last_replay"] = "未回复";
			} else {
				$ticket_data[$key]["last_replay"] = friend_date($replay_data["create_time"]);
			}
		}
		$returndata["ticket_data"] = $ticket_data;
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	private function get_search($data)
	{
		$where = [["merged_ticket_id", "=", 0]];
		if (isset($data["productid"]) && $data["productid"] > 0) {
			if (isset($data["uid"]) && $data["uid"] > 0) {
				$tmp = \think\Db::name("host")->where([["productid", "", $data["productid"]], ["uid", "=", $data["uid"]]])->select();
				unset($data["uid"]);
			} else {
				$tmp = \think\Db::name("host")->where("productid", "=", $data["productid"])->select();
			}
			if (isset($tmp[0])) {
				$tmp1 = array_column($tmp->toArray(), "id");
				if (isset($data["hostid"]) && $data["hostid"] > 0) {
					$tmp1[] = $data["hostid"];
					unset($data["hostid"]);
				}
				$where[] = ["hostid", "in", $tmp1];
			}
		}
		if (isset($data["uid"]) && $data["uid"] > 0) {
			$where[] = ["uid", "=", $data["uid"]];
		}
		if (isset($data["hostid"]) && $data["hostid"] > 0) {
			$where[] = ["host_id", "=", $data["hostid"]];
		}
		return $where;
	}
	/**
	 * @title 回复工单
	 * @description 回复工单
	 * @param       .name:id type:int require:1 default: other: desc:工单id
	 * @param       .name:content type:string require:1 default: other: desc:回复内容
	 * @author huanghao
	 * @url         admin/reply_ticket
	 * @method      POST
	 * @time        2019-11-27
	 */
	public function reply()
	{
		$params = input("post.");
		if (empty($params["content"])) {
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_REPLY_CONTENT_EMPTY");
			return jsonrule($result);
		}
		$id = intval($params["id"]);
		$info = \think\Db::name("ticket")->where("id", $id)->where("merged_ticket_id", 0)->find();
		if (empty($info)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		if (!model("TicketDepartmentAdmin")->check($info["dptid"])) {
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_REPLY_NO_AUTH");
			return jsonrule($result);
		}
		$admin = cmf_get_current_admin_id();
		$upload = new \app\common\logic\Upload();
		foreach ($params["attachment"] as $v) {
			$tmp = $upload->moveTo($v, config("ticket_attachments"));
			if (isset($tmp["error"])) {
				return jsonrule(["status" => 400, "msg" => $tmp["error"]]);
			}
		}
		$admin_info = \think\Db::name("user")->field("user_login")->where("id", $admin)->find();
		$data["tid"] = $id;
		$data["admin_id"] = $admin;
		$data["create_time"] = time();
		$data["content"] = model("Ticket")->parse($params["content"], model("Ticket")->getUser());
		$data["admin"] = $admin_info["user_login"] ?: "";
		$data["attachment"] = implode(",", $params["attachment"]) ?? "";
		\think\Db::startTrans();
		try {
			\think\Db::name("ticket")->where("id", $id)->update(["last_reply_time" => time(), "client_unread" => 1, "status" => 2, "handle" => $admin, "handle_time" => time()]);
			$r = \think\Db::name("ticket_reply")->insertGetId($data);
			\think\Db::commit();
			pushTicketReply($r);
			$result["status"] = 200;
			$result["msg"] = lang("TICKET_REPLY_SUCCESS");
		} catch (\Exception $e) {
			\think\Db::rollback();
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_REPLY_FAILED");
		}
		if ($result["status"] == 200) {
			$arr_admin = ["relid" => $id, "name" => "工单已回复提醒", "type" => "support", "sync" => true, "admin" => false, "ip" => get_client_ip6()];
			$curl_multi_data[0] = ["url" => "async", "data" => $arr_admin];
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$sms = new \app\common\logic\Sms();
			$client = check_type_is_use($message_template_type["ticket_reply"], $info["uid"], $sms);
			if ($client) {
				$params = ["subject" => $info["title"]];
				$arr = ["name" => $message_template_type[strtolower("ticket_reply")], "phone" => $client["phone_code"] . $client["phonenumber"], "params" => json_encode($params), "sync" => false, "uid" => $info["uid"], "username" => $client["username"], "delay_time" => 0, "is_market" => false];
				$curl_multi_data[count($curl_multi_data)] = ["url" => "async_sms", "data" => $arr];
			}
			asyncCurlMulti($curl_multi_data);
			active_log(sprintf($this->lang["Ticket_admin_reply"], $info["uid"], $id, $info["title"]), $info["uid"]);
			model("SystemLog")->log(lang("TICKET_REPLY_SUCCESS_LOG", [$info["tid"]]), "ticket", $id);
			$department = \think\Db::name("ticket_department")->field("id,name")->where("id", $info["dptid"])->find();
			$hook_data = ["ticketid" => $id, "replyid" => $r, "dptid" => $department["id"], "dptname" => $department["name"], "title" => html_entity_decode($info["title"], ENT_QUOTES), "content" => html_entity_decode($data["content"], ENT_QUOTES), "priority" => $info["priority"], "admin" => $data["admin"], "status" => 2, "status_title" => \think\Db::name("ticket_status")->where("id", 2)->value("title")];
			hook("ticket_admin_reply", $hook_data);
			$host_obj = new \app\common\logic\Host();
			$data["id"] = $r;
			$host_obj->replyTicket($info["host_id"], $data);
		} else {
			model("SystemLog")->log(lang("TICKET_REPLY_FAILED_LOG", [$info["tid"]]), "ticket", $id);
		}
		return jsonrule($result);
	}
	/**
	 * @title 编辑工单回复
	 * @description 编辑工单回复
	 * @param       .name:id type:int require:1 default: other: desc:工单回复id|工单id
	 * @param       .name:content type:int require:1 default: other: desc:新内容
	 * @param       .name:type type:string require:1 default: other: desc:类型t工单,r回复
	 * @author huanghao
	 * @url         admin/save_ticket_reply
	 * @method      POST
	 * @time        2019-11-29
	 */
	public function saveReply()
	{
		$id = intval(input("post.id"));
		$content = input("post.content", "");
		$type = input("post.type");
		if (empty($content)) {
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_REPLY_CONTENT_EMPTY");
			return jsonrule($result);
		}
		if ($type == "r") {
			$info = \think\Db::name("ticket_reply")->field("id,tid,content")->where("id", $id)->find();
			if (empty($info)) {
				$result["status"] = 406;
				$result["msg"] = lang("ID_ERROR");
				return jsonrule($result);
			}
			$ticket = \think\Db::name("ticket")->where("id", $info["tid"])->where("merged_ticket_id", 0)->find();
		} elseif ($type == "t") {
			$ticket = \think\Db::name("ticket")->where("id", $id)->where("merged_ticket_id", 0)->find();
		} else {
			$result["status"] = 406;
			$result["msg"] = lang("TYPE_ERROR");
			return jsonrule($result);
		}
		if (empty($ticket)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		if (!model("TicketDepartmentAdmin")->check($ticket["dptid"])) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		if ($type == "r") {
			$r = \think\Db::name("ticket_reply")->where("id", $id)->update(["content" => $content]);
		} elseif ($type == "t") {
			$r = \think\Db::name("ticket")->where("id", $id)->update(["content" => $content]);
		}
		if ($r) {
			model("SystemLog")->log(lang("TICKET_MODIFY_REPLY_SUCCESS_LOG", [$ticket["tid"]]), "ticket", $ticket["id"]);
		}
		active_log(sprintf($this->lang["Ticket_admin_saveReply"], $ticket["uid"], $id, $ticket["title"]), $ticket["uid"]);
		$result["status"] = 200;
		$result["msg"] = lang("UPDATE SUCCESS");
		return jsonrule($result);
	}
	/**
	 * @title 合并工单
	 * @description 合并工单
	 * @param       .name:id type:array require:1 default: other: desc:工单id
	 * @author huanghao
	 * @url         admin/merge_ticket
	 * @method      POST
	 * @time        2019-11-29
	 */
	public function mergeTicket()
	{
		$id = array_filter(array_unique(input("post.id")), function ($x) {
			return is_numeric($x) && $x > 0;
		});
		if (empty($id)) {
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_MERGE_NONE");
			return jsonrule($result);
		}
		if (count($id) < 2) {
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_MERGE_NEED_TWO_OR_MORE");
			return jsonrule($result);
		}
		$allow = model("TicketDepartmentAdmin")->getAllow();
		$ticket = \think\Db::name("ticket")->field("id,tid,uid")->whereIn("id", $id)->whereIn("dptid", $allow)->where("merged_ticket_id", 0)->select()->toArray();
		if (empty($ticket)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$id = array_column($ticket, "id");
		sort($id);
		$ticketid = array_shift($id);
		$r = \think\Db::name("ticket")->whereIn("id", $id)->update(["merged_ticket_id" => $ticketid, "client_unread" => 0, "admin_unread" => 0]);
		if ($r) {
			\think\Db::name("ticket_note")->whereIn("tid", $id)->update(["tid" => $ticketid]);
			\think\Db::name("ticket_reply")->whereIn("tid", $id)->update(["tid" => $ticketid]);
			$tickets = array_column($ticket, "tid");
			$merged = array_shift($tickets);
			model("SystemLog")->log(lang("TICKET_MERGE_SUCCESS", [implode(",", $tickets), $merged]), "ticket", $ticketid);
			foreach ($tickets as $tic => $value) {
				active_log(sprintf($this->lang["Ticket_admin_mergeTicket"], $value["uid"], $value["id"]), $value["uid"]);
			}
		}
		$result["status"] = 200;
		$result["msg"] = lang("SUCCESS MESSAGE");
		return jsonrule($result);
	}
	/**
	 * @title 修正工单
	 * @description 修正状态
	 * @param       .name:id type:array require:1 default: other: desc:工单id
	 * @param       .name:status type:string require:1 default: other: desc:状态
	 * @author huanghao
	 * @url         admin/close_ticket
	 * @method      POST
	 * @time        2019-11-29
	 */
	public function closeTicket()
	{
		$id = array_filter(array_unique(input("post.id")), function ($x) {
			return is_numeric($x) && $x > 0;
		});
		if (empty($id)) {
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_CLOSE_NONE");
			return jsonrule($result);
		}
		$status = $this->request->param("status", 4);
		$status = \think\Db::name("ticket_status")->where("id", $status)->find();
		if (!isset($status["id"])) {
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_CLOSE_NONE");
			return jsonrule($result);
		}
		$allow = model("TicketDepartmentAdmin")->getAllow();
		$change_status_ticket = \think\Db::name("ticket")->whereIn("id", $id)->where("status", "<>", $status["id"])->where("merged_ticket_id", 0)->whereIn("dptid", $allow)->column("id");
		$r = \think\Db::name("ticket")->whereIn("id", $id)->where("merged_ticket_id", 0)->whereIn("dptid", $allow)->update(["status" => $status["id"], "update_time" => time()]);
		if ($r) {
			$tickets = \think\Db::name("ticket")->field("id,uid,tid")->whereIn("id", $id)->whereIn("dptid", $allow)->where("merged_ticket_id", 0)->select()->toArray();
			foreach ($tickets as $tic => $value) {
				active_log(sprintf($this->lang["Ticket_admin_closeTicket"], $value["uid"], $value["id"], $status["title"]), $value["uid"]);
			}
			model("SystemLog")->log(lang("TICKET_CLOSE_SUCCESS_LOG", [implode(",", array_column($tickets, "tid"))]));
			if (!empty($change_status_ticket)) {
				$hook_data = ["ticketid" => $change_status_ticket, "status" => $status["id"], "status_title" => $status["title"], "adminid" => cmf_get_current_admin_id()];
				hook("ticket_status_change", $hook_data);
			}
			if ($status["id"] == 4) {
				hook("ticket_close", ["ticketid" => $id]);
			}
		}
		$result["status"] = 200;
		$result["msg"] = lang("SUCCESS MESSAGE");
		return jsonrule($result);
	}
	/**
	 * @title 删除工单
	 * @description 删除工单
	 * @param       .name:id type:array require:1 default: other: desc:工单id
	 * @author huanghao
	 * @url         admin/delete_ticket
	 * @method      POST
	 * @time        2019-11-29
	 */
	public function deleteTicket()
	{
		$id = array_filter(array_unique(input("post.id")), function ($x) {
			return is_numeric($x) && $x > 0;
		});
		if (empty($id)) {
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_DELETE_EMPTY");
			return jsonrule($result);
		}
		$allow = model("TicketDepartmentAdmin")->getAllow();
		$ticket = \think\Db::name("ticket")->field("id,tid,attachment,dptid,uid,title")->whereIn("id", $id)->whereIn("dptid", $allow)->where("merged_ticket_id", 0)->select()->toArray();
		if (empty($ticket)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$id = array_column($ticket, "id");
		$attachment = array_column($ticket, "attachment") ?: [];
		$merged_ticket = \think\Db::name("ticket")->field("id,attachment,dptid")->whereIn("merged_ticket_id", $id)->select()->toArray();
		$note = \think\Db::name("ticket_note")->field("id,tid,attachment")->whereIn("tid", $id)->select()->toArray();
		$reply = \think\Db::name("ticket_reply")->field("id,tid,attachment")->whereIn("tid", $id)->select()->toArray();
		$merged_ticket_attachment = array_column($merged_ticket, "attachment") ?: [];
		$note_attachment = array_column($note, "attachment") ?: [];
		$reply_attachment = array_column($reply, "attachment") ?: [];
		$attachment = array_filter(array_merge($attachment, $merged_ticket_attachment, $note_attachment, $reply_attachment));
		$delete = array_merge($id, array_column($merged_ticket, "id"));
		$dptid = array_merge(array_column($ticket, "dptid"), array_column($merged_ticket, "dptid"));
		$fields = \think\Db::name("customfields")->where("type", "ticket")->whereIn("relid", $dptid)->select()->toArray();
		\think\Db::startTrans();
		try {
			$r = \think\Db::name("ticket")->whereIn("id", $delete)->delete();
			\think\Db::name("ticket_note")->whereIn("tid", $delete)->delete();
			\think\Db::name("ticket_reply")->whereIn("tid", $delete)->delete();
			foreach ($ticket as $tic => $value) {
				active_log(sprintf($this->lang["Ticket_admin_deleteTicket"], $value["uid"], $value["id"], $value["title"]), $value["uid"]);
			}
			if (!empty($fields)) {
				\think\Db::name("customfieldsvalues")->whereIn("fieldid", array_column($fields, "id"))->whereIn("relid", $delete)->delete();
			}
			if ($r) {
				\think\Db::commit();
				$result["status"] = 200;
				$result["msg"] = lang("DELETE SUCCESS");
			} else {
				\think\Db::rollback();
				$result["status"] = 406;
				$result["msg"] = lang("DELETE FAIL");
			}
		} catch (\Exception $e) {
			\think\Db::rollback();
			$result["status"] = 406;
			$result["msg"] = lang("DELETE FAIL");
		}
		if ($result["status"] == 200) {
			$this->deleteAttachments($attachment);
			model("SystemLog")->log(lang("TICKET_DELETE_SUCCESS_LOG", [implode(",", array_column($ticket, "tid"))]));
			hook("ticket_delete", ["ticketid" => $id, "adminid" => cmf_get_current_admin_id()]);
		}
		return jsonrule($result);
	}
	/**
	 * @title 添加工单备注
	 * @description 添加工单备注
	 * @param       .name:id type:int require:1 default: other: desc:工单id
	 * @param       .name:content type:string require:1 default: other: desc:备注内容
	 * @author huanghao
	 * @url         admin/add_ticket_note
	 * @method      POST
	 * @time        2019-11-27
	 */
	public function addNote()
	{
		$params = input("post.");
		if (empty($params["content"]) || strlen($params) > 10000) {
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_NOTE_CONTENT_EMPTY");
			return jsonrule($result);
		}
		$id = intval($params["id"]);
		$info = \think\Db::name("ticket")->where("id", $id)->where("merged_ticket_id", 0)->find();
		if (empty($info)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		if (!model("TicketDepartmentAdmin")->check($info["dptid"])) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		if (isset($params["attachment"][0])) {
			$upload = new \app\common\logic\Upload();
			foreach ($params["attachment"] as $v) {
				$tmp = $upload->moveTo($v, config("ticket_attachments"));
				if (isset($tmp["error"])) {
					return jsonrule(["status" => 400, "msg" => $tmp["error"]]);
				}
			}
			$data["attachment"] = implode(",", $params["attachment"]);
		}
		$admin = cmf_get_current_admin_id();
		$admin_info = \think\Db::name("user")->field("user_login")->where("id", $admin)->find();
		$data["tid"] = $id;
		$data["create_time"] = time();
		$data["content"] = $params["content"];
		$data["admin"] = $admin_info["user_login"] ?: "";
		$r = \think\Db::name("ticket_note")->insert($data);
		$t = \think\Db::name("ticket")->where("id", $info["ticketid"])->find();
		if ($r) {
			model("SystemLog")->log(lang("TICKET_NOTE_ADD_SUCCESS_LOG", [$info["tid"]]), "ticket", $id);
			foreach ($params["attachment"] as $k => $v) {
				$params["attachment"][$k] = config("ticket_attachments") . $v;
			}
			$hook_data = ["ticketid" => $id, "content" => html_entity_decode($data["content"], ENT_QUOTES), "attachment" => $params["attachment"] ?? [], "adminid" => $admin];
			hook("ticket_add_note", $hook_data);
		}
		active_log(sprintf($this->lang["Ticket_admin_addNote"], $info["uid"], $id, $t["title"]), $info["uid"]);
		$result["status"] = 200;
		$result["msg"] = lang("ADD SUCCESS");
		return jsonrule($result);
	}
	/**
	 * @title 删除工单备注
	 * @description 删除工单备注
	 * @param       .name:id type:int require:1 default: other: desc:工单备注id
	 * @author huanghao
	 * @url         admin/delete_ticket_note
	 * @method      POST
	 * @time        2019-11-29
	 */
	public function deleteNote()
	{
		$id = input("post.id");
		$info = \think\Db::name("ticket_note")->field("t.uid,tn.id,tn.attachment,t.tid,t.dptid,t.id ticketid")->alias("tn")->leftJoin("ticket t", "tn.tid=t.id")->where("tn.id", $id)->where("t.merged_ticket_id", 0)->find();
		if (empty($info)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		if (!model("TicketDepartmentAdmin")->check($info["dptid"])) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$r = \think\Db::name("ticket_note")->where("id", $id)->delete();
		$t = \think\Db::name("ticket")->where("id", $info["ticketid"])->find();
		if ($r) {
			$this->deleteAttachments(explode(",", $info["attachment"]));
			model("SystemLog")->log(lang("TICKET_NOTE_DELETE_SUCCESS_LOG", [$info["tid"]]), "ticket", $info["ticketid"]);
		}
		active_log(sprintf($this->lang["Ticket_admin_deleteNote"], $info["uid"], $id, $t["title"]), $info["uid"]);
		$result["status"] = 200;
		$result["msg"] = lang("DELETE SUCCESS");
		return jsonrule($result);
	}
	/**
	 * @title 删除工单回复
	 * @description 删除工单回复
	 * @param       .name:id type:int require:1 default: other: desc:工单备注id
	 * @author huanghao
	 * @url         admin/delete_ticket_reply
	 * @method      POST
	 * @time        2019-11-29
	 */
	public function deleteReply()
	{
		$id = input("post.id");
		$info = \think\Db::name("ticket_reply")->field("t.uid,tn.id,tn.attachment,t.tid,t.dptid,t.id ticketid")->alias("tn")->leftJoin("ticket t", "tn.tid=t.id")->where("tn.id", $id)->where("t.merged_ticket_id", 0)->find();
		if (empty($info)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		if (!model("TicketDepartmentAdmin")->check($info["dptid"])) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$r = \think\Db::name("ticket_reply")->where("id", $id)->delete();
		$t = \think\Db::name("ticket")->where("id", $info["ticketid"])->find();
		if ($r) {
			$this->deleteAttachments(explode(",", $info["attachment"]));
			model("SystemLog")->log(lang("TICKET_REPLY_DELETE_SUCCESS_LOG", [$info["tid"]]), "ticket", $info["ticketid"]);
			$hook_data = ["ticketid" => $info["tid"], "replyid" => $id, "adminid" => cmf_get_current_admin_id()];
			hook("ticket_delete_reply", $hook_data);
		}
		active_log(sprintf($this->lang["Ticket_admin_deleteReply"], $info["uid"], $info["tid"], $t["title"]), $info["uid"]);
		$result["status"] = 200;
		$result["msg"] = lang("DELETE SUCCESS");
		return jsonrule($result);
	}
	/**
	 * @title 删除附件
	 * @description 删除附件
	 * @param       .name:id type:int require:1 default: other: desc:工单回复id或工单备注id或工单id
	 * @param       .name:type type:string require:1 default: other: desc:id类型,r工单回复,n工单备注,t工单
	 * @param       .name:index type:int require:1 default: other: desc:要删除附件的index
	 * @author huanghao
	 * @url         admin/delete_ticket_attachment
	 * @method      POST
	 * @time        2019-11-29
	 */
	public function deleteAttachment()
	{
		$params = input("post.");
		$rule = ["id" => "require", "type" => "require|in:r,n,t", "index" => "require|number"];
		$msg = ["id.require" => lang("ID_ERROR"), "type.require" => lang("TYPE_ERROR"), "type.in" => lang("TYPE_ERROR"), "index.require" => lang("TICKET_ATTACHMENT_DELETE_EMPTY"), "index.number" => lang("TICKET_ATTACHMENT_ERROR")];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$id = intval($params["id"]);
		$index = intval($params["index"]);
		if ($params["type"] == "r") {
			$table = "ticket_reply";
		} elseif ($params["type"] == "n") {
			$table = "ticket_note";
		} elseif ($params["type"] == "t") {
			$table = "ticket";
		} else {
			$result["status"] = 406;
			$result["msg"] = lang("TYPE_ERROR");
			return $result;
		}
		if ($table == "ticket") {
			$ticket_info = \think\Db::name($table)->field("id,dptid,tid,attachment")->where("id", $id)->find();
		} else {
			$ticket_info = \think\Db::name($table)->field("b.id,b.tid,b.dptid,a.attachment")->alias("a")->leftJoin("ticket b", "a.tid=b.id")->where("a.id", $id)->where("b.merged_ticket_id", 0)->find();
		}
		if (empty($ticket_info)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		if (!model("TicketDepartmentAdmin")->check($ticket_info["dptid"])) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$attachment = array_filter(explode(",", $ticket_info["attachment"]));
		if (empty($attachment[$index])) {
			$result["status"] = 200;
			$result["msg"] = lang("TICKET_ATTACHMENT_NOT_EXIST");
			return jsonrule($result);
		}
		$this->deleteAttachments($attachment[$index]);
		model("SystemLog")->log(lang("TICKET_ATTACHMENT_DELETE_SUCCESS_LOG", [$attachment[$index]]), "ticket", $ticket_info["id"]);
		unset($attachment[$index]);
		\think\Db::name($table)->where("id", $id)->update(["attachment" => implode(",", $attachment)]);
		$result["status"] = 200;
		$result["msg"] = lang("DELETE SUCCESS");
		return jsonrule($result);
	}
	/**
	 * @title 下载附件
	 * @description 下载附件
	 * @param       .name:id type:int require:1 default: other: desc:工单回复id或工单备注id或工单id
	 * @param       .name:type type:string require:1 default: other: desc:id类型,r工单回复,n工单备注,t工单
	 * @param       .name:index type:int require:1 default: other: desc:要下载的附件的index
	 * @author huanghao
	 * @url         admin/download_ticket_attachment
	 * @method      GET
	 * @time        2019-11-29
	 */
	public function downloadAttachment()
	{
		$params = input("get.");
		$rule = ["id" => "require", "type" => "require|in:r,n,t", "index" => "require|number"];
		$msg = ["id.require" => lang("ID_ERROR"), "type.require" => lang("TYPE_ERROR"), "type.in" => lang("TYPE_ERROR"), "index.require" => lang("TICKET_ATTACHMENT_DOWNLOAD_EMPTY"), "index.number" => lang("TICKET_ATTACHMENT_ERROR")];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$id = intval($params["id"]);
		$index = intval($params["index"]);
		if ($params["type"] == "r") {
			$table = "ticket_reply";
		} elseif ($params["type"] == "n") {
			$table = "ticket_note";
		} elseif ($params["type"] == "t") {
			$table = "ticket";
		} else {
			$result["status"] = 406;
			$result["msg"] = "类型错误";
			return $result;
		}
		if ($table == "ticket") {
			$ticket_info = \think\Db::name($table)->field("id,dptid,tid,attachment")->where("id", $id)->find();
		} else {
			$ticket_info = \think\Db::name($table)->field("b.tid,b.dptid,a.attachment")->alias("a")->leftJoin("ticket b", "a.tid=b.id")->where("a.id", $id)->where("b.merged_ticket_id", 0)->find();
		}
		if (empty($ticket_info)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return jsonrule($result);
		}
		if (!model("TicketDepartmentAdmin")->check($ticket_info["dptid"])) {
			$result["status"] = 406;
			$result["msg"] = "没有权限";
			return jsonrule($result);
		}
		$attachment = array_filter(explode(",", $ticket_info["attachment"]));
		if (empty($attachment[$index])) {
			$result["status"] = 404;
			$result["msg"] = "文件不存在";
			return jsonrule($result);
		}
		$file = htmlspecialchars_decode(configuration("domain") . config("ticket_url") . $attachment[$index]);
		return jsonrule(["status" => 200, "msg" => "成功", "data" => ["filename" => $file]]);
	}
	/**
	 * @title 获取工单详情
	 * @description 获取工单详情
	 * @param       .name:id type:int require:1 default: other: desc:工单id
	 * @return      .list.id:工单id|回复id|备注id|工单转移日志id
	 * @return      .list.mode:工单转移方式(0:指定处理人1:移动部门)
	 * @return      .list.mode_zh:工单转移方式(中文)
	 * @return      .list.desc:描述
	 * @return      .list.remarks:备注
	 * @return      .list.from:发出人
	 * @return      .list.to:接收人
	 * @return      .list.type:类型,t工单,r回复,n备注
	 * @return      .list.content:内容
	 * @return      .list.attachment.name:附件名称
	 * @return      .list.attachment.img:附件图片
	 * @return      .list.format_time:时间
	 * @return      .list.user:发出人
	 * @return      .list.realname:管理员真实姓名
	 * @return      .list.user_type:用户类型
	 * @return      .list.star:评价星级(只会在管理员回复有)
	 * @return      .ticket.dptid:工单部门id
	 * @return      .ticket.dpt_name:工单部门名称
	 * @return      .ticket.title:工单标题
	 * @return      .ticket.status:工单状态
	 * @return      .ticket.cc:抄送收件人
	 * @return      .ticket.uid:工单用户id
	 * @return      .ticket.flag:标记的管理员id
	 * @return      .ticket.priority:优先级
	 * @return      .customfields.id:自定义字段id
	 * @return      .customfields.fieldname:自定义字段名称
	 * @return      .customfields.fieldtype:自定义字段类型
	 * @return      .customfields.description:自定义字段描述
	 * @return      .customfields.fieldoptions:自定义字段选项
	 * @return      .customfields.required:自定义字段是否必填
	 * @return      .customfields.value:自定义字段值
	 * @author huanghao
	 * @url         admin/list_ticket/:id
	 * @method      GET
	 * @time        2019-11-29
	 */
	public function ticketDetail($id = 0)
	{
		$id = intval($id);
		$ticket = \think\Db::name("ticket")->where("id", $id)->where("merged_ticket_id", 0)->find();
		if (empty($ticket)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$ticket_department = \think\Db::name("ticket_department")->where("id", $ticket["dptid"])->find();
		$ticket["dpt_name"] = $ticket_department["name"];
		$user = \think\Db::name("user")->where("id", $ticket["handle"])->find();
		$ticket["handle_name"] = !empty($user["user_login"]) ? $user["user_login"] : "";
		if (!model("TicketDepartmentAdmin")->check($ticket["dptid"])) {
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_VIEW_NO_AUTH");
			return jsonrule($result);
		}
		\think\Db::name("ticket")->where("id", $id)->update(["admin_unread" => 0]);
		$list = [];
		$url = $this->request->host() . config("ticket_url");
		$list[] = ["id" => $id, "uid" => $ticket["uid"], "admin_id" => \intval($ticket["admin_id"]), "type" => "t", "content" => $ticket["content"], "attachment" => isset($ticket["attachment"][0]) ? array_map(function ($v) use($url) {
			return $url . $v;
		}, explode(",", $ticket["attachment"])) : [], "time" => $ticket["create_time"], "format_time" => date("Y-m-d H:i:s", $ticket["create_time"]), "is_deliver" => $ticket["is_deliver"], "is_receive" => $ticket["is_receive"]];
		if (!empty($ticket["uid"])) {
			$user_info = \think\Db::name("clients")->field("id,username")->where("id", $ticket["uid"])->find();
			$list[0]["user"] = $user_info["username"];
			$list[0]["user_type"] = lang("USER");
		} else {
			$list[0]["user"] = $ticket["name"];
			$list[0]["user_type"] = lang("USER");
		}
		$note = \think\Db::name("ticket_note")->where("tid", $id)->select()->toArray();
		foreach ($note as $v) {
			$user = \think\Db::name("user")->find($ticket["admin_id"]);
			$list[0]["user_nickname"] = isset($user["user_nickname"][0]) ?? $ticket["admin"];
			$list[] = ["id" => $v["id"], "type" => "n", "content" => $v["content"], "attachment" => isset($v["attachment"][0]) ? array_map(function ($v1) use($url) {
				return $url . $v1;
			}, explode(",", $v["attachment"])) : [], "time" => $v["create_time"], "format_time" => date("Y-m-d H:i:s", $v["create_time"]), "user" => $v["admin"], "realname" => isset($user["user_nickname"][0]) ? $user["user_nickname"] : $v["admin"], "user_type" => lang("ADMIN")];
		}
		$reply = \think\Db::name("ticket_reply")->order("id", "DESC")->where("tid", $id)->select()->toArray();
		foreach ($reply as $v) {
			$v["type"] = "r";
			$v["attachment"] = isset($v["attachment"][0]) ? array_map(function ($v1) use($url) {
				return $url . $v1;
			}, explode(",", $v["attachment"])) : [];
			$v["time"] = $v["create_time"];
			$v["format_time"] = date("Y-m-d H:i:s", $v["create_time"]);
			if (!empty($v["admin_id"])) {
				$v["user_type"] = lang("ADMIN");
				$v["user"] = $v["admin"];
				$v["realname"] = "";
				$tmp = \think\Db::name("user")->find($v["admin_id"]);
				if (isset($tmp["user_nickname"])) {
					$v["realname"] = $tmp["user_nickname"];
					$v["user"] = $tmp["user_nickname"];
				}
			} else {
				$v["user"] = $v["name"];
				$v["user_type"] = lang("USER");
				$tmp = \think\Db::name("clients")->find($v["uid"]);
				if (isset($tmp["username"])) {
					$v["user"] = $tmp["username"];
				}
			}
			if ($v["is_receive"] == 1 && $v["source"] == 1) {
				$v["deliver_status"] = 2;
				$v["deliver_status_title"] = "上游API回复";
			} elseif ($v["is_receive"] == 1 && $v["source"] == 0) {
				$v["deliver_status"] = 1;
				$v["deliver_status_title"] = "下游API提交";
			}
			$list[] = $v;
		}
		$reply = \think\Db::name("ticket")->order("id", "DESC")->where("merged_ticket_id", $id)->select()->toArray();
		foreach ($reply as $v) {
			$v["type"] = "t";
			$v["attachment"] = isset($v["attachment"][0]) ? array_map(function ($v1) use($url) {
				return $url . $v1;
			}, explode(",", $v["attachment"])) : [];
			$v["time"] = $v["create_time"];
			$v["format_time"] = date("Y-m-d H:i:s", $v["create_time"]);
			if (!empty($v["admin_id"])) {
				$v["user_type"] = lang("ADMIN");
				$v["user"] = $v["admin"];
				$v["realname"] = "";
				$tmp = \think\Db::name("user")->find($v["admin_id"]);
				if (isset($tmp["user_nickname"])) {
					$v["user"] = $tmp["user_nickname"];
					$v["realname"] = $tmp["user_nickname"];
				}
			} else {
				$v["user"] = $v["name"];
				$v["user_type"] = lang("USER");
				$tmp = \think\Db::name("clients")->find($v["uid"]);
				if (isset($tmp["username"])) {
					$v["user"] = $tmp["username"];
				}
			}
			$list[] = $v;
		}
		$ticket_transfer_log = \think\Db::name("ticket_transfer_log")->where("tid", $id)->select()->toArray();
		foreach ($ticket_transfer_log as &$v) {
			if ($v["mode"] == 1) {
				$ticket_department = \think\Db::name("ticket_department")->find($v["dptid"]);
				$to = $ticket_department["name"];
				if ($v["handle"] > 0) {
					$user = \think\Db::name("user")->find($v["handle"]);
					$to = $ticket_department["name"] . "(" . $user["user_login"] . ")";
				}
			} else {
				$user = \think\Db::name("user")->find($v["handle"]);
				$to = $user["user_login"];
			}
			if ($v["mode"] == 1) {
				$ticket_department = \think\Db::name("ticket_department")->find($v["old_dptid"]);
				$from = $ticket_department["name"];
				if ($v["handle"] > 0) {
					$user = \think\Db::name("user")->find($v["old_handle"]);
					$to = $ticket_department["name"] . "(" . $user["user_login"] . ")";
				}
			} else {
				$user = \think\Db::name("user")->find($v["old_handle"]);
				$from = $user["user_login"];
			}
			$list[] = ["id" => $v["id"], "type" => "log", "mode" => $v["mode"], "mode_zh" => config("app.ticket_transfer_mode." . $v["mode"]), "desc" => $v["desc"], "remarks" => $v["remarks"], "time" => $v["create_time"], "format_time" => date("Y-m-d H:i:s", $v["create_time"]), "from" => $from, "to" => $to, "user_type" => "移交"];
		}
		$ticket["host"] = [];
		$ticket["product"] = "";
		$host["id"] = 0;
		if ($ticket["host_id"] > 0) {
			$host = \think\Db::name("host")->field("h.id,h.initiative_renew,h.domain,h.uid,h.regdate as create_time,h.productid,h.billingcycle,h.domain,h.payment,h.nextduedate,h.firstpaymentamount,h.amount,h.domainstatus,c.username,p.name as productname,c.currency,p.type,h.serverid")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("clients c", "c.id=h.uid")->where("h.id", $ticket["host_id"])->find();
			$ticket["host"][] = $host;
			$tmp = \think\Db::name("currencies")->select()->toArray();
			$currency = array_column($tmp, null, "id");
			foreach ($ticket["host"] as &$v) {
				$v["domainstatus_en"] = $v["domainstatus"];
				$v["billingcycle"] = config("billing_cycle")[$v["billingcycle"]];
				$v["status_color"] = config("public.domainstatus")[$v["domainstatus"]]["color"];
				$v["domainstatus"] = config("public.domainstatus")[$v["domainstatus"]]["name"];
				$v["firstpaymentamount"] = $currency[$v["currency"]]["prefix"] . $v["firstpaymentamount"] . $currency[$v["currency"]]["suffix"];
				$v["amount"] = $currency[$v["currency"]]["prefix"] . $v["amount"] . $currency[$v["currency"]]["suffix"];
			}
		}
		if (cmf_get_conf("ticket_reply_order", "asc") == "desc") {
			array_multisort(array_column($list, "time"), SORT_DESC, $list);
		} else {
			array_multisort(array_column($list, "time"), SORT_ASC, $list);
		}
		$customfields = (new \app\common\model\CustomfieldsModel())->getCustomValue($ticket["dptid"], $id, "ticket");
		if ($ticket["status"] == 3 && $ticket["is_receive"] == 1) {
			$ticket_reply = \think\Db::name("ticket_reply")->where("tid", $ticket["id"])->order("id", "desc")->find();
			if ($ticket_reply["is_receive"] == 1 && $ticket_reply["source"] == 0) {
				$ticket["deliver_status"] = 1;
				$ticket["deliver_status_title"] = "下游已回复";
			}
		} else {
			if ($ticket["status"] == 2 && $ticket["is_deliver"] == 1) {
				$ticket_reply = \think\Db::name("ticket_reply")->where("tid", $ticket["id"])->order("id", "desc")->find();
				if ($ticket_reply["is_receive"] == 1 && $ticket_reply["source"] == 1) {
					$ticket["deliver_status"] = 2;
					$ticket["deliver_status_title"] = "上游已回复";
				}
			}
		}
		$user = \think\Db::name("user")->find(cmf_get_current_admin_id());
		$result["status"] = 200;
		$result["data"] = ["list" => $list, "ticket" => ["dptid" => $ticket["dptid"], "dpt_name" => $ticket["dpt_name"], "handle" => $ticket["handle"], "handle_name" => $ticket["handle_name"], "title" => html_entity_decode($ticket["title"], ENT_QUOTES), "status" => $ticket["status"], "type" => "t", "cc" => $ticket["cc"], "uid" => $ticket["uid"], "flag" => $ticket["flag"], "priority" => $ticket["priority"], "host" => $ticket["host"], "hostid" => $host["id"], "is_deliver" => $ticket["is_deliver"], "is_receive" => $ticket["is_receive"], "deliver_status" => $ticket["deliver_status"], "deliver_status_title" => $ticket["deliver_status_title"]], "user" => $user, "customfields" => $customfields];
		return jsonrule($result);
	}
	/**
	 * @title 工单信息获取产品
	 * @description 工单信息获取产品
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段,username,create_time,gateway,description,amount_in,fees,amount_out
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @param .name:uid type:int require:1 default: other: desc:用户id
	 * @param .name:hostid type:int require:1 default: other: desc:hostid
	 * @author lgd
	 * @url         admin/ticket_detail_host
	 * @method      GET
	 * @time        2020-10-09
	 */
	public function getTicketDetailHost()
	{
		$params = input("get.");
		$page = intval($params["page"]);
		$uid = intval($params["uid"]);
		$limit = intval($params["limit"]);
		$page = $page ?? config("page");
		$limit = $limit ?? config("limit");
		$order = $params["order"] ?? "h.id";
		$sort = $params["sort"] ?? "desc";
		$hostid = intval($params["hostid"]);
		$host_list = \think\Db::name("host")->field("h.id,h.initiative_renew,h.domain,h.uid,h.regdate as create_time,h.productid,h.billingcycle,h.domain,h.payment,h.nextduedate,h.amount,h.domainstatus,c.username,p.name as productname,c.currency,p.type,h.serverid")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("clients c", "c.id=h.uid")->where("h.uid", $uid)->order("h.id", "desc")->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		$count = \think\Db::name("host")->field("h.id,h.initiative_renew,h.domain,h.uid,h.regdate as create_time,h.productid,h.billingcycle,h.domain,h.payment,h.nextduedate,h.amount,h.domainstatus,c.username,p.name as productname,c.currency,p.type,h.serverid")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("clients c", "c.id=h.uid")->where("h.uid", $uid)->order("h.id", "desc")->count();
		$tmp = \think\Db::name("currencies")->select()->toArray();
		$currency = array_column($tmp, null, "id");
		foreach ($host_list as &$v) {
			if ($v["id"] == $hostid) {
				$v["related"] = 1;
			} else {
				$v["related"] = 0;
			}
			$v["billingcycle"] = config("billing_cycle")[$v["billingcycle"]];
			$v["status_color"] = config("public.domainstatus")[$v["domainstatus"]]["color"];
			$v["domainstatus"] = config("public.domainstatus")[$v["domainstatus"]]["name"];
			$v["amount"] = $currency[$v["currency"]]["prefix"] . $v["amount"] . $currency[$v["currency"]]["suffix"];
		}
		$result["status"] = 200;
		$result["data"] = $host_list;
		$result["total"] = $count;
		return jsonrule($result);
	}
	/**
	 * @title 修改工单信息
	 * @description 修改工单信息
	 * @param       .name:id type:int require:1 default: other: desc:工单id
	 * @param       .name:dptid type:int require:0 default: other: desc:部门id
	 * @param       .name:title type:string require:0 default: other: desc:标题
	 * @param       .name:status type:string require:0 default: other: desc:状态
	 * @param       .name:cc type:string require:0 default: other: desc:抄送收件人
	 * @param       .name:uid type:int require:0 default: other: desc:用户id
	 * @param       .name:flag type:int require:0 default: other: desc:标记的管理员id
	 * @param       .name:customfield type:array require:0 default: other: desc:自定义字段的值
	 * @author huanghao
	 * @url         admin/save_ticket
	 * @method      POST
	 * @time        2019-11-29
	 */
	public function saveTicket()
	{
		$params = input("post.");
		$id = intval($params["id"]);
		$uid = intval($params["uid"]);
		$ticket = \think\Db::name("ticket")->where("id", $id)->find();
		if (empty($ticket)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$data = [];
		$dec = "";
		if (isset($params["dptid"])) {
			if (!model("TicketDepartmentAdmin")->check($params["dptid"])) {
				$result["status"] = 406;
				$result["msg"] = lang("ID_ERROR");
				return jsonrule($result);
			}
			$results = \think\Db::name("ticket_department")->field("name")->where("id", $params["dptid"])->find();
			$new_dptname = $results["name"];
			$results1 = \think\Db::name("ticket_department")->field("name")->where("id", $ticket["dptid"])->find();
			$data["dptid"] = intval($params["dptid"]);
			if ($params["dptid"] != $ticket["dptid"]) {
				$dec .= "工单部门由“" . $results["name"] . "”改为“" . $results1["name"] . "”，";
			}
		}
		if (!empty($params["title"])) {
			$data["title"] = $params["title"];
			if ($params["title"] != $ticket["title"]) {
				$dec .= "工单标题由“" . $ticket["title"] . "”改为“" . $params["title"] . "”，";
			}
		}
		if (!empty($params["status"])) {
			$status = \think\Db::name("ticket_status")->where("id", $params["status"])->find();
			$status1 = \think\Db::name("ticket_status")->where("id", $ticket["status"])->find();
			if (!empty($status)) {
				$data["status"] = $status["id"];
				if ($params["status"] != $ticket["status"]) {
					$dec .= "工单状态由“" . $status1["title"] . "”改为“" . $status["title"] . "”，";
				}
			}
		}
		if (isset($params["cc"])) {
			$data["cc"] = $params["cc"];
			if ($params["cc"] != $ticket["cc"]) {
				$dec .= "收件人由“" . $ticket["cc"] . "”改为“" . $params["cc"] . "”，";
			}
		}
		if (isset($params["uid"])) {
			$uid = intval($uid);
			if (!empty($uid)) {
				$user_info = \think\Db::name("clients")->field("id,username")->where("id", $uid)->find();
				$user_info1 = \think\Db::name("clients")->field("id,username")->where("id", $ticket["uid"])->find();
				if (!empty($user_info)) {
					$data["uid"] = $user_info["id"];
				}
			} else {
				$data["uid"] = 0;
			}
			if ($params["uid"] != $ticket["uid"]) {
				$dec .= "客户名由“" . $user_info1["username"] . "”改为“" . $user_info["username"] . "”，";
			}
		}
		if (!empty($params["flag"])) {
			$admin = \think\Db::name("user")->field("id")->where("id", $params["flag"])->find();
			$data["flag"] = intval($admin["id"]);
		}
		$data["admin_id"] = cmf_get_current_admin_id();
		$data["update_time"] = time();
		$r = \think\Db::name("ticket")->where("id", $id)->update($data);
		if (!isset($data["dptid"]) || $data["dptid"] == $ticket["dptid"]) {
			if (isset($params["customfield"]) && is_array($params["customfield"])) {
				(new \app\common\model\CustomfieldsModel())->updateCustomValue($ticket["dptid"], $id, $params["customfield"], "ticket");
			}
		}
		if (!empty($data["dptid"]) && $data["dptid"] != $ticket["dptid"]) {
			(new \app\common\model\CustomfieldsModel())->deleteCustomValue($ticket["dptid"], $id, "ticket");
		}
		if ($r) {
			model("SystemLog")->log(lang("TICKET_MODIFY_SUCCESS_LOG", [$ticket["tid"]]), "ticket", $id);
		}
		if (empty($dec)) {
			$dec .= "什么都没有修改";
		}
		active_log(sprintf($this->lang["Ticket_admin_saveTicket"], $uid, $id, $ticket["title"], $dec), $uid);
		unset($dec);
		if (isset($data["title"]) && $data["title"] != $ticket["title"]) {
			$hook_data = ["ticketid" => $id, "title" => html_entity_decode($data["title"], ENT_QUOTES)];
			hook("ticket_title_change", $hook_data);
		}
		if (isset($data["dptid"]) && $data["dptid"] != $ticket["dptid"]) {
			$hook_data = ["ticketid" => $id, "dptid" => $data["dptid"], "dptname" => $new_dptname ?? ""];
			hook("ticket_department_change", $hook_data);
		}
		if (isset($data["status"]) && $data["status"] != $ticket["status"]) {
			$hook_data = ["ticketid" => [$id], "status" => $data["status"], "status_title" => $status["title"], "adminid" => cmf_get_current_admin_id()];
			hook("ticket_status_change", $hook_data);
			if ($data["status"] == 4) {
				hook("ticket_close", ["ticketid" => $id]);
			}
		}
		$result["status"] = 200;
		$result["msg"] = lang("UPDATE SUCCESS");
		return jsonrule($result);
	}
	protected function uploadAttachments()
	{
		$data = [];
		$files = request()->file("attachment");
		foreach ($files as $file) {
			$info = $file->rule(function () {
				return mt_rand(1000, 9999) . "_" . md5(microtime(true));
			})->move(TICKET_DOWN_PATH);
			if ($info) {
				$data[] = $info->getFilename();
			} else {
				foreach ($data["attachment"] as $val) {
					@unlink(TICKET_DOWN_PATH . $val);
				}
				$result["status"] = 406;
				$result["msg"] = $file->getError();
				return $result;
			}
		}
		$result["status"] = 200;
		$result["data"] = $data;
		return $result;
	}
	protected function deleteAttachments($attachment = [])
	{
		if (is_string($attachment) && !empty($attachment)) {
			$r = @unlink(TICKET_DOWN_PATH . $attachment);
		} else {
			if (is_array($attachment)) {
				foreach ($attachment as $v) {
					if (!empty($v)) {
						@unlink(TICKET_DOWN_PATH . $v);
					}
				}
			}
		}
	}
	protected function formatAttachments($attachment = [])
	{
		$res = [];
		foreach ($attachment as $v) {
			$doc = substr($v, -4);
			if (in_array($doc, [".jpg", ".gif", ".png", "jpeg"])) {
				$img = base64EncodeImage(TICKET_DOWN_PATH . $v);
			} else {
				$img = "";
			}
			$res[] = ["name" => $v, "img" => $img];
		}
		return $res;
	}
	/**
	 * @title 工单统计
	 * @description 工单统计
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段,ticket_count,ticket_star_count,ticket_star_sum,ticket_star_1,ticket_star_2,ticket_star_3,ticket_star_4,ticket_star_5
	 * @param .name:sort type:int require:1 default:10 other: desc:ASC,DESC
	 * @param .name:start type:int require:0 default: other: desc:开始时间
	 * @param .name:end type:int require:0 default: other: desc:结束时间
	 * @return      .limit:每页条数
	 * @return      .page:当前页数
	 * @return      .sum:总条数
	 * @return      .max_page:最大页数
	 * @return      .list.id:管理员id
	 * @return      .list.user_login:管理员用户名
	 * @return      .list.user_nickname:管理员昵称
	 * @return      .list.ticket_count:工单总数
	 * @return      .list.ticket_close:已关闭工单数量
	 * @return      .list.ticket_star_sum:工单合计评分
	 * @return      .list.ticket_star_1:1分工单数量
	 * @return      .list.ticket_star_2:2分工单数量
	 * @return      .list.ticket_star_3:3分工单数量
	 * @return      .list.ticket_star_4:4分工单数量
	 * @return      .list.ticket_star_5:5分工单数量
	 * @author xj
	 * @url         admin/ticket_statistics
	 * @method      GET
	 * @time        2020-12-23
	 */
	public function ticketStatistics()
	{
		$params = input("get.");
		$page = intval($params["page"]);
		$limit = intval($params["limit"]);
		$page = $page ?? config("page");
		$limit = $limit ?? config("limit");
		$order = in_array($params["order"], ["ticket_count", "ticket_star_sum", "ticket_star_1", "ticket_star_2", "ticket_star_3", "ticket_star_4", "ticket_star_5"]) ?? "ticket_count";
		$sort = $params["sort"] ?? "desc";
		$start = intval($params["start"]);
		$end = intval($params["end"]);
		$start = $start > 0 ? $start : strtotime(date("Y-m"));
		$end = $end > 0 ? $end : time();
		$ticket_department_admin = \think\Db::name("ticket_department_admin")->field("admin_id")->select()->toArray();
		$admins_id = array_unique(array_column($ticket_department_admin, "admin_id"));
		$admins = \think\Db::name("user")->field("id,user_login,user_nickname")->whereIn("id", $admins_id)->select()->toArray();
		$tickets = \think\Db::name("ticket")->where("create_time", ">=", $start)->where("create_time", "<=", $end)->select()->toArray();
		$ticket_replys = \think\Db::name("ticket_reply")->where("create_time", ">=", $start)->where("create_time", "<=", $end)->select()->toArray();
		foreach ($admins as $key => &$value) {
			$value["ticket_count"] = 0;
			$value["ticket_close"] = 0;
			$value["ticket_star_count"] = 0;
			$value["ticket_star_sum"] = 0;
			$value["ticket_star_1"] = 0;
			$value["ticket_star_2"] = 0;
			$value["ticket_star_3"] = 0;
			$value["ticket_star_4"] = 0;
			$value["ticket_star_5"] = 0;
			foreach ($tickets as $v) {
				if ($v["handle"] == $value["id"]) {
					$value["ticket_count"]++;
					if ($v["status"] == 4) {
						$value["ticket_close"]++;
					}
				}
			}
			foreach ($ticket_replys as $v) {
				if ($v["admin_id"] == $value["id"]) {
					$value["ticket_star_sum"] += $v["star"];
					if ($v["star"] == 1) {
						$value["ticket_star_1"]++;
					}
					if ($v["star"] == 2) {
						$value["ticket_star_2"]++;
					}
					if ($v["star"] == 3) {
						$value["ticket_star_3"]++;
					}
					if ($v["star"] == 4) {
						$value["ticket_star_4"]++;
					}
					if ($v["star"] == 5) {
						$value["ticket_star_5"]++;
					}
				}
			}
		}
		$count = count($admins);
		$orderBy = array_column($admins, $order);
		$limitStart = ($page - 1) * $limit;
		if (strtolower($sort) == "desc") {
			array_multisort($orderBy, SORT_DESC, $admins);
		} else {
			array_multisort($orderBy, SORT_ASC, $admins);
		}
		$admins = array_slice($admins, $limitStart, $limit);
		$result["status"] = 200;
		$result["msg"] = "工单统计数据获取成功";
		$result["data"] = ["start" => $start, "end" => $end, "limit" => $limit, "page" => $page, "sum" => $count, "max_page" => ceil($count / $limit), "list" => $admins];
		return jsonrule($result);
	}
	/**
	 * @title 工单接单
	 * @description 工单接单
	 * @param       .name:id type:int require:1 default: other: desc:工单id
	 * @author xujin
	 * @url         admin/ticket_receive
	 * @method      PUT
	 * @time        2020-12-24
	 */
	public function ticketReceive()
	{
		$params = input("post.");
		$id = intval($params["id"]);
		$ticket = \think\Db::name("ticket")->where("id", $id)->find();
		if (empty($ticket)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		if (!empty($ticket["handle"])) {
			$result["status"] = 406;
			$result["msg"] = "工单已被接单";
			return jsonrule($result);
		}
		$admin = cmf_get_current_admin_id();
		$admin_info = \think\Db::name("user")->field("user_login")->where("id", $admin)->find();
		$r = \think\Db::name("ticket")->where("id", $id)->update(["handle" => $admin, "handle_time" => time(), "status" => 5]);
		if ($r) {
			$desc = lang("TICKET_RECEIVE_SUCCESS_LOG", [$id, $admin_info["user_login"]]);
			model("SystemLog")->log($desc, "ticket", $id);
		}
		$result["status"] = 200;
		$result["msg"] = lang("UPDATE SUCCESS");
		return jsonrule($result);
	}
	/**
	 * @title 获取工单转移对象
	 * @description 获取工单转移对象
	 * @param       .name:id type:int require:1 default: other: desc:工单id
	 * @author xujin
	 * @url         admin/ticket_transfer_list
	 * @method      GET
	 * @time        2020-12-25
	 */
	public function ticketTransferList()
	{
		$params = input("get.");
		$id = intval($params["id"]);
		$ticket = \think\Db::name("ticket")->where("id", $id)->find();
		$ticket_department = \think\Db::name("ticket_department")->field("id,name")->where("id", "<>", $ticket["dptid"])->select()->toArray();
		foreach ($ticket_department as &$value) {
			$ticket_department_admin = \think\Db::name("ticket_department_admin")->field("admin_id")->where("dptid", $value["id"])->select()->toArray();
			$value["admins"] = \think\Db::name("user")->field("id,user_login")->whereIn("id", array_column($ticket_department_admin, "admin_id"))->where("id", "<>", $ticket["handle"])->select()->toArray();
		}
		$ticket_department_admin = \think\Db::name("ticket_department_admin")->field("admin_id")->where("dptid", $ticket["dptid"])->select()->toArray();
		$admins = \think\Db::name("user")->field("id,user_login")->whereIn("id", array_column($ticket_department_admin, "admin_id"))->where("id", "<>", $ticket["handle"])->select()->toArray();
		$data = ["departments" => $ticket_department, "admins" => $admins];
		$result["status"] = 200;
		$result["data"] = $data;
		return jsonrule($result);
	}
	/**
	 * @title 转移工单
	 * @description 转移工单
	 * @param       .name:id type:int require:1 default: other: desc:工单id
	 * @param       .name:mode type:int require:1 default:0 other: desc:转移方式0:指定处理人1:移动部门
	 * @param       .name:handle type:int require:0 default: other: desc:处理人id
	 * @param       .name:dptid type:int require:0 default: other: desc:部门id
	 * @param       .name:remarks type:string require:0 default: other: desc:备注
	 * @author xujin
	 * @url         admin/ticket_transfer
	 * @method      PUT
	 * @time        2020-12-24
	 */
	public function ticketTransfer()
	{
		$params = input("post.");
		$id = intval($params["id"]);
		$handle = intval($params["handle"]);
		$dptid = intval($params["dptid"]);
		$mode = intval($params["mode"]) ?? 0;
		$ticket = \think\Db::name("ticket")->where("id", $id)->find();
		if (empty($ticket)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$admin = cmf_get_current_admin_id();
		if ($mode == 1) {
			$ticket_department = \think\Db::name("ticket_department")->where("id", $dptid)->find();
			if (empty($ticket_department)) {
				$result["status"] = 406;
				$result["msg"] = lang("ID_ERROR");
				return jsonrule($result);
			}
			$old_ticket_department = \think\Db::name("ticket_department")->where("id", $ticket["dptid"])->find();
			if (empty($ticket["handle"])) {
				$r = \think\Db::name("ticket")->where("id", $id)->update(["dptid" => $dptid]);
				if ($r) {
					$desc = lang("TICKET_TRANSFER_SUCCESS_LOG", [$id, $old_ticket_department["name"], $ticket_department["name"], config("app.ticket_transfer_mode." . $mode)]);
					\think\Db::name("ticket_transfer_log")->insertGetId(["tid" => $id, "desc" => $desc, "remarks" => $params["remarks"] ?? "", "mode" => $mode, "old_dptid" => $ticket["dptid"], "dptid" => $dptid, "admin" => $admin, "create_time" => time()]);
					model("SystemLog")->log($desc, "ticket", $id);
				}
			} else {
				$handle_info = \think\Db::name("user")->field("user_login")->where("id", $handle)->find();
				if (empty($handle_info)) {
					$result["status"] = 406;
					$result["msg"] = lang("ID_ERROR");
					return jsonrule($result);
				}
				$old_handle_info = \think\Db::name("user")->field("user_login")->where("id", $ticket["handle"])->find();
				$r = \think\Db::name("ticket")->where("id", $id)->update(["dptid" => $dptid, "handle" => $handle, "handle_time" => time()]);
				if ($r) {
					$desc = lang("TICKET_TRANSFER_SUCCESS_LOG", [$id, $old_ticket_department["name"] . "(" . $old_handle_info["user_login"] . ")", $ticket_department["name"] . "(" . $handle_info["user_login"] . ")", config("app.ticket_transfer_mode." . $mode)]);
					\think\Db::name("ticket_transfer_log")->insertGetId(["tid" => $id, "desc" => $desc, "remarks" => $params["remarks"] ?? "", "mode" => $mode, "old_handle" => $ticket["handle"], "handle" => $handle, "old_dptid" => $ticket["dptid"], "dptid" => $dptid, "admin" => $admin, "create_time" => time()]);
					model("SystemLog")->log($desc, "ticket", $id);
				}
			}
		} else {
			if (empty($ticket["handle"])) {
				$result["status"] = 406;
				$result["msg"] = "当前工单无人接单";
				return jsonrule($result);
			}
			$handle_info = \think\Db::name("user")->field("user_login")->where("id", $handle)->find();
			if (empty($handle_info)) {
				$result["status"] = 406;
				$result["msg"] = lang("ID_ERROR");
				return jsonrule($result);
			}
			$ticket_department_admin = \think\Db::name("ticket_department_admin")->field("dptid")->where("admin_id", $handle)->select()->toArray();
			if (!in_array($ticket["dptid"], array_column($ticket_department_admin, "dptid"))) {
				$result["status"] = 406;
				$result["msg"] = "所选处理人不在工单指定部门中";
				return jsonrule($result);
			}
			$old_handle_info = \think\Db::name("user")->field("user_login")->where("id", $ticket["handle"])->find();
			$r = \think\Db::name("ticket")->where("id", $id)->update(["handle" => $handle, "handle_time" => time()]);
			if ($r) {
				$desc = lang("TICKET_TRANSFER_SUCCESS_LOG", [$id, $old_handle_info["user_login"], $handle_info["user_login"], config("app.ticket_transfer_mode." . $mode)]);
				\think\Db::name("ticket_transfer_log")->insertGetId(["tid" => $id, "desc" => $desc, "remarks" => $params["remarks"] ?? "", "mode" => $mode, "old_handle" => $ticket["handle"], "handle" => $handle, "admin" => $admin, "create_time" => time()]);
				model("SystemLog")->log($desc, "ticket", $id);
			}
		}
		$result["status"] = 200;
		$result["msg"] = lang("UPDATE SUCCESS");
		return jsonrule($result);
	}
}