<?php

namespace app\home\controller;

/**
 * @title 前台日志（所有日志接口）
 * @description 接口说明:包括所有前台的日志列表,展示给客户看的日志！
 */
class RecordLogController extends CommonController
{
	/**
	 * @title 操作日志
	 * @description 接口说明:系统日志
	 * @author wyh
	 * @url user_logs
	 * @method GET
	 * @time 2020-05-18
	 * @param name:page type:int  require:0  default:1 other: desc:页码
	 * @param name:limit type:int  require:0  default:1 other: desc:每页条数
	 * @param name:keywords type:int  require:0  default: other: desc:关键字
	 * @param name:orderby type:string  require:0  default:id other: desc:排序字段
	 * @param name:sorting type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @return log_list:日志数据@
	 * @log_list  create_time:时间
	 * @log_list  description:描述
	 * @log_list  user:用户
	 * @log_list  ipaddr:ip地址
	 * @return count:数量
	 */
	public function getUserLogs(\think\Request $request)
	{
		$uid = request()->uid;
		if (!$uid) {
			return json(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$param = $request->param();
		$page = isset($param["page"]) ? intval($param["page"]) : config("page");
		$limit = isset($param["limit"]) ? intval($param["limit"]) : (configuration("NumRecordstoDisplay") ?: config("limit"));
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sorting = $param["sorting"] ?? "DESC";
		$fun = function (\think\db\Query $query) use($uid, $param) {
			$query->where("uid", $uid);
			$query->where("type", "neq", 1);
			$query->where("usertype", "Client");
			$query->whereOr("usertype", "Sub-Account");
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime("+1 days", $start_time);
				$query->whereBetweenTime("create_time", $start_time, $end_time);
			}
		};
		$logs = \think\Db::name("activity_log")->field("create_time,id,ipaddr,description,uid,user,port")->where($fun)->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["keywords"])) {
				$search_desc = $param["keywords"];
				$query->whereOr("description", "like", "%{$search_desc}%");
				$query->whereOr("ipaddr", "like", "%{$search_desc}%");
			}
		})->withAttr("description", function ($value, $data) {
			$pattern = "/(?P<name>\\w+ ID):(?P<digit>\\d+)/";
			preg_match_all($pattern, $value, $matches);
			$name = $matches["name"];
			$digit = $matches["digit"];
			if (!empty($name)) {
				if (defined("VIEW_TEMPLATE_WEBSITE") && VIEW_TEMPLATE_WEBSITE) {
					foreach ($name as $k => $v) {
						$relid = $digit[$k];
						$str = $v . ":" . $relid;
						if ($v == "Invoice ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"/billing\"><span>" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "User ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"/details\"><span>" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "Host ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"/servicedetail?id=" . $relid . "\"><span>" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "Order ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"/billing\"><span>" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "Ticket ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"/viewticket?tid=" . $relid . "\"><span>" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "Transaction ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"/billing\"><span>" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						}
					}
				} else {
					foreach ($name as $k => $v) {
						$relid = $digit[$k];
						$str = $v . ":" . $relid;
						if ($v == "Invoice ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/finance\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "User ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/personal-center\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "Host ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/server/log?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "Order ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/finance?id=" . $relid . "\"><span class=\"el-link--inner\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "Ticket ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/tickets/viewticket?tid=" . $relid . "\"><span class=\"el-link--inner\"  style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "Transaction ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/finance\"><span class=\"el-link--inner\"  style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						}
					}
				}
				return $value;
			} else {
				return $value;
			}
		})->withAttr("ipaddr", function ($value, $data) {
			if (empty($data["port"])) {
				return $value;
			} else {
				return $value .= ":" . $data["port"];
			}
		})->order("{$orderby} {$sorting}")->order("create_time", "DESC")->page($page)->limit($limit)->select()->toArray();
		$count = \think\Db::name("activity_log")->where($fun)->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["keywords"])) {
				$search_desc = $param["keywords"];
				$query->whereOr("description", "like", "%{$search_desc}%");
				$query->whereOr("ipaddr", "like", "%{$search_desc}%");
			}
		})->count();
		$returndata = [];
		$returndata["count"] = $count;
		$returndata["log_list"] = $logs;
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $returndata]);
	}
	/**
	 * @title 操作主机日志
	 * @description 接口说明:系统主机日志
	 * @author wyh
	 * @url user_logdcims
	 * @method GET
	 * @time 2020-05-18
	 * @param name:page type:int  require:0  default:1 other: desc:页码
	 * @param name:limit type:int  require:0  default:1 other: desc:每页条数
	 * @param name:keywords type:string  require:0  default: other: desc:通过关键字查询
	 * @return log_list:日志数据@
	 * @log_list  create_time:时间
	 * @log_list  description:描述
	 * @log_list  user:用户
	 * @log_list  ipaddr:ip地址
	 * @return count:数量
	 */
	public function getUserLogDcs(\think\Request $request)
	{
		$uid = request()->uid;
		if (!$uid) {
			return json(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$param = $request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sorting = $param["sorting"] ?? "DESC";
		$fun = function (\think\db\Query $query) use($uid, $param) {
			$query->where("uid", $uid);
			$query->where("activeid", $uid);
			$query->where("usertype", "Client");
			$query->where("type", 2);
			$query->where("type_data_id", $param["id"]);
			$query->whereOr("usertype", "Sub-Account");
		};
		$logs = \think\Db::name("activity_log")->field("create_time,id,ipaddr,description,uid,user,port")->where($fun)->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["keywords"])) {
				$search_desc = $param["keywords"];
				$query->whereOr("description", "like", "%{$search_desc}%");
				$query->whereOr("ipaddr", "like", "%{$search_desc}%");
			}
		})->withAttr("description", function ($value, $data) {
			$pattern = "/(?P<name>\\w+ ID):(?P<digit>\\d+)/";
			preg_match_all($pattern, $value, $matches);
			$name = $matches["name"];
			$digit = $matches["digit"];
			if (!empty($name)) {
				if (defined("VIEW_TEMPLATE_WEBSITE") && VIEW_TEMPLATE_WEBSITE) {
					foreach ($name as $k => $v) {
						$relid = $digit[$k];
						$str = $v . ":" . $relid;
						if ($v == "Invoice ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"/billing\"><span>" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "User ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"/details\"><span>" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "Ticket ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"/viewticket?tid=" . $relid . "\"><span>" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						}
					}
				} else {
					foreach ($name as $k => $v) {
						$relid = $digit[$k];
						$str = $v . ":" . $relid;
						if ($v == "Invoice ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/finance\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "User ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/personal-center\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($v == "Ticket ID") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/tickets/viewticket?tid=" . $relid . "\"><span class=\"el-link--inner\"  style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						}
					}
				}
				return $value;
			} else {
				return $value;
			}
		})->withAttr("ipaddr", function ($value, $data) {
			if (empty($data["port"])) {
				return $value;
			} else {
				return $value .= ":" . $data["port"];
			}
		})->order("{$orderby} {$sorting}")->order("id", "DESC")->page($page)->limit($limit)->select()->toArray();
		$count = \think\Db::name("activity_log")->where($fun)->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["keywords"])) {
				$search_desc = $param["keywords"];
				$query->whereOr("description", "like", "%{$search_desc}%");
				$query->whereOr("ipaddr", "like", "%{$search_desc}%");
			}
		})->count();
		$returndata = [];
		$returndata["count"] = $count;
		$returndata["log_list"] = $logs;
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $returndata]);
	}
}