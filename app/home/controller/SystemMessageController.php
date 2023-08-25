<?php

namespace app\home\controller;

/**
 * @title 系统消息
 * Class SystemMessage
 * @package app\home\controller
 */
class SystemMessageController extends \cmf\controller\HomeBaseController
{
	private $system_message_type = [1 => "work_order_message", 2 => "product_news", 3 => "on_site_news", 4 => "event_news"];
	/**
	 * @title 获取系统消息列表
	 * @description 接口说明:
	 * @param name:type type:int  require:0  default:0 other: desc:消息类型：0-全部，1-工单消息，2-产品消息，3-站内信，4-活动消息
	 * @param name:page type:int  require:0  default:1 other: desc:页码
	 * @param name:limit type:int  require:0  default:10 other: desc:每页个数
	 * @return list:列表数据@
	 * @list  id:消息id
	 * @list  title:消息标题
	 * @list  content:内容
	 * @list  attachment:附件地址
	 * @list  create_time:创建时间
	 * @list  read_time:阅读时间，未阅读则为0
	 * @return count:总数量
	 * @return unread_count:未阅读统计@
	 * @unread_count id:消息分类id
	 * @unread_count name:消息分类名称
	 * @unread_count unread_num:消息分类-未读数量
	 *
	 * @return total_page:总页码
	 * @url /sys_messgage
	 * @method GET
	 */
	public function getMessageList(\think\Request $request)
	{
		$params = $data = $this->request->param();
		$page = $params["page"] ?? config("page");
		$limit = $params["limit"] ?? config("limit");
		$list = \think\Db::name("system_message")->alias("sm")->join("clients c", "c.id = sm.uid")->where(function (\think\db\Query $query) use($params) {
			if ($params["type"] > 0) {
				$query->where("sm.type", $params["type"]);
			}
			$query->where("sm.delete_time", 0);
			$query->where("sm.uid", $params["uid"]);
		})->page($page)->limit($limit)->field("sm.*,c.username,c.phonenumber,c.email")->order("sm.id", "desc")->select()->toArray();
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
				$item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
				$item["type_text"] = $this->system_message_type[$item["type"]];
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
				}
			}
		}
		$system_message_type = $this->system_message_type;
		foreach ($system_message_type as $key => $type_item) {
			$temp_message["id"] = $key;
			$temp_message["name"] = $type_item;
			$temp_message["unread_num"] = \think\Db::name("system_message")->where("delete_time", 0)->where("read_time", 0)->where("type", $key)->where("uid", $params["uid"])->count();
			$unread_count[] = $temp_message;
		}
		$data["list"] = $list;
		$data["count"] = $count;
		$data["unread_count"] = $unread_count;
		return jsonrule(["status" => 200, "msg" => "成功", "data" => $data]);
	}
	/**
	 * @title 系统消息列表-未读
	 * @description 接口说明: 返回上面的未读导航
	 *
	 * @url /sys_messgage_unread
	 * @method GET
	 */
	public function getUnreadList()
	{
		$params = $data = $this->request->param();
		$unread_count = [];
		$unread_count_num = 0;
		$system_message_type = $this->system_message_type;
		foreach ($system_message_type as $key => $type_item) {
			$temp_message["id"] = $key;
			$temp_message["name"] = $type_item;
			$temp_message["unread_num"] = \think\Db::name("system_message")->where("delete_time", 0)->where("read_time", 0)->where("type", $key)->where("uid", $params["uid"])->count();
			$unread_count_num += $temp_message["unread_num"];
			$unread_count[] = $temp_message;
		}
		return jsonrule(["status" => 200, "msg" => "成功", "data" => ["unread_nav" => $unread_count, "unread_num" => $unread_count_num]]);
	}
	/**
	 * @title 阅读消息
	 * @description 接口说明: 可以批量阅读
	 * @param name:ids type:array  require:false  default: other: desc：指定要阅读的消息id，为空则是自己的未读消息【全部阅读】
	 * @param name:type type:string  require:0  default:0 other: desc：消息类型
	 *
	 * @url /read_messgage
	 * @method GET
	 */
	public function readSystemMessage()
	{
		$param = $this->request->param();
		$ids = $param["ids"];
		$type = $param["type"] ?? 0;
		$user_message_ids = \think\Db::name("system_message")->where("uid", $param["uid"])->column("id");
		if (empty($user_message_ids)) {
			return jsonrule(["status" => 400, "msg" => "暂无可阅读消息"]);
		}
		if ($ids) {
			foreach ($ids as $item) {
				if (!in_array($item, $user_message_ids)) {
					return jsonrule(["status" => 400, "msg" => "参数错误，只能阅读自己的消息"]);
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
			return jsonrule(["status" => 400, "msg" => "阅读失败"]);
		} else {
			return jsonrule(["status" => 200, "msg" => "阅读成功"]);
		}
	}
	/**
	 * @title 删除消息
	 * @description 接口说明: 可以批量删除
	 * @param name:ids type:array  require:false  default: other: desc：指定要删除的消息id，为空则是自己的消息【全部删除】
	 * @param name:type type:string  require:0  default:0 other: desc：消息类型
	 *
	 * @url /delete_messgage
	 * @method GET
	 */
	public function deleteSystemMessage()
	{
		$param = $this->request->param();
		$ids = $param["ids"];
		$type = $param["type"] ?? 0;
		$user_message_ids = \think\Db::name("system_message")->where("uid", $param["uid"])->column("id");
		if (empty($user_message_ids)) {
			return jsonrule(["status" => 400, "msg" => "暂无可删除消息"]);
		}
		if ($ids) {
			foreach ($ids as $item) {
				if (!in_array($item, $user_message_ids)) {
					return jsonrule(["status" => 400, "msg" => "参数错误，只能删除自己的消息"]);
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
			return jsonrule(["status" => 400, "msg" => "删除失败"]);
		} else {
			return jsonrule(["status" => 200, "msg" => "删除成功"]);
		}
	}
}