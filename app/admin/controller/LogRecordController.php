<?php

namespace app\admin\controller;

/**
 * @title 日志记录
 * @description 接口描述
 */
class LogRecordController extends AdminBaseController
{
	/**
	 * @title 系统日志
	 * @description 接口说明:系统日志
	 * @author 萧十一郎
	 * @url /admin/log_record/systemlog
	 * @method GET
	 * @time 2019-11-27
	 * @param name:page type:int  require:0  default:1 other: desc:页码
	 * @param name:limit type:int  require:0  default:1 other: desc:每页条数
	 * @param name:search_time type:int  require:0  default: other: desc:传入时间戳，返回当天日志
	 * @param name:search_name type:string  require:0  default: other: desc:查找用户名
	 * @param name:search_desc type:string  require:0  default: other: desc:通过描述查询
	 * @param name:search_ip type:string  require:0  default: other: desc:ip地址查询
	 * @param name:orderby type:string  require:0  default:id other: desc:排序字段
	 * @param name:sorting type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @return log_list:日志数据@
	 * @log_list  create_time:时间
	 * @log_list  description:描述
	 * @log_list  new_desc:带跳转链接的描述
	 * @log_list  user:用户
	 * @log_list  ipaddr:ip地址
	 *
	 * @return search_time:搜索特定某一天
	 * @return search_name:搜索操作用户
	 * @return search_desc:搜索描述
	 * @return search_ip:搜索ip地址
	 * @return pagecount:每页显示条数
	 * @return page:当前页码
	 * @return orderby:排序字段
	 * @return sorting:asc/desc,顺序或倒叙
	 * @return total_page:总页码
	 * @return count:总新闻数量
	 */
	public function getSystemLog(\think\Request $request)
	{
		$zjmf_authorize = configuration("zjmf_authorize");
		if (empty($zjmf_authorize)) {
			\compareLicense();
		} else {
			$_strcode = _strcode($zjmf_authorize, "DECODE", "zjmf_key_strcode");
			$_strcode = explode("|zjmf|", $_strcode);
			$authkey = "-----BEGIN PUBLIC KEY-----\r\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDg6DKmQVwkQCzKcFYb0BBW7N2f\r\nI7DqL4MaiT6vibgEzH3EUFuBCRg3cXqCplJlk13PPbKMWMYsrc5cz7+k08kgTpD4\r\ntevlKOMNhYeXNk5ftZ0b6MAR0u5tiyEiATAjRwTpVmhOHOOh32MMBkf+NNWrZA/n\r\nzcLRV8GU7+LcJ8AH/QIDAQAB\r\n-----END PUBLIC KEY-----";
			$pu_key = openssl_pkey_get_public($authkey);
			foreach ($_strcode as $v) {
				openssl_public_decrypt(base64_decode($v), $de, $pu_key);
				$de_str .= $de;
			}
			$auth = json_decode($de_str, true);
			if (time() > $auth["last_license_time"] + 86400) {
				\compareLicense();
			}
		}
		$param = $request->param();
		$page = isset($param["page"]) ? intval($param["page"]) : config("page");
		$limit = isset($param["limit"]) ? intval($param["limit"]) : (configuration("NumRecordstoDisplay") ?: config("limit"));
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sorting = $param["sorting"] ?? "DESC";
		$log_list = \think\Db::name("activity_log")->field("create_time,id,ipaddr,description as new_desc,uid,user,port")->where(function (\think\db\Query $query) use($param) {
			$query->where("user", "neq", "System");
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("create_time", "<=", $end_time);
				$query->where("create_time", ">=", $start_time);
			}
			if (!empty($param["search_name"])) {
				$search_name = $param["search_name"];
				$query->where("user", "like", "%{$search_name}%");
			}
			if (!empty($param["search_desc"])) {
				$search_desc = $param["search_desc"];
				$query->where("description", "like", "%{$search_desc}%");
			}
			if (!empty($param["search_ip"])) {
				$search_ip = $param["search_ip"];
				$query->where("ipaddr", "like", "%{$search_ip}%");
			}
		})->withAttr("new_desc", function ($value, $data) {
			$pattern = "/(?P<name>\\w+ ID):(?P<digit>\\d+)/";
			preg_match_all($pattern, $value, $matches);
			$name = $matches["name"];
			$digit = $matches["digit"];
			if (!empty($name)) {
				foreach ($name as $k => $v) {
					$relid = $digit[$k];
					$str = $v . ":" . $relid;
					if ($v == "Invoice ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/bill-detail?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "User ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-view/abstract?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: \">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "FlowPacket ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/dcim-traffic?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Host ID") {
						$host = \think\Db::name("host")->alias("a")->field("a.uid")->where("a.id", $relid)->find();
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-view/product-innerpage?hid=" . $relid . "&id=" . $host["uid"] . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Promo_codeID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/promo-code-add?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Order ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/order-detail?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Admin ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/admin-edit?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Contacts ID") {
					} elseif ($v == "News ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/add-news?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "TD ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/new-work-order-dept?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Ticket ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/support-ticket?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Product ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/edit-product?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "IP") {
					} elseif ($v == "PCG ID") {
						$pco = \think\Db::name("product_config_options")->where("id", $relid)->find();
						$url = "<a class=\"el-link el-link--primary is-underline\" target=\"blank\" href=\"#/edit-configurable-option-group?groupId=" . $pco["gid"] . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Service ID") {
						$server = \think\Db::name("servers")->where("id", $relid)->find();
						if ($server["server_type"] == "dcimcloud") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/zjmfcloud\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($server["server_type"] == "dcim") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/dcim\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} else {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/add-server?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						}
					} elseif ($v == "Create ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/balance-details?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Transaction ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/business-statement?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Role ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/permissions-edit?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Group ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-group?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "ProductGroup ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/add-product-group?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Currency ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/currency-settings?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
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
		$count = \think\Db::name("activity_log")->where(function (\think\db\Query $query) use($param) {
			$query->where("user", "neq", "System");
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("create_time", "<=", $end_time);
				$query->where("create_time", ">=", $start_time);
			}
			if (!empty($param["search_name"])) {
				$search_name = $param["search_name"];
				$query->where("user", "like", "%{$search_name}%");
			}
			if (!empty($param["search_desc"])) {
				$search_desc = $param["search_desc"];
				$query->where("description", "like", "%{$search_desc}%");
			}
			if (!empty($param["search_ip"])) {
				$search_ip = $param["search_ip"];
				$query->where("ipaddr", "like", "%{$search_ip}%");
			}
		})->count();
		$user_list = \think\Db::name("activity_log")->field("user")->group("user")->select()->toArray();
		$returndata = [];
		$returndata["count"] = $count;
		$returndata["user_list"] = array_column($user_list, "user");
		$returndata["log_list"] = $log_list;
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $returndata]);
	}
	/**
	 * @title 自动任务日志
	 * @description 接口说明:自动任务系统日志
	 * @author 萧十一郎
	 * @url /admin/log_record/cronsystemlog
	 * @method GET
	 * @time 2019-11-27
	 * @param name:page type:int  require:0  default:1 other: desc:页码
	 * @param name:limit type:int  require:0  default:1 other: desc:每页条数
	 * @param name:search_time type:int  require:0  default: other: desc:传入时间戳，返回当天日志
	 * @param name:search_name type:string  require:0  default: other: desc:查找用户名
	 * @param name:search_desc type:string  require:0  default: other: desc:通过描述查询
	 * @param name:search_ip type:string  require:0  default: other: desc:ip地址查询
	 * @param name:orderby type:string  require:0  default:id other: desc:排序字段
	 * @param name:sorting type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @return log_list:日志数据@
	 * @log_list  create_time:时间
	 * @log_list  description:描述
	 * @log_list  new_desc:带跳转链接的描述
	 * @log_list  user:用户
	 * @log_list  ipaddr:ip地址
	 *
	 * @return search_time:搜索特定某一天
	 * @return search_name:搜索操作用户
	 * @return search_desc:搜索描述
	 * @return search_ip:搜索ip地址
	 * @return pagecount:每页显示条数
	 * @return page:当前页码
	 * @return orderby:排序字段
	 * @return sorting:asc/desc,顺序或倒叙
	 * @return total_page:总页码
	 * @return count:总新闻数量
	 */
	public function getCronSystemLog(\think\Request $request)
	{
		$param = $request->param();
		$page = isset($param["page"]) ? intval($param["page"]) : config("page");
		$limit = isset($param["limit"]) ? intval($param["limit"]) : (configuration("NumRecordstoDisplay") ?: config("limit"));
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sorting = $param["sorting"] ?? "DESC";
		$log_list = \think\Db::name("activity_log")->field("create_time,id,ipaddr,description as new_desc,uid,user,port")->where(function (\think\db\Query $query) use($param) {
			$query->where("user", "System");
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("create_time", "<=", $end_time);
				$query->where("create_time", ">=", $start_time);
			}
			if (!empty($param["search_name"])) {
				$search_name = $param["search_name"];
				$query->where("user", "like", "%{$search_name}%");
			}
			if (!empty($param["search_desc"])) {
				$search_desc = $param["search_desc"];
				$query->where("description", "like", "%{$search_desc}%");
			}
			if (!empty($param["search_ip"])) {
				$search_ip = $param["search_ip"];
				$query->where("ipaddr", "like", "%{$search_ip}%");
			}
		})->withAttr("new_desc", function ($value, $data) {
			$pattern = "/(?P<name>\\w+ ID):(?P<digit>\\d+)/";
			preg_match_all($pattern, $value, $matches);
			$name = $matches["name"];
			$digit = $matches["digit"];
			if (!empty($name)) {
				foreach ($name as $k => $v) {
					$relid = $digit[$k];
					$str = $v . ":" . $relid;
					if ($v == "Invoice ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/bill-detail?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "User ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-view/abstract?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: \">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "FlowPacket ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/dcim-traffic?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Host ID") {
						$host = \think\Db::name("host")->alias("a")->field("a.uid")->where("a.id", $relid)->find();
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-view/product-innerpage?hid=" . $relid . "&id=" . $host["uid"] . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Promo_codeID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/promo-code-add?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Order ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/order-detail?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Admin ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/admin-edit?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Contacts ID") {
					} elseif ($v == "News ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/add-news?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "TD ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/new-work-order-dept?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Ticket ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/support-ticket?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Product ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/edit-product?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "IP") {
					} elseif ($v == "PCG ID") {
						$pco = \think\Db::name("product_config_options")->where("id", $relid)->find();
						$url = "<a class=\"el-link el-link--primary is-underline\" target=\"blank\" href=\"#/edit-configurable-option1?groupId=" . $pco["gid"] . "&optionId=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Service ID") {
						$server = \think\Db::name("servers")->where("id", $relid)->find();
						if ($server["server_type"] == "dcimcloud") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/zjmfcloud\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($server["server_type"] == "dcim") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/dcim\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} else {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/add-server?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						}
					} elseif ($v == "Create ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/balance-details?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Transaction ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/business-statement?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Role ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/permissions-edit?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Group ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-group?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Currency ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/currency-settings?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
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
		$count = \think\Db::name("activity_log")->where(function (\think\db\Query $query) use($param) {
			$query->where("user", "System");
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("create_time", "<=", $end_time);
				$query->where("create_time", ">=", $start_time);
			}
			if (!empty($param["search_name"])) {
				$search_name = $param["search_name"];
				$query->where("user", "like", "%{$search_name}%");
			}
			if (!empty($param["search_desc"])) {
				$search_desc = $param["search_desc"];
				$query->where("description", "like", "%{$search_desc}%");
			}
			if (!empty($param["search_ip"])) {
				$search_ip = $param["search_ip"];
				$query->where("ipaddr", "like", "%{$search_ip}%");
			}
		})->count();
		$user_list = \think\Db::name("activity_log")->field("user")->group("user")->select()->toArray();
		$returndata = [];
		$returndata["count"] = $count;
		$returndata["user_list"] = array_column($user_list, "user");
		$returndata["log_list"] = $log_list;
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $returndata]);
	}
	/**
	 * @title 系统管理员登录日志
	 * @description 接口说明:系统管理员登录日志
	 * @author 萧十一郎
	 * @url /admin/log_record/adminlog
	 * @method GET
	 * @time 2019-11-30
	 * @param .name:page type:int require:0 default:1 other: desc:页码
	 * @param .name:limit type:int require:0 default:1 other: desc:每页条数
	 * @param name:orderby type:string  require:0  default:id other: desc:排序字段
	 * @param name:sorting type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @param name:search_time type:int  require:0  default: other: desc:传入时间戳，返回当天日志
	 * @param name:search_name type:string  require:0  default: other: desc:查找用户名
	 * @param name:search_ip type:string  require:0  default: other: desc:ip地址查询
	 * @return page:页码
	 * @return log_list:日志数据@
	 * @log_list  admin_username:管理员名
	 * @log_list  logintime:登录时间
	 * @log_list  logouttime:注销时间
	 * @log_list  ipaddress:ip 地址
	 * @log_list  lastvisit:最后访问
	 */
	public function getAdminLog(\think\Request $request)
	{
		$param = $request->param();
		$page = isset($param["page"]) ? intval($param["page"]) : config("page");
		$limit = isset($param["limit"]) ? intval($param["limit"]) : (configuration("NumRecordstoDisplay") ?: config("limit"));
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sorting = $param["sorting"] ?? "DESC";
		$count = \think\Db::name("admin_log")->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("logintime", "<=", $end_time);
				$query->where("logintime", ">=", $start_time);
			}
			if (!empty($param["search_name"])) {
				$search_name = $param["search_name"];
				$query->where("admin_username", "like", "%{$search_name}%");
			}
			if (!empty($param["search_ip"])) {
				$search_ip = $param["search_ip"];
				$query->where("ipaddress", "like", "%{$search_ip}%");
			}
		})->count();
		$log_list = \think\Db::name("admin_log")->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("logintime", "<=", $end_time);
				$query->where("logintime", ">=", $start_time);
			}
			if (!empty($param["search_name"])) {
				$search_name = $param["search_name"];
				$query->where("admin_username", "like", "%{$search_name}%");
			}
			if (!empty($param["search_ip"])) {
				$search_ip = $param["search_ip"];
				$query->where("ipaddress", "like", "%{$search_ip}%");
			}
		})->withAttr("ipaddress", function ($value, $data) {
			if (empty($data["port"])) {
				return $value;
			} else {
				return $value .= ":" . $data["port"];
			}
		})->order("{$orderby} {$sorting}")->order("id", "DESC")->page($page)->limit($limit)->select()->toArray();
		$user_list = \think\Db::name("admin_log")->field("admin_username")->group("admin_username")->select()->toArray();
		$user_list = array_column($user_list, "admin_username");
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "count" => $count, "data" => $log_list, "user_list" => $user_list]);
	}
	/**
	 * @title 通知日志
	 * @description 接口说明:通知日志
	 * @author 萧十一郎
	 * @url /admin/log_record/notifylog
	 * @method GET
	 * @time 2019-11-30
	 * @param .name:page type:int require:0 default:1 other: desc:页码
	 * @param .name:limit type:int require:0 default:1 other: desc:每页条数
	 * @param name:orderby type:string  require:0  default:id other: desc:排序字段
	 * @param name:sorting type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @param name:search_time type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @param name:message type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @param name:type type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @return page:页码
	 * @return log_list:日志数据@
	 * @log_list to:接收人
	 * @log_list create_time:发送时间
	 * @log_list message:消息内容
	 * @log_list type:类型（微信，短信，邮件）
	 * @log_list subject:主题
	 * @log_list uid:用户id
	 */
	public function getNotifyLog(\think\Request $request)
	{
		$param = $request->param();
		$page = isset($param["page"]) ? intval($param["page"]) : config("page");
		$limit = isset($param["limit"]) ? intval($param["limit"]) : (configuration("NumRecordstoDisplay") ?: config("limit"));
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sorting = $param["sorting"] ?? "DESC";
		$count = \think\Db::name("notify_log")->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("create_time", "<=", $end_time);
				$query->where("create_time", ">=", $start_time);
			}
			if (!empty($param["message"])) {
				$message = $param["message"];
				$query->where("message", "like", "%{$message}%");
			}
			if (!empty($param["type"])) {
				$type = $param["type"];
				$query->where("type", "like", "%{$type}%");
			}
		})->count();
		$log_list = \think\Db::name("notify_log")->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("create_time", "<=", $end_time);
				$query->where("create_time", ">=", $start_time);
			}
			if (!empty($param["message"])) {
				$message = $param["message"];
				$query->where("message", "like", "%{$message}%");
			}
			if (!empty($param["type"])) {
				$type = $param["type"];
				$query->where("type", "like", "%{$type}%");
			}
		})->order("{$orderby} {$sorting}")->order("id", "DESC")->page($page)->limit($limit)->select()->toArray();
		$type = ["email", "sms", "wechat"];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "count" => $count, "data" => $log_list, "type" => $type]);
	}
	/**
	 * @title 系统邮件日志
	 * @description 接口说明:系统邮件日志
	 * @author wyh
	 * @url /admin/log_record/emaillog
	 * @method GET
	 * @time 2019-11-30
	 * @param .name:page type:int require:0 default:1 other: desc:页码
	 * @param .name:limit type:int require:0 default:1 other: desc:每页条数
	 * @param name:orderby type:string  require:0  default:id other: desc:排序字段
	 * @param name:sorting type:string  require:0  default:asc other: desc:，顺序或倒叙
	 * @param name:search_time type:string  require:0  default: other: desc:，查询时间
	 * @param name:subject type:string  require:0  default: other: desc:，主题
	 * @param name:username type:string  require:0  default: other: desc:，收件人
	 * @param name:uid type:string  require:0  default: other: desc:，收件人id
	 * @return log_list:日志数据@
	 * @log_list  create_time:时间
	 * @log_list  subject:主题
	 * @log_list  username:收件人
	 * @log_list  status:状态1成功 0 失败
	 * @log_list  fail_reason:原因
	 */
	public function getEmailLog()
	{
		$param = $this->request->param();
		$page = isset($param["page"]) ? intval($param["page"]) : config("page");
		$limit = isset($param["limit"]) ? intval($param["limit"]) : (configuration("NumRecordstoDisplay") ?: config("limit"));
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sorting = $param["sorting"] ?? "DESC";
		$count = \think\Db::name("email_log")->alias("a")->field("a.id,a.to,a.create_time,a.subject,b.username,a.status,a.fail_reason,a.ip")->leftJoin("clients b", "b.id = a.uid")->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["uid"])) {
				$query->where("uid", $param["uid"])->where("a.is_admin", 0);
			}
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("a.create_time", "<=", $end_time);
				$query->where("a.create_time", ">=", $start_time);
			}
			if (!empty($param["subject"])) {
				$subject = $param["subject"];
				$query->where("a.subject", "like", "%{$subject}%");
			}
			if (!empty($param["username"])) {
				$username = $param["username"];
				$query->where("a.to", "like", "%{$username}%");
			}
		})->count();
		$email_lists = \think\Db::name("email_log")->alias("a")->field("a.id,a.to,a.create_time,a.subject,b.username,a.status,a.fail_reason,a.ip,a.port")->leftJoin("clients b", "b.id = a.uid")->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["uid"])) {
				$query->where("uid", $param["uid"])->where("a.is_admin", 0);
			}
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("a.create_time", "<=", $end_time);
				$query->where("a.create_time", ">=", $start_time);
			}
			if (!empty($param["subject"])) {
				$subject = $param["subject"];
				$query->where("a.subject", "like", "%{$subject}%");
			}
			if (!empty($param["username"])) {
				$username = $param["username"];
				$query->where("a.to", "like", "%{$username}%");
			}
		})->withAttr("ip", function ($value, $data) {
			if (empty($data["port"])) {
				return $value;
			} else {
				return $value .= ":" . $data["port"];
			}
		})->order("{$orderby} {$sorting}")->order("id", "DESC")->page($page)->limit($limit)->select()->toArray();
		$email_lists_filter = [];
		foreach ($email_lists as $key => $email_list) {
			$email_lists_filter[$key] = $email_list;
			$email_lists_filter[$key]["username"] = $email_list["to"];
		}
		$user_list = \think\Db::name("clients")->alias("b")->field("b.id,b.username")->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "count" => $count, "data" => $email_lists_filter, "user_list" => $user_list]);
	}
	/**
	 * @title 查看邮件信息
	 * @description 接口说明:查看邮件信息
	 * @author wyh
	 * @url /admin/log_record/emaildetail/:id
	 * @method GET
	 * @time 2019-11-30
	 * @param .name:id type:int require:0 default:1 other: desc:id
	 * @return detail:日志数据@
	 * @detail username:发送给
	 * @detail to:邮件
	 * @detail subject:主题
	 * @detail message:信息
	 */
	public function getEmailDetail()
	{
		$params = $this->request->param();
		$id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$id) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$detail = \think\Db::name("email_log")->field("to,subject,message,is_admin,uid")->where("id", $id)->find();
		if ($detail["is_admin"]) {
			$detail["username"] = \think\Db::name("user")->where("id", $detail["uid"])->value("user_nickname");
		} else {
			$detail["username"] = \think\Db::name("clients")->where("id", $detail["uid"])->value("username");
		}
		$detail["content"] = htmlspecialchars_decode($detail["content"]);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "detail" => $detail]);
	}
	public function getWechatLog()
	{
	}
	/**
	 * @title 系统短信日志
	 * @description 接口说明:系统短信日志
	 * @author 刘国栋
	 * @url /admin/log_record/smslog
	 * @method GET
	 * @time 2020-05-26
	 * @param .name:page type:int require:0 default:1 other: desc:页码
	 * @param .name:limit type:int require:0 default:1 other: desc:每页条数
	 * @param name:orderby type:string  require:0  default:id other: desc:排序字段
	 * @param name:sorting type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @param name:search_time type:string  require:0  default:asc other: desc时间
	 * @param name:phone type:int  require:0  default: other: desc:手机
	 * @param name:username type:string  require:0  default: other: desc:姓名
	 * @param name:uid type:int  require:0  default: other: desc:用户id
	 * @return log_list:日志数据@
	 * @log_list  create_time:时间
	 * @log_list  phone:手机
	 * @log_list  content:内容
	 * @log_list  phone_code:手机验证码
	 * @log_list  uid:接收人
	 * @log_list  username:接收人
	 * @log_list  status:0失败1成功
	 * @log_list  fail_reason:失败原因
	 */
	public function getSmsLog()
	{
		$param = $this->request->param();
		$page = isset($param["page"]) ? intval($param["page"]) : config("page");
		$limit = isset($param["limit"]) ? intval($param["limit"]) : (configuration("NumRecordstoDisplay") ?: config("limit"));
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sorting = $param["sorting"] ?? "DESC";
		$count = \think\Db::name("message_log")->alias("a")->field("a.id,a.uid,a.create_time,a.content,a.fail_reason,a.status,a.phone,a.phone_code,b.username,a.ip,a.port")->leftJoin("clients b", "b.id = a.uid")->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["uid"])) {
				$query->where("uid", $param["uid"]);
			}
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("a.create_time", "<=", $end_time);
				$query->where("a.create_time", ">=", $start_time);
			}
			if (!empty($param["phone"])) {
				$phone = $param["phone"];
				$query->where("a.phone", "like", "%{$phone}%");
			}
			if (!empty($param["username"])) {
				$username = $param["username"];
				$query->where("b.username", "like", "%{$username}%");
			}
		})->count();
		$email_lists = \think\Db::name("message_log")->alias("a")->field("a.id,a.uid,a.create_time,a.content,a.fail_reason,a.status,a.phone,a.phone_code,b.username,a.ip,a.port")->leftJoin("clients b", "b.id = a.uid")->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["uid"])) {
				$query->where("uid", $param["uid"]);
			}
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("a.create_time", "<=", $end_time);
				$query->where("a.create_time", ">=", $start_time);
			}
			if (!empty($param["phone"])) {
				$phone = $param["phone"];
				$query->where("a.phone", "like", "%{$phone}%");
			}
			if (!empty($param["username"])) {
				$username = $param["username"];
				$query->where("b.username", "like", "%{$username}%");
			}
		})->withAttr("ip", function ($value, $data) {
			if (empty($data["port"])) {
				return $value;
			} else {
				return $value .= ":" . $data["port"];
			}
		})->order("{$orderby} {$sorting}")->order("id", "DESC")->page($page)->limit($limit)->select()->toArray();
		$email_lists_filter = [];
		foreach ($email_lists as $key => $email_list) {
			$email_lists_filter[$key] = $email_list;
		}
		$user_list = \think\Db::name("clients")->alias("b")->field("b.id,b.username")->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "count" => $count, "data" => $email_lists_filter, "user_list" => $user_list]);
	}
	/**
	 * @title 系统短信日志
	 * @description 接口说明:系统短信日志
	 * @author 刘国栋
	 * @url /admin/log_record/smslogm
	 * @method GET
	 * @time 2020-05-26
	 * @param .name:page type:int require:0 default:1 other: desc:页码
	 * @param .name:limit type:int require:0 default:1 other: desc:每页条数
	 * @param name:orderby type:string  require:0  default:id other: desc:排序字段
	 * @param name:sorting type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @param name:uid type:int  require:0  default: other: desc:uid
	 * @return log_list:日志数据@
	 * @log_list  create_time:时间
	 * @log_list  phone:手机
	 * @log_list  content:内容
	 * @log_list  phone_code:手机验证码
	 * @log_list  uid:接收人
	 * @log_list  username:接收人
	 * @log_list  status:0失败1成功
	 * @log_list  fail_reason:失败原因
	 */
	public function getSmsLogM()
	{
		$param = $this->request->param();
		$uid = intval($param["uid"]);
		$page = isset($param["page"]) ? intval($param["page"]) : config("page");
		$limit = isset($param["limit"]) ? intval($param["limit"]) : (configuration("NumRecordstoDisplay") ?: config("limit"));
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sorting = $param["sorting"] ?? "DESC";
		$count = \think\Db::name("message_log")->alias("a")->field("a.id,a.uid,a.create_time,a.content,a.fail_reason,a.status,a.phone,a.phone_code,b.username,a.ip")->leftJoin("clients b", "b.id = a.uid")->where(function (\think\db\Query $query) use($uid, $param) {
			$query->where("uid", $uid);
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("a.create_time", "<=", $end_time);
				$query->where("a.create_time", ">=", $start_time);
			}
			if (!empty($param["phone"])) {
				$phone = $param["phone"];
				$query->where("a.phone", "like", "%{$phone}%");
			}
			if (!empty($param["username"])) {
				$username = $param["username"];
				$query->where("b.username", "like", "%{$username}%");
			}
		})->count();
		$email_lists = \think\Db::name("message_log")->alias("a")->field("a.id,a.uid,a.create_time,a.content,a.fail_reason,a.status,a.phone,a.phone_code,b.username")->leftJoin("clients b", "b.id = a.uid")->where(function (\think\db\Query $query) use($uid, $param) {
			$query->where("uid", $uid);
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("a.create_time", "<=", $end_time);
				$query->where("a.create_time", ">=", $start_time);
			}
			if (!empty($param["phone"])) {
				$phone = $param["phone"];
				$query->where("a.phone", "like", "%{$phone}%");
			}
			if (!empty($param["username"])) {
				$username = $param["username"];
				$query->where("b.username", "like", "%{$username}%");
			}
		})->order("{$orderby} {$sorting}")->order("id", "DESC")->page($page)->limit($limit)->select()->toArray();
		$email_lists_filter = [];
		foreach ($email_lists as $key => $email_list) {
			$email_lists_filter[$key] = $email_list;
		}
		$user_list = \think\Db::name("message_log")->alias("a")->leftJoin("clients b", "b.id = a.uid")->field("b.id,b.username")->group("b.username")->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "count" => $count, "data" => $email_lists_filter, "user_list" => $user_list]);
	}
	/**
	 * @title 站内信日志列表
	 * @description 接口说明: 站内信日志列表
	 * @url /admin/log_record/system_message_log
	 * @method GET
	 * @param .name:search_time type:array require:0 default: other: desc:时间段search_time[0] 开始时间，search_time[1]结束时间
	 * @param .name:read_type type:int require:0 default:-1 other: desc:状态：-1全部，0-未读，1-已读
	 * @param .name:keywords type:string require:0 default: other: desc:搜索关键字-主题
	 * @param .name:username type:string require:0 default: other: desc:搜索关键字-客户名
	 * @param .name:page type:int require:0 default:1 other: desc:页码
	 * @param .name:limit type:int require:0 default:1 other: desc:每页条数
	 * @param name:orderby type:string  require:0  default:id other: desc:排序字段
	 * @param name:sorting type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @param name:uid type:int  require:0  default: other: desc:uid
	 */
	public function getSystemMessageLog(\think\Request $request)
	{
		$param = $this->request->param();
		$page = isset($param["page"]) ? intval($param["page"]) : config("page");
		$limit = isset($param["limit"]) ? intval($param["limit"]) : (configuration("NumRecordstoDisplay") ?: config("limit"));
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sorting = $param["sorting"] ?? "DESC";
		$count = \think\Db::name("system_message")->alias("sm")->join("clients c", "c.id = sm.uid")->where(function (\think\db\Query $query) use($param) {
			if (intval($param["uid"])) {
				$query->where("sm.uid", intval($param["uid"]));
			}
			if ($param["search_time"][0]) {
				$query->where("sm.create_time", ">=", $param["search_time"][0]);
			}
			if ($param["search_time"][1]) {
				$query->where("sm.create_time", "<=", $param["search_time"][1]);
			}
			if ($param["read_type"] == 0) {
				$query->where("sm.read_time", 0);
			}
			if ($param["read_type"] == 1) {
				$query->where("sm.read_time", ">", 0);
			}
			if (trim($param["keywords"])) {
				$query->where("sm.title", "like", "%" . trim($param["keywords"]) . "%");
			}
			if (trim($param["username"])) {
				$query->where("c.username", "like", "%" . trim($param["username"]) . "%");
			}
		})->count();
		$list = \think\Db::name("system_message")->alias("sm")->field("sm.*,c.username,c.phonenumber,c.email")->join("clients c", "c.id = sm.uid")->where(function (\think\db\Query $query) use($param) {
			if (intval($param["uid"])) {
				$query->where("sm.uid", intval($param["uid"]));
			}
			if ($param["search_time"][0]) {
				$query->where("sm.create_time", ">=", $param["search_time"][0]);
			}
			if ($param["search_time"][1]) {
				$query->where("sm.create_time", "<=", $param["search_time"][1]);
			}
			if ($param["read_type"] == 0) {
				$query->where("sm.read_time", 0);
			}
			if ($param["read_type"] == 1) {
				$query->where("sm.read_time", ">", 0);
			}
			if (trim($param["keywords"])) {
				$query->where("sm.title", "like", "%" . trim($param["keywords"]) . "%");
			}
			if (trim($param["username"])) {
				$query->where("c.username", "like", "%" . trim($param["username"]) . "%");
			}
		})->order($orderby, $sorting)->page($page)->limit($limit)->select()->toArray();
		if ($list) {
			foreach ($list as &$item) {
				$item["content"] = htmlspecialchars_decode($item["content"]);
				$item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
				if ($item["attachment"]) {
					$attachment = explode(",", $item["attachment"]);
					$attachment_arr = [];
					foreach ($attachment as $at_item) {
						$at_info = explode("^", $at_item);
						$temp = [];
						$temp["path"] = $_SERVER["REQUEST_SCHEME"] . "://" . $request->host() . config("system_message_url") . $at_item;
						$temp["name"] = $at_info[1];
						$attachment_arr[] = $temp;
					}
					$item["attachment"] = $attachment_arr;
				}
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["count" => $count, "list" => $list]]);
	}
	/**
	 * @title Api日志列表
	 * @description 接口说明: Api日志列表
	 * @url /admin/log_record/api_log
	 * @method GET
	 * @param .name:keywords type:string require:0 default: other: desc:搜索关键字
	 * @param .name:time type:string require:0 default: other: desc:按时间搜索
	 * @param .name:uid type:string require:0 default: other: desc:按用户搜索,公共接口调列表,与产品内页客户列表那里调用相同
	 * @param .name:page type:int require:0 default:1 other: desc:页码
	 * @param .name:limit type:int require:0 default:1 other: desc:每页条数
	 * @param name:orderby type:string  require:0  default:id other: desc:排序字段
	 * @param name:sorting type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @param name:uid type:int  require:0  default: other: desc:uid
	 */
	public function getApiLog()
	{
		$param = $this->request->param();
		$keywords = isset($param["keywords"]) ? trim($param["keywords"]) : "";
		$time = isset($param["time"]) ? $param["time"] : 0;
		$uid = isset($param["uid"]) ? intval($param["uid"]) : 0;
		$page = isset($param["page"]) ? intval($param["page"]) : config("page");
		$limit = isset($param["limit"]) ? intval($param["limit"]) : (configuration("NumRecordstoDisplay") ?: config("limit"));
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "a.id";
		$sorting = $param["sorting"] ?? "DESC";
		$where = function (\think\db\Query $query) use($keywords, $time, $uid) {
			if (!empty($keywords)) {
				$query->where("a.description|a.ip", "like", "%{$keywords}%");
			}
			if (!empty($uid)) {
				$query->where("a.uid", $uid);
			}
			if (!empty($time)) {
				$start_time = strtotime(date("Y-m-d", $time));
				$end_time = strtotime(date("Y-m-d", $time)) + 86400;
				$query->where("a.create_time", "<=", $end_time);
				$query->where("a.create_time", ">=", $start_time);
			}
		};
		$count = \think\Db::name("api_resource_log")->alias("a")->field("a.id,a.create_time,a.description,a.ip,b.username")->leftJoin("clients b", "a.uid = b.id")->where($where)->count();
		$list = \think\Db::name("api_resource_log")->alias("a")->field("a.id,a.create_time,a.description,a.ip,b.username,a.port,a.uid")->leftJoin("clients b", "a.uid = b.id")->where($where)->withAttr("description", function ($value, $data) {
			$pattern = "/(?P<name>\\w+ ID):(?P<digit>\\d+)/";
			preg_match_all($pattern, $value, $matches);
			$name = $matches["name"];
			$digit = $matches["digit"];
			if (!empty($name)) {
				foreach ($name as $k => $v) {
					$relid = $digit[$k];
					$str = $v . ":" . $relid;
					if ($v == "User ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-view/abstract?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: \">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Product ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/edit-product?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					}
				}
				return $value;
			} else {
				return $value;
			}
		})->withAttr("ip", function ($value, $data) {
			if (empty($data["port"])) {
				return $value;
			} else {
				return $value .= ":" . $data["port"];
			}
		})->order($orderby, $sorting)->page($page)->limit($limit)->select()->toArray();
		$uids = \think\Db::name("api_resource_log")->field("uid")->distinct(true)->column("uid");
		$user = \think\Db::name("clients")->field("id,username")->whereIn("id", $uids)->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["count" => $count, "list" => $list, "user" => $user]]);
	}
	/**
	 * @title 删除日志页面
	 * @description 接口说明: 删除日志页面
	 * @author wyh
	 * @time 2020-12-02
	 * @url /admin/log_record/delete_log_page
	 * @method GET
	 * @param .name:type type:string require:0 default: other: desc:日志类型
	 * @return  count:总数
	 * @return  type:类型
	 */
	public function getDeleteLogPage()
	{
		$param = $this->request->param();
		$type = $param["type"];
		if (!in_array($type, array_keys(config("log_type")))) {
			$type = "system_log";
		}
		switch ($type) {
			case "system_log":
				$count = \think\Db::name("activity_log")->where("user", "neq", "System")->count();
				break;
			case "admin_log":
				$count = \think\Db::name("admin_log")->count();
				break;
			case "email_log":
				$count = \think\Db::name("email_log")->count();
				break;
			case "sms_log":
				$count = \think\Db::name("message_log")->count();
				break;
			case "system_message_log":
				$count = \think\Db::name("system_message")->count();
				break;
			case "cron_system_log":
				$count = \think\Db::name("activity_log")->where("user", "like", "System")->count();
				break;
			case "api_log":
				$count = \think\Db::name("api_resource_log")->count();
				break;
			default:
				$count = 0;
				break;
		}
		$data = ["count" => $count, "type" => config("log_type")];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 删除日志页面(二次确认)
	 * @description 接口说明: 删除日志页面(二次确认)
	 * @author wyh
	 * @time 2020-12-02
	 * @url /admin/log_record/affirm_delete_log_page
	 * @method GET
	 * @param .name:type type:string require:1 default: other: desc:日志类型
	 * @param .name:time type:string require:1 default: other: desc:时间 没选择，就不传此参数
	 */
	public function getAffirmDeleteLogPage()
	{
		$param = $this->request->param();
		$type = $param["type"];
		if (!in_array($type, array_keys(config("log_type")))) {
			return jsonrule(["status" => 400, "msg" => "日志类型错误"]);
		}
		if (isset($param["time"])) {
			$time = $param["time"];
		}
		$where = function (\think\db\Query $query) use($time, $type) {
			if ($time) {
				if ($type == "admin_log") {
					$query->where("logintime", "<=", $time);
				} else {
					$query->where("create_time", "<=", $time);
				}
			}
		};
		switch ($type) {
			case "system_log":
				$count = \think\Db::name("activity_log")->where($where)->where("user", "neq", "System")->count();
				break;
			case "admin_log":
				$count = \think\Db::name("admin_log")->where($where)->count();
				break;
			case "email_log":
				$count = \think\Db::name("email_log")->where($where)->count();
				break;
			case "sms_log":
				$count = \think\Db::name("message_log")->where($where)->count();
				break;
			case "system_message_log":
				$count = \think\Db::name("system_message")->where($where)->count();
				break;
			case "cron_system_log":
				$count = \think\Db::name("activity_log")->where($where)->where("user", "like", "System")->count();
				break;
			case "api_log":
				$count = \think\Db::name("api_resource_log")->where($where)->count();
				break;
			default:
				$count = 0;
				break;
		}
		$data = ["count" => $count, "time" => time()];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 删除日志
	 * @description 接口说明: 删除日志：确认删除
	 * @author wyh
	 * @time 2020-12-02
	 * @url /admin/log_record/delete_log
	 * @method DELETE
	 * @param .name:type type:string require:1 default: other: desc:日志类型
	 */
	public function deleteLog()
	{
		$param = $this->request->param();
		$type = $param["type"];
		if (!in_array($type, array_keys(config("log_type")))) {
			return jsonrule(["status" => 400, "msg" => "日志类型错误"]);
		}
		if (isset($param["time"])) {
			$time = $param["time"];
		}
		$where = function (\think\db\Query $query) use($time, $type) {
			if ($time) {
				if ($type == "admin_log") {
					$query->where("logintime", "<=", $time);
				} else {
					$query->where("create_time", "<=", $time);
				}
			}
		};
		$hook_data = ["adminid" => cmf_get_current_admin_id(), "type" => $type];
		hook("before_delete_log", $hook_data);
		switch ($type) {
			case "system_log":
				$count = \think\Db::name("activity_log")->where($where)->where("user", "neq", "System")->delete();
				break;
			case "admin_log":
				$count = \think\Db::name("admin_log")->where($where)->delete();
				break;
			case "email_log":
				$count = \think\Db::name("email_log")->where($where)->delete();
				break;
			case "sms_log":
				$count = \think\Db::name("message_log")->where($where)->delete();
				break;
			case "system_message_log":
				$count = \think\Db::name("system_message")->where($where)->delete();
				break;
			case "cron_system_log":
				$count = \think\Db::name("activity_log")->where($where)->where("user", "like", "System")->delete();
				break;
			case "api_log":
				$count = \think\Db::name("api_resource_log")->where($where)->delete();
				break;
			default:
				$count = 0;
				break;
		}
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
}