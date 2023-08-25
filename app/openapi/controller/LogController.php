<?php

namespace app\openapi\controller;

/**
 * @title 日志
 * @description 接口说明
 */
class LogController extends \cmf\controller\HomeBaseController
{
	public function systemLog(\think\Request $request)
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
		$logs = \think\Db::name("activity_log")->field("id,description,ipaddr ip,port,create_time,user")->where($fun)->where(function (\think\db\Query $query) use($param) {
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
		})->order("{$orderby} {$sorting}")->order("create_time", "DESC")->page($page)->limit($limit)->select()->toArray();
		$count = \think\Db::name("activity_log")->where($fun)->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["keywords"])) {
				$search_desc = $param["keywords"];
				$query->whereOr("description", "like", "%{$search_desc}%");
				$query->whereOr("ipaddr", "like", "%{$search_desc}%");
			}
		})->count();
		$returndata = [];
		$returndata["total"] = $count;
		$returndata["log"] = $logs;
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $returndata]);
	}
	public function loginLog(\think\Request $request)
	{
		$page = $request->param("page", 1);
		$limit = $request->param("limit", 10);
		$orderby = $request->param("orderby", "id");
		$sort = $request->param("sort", "desc");
		$where[] = ["uid", "=", $request->uid];
		$param = $request->param();
		$where[] = ["type", "=", 1];
		$res = \think\Db::name("activity_log")->field("id,description,ipaddr ip,port,create_time,user")->where($where)->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["keywords"])) {
				$search_desc = $param["keywords"];
				$query->whereOr("description", "like", "%{$search_desc}%");
				$query->whereOr("ipaddr", "like", "%{$search_desc}%");
			}
		})->page($page, $limit)->order($orderby, $sort)->select()->toArray();
		$count = \think\Db::name("activity_log")->where($where)->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["keywords"])) {
				$search_desc = $param["keywords"];
				$query->whereOr("description", "like", "%{$search_desc}%");
				$query->whereOr("ipaddr", "like", "%{$search_desc}%");
			}
		})->count();
		$data = ["total" => $count, "log" => $res];
		return json(["data" => $data, "status" => "200", "msg" => lang("SUCCESS MESSAGE")]);
	}
	public function apiLog()
	{
		$param = $this->request->param();
		$page = !empty($param["page"]) ? intval($param["page"]) : config("page");
		$limit = !empty($param["limit"]) ? intval($param["limit"]) : config("limit");
		$order = !empty($param["order"]) ? trim($param["order"]) : "a.id";
		$sort = !empty($param["sort"]) ? trim($param["sort"]) : "DESC";
		$where = function (\think\db\Query $query) use($param) {
			$query->where("a.uid", request()->uid);
			if (!empty($param["keywords"])) {
				$keyword = $param["keywords"];
				$query->where("a.ip|a.description|b.username|a.port", "like", "%{$keyword}%");
			}
		};
		$logs = \think\Db::name("api_resource_log")->alias("a")->field("a.id,a.description,a.ip,a.port,a.create_time,b.username user")->leftJoin("clients b", "a.uid = b.id")->where($where)->withAttr("description", function ($value, $data) {
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
		})->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		$count = \think\Db::name("api_resource_log")->alias("a")->leftJoin("clients b", "a.uid = b.id")->where($where)->count();
		$data = ["log" => $logs, "total" => $count];
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
}