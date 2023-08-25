<?php

namespace app\openapi\controller;

/**
 * @title 消息中心
 * @description 接口说明
 */
class MessageController extends \cmf\controller\HomeBaseController
{
	private $system_message_type = [1 => "work_order_message", 2 => "product_news", 3 => "on_site_news", 4 => "event_news"];
	public function message(\think\Request $request)
	{
		$params = $this->request->param();
		$page = $params["page"] ?? config("page");
		$limit = $params["limit"] ?? config("limit");
		if (in_array($params["type"], $this->system_message_type)) {
			$params["type"] = array_search($params["type"], $this->system_message_type);
		}
		$list = \think\Db::name("system_message")->alias("sm")->join("clients c", "c.id = sm.uid")->where(function (\think\db\Query $query) use($params) {
			if ($params["type"] > 0) {
				$query->where("sm.type", $params["type"]);
			}
			$query->where("sm.delete_time", 0);
			$query->where("sm.uid", $params["uid"]);
		})->page($page)->limit($limit)->field("sm.id,sm.title,sm.content,sm.attachment,sm.type,sm.is_market,sm.create_time,sm.read_time")->order("sm.id", "desc")->select()->toArray();
		$count = \think\Db::name("system_message")->alias("sm")->join("clients c", "c.id = sm.uid")->where(function (\think\db\Query $query) use($params) {
			if ($params["type"] > 0) {
				$query->where("sm.type", $params["type"]);
			}
			$query->where("sm.delete_time", 0);
			$query->where("sm.uid", $params["uid"]);
		})->count();
		if ($list) {
			foreach ($list as &$item) {
				$item["content"] = htmlspecialchars_decode(htmlspecialchars_decode($item["content"]));
				$item["type"] = $this->system_message_type[$item["type"]];
				if ($item["attachment"]) {
					$attachment = explode(",", $item["attachment"]);
					$item["attachment"] = [];
					foreach ($attachment as &$attachment_item) {
						if ($item["type"] == "3") {
							$temp = [];
							$temp["path"] = $_SERVER["REQUEST_SCHEME"] . "://" . $request->host() . config("system_message_url") . $attachment_item;
							$attachment_item = explode("^", $attachment_item);
							$temp["name"] = $attachment_item[1];
							$item["attachment"][] = $temp;
						}
					}
				} else {
					$item["attachment"] = [];
				}
			}
		}
		$system_message_type = $this->system_message_type;
		foreach ($system_message_type as $key => $type_item) {
			$temp_message["type"] = $type_item;
			$temp_message["count"] = \think\Db::name("system_message")->where("delete_time", 0)->where("read_time", 0)->where("type", $key)->where("uid", $params["uid"])->count();
			$unread_count[] = $temp_message;
		}
		$data["message"] = $list;
		$data["total"] = $count;
		$data["unread_message"] = $unread_count;
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function readMessage()
	{
		$param = $this->request->param();
		$ids = $param["ids"];
		$type = $param["type"] ?? 0;
		$user_message_ids = \think\Db::name("system_message")->where("uid", $param["uid"])->column("id");
		if (empty($user_message_ids)) {
			return json(["status" => 400, "msg" => "No message to read"]);
		}
		if ($ids) {
			foreach ($ids as $item) {
				if (!in_array($item, $user_message_ids)) {
					return json(["status" => 400, "msg" => "Parameter error"]);
				}
			}
		}
		$result = \think\Db::name("system_message")->where("uid", $param["uid"])->where(function (\think\db\Query $query) use($ids, $type) {
			if ($ids) {
				$query->where("id", "in", $ids);
			}
			if ($type) {
				$query->where("type", $type);
			}
		})->update(["read_time" => time()]);
		if ($result === false) {
			return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		} else {
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
	}
	public function deleteMessage()
	{
		$param = $this->request->param();
		$ids = $param["ids"];
		$type = $param["type"] ?? 0;
		$user_message_ids = \think\Db::name("system_message")->where("uid", $param["uid"])->column("id");
		if (empty($user_message_ids)) {
			return json(["status" => 400, "msg" => "No message to delete"]);
		}
		if ($ids) {
			foreach ($ids as $item) {
				if (!in_array($item, $user_message_ids)) {
					return json(["status" => 400, "msg" => "Parameter error"]);
				}
			}
		}
		$result = \think\Db::name("system_message")->where("uid", $param["uid"])->where(function (\think\db\Query $query) use($ids, $type) {
			if ($ids) {
				$query->where("id", "in", $ids);
			}
			if ($type) {
				$query->where("type", $type);
			}
		})->update(["delete_time" => time()]);
		if ($result === false) {
			return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		} else {
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
	}
}