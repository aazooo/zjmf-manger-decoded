<?php

namespace app\home\controller;

/**
 * @title 前台API管理
 * @description 接口说明:前台API管理
 */
class ZjmfFinanceApiController extends CommonController
{
	/**
	 * 时间 2021-05-27
	 * @title 重置秘钥
	 * @desc 重置秘钥
	 * @url /zjmf_finance_api/reset
	 * @method POST
	 * @author wyh
	 */
	public function resetApiPwd()
	{
		$uid = request()->uid;
		\think\Db::name("clients")->where("id", $uid)->update(["api_password" => aesPasswordEncode(randStrToPass(12, 0))]);
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * 时间 2021-05-27
	 * @title 开启/关闭API功能
	 * @desc 开启/关闭API功能
	 * @url /zjmf_finance_api/open
	 * @param  .name:api_open type:int require:1 desc:1开启 0关闭
	 * @method POST
	 * @author wyh
	 */
	public function apiOpen()
	{
		if (!configuration("allow_resource_api")) {
			return jsons(["status" => 400, "msg" => "暂未开启API功能"]);
		}
		$param = $this->request->param();
		$uid = request()->uid;
		$up = ["api_open" => intval($param["api_open"])];
		if ($param["api_open"] == 1) {
			$up["api_create_time"] = time();
		}
		\think\Db::name("clients")->where("id", $uid)->update($up);
		if ($param["api_open"] == 1) {
			return jsons(["status" => 200, "msg" => "开启成功"]);
		} else {
			return jsons(["status" => 200, "msg" => "关闭成功"]);
		}
	}
	/**
	 * 时间 2021-05-27
	 * @title API概览
	 * @desc API概览,上游管理下游
	 * @url /zjmf_finance_api/summary
	 * @param  .name:uid type:int require:1 desc:客户ID
	 * @method GET
	 * @author wyh
	 * @return  client:基础数据@
	 * @client api_password:api密钥
	 * @client api_create_time:开通时间
	 * @client agent_count:代理商品数量
	 * @client host_count:API产品数量 总量
	 * @client active_count:API产品数量 已激活
	 * @client api_count:昨日api请求次数
	 * @client ratio:日环比
	 * @client up:1上升，0下降
	 * @client lock_reason:锁定原因
	 * @client api_lock_time:锁定时间
	 * @return form_api:最近7天每天的api请求次数
	 * @return free_products:豁免产品@
	 * @free_products id:
	 * @free_products name:名称
	 * @free_products ontrial:试用数量
	 * @free_products qty:最大购买数量
	 */
	public function summary()
	{
		$uid = request()->uid;
		$client = \think\Db::name("clients")->field("api_password,api_create_time,api_open,lock_reason,api_lock_time")->where("id", $uid)->find();
		if (!judgeApi($uid)) {
			return jsons(["status" => 400, "msg" => "暂未开通API功能"]);
		}
		$client["api_password"] = aesPasswordDecode($client["api_password"]);
		$agent_pids = \think\Db::name("api_resource_log")->where("uid", $uid)->where("pid", "<>", 0)->field("pid")->distinct(true)->column("pid");
		$host_count = \think\Db::name("host")->whereIn("productid", $agent_pids)->where("uid", $uid)->count();
		$active_count = \think\Db::name("host")->where("domainstatus", "Active")->whereIn("productid", $agent_pids)->where("uid", $uid)->count();
		$client["agent_count"] = count($agent_pids);
		$client["host_count"] = $host_count;
		$client["active_count"] = $active_count;
		$yesterday_start = strtotime(date("Y-m-d", time()));
		$yesterday_end = $yesterday_start + 86400;
		$api_count = \think\Db::name("api_resource_log")->where("uid", $uid)->whereBetweenTime("create_time", $yesterday_start, $yesterday_end)->count();
		$client["api_count"] = $api_count;
		$before_yesterday_start = strtotime(date("Y-m-d", strtotime("-1 days")));
		$before_yesterday_end = $before_yesterday_start + 86400;
		$api_count2 = \think\Db::name("api_resource_log")->where("uid", $uid)->whereBetweenTime("create_time", $before_yesterday_start, $before_yesterday_end)->count();
		$ratio1 = bcdiv($api_count, $api_count2, 2) * 100;
		$client["ratio"] = $ratio1 . "%";
		$before_yesterday_start2 = strtotime(date("Y-m-d", strtotime("-2 days")));
		$before_yesterday_end2 = $before_yesterday_start2 + 86400;
		$api_count3 = \think\Db::name("api_resource_log")->where("uid", $uid)->whereBetweenTime("create_time", $before_yesterday_start2, $before_yesterday_end2)->count();
		$ratio2 = bcdiv($api_count3, $api_count2, 2) * 100;
		$client["up"] = 0;
		if ($ratio2 <= $ratio1) {
			$client["up"] = 1;
		}
		$form_api = $this->getEveryDayTotal(strtotime(date("Y-m-d", strtotime("-6 days"))));
		$free_products = \think\Db::name("api_user_product")->field("a.id,b.name,a.ontrial,a.qty")->alias("a")->leftJoin("products b", "a.pid = b.id")->where("uid", $uid)->select()->toArray();
		$data = ["client" => $client, "form_api" => $form_api, "free_products" => $free_products];
		$result = ["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data];
		return jsons($result);
	}
	private function getTotal($start, $end)
	{
		$total = \think\Db::name("api_resource_log")->where("uid", request()->uid)->whereBetweenTime("create_time", $start, $end)->count();
		return intval($total);
	}
	private function getEveryDayTotal($month_start)
	{
		$days = 7;
		$month_every_day_total = [];
		for ($i = 0; $i <= $days - 1; $i++) {
			${$i + 1 . "_start"} = strtotime("+" . $i . " days", $month_start);
			${$i + 1 . "_end"} = strtotime("+" . ($i + 1) . " days -1 seconds", $month_start);
			${$i + 1 . "_total"} = $this->getTotal(${$i + 1 . "_start"}, ${$i + 1 . "_end"});
			array_push($month_every_day_total, ${$i + 1 . "_total"});
		}
		return $month_every_day_total;
	}
	/**
	 * 时间 2021-06-03
	 * @title API日志
	 * @desc API日志
	 * @url /zjmf_finance_api/logs
	 * @method GET
	 * @author wyh
	 * @version v1
	 * @param   .name:uid type:int require:0 desc:客户ID，单个客户日志需要传 default:1
	 * @param   .name:page type:int require:0 desc:页数 default:1
	 * @param   .name:limit type:int require:0 desc:每页条数 default:10
	 * @param   .name:order type:string require:0 desc:排序(id,name,hostname,server_num,api_status) default:id
	 * @param   .name:sort type:string require:0 desc:排序方向(asc,desc) default:asc
	 * @param name:keyword type:int  require:0  default: other: desc:关键字查询
	 * @return  logs:日志@
	 * @list  id:ID
	 * @list  create_time:
	 * @list  description:
	 * @list  ip:
	 * @list  username:
	 */
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
		$logs = \think\Db::name("api_resource_log")->alias("a")->field("a.id,a.create_time,a.description,a.ip,b.username,a.port")->leftJoin("clients b", "a.uid = b.id")->where($where)->withAttr("description", function ($value, $data) {
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
		})->withAttr("ip", function ($value, $data) {
			if (empty($data["port"])) {
				return $value;
			} else {
				return $value .= ":" . $data["port"];
			}
		})->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		$count = \think\Db::name("api_resource_log")->alias("a")->leftJoin("clients b", "a.uid = b.id")->where($where)->count();
		$data = ["logs" => $logs, "count" => $count];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
}