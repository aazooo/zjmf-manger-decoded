<?php

namespace app\openapi\controller;

/**
 * @title 工单管理
 * @description 接口说明
 */
class TicketController extends \cmf\controller\HomeBaseController
{
	public function getTickets()
	{
		$param = $this->request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : config("page");
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$uid = request()->uid;
		$order = "`status`=4,`status`=1 DESC,`last_reply_time` DESC";
		$list = \think\Db::name("ticket")->alias("a")->field("a.tid id,a.dptid department_id,a.uid,a.host_id,a.name,a.email,a.create_time,a.title,a.content,a.status,a.priority,a.admin,a.last_reply_time,a.update_time,b.name department_name,p.name product_name")->leftJoin("ticket_department b", "a.dptid=b.id")->leftJoin("host h", "a.host_id = h.id")->leftJoin("products p", "h.productid = p.id")->where("a.merged_ticket_id", 0)->where("b.hidden", 0)->where("a.uid", $uid)->orderRaw($order)->page($page)->limit($limit)->select()->toArray();
		$count = \think\Db::name("ticket")->alias("a")->leftJoin("ticket_department b", "a.dptid=b.id")->where("a.merged_ticket_id", 0)->where("b.hidden", 0)->where("a.uid", $uid)->group("a.id")->count();
		$status = \think\Db::name("ticket_status")->field("id,title,color")->select()->toArray();
		$tmp = array_column($status, "title", "id");
		foreach ($list as $k => &$v) {
			$v["status"] = $tmp[$v["status"]] ?? [];
		}
		$result["status"] = 200;
		$result["msg"] = lang("SUCCESS MESSAGE");
		$result["data"] = ["total" => $count, "list" => $list];
		return json($result);
	}
	public function getOpenTicketPage()
	{
		$uid = request()->uid;
		$returndata = [];
		$returndata["host"] = \think\Db::name("host")->where("uid", $uid)->field("h.id,p.name as product_name,h.domainstatus status,h.domain name,h.dedicatedip ip")->alias("h")->leftJoin("products p", "p.id=h.productid")->whereIn("h.domainstatus", ["Pending", "Active", "Suspended"])->select()->toArray();
		$priority = ["high", "medium", "low"];
		$returndata["priority"] = $priority;
		$depart = \think\Db::name("ticket_department")->field("id,name")->where("hidden", 0)->select()->toArray();
		$ticketCustom = \think\Db::name("customfields")->field("id,fieldname name,fieldtype type,fieldoptions options,description,regexpr,required,relid")->whereIn("relid", array_column($depart, "id"))->where("type", "ticket")->select()->toArray();
		$custom = [];
		foreach ($ticketCustom as $val) {
			if ($val["type"] == "dropdown") {
				$val["options"] = explode(",", $val["options"]);
			}
			$relid = $val["relid"];
			unset($val["relid"]);
			$custom[$relid][] = $val;
		}
		foreach ($depart as &$value) {
			$value["custom_fields"] = $custom[$value["id"]];
		}
		$returndata["department"] = $depart;
		return json(["status" => 200, "data" => $returndata]);
	}
	public function createTicket()
	{
		$params = $this->request->param();
		if ($this->request->attachment) {
			$params["attachment"] = $this->request->attachment;
		}
		$rule = ["department_id" => "require|number", "title" => "require", "content" => "require|length:0,10000", "host_id" => "number"];
		$msg = ["department_id.require" => "请选择工单部门", "department_id.number" => "类型错误", "title.require" => "请输入工单标题", "content.require" => "请输入工单内容", "host_id.number" => "产品错误"];
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return json(["status" => 400, "msg" => $validate->getError()]);
		}
		$uid = request()->uid;
		$data = [];
		$user_info = \think\Db::name("clients")->where("id", $uid)->find();
		if (empty($user_info)) {
			$result["status"] = 400;
			$result["msg"] = "illegal user";
			return json($result);
		}
		$data["uid"] = $uid;
		$data["name"] = $user_info["username"];
		$data["email"] = $user_info["email"];
		$department = \think\Db::name("ticket_department")->field("id,name,is_product_order,is_certifi")->where("id", $params["department_id"])->where("hidden", 0)->find();
		if (empty($department)) {
			$result["status"] = 400;
			$result["msg"] = "Department ID error";
			return json($result);
		}
		if ($department["is_certifi"] == 1 && !checkCertify($uid)) {
			return json(["status" => 400, "msg" => "Please verify your real name"]);
		}
		if ($department["is_product_order"] == 1) {
			$host = \think\Db::name("host")->alias("h")->join("orders o", "o.id=h.orderid")->field("h.id")->where("h.uid", $uid)->find();
			if (empty($host)) {
				$result["status"] = 400;
				$result["msg"] = "You have not purchased the product and cannot initiate the work order";
				return json($result);
			}
		}
		$customfields = \think\Db::name("customfields")->where("relid", $department["id"])->where("type", "ticket")->select()->toArray();
		foreach ($customfields as $k => $v) {
			if (!empty($v["required"]) && !isset($params["custom_field"][$v["id"]]) && $params["custom_field"][$v["id"]] == "") {
				$result["status"] = 400;
				$result["msg"] = $v["fieldname"] . " cannot be empty";
				return json($result);
			}
		}
		$data["dptid"] = $department["id"];
		$service = intval($params["service"]);
		if (!empty($service)) {
			$service = \think\Db::name("host")->field("id")->where("uid", $uid)->where("id", $params["id"])->find();
			if (!empty($service["id"])) {
				$service = $service["id"];
			} else {
				$service = 0;
			}
		}
		$data["attachment"] = "";
		if (isset($params["attachment"][0])) {
			$upload = new \app\common\logic\Upload();
			$ret = $upload->moveTo($params["attachment"], config("ticket_attachments"));
			if (isset($ret["error"])) {
				$result["status"] = 400;
				$result["msg"] = lang("UPLOAD FAIL");
				return json($result);
			}
			$data["attachment"] = implode(",", $ret);
		}
		switch ($params["select"]) {
			case "高":
				$data["priority"] = "high";
				break;
			case "中":
				$data["priority"] = "medium";
				break;
			case "低":
				$data["priority"] = "low";
				break;
		}
		$data["create_time"] = time();
		$data["title"] = $params["title"];
		$data["content"] = $params["content"];
		$data["status"] = 1;
		$data["priority"] = !empty($params["priority"]) ? $params["priority"] : $data["priority"];
		$data["admin"] = "";
		$data["admin_unread"] = 1;
		$data["tid"] = model("Ticket")->getTid();
		$data["c"] = cmf_random_str(8, "lower_upper_number");
		$data["last_reply_time"] = time();
		$data["service"] = $service;
		$data["cc"] = "";
		$data["host_id"] = $params["hostid"] ?? 0;
		$data["is_receive"] = isset($params["is_api"]) ? 1 : 0;
		$data["token"] = md5(uniqid() . randStr() . $data["tid"] . mt_rand(100, 999));
		$r = \think\Db::name("ticket")->insertGetId($data);
		if ($r) {
			ticketDeliver($r);
			$create_time = time();
			foreach ($customfields as $v) {
				if (isset($params["customfield"][$v["id"]])) {
					if (!empty($v["regexpr"]) && !preg_match("/" . str_replace("/", "\\/", $v["regexpr"]) . "/", $params["customfield"][$v["id"]])) {
						continue;
					}
					if ($v["fieldname"] == "select" && !in_array($params["customfield"][$v["id"]], explode(",", $v["fieldoptions"]))) {
						continue;
					}
					$insert = ["fieldid" => $v["id"], "relid" => $r, "value" => $params["customfield"][$v["id"]], "create_time" => $create_time, "update_time" => 0];
					$model = \think\Db::name("customfieldsvalues")->insert($insert);
				}
			}
			$departmentadmin = \think\Db::name("ticket_department_admin")->field("t.admin_id,u.only_oneself_notice")->alias("t")->leftJoin("User u", "u.id=t.admin_id")->where("t.dptid", $params["department_id"])->select()->toArray();
			$curl_multi_data = [];
			foreach ($departmentadmin as $key => $value) {
				if ($value["only_oneself_notice"] == 1 && $value["admin_id"] != $user_info["sale_id"]) {
					continue;
				} else {
					$arr_admin = ["relid" => $r, "name" => "【管理员】新工单提示", "type" => "support", "sync" => true, "admin" => true, "adminid" => $value["admin_id"], "ip" => get_client_ip6()];
					$curl_multi_data[count($curl_multi_data)] = ["url" => "async", "data" => $arr_admin];
				}
			}
			$arr_client = ["relid" => $r, "name" => "工单创建成功", "type" => "support", "sync" => true, "admin" => false, "ip" => get_client_ip6()];
			$curl_multi_data[count($curl_multi_data)] = ["url" => "async", "data" => $arr_client];
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$sms = new \app\common\logic\Sms();
			$client = check_type_is_use($message_template_type[strtolower("Support_Ticket_Opened")], $data["uid"], $sms);
			if ($client) {
				$params = ["ticket_createtime" => date("Y-m-d H:i:s", time()), "ticketnumber_tickettitle" => $data["title"]];
				$arr_sms = ["name" => $message_template_type[strtolower("Support_Ticket_Opened")], "phone" => $client["phone_code"] . $client["phonenumber"], "params" => json_encode($params), "sync" => false, "uid" => $data["uid"], "username" => $client["username"], "delay_time" => 0, "is_market" => false];
				$curl_multi_data[count($curl_multi_data)] = ["url" => "async_sms", "data" => $arr_sms];
			}
			asyncCurlMulti($curl_multi_data);
			$data["attachment"] = array_filter(explode(",", $data["attachment"]));
			foreach ($data["attachment"] as $k => $v) {
				$data["attachment"][$k] = config("ticket_attachments") . $v;
			}
			$hook_data = ["ticketid" => $r, "tid" => $data["tid"], "uid" => $uid, "dptid" => $data["dptid"], "dptname" => $department["name"], "title" => html_entity_decode($data["title"], ENT_QUOTES), "content" => html_entity_decode($data["content"], ENT_QUOTES), "priority" => $data["priority"], "hostid" => $data["host_id"], "attachment" => $data["attachment"] ?: []];
			hook("ticket_open", $hook_data);
		}
		active_logs(sprintf($this->lang["Ticket_home_createTicket"], $data["tid"], $data["title"]), $uid);
		active_logs(sprintf($this->lang["Ticket_home_createTicket"], $data["tid"], $data["title"]), $uid, "", 2);
		$host_obj = new \app\common\logic\Host();
		$host_obj->createTicket($data["host_id"], $r);
		if ($r) {
			$ticket_deliver = \think\Db::name("ticket_deliver")->alias("a")->leftJoin("ticket_deliver_products b", "b.tdid = a.id")->leftJoin("ticket_deliver_department c", "c.tdid=b.tdid")->leftJoin("ticket d", "d.dptid=c.dptid")->leftJoin("host e", "e.id=d.host_id")->leftJoin("products f", "f.id=e.productid")->leftJoin("ticket_department_upstream g", "g.dptid=c.dptid AND f.zjmf_api_id=g.api_id")->field("a.is_open_auto_reply,a.bz,a.id")->where("e.productid=b.pid")->where("d.id", $r)->find();
			$td = \think\Db::name("ticket_department")->where("id", $this->request->department_id)->find();
			if (!empty($ticket_deliver["id"])) {
				$td["is_open_auto_reply"] = $ticket_deliver["is_open_auto_reply"];
				$td["bz"] = $ticket_deliver["bz"];
			}
			if ($td["is_open_auto_reply"] == 1) {
				if ($td["time_type"] == 1) {
					$td["minutes"] = $td["minutes"] * 60;
				}
				$reply["tid"] = $r;
				$reply["uid"] = $uid;
				$reply["create_time"] = time();
				$reply["content"] = $td["bz"];
				$reply["admin_id"] = 1;
				$reply["admin"] = "admin";
				$reply["attachment"] = "";
				\think\Db::name("ticket_reply")->insertGetId($reply);
				\think\Db::name("ticket")->where("id", $r)->update(["is_auto_reply" => 1, "admin_unread" => 1, "last_reply_time" => time()]);
				active_log(sprintf("自动回复工单成功#User ID:%d - Ticket ID:%s - %s", $uid, $r, $params["title"]), $uid);
			}
		}
		$result["status"] = 200;
		$result["msg"] = lang("SUCCESS MESSAGE");
		$result["data"] = ["id" => $data["tid"]];
		return json($result);
	}
	public function ticketDetail($id = 0)
	{
		$uid = request()->uid;
		$ticket = \think\Db::name("ticket")->alias("t")->field("t.tid id,t.dptid department_id,t.uid,t.host_id,t.name,t.email,t.create_time,t.title,t.content,t.status,t.priority,t.admin,t.attachment,t.last_reply_time,t.update_time,td.name department_name")->leftJoin("ticket_department td", "t.dptid=td.id")->where("t.tid", $id)->where("t.uid", $uid)->where("t.merged_ticket_id", 0)->where("td.hidden", 0)->find();
		if (empty($ticket)) {
			$result["status"] = 400;
			$result["msg"] = "ID error";
			return json($result);
		}
		\think\Db::name("ticket")->where("id", $id)->update(["client_unread" => 0]);
		$clients = \think\Db::name("clients")->field("username")->where("id", $ticket["uid"])->find();
		$url = $this->request->host() . config("ticket_url");
		$list = [];
		$reply = \think\Db::name("ticket_reply")->field("content,admin,admin_id,attachment,create_time")->order("create_time", "DESC")->where("tid", $id)->select()->toArray();
		foreach ($reply as $v) {
			$v["type"] = "reply";
			$v["attachment"] = isset($v["attachment"][0]) ? array_map(function ($v1) use($url) {
				return $url . $v1;
			}, explode(",", $v["attachment"])) : [];
			if (!empty($v["admin"])) {
				$v["content"] = htmlspecialchars_decode($v["content"]);
				$v["user_type"] = "admin";
				$v["user"] = $v["admin"];
				$tmp = \think\Db::name("user")->find($v["admin_id"]);
				if (isset($tmp["user_nickname"])) {
					$v["user"] = $tmp["user_nickname"];
				}
			} else {
				$v["content"] = ticketContent($v["content"]);
				$v["user_type"] = "user";
				$v["user"] = $clients["username"];
			}
			unset($v["admin"]);
			unset($v["admin_id"]);
			$list[] = $v;
		}
		$reply = \think\Db::name("ticket")->order("id", "DESC")->where("merged_ticket_id", $id)->select()->toArray();
		foreach ($reply as $v) {
			$v["type"] = "ticket";
			$v["attachment"] = isset($v["attachment"][0]) ? array_map(function ($v1) use($url) {
				return $url . $v1;
			}, explode(",", $v["attachment"])) : [];
			$v["time"] = $v["create_time"];
			$v["format_time"] = date("Y-m-d H:i:s", $v["create_time"]);
			$v["realname"] = "";
			if ($v["admin_id"] > 0) {
				$v["content"] = htmlspecialchars_decode($v["content"]);
				$v["user_type"] = lang("ADMIN");
				$v["user"] = $v["admin"];
				$tmp = \think\Db::name("user")->find($v["admin_id"]);
				if (isset($tmp["user_nickname"])) {
					$v["user"] = $tmp["user_nickname"];
				}
			} elseif ($v["uid"] > 0) {
				$v["content"] = ticketContent($v["content"]);
				$v["user"] = $v["name"];
				$v["user_type"] = lang("USER");
				$tmp = \think\Db::name("clients")->find($v["uid"]);
				if (isset($tmp["username"])) {
					$v["user"] = $tmp["username"];
				}
				$v["avatar"] = base64EncodeImage(config("client_avatar") . $v["avatar"]);
			}
			$list[] = $v;
		}
		if (cmf_get_conf("ticket_reply_order", "asc") == "desc") {
			array_multisort(array_column($list, "time"), SORT_DESC, $list);
		} else {
			array_multisort(array_column($list, "time"), SORT_ASC, $list);
		}
		$ticket["status"] = \think\Db::name("ticket_status")->where("id", $ticket["status"])->value("title");
		$ticket["host"] = "";
		$ticket["product"] = "";
		if ($ticket["host_id"] > 0) {
			$host = \think\Db::name("host")->alias("a")->field("b.name,a.domain,a.id")->leftJoin("products b", "a.productid = b.id")->where("a.id", $ticket["host_id"])->find();
			$ticket["host"] = $host["name"] . "-" . $host["domain"];
		}
		$result["status"] = 200;
		$result["data"] = ["reply" => $list, "ticket" => $ticket];
		return json($result);
	}
	public function replyTicket($id = 0)
	{
		$params = $this->request->param();
		$uid = request()->uid;
		$ticket = model("Ticket")->getTicket(["tid" => $id, "uid" => $uid]);
		if (empty($ticket)) {
			$result["status"] = 400;
			$result["msg"] = "ID error";
			return json($result);
		}
		$rule = ["content" => "require|length:0,10000"];
		$msg = ["content.require" => "请输入回复内容"];
		if ($this->request->attachment) {
			$params["attachment"] = $this->request->attachment;
		}
		$validate = new \think\Validate($rule, $msg);
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return json(["status" => 400, "msg" => $validate->getError()]);
		}
		$data = [];
		$upload = new \app\common\logic\Upload();
		$ret = $upload->moveTo($params["attachment"], config("ticket_attachments"));
		if (isset($ret["error"])) {
			$result["status"] = 400;
			$result["msg"] = lang("UPLOAD FAIL");
			return json($result);
		}
		$data["tid"] = $ticket["id"];
		$data["uid"] = intval($uid);
		$data["create_time"] = time();
		$data["content"] = $params["content"];
		$data["attachment"] = implode(",", $ret) ?: "";
		$data["is_receive"] = isset($params["is_api"]) ? 1 : 0;
		\think\Db::startTrans();
		try {
			$r1 = \think\Db::name("ticket_reply")->insertGetId($data);
			$r2 = \think\Db::name("ticket")->where("id", $data["tid"])->update(["admin_unread" => 1, "last_reply_time" => time(), "status" => 3]);
			if ($r1 && $r2) {
				\think\Db::commit();
				ticketReplyDeliver($r1);
				$result["status"] = 200;
				$result["msg"] = "回复成功";
			} else {
				\think\Db::rollback();
				$result["status"] = 400;
				$result["msg"] = "回复失败";
			}
		} catch (\Exception $e) {
			\think\Db::rollback();
			$result["status"] = 400;
			$result["msg"] = "回复失败" . $e->getMessage();
		}
		$title = \think\Db::name("ticket")->field("title,dptid")->where("id", $data["tid"])->find();
		if ($result["status"] == 200) {
			active_logs(sprintf($this->lang["Ticket_home_replyTicket_success"], $id, $title["title"]), $uid);
			active_logs(sprintf($this->lang["Ticket_home_replyTicket_success"], $id, $title["title"]), $uid, "", 2);
			$departmentadmin = \think\Db::name("ticket_department_admin")->field("t.admin_id,u.only_oneself_notice")->alias("t")->leftJoin("User u", "u.id=t.admin_id")->where("t.dptid", $title["dptid"])->select()->toArray();
			$user_info = \think\Db::name("clients")->where("id", $uid)->find();
			$curl_multi_data = [];
			foreach ($departmentadmin as $key => $value) {
				if ($value["only_oneself_notice"] == 1 && $value["admin_id"] != $user_info["sale_id"]) {
					continue;
				} else {
					$arr_admin = ["relid" => $data["tid"], "name" => "【管理员】工单回复提示", "type" => "support", "sync" => true, "admin" => true, "adminid" => $value["admin_id"], "ip" => get_client_ip6()];
					$curl_multi_data[count($curl_multi_data)] = ["url" => "async", "data" => $arr_admin];
				}
			}
			asyncCurlMulti($curl_multi_data);
			$hook_data = ["ticketid" => $ticket["id"], "replyid" => $r1, "uid" => $uid, "dptid" => $ticket["dptid"], "dptname" => $ticket["dptname"], "title" => html_entity_decode($ticket["title"], ENT_QUOTES), "content" => html_entity_decode($data["content"], ENT_QUOTES), "priority" => $ticket["priority"], "status" => 3, "status_title" => \think\Db::name("ticket_status")->where("id", 3)->value("title")];
			hook("ticket_user_reply", $hook_data);
			$data["id"] = $r1;
			$host_obj = new \app\common\logic\Host();
			$host_obj->replyTicket($ticket["host_id"], $data);
		}
		return json($result);
	}
}