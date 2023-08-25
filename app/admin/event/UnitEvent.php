<?php

namespace app\admin\event;

class UnitEvent
{
	/**
	 * 首页统计
	 * @auther 上官🔪
	 */
	public function headCount()
	{
		$count = \app\admin\model\Order::whereNull("delete_time")->where("status", "Pending")->count("id");
		$stickt_unread = db("ticket")->where("admin_unread", 0)->count("id");
		$cancell_count = db("cancel_requests")->alias("c")->join("host h", "h.id=c.relid")->where("auto_terminate_end_cycle", "<>", 1)->where("domainstatus", "<>", "Cancelled")->count();
		$module_queue = db("module_queue")->where("completed", 1)->count();
		$data = ["count" => $count, "stickt_unread" => $stickt_unread, "cancell_count" => $cancell_count, "module_queue" => $module_queue];
		return $data;
	}
	/**
	 * 工单展示
	 * @auther 上官🔪
	 */
	public function ticketList()
	{
		$rows = db("ticket")->whereIn("status", "Open,CustomerReply")->field("count(id) as count,id,title,last_reply_time")->order("last_reply_time", "desc")->limit(20)->select();
		$aid = cmf_get_current_admin_id();
		$assign_count = db("ticket")->where("flag", $aid)->whereIn("status", "Open,CustomerReply")->count("id");
		$rows["assign_count"] = $assign_count;
		return $rows;
	}
	/**
	 * 产品展示
	 * @auther 上官🔪
	 */
	public function productList()
	{
		$rows = db("products")->where("hidden", "<>", 1)->limit(20)->order("id", "desc")->select();
		return $rows;
	}
	/**
	 * 活跃用户
	 * @auther 上官🔪
	 */
	public function activeUser()
	{
		$rows = db("clients")->whereTime("lastlogin", "-90 day")->field("count(id) as count,id,username,lastloginip,lastlogin")->limit(50)->order("id", "desc")->select();
		return $rows;
	}
	/**
	 * 资金流水信息
	 * @auther 上官🔪
	 */
	public function acountList()
	{
		$rows = db("accounts")->field("id,uid,currency,gateway,create_time,amount_in,amount_out")->limit(30)->order("id", "desc")->select();
		return json($rows);
	}
	/**
	 * 系统信息
	 * @auther 上官🔪
	 */
	public function systemInfo()
	{
		$data = ["server_ip" => $_SERVER["SERVER_ADDR"], "server_name" => $_SERVER["SERVER_NAME"], "server_port" => $_SERVER["SERVER_PORT"], "server_version" => php_uname("s") . php_uname("r"), "server_system" => php_uname(), "php_version" => PHP_VERSION, "include_path" => DEFAULT_INCLUDE_PATH, "php_sapi_name" => php_sapi_name(), "now_time" => date("Y-m-d H:i:s"), "upload_max_filesize" => get_cfg_var("upload_max_filesize"), "max_execution_time" => get_cfg_var("max_execution_time") . "秒 ", "memory_limit" => get_cfg_var("memory_limit") ? get_cfg_var("memory_limit") : "无", "processor_identifier" => $_SERVER["PROCESSOR_IDENTIFIER"], "system_root" => $_SERVER["SystemRoot"], "http_accept_language" => $_SERVER["HTTP_ACCEPT_LANGUAGE"]];
		return $data;
	}
	/**
	 * Activity
	 * @auther 上官🔪
	 */
	public function activityList()
	{
		$rows = db("activity_log")->limit(50)->order("cerate_time", "desc")->select();
		return $rows;
	}
}