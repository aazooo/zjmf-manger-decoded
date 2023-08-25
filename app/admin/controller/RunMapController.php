<?php

namespace app\admin\controller;

/**
 * @title 任务队列API支持
 * @description 接口说明:提供任务队列API
 */
class RunMapController extends AdminBaseController
{
	protected $CRON_TYPE_DATE = [["type" => 1, "name" => "自动暂停", "is_show" => 1, "show_fail" => 1, "keywords" => "产品到期暂停"], ["type" => 2, "name" => "未实名暂停", "is_show" => 1, "show_fail" => 1, "keywords" => "未实名客户产品暂停"], ["type" => 3, "name" => "信用额产品暂停", "is_show" => 1, "show_fail" => 1, "keywords" => "信用额账单未支付"], ["type" => 4, "name" => "自动删除", "is_show" => 1, "show_fail" => 1, "keywords" => "产品到期删除"], ["type" => 5, "name" => "停用产品删除", "is_show" => 0, "show_fail" => 1, "keywords" => "停用产品删除"], ["type" => 6, "name" => "删除订单", "is_show" => 1, "show_fail" => 0, "keywords" => ""], ["type" => 7, "name" => "DCIM流量重置", "is_show" => 1, "show_fail" => 1, "keywords" => "DCIM流量"], ["type" => 8, "name" => "魔方云流量重置", "is_show" => 1, "show_fail" => 1, "keywords" => "魔方云流量"], ["type" => 9, "name" => "关闭工单", "is_show" => 1, "show_fail" => 0, "keywords" => ""], ["type" => 10, "name" => "信用额账单提醒", "is_show" => 1, "show_fail" => 0, "keywords" => ""], ["type" => 11, "name" => "信用额还款提醒", "is_show" => 1, "show_fail" => 0, "keywords" => ""], ["type" => 12, "name" => "账单提醒", "is_show" => 1, "show_fail" => 0, "keywords" => ""], ["type" => 13, "name" => "生成账单", "is_show" => 1, "show_fail" => 0, "keywords" => ""]];
	/**
	 * @title 任务队列列表
	 * @description 接口说明: 任务队列列表
	 * @url /admin/run_map/list
	 * @method GET
	 * @param .name:keywords type:string require:0 default: other: desc:搜索关键字
	 * @param .name:query[user] type:string require:0 default: other: desc:用户名关键字
	 * @param .name:query[from_type] type:int require:0 default: other: desc:来源类型 （100定时任务、200手动任务、300异步触发【订单】、400对接上游、500下游发起）
	 * @param .name:query[status] type:int require:0 default: other: desc:状态 （1成功、0失败）
	 * @param .name:query[active_type] type:int require:0 default: other: desc:来源类型 （1开通、2暂停、3解除暂停、4删除、5续费、6升降级）
	 * @param .name:status type:int require:0 default: other: desc:执行状态
	 * @param .name:page type:int require:0 default:1 other: desc:页码
	 * @param .name:limit type:int require:0 default:1 other: desc:每页条数
	 * @param .name:orderby type:string  require:0  default:create_time other: desc:排序字段
	 * @param .name:sorting type:string  require:0  default:desc other: desc:desc/asc，倒叙/顺序
	 * @param .name:supplier_id type:int require:0 default:1 other: desc:供应商ID
	 */
	public function runMapList(\think\Request $request)
	{
		$param = $request->param();
		$keywords = isset($param["keywords"]) ? trim($param["keywords"]) : "";
		$where_data = isset($param["query"]) ? (array) $param["query"] : [];
		$page = isset($param["page"]) ? intval($param["page"]) : config("page");
		$limit = isset($param["limit"]) ? intval($param["limit"]) : (configuration("NumRecordstoDisplay") ?: config("limit"));
		$supplier_id = isset($param["supplier_id"]) ? \intval($param["supplier_id"]) : 0;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "r.create_time";
		$sorting = $param["sorting"] ?? "DESC";
		$where_data["keywords"] = $keywords;
		$model_run_map = new \app\admin\model\RunMapModel();
		if (empty($supplier_id)) {
			$res_data = $model_run_map->getAllPage($where_data, $page, $limit, $orderby, $sorting);
		} else {
			$res_data = $model_run_map->getAllPage($where_data, $page, $limit, $orderby, $sorting, $supplier_id);
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["count" => $res_data["count"], "list" => $res_data["list"]]]);
	}
	/**
	 * @title 失败队列任务进行重发
	 * @description 接口说明: 任务队列列表
	 * @url /admin/run_map/repeat_task
	 * @method POST
	 * @param .name:id type:in require:0 default: other: desc:重发的请求任务id
	 * @other active_type 1开通、2暂停、3解除暂停、4删除、5续费、6升降级、7DCIM流量重置、8魔方云流量重置
	 */
	public function repeatTask(\think\Request $request)
	{
		$param = $request->param();
		$ID = $param["rm_id"];
		if (empty($ID)) {
			return jsonrule(["status" => 400, "msg" => "未找到有效记录", "data" => []]);
		}
		$model_run_map = new \app\admin\model\RunMapModel();
		$res_data = $model_run_map->getOne("id", $ID);
		if (empty($res_data) || $res_data["status"] == 1) {
			return jsonrule(["status" => 400, "msg" => "未找到有效记录", "data" => []]);
		}
		$active_type_param = json_decode($res_data["active_type_param"], true);
		$host_logic = new \app\common\logic\Host();
		$host_logic->is_admin = true;
		switch ($res_data["active_type"]) {
			case 1:
				$result = $host_logic->create($active_type_param[0], $active_type_param[1]);
				break;
			case 2:
				$result = $host_logic->suspend($active_type_param[0], $active_type_param[1], $active_type_param[2], $active_type_param[3]);
				break;
			case 3:
				$result = $host_logic->unsuspend($active_type_param[0], $active_type_param[1], $active_type_param[2], $active_type_param[3]);
				break;
			case 4:
				$result = $host_logic->terminate($active_type_param[0], $active_type_param[1]);
				break;
			case 5:
				$result = $host_logic->renew($active_type_param[0]);
				break;
			case 6:
				$result = $host_logic->changePackage($active_type_param[0], $active_type_param[1], $active_type_param[2], $active_type_param[3]);
				break;
			case 7:
				$dcim = new \app\common\logic\Dcim();
				$dcim->init($active_type_param[0]);
				$result = $dcim->resetFlow($active_type_param[1], $active_type_param[2], $active_type_param[3]);
				break;
			case 8:
				$dcimcloud = new \app\common\logic\DcimCloud();
				$dcimcloud->is_admin = true;
				$dcimcloud->setUrlByServer($active_type_param[0]);
				$result = $dcimcloud->resetFlow($active_type_param[1], $active_type_param[2], $active_type_param[3], $active_type_param[4]);
				break;
			default:
				$result = ["status" => 400, "msg" => "无效记录", "data" => []];
		}
		$type_data = $this->CRON_TYPE_DATE;
		foreach ($type_data as $k => $v) {
			if (empty($v["keywords"])) {
				continue;
			}
			$res_check = strpos($res_data["description"], $v["keywords"]);
			if ($res_check !== false) {
				$cron_type = $v["type"];
				break;
			}
		}
		if ($result["status"] == 200) {
			$model_run_map->editStatus($ID, 1);
			$logic_run = new \app\common\logic\RunMap();
			$logic_run->cronSuccess($res_data["last_execute_time"], $cron_type, $res_data["host_id"]);
			return jsonrule(["status" => 200, "msg" => $result["msg"], "data" => []]);
		} else {
			$model_run_map->editStatus($ID, 0);
			return jsonrule(["status" => 400, "msg" => $result["msg"], "data" => []]);
		}
	}
	/**
	 * @title 定时任务执行,曲线趋势图
	 * @description 接口说明: 定时任务执行,曲线趋势图
	 * @url /admin/run_cron/trend
	 * @method GET
	 * @param .name:time_type type:int require:0 default:1 other: desc:时间跨度类型
	 */
	public function runCronTrend(\think\Request $request)
	{
		$param = $request->param();
		$time_type = $param["time_type"] ?: 1;
		switch ($time_type) {
			case 1:
				$start_time = date("Ymd", strtotime("-7 day"));
				$end_time = date("Ymd");
				break;
			case 2:
				$start_time = date("Ymd", strtotime("-15 day"));
				$end_time = date("Ymd");
				break;
			case 3:
				$start_time = date("Ymd", strtotime("-30 day"));
				$end_time = date("Ymd");
				break;
			case 4:
				$start_time = date("Ymd", strtotime("-90 day"));
				$end_time = date("Ymd");
				break;
		}
		$model_run_map = new \app\admin\model\RunMapModel();
		$res = $model_run_map->cronGetCountTrend($start_time, $end_time, 0);
		return jsonrule(["status" => 200, "msg" => "", "data" => $res]);
	}
	/**
	 * @title 定时任务执行统计
	 * @description 接口说明: 定时任务执行,曲线趋势图
	 * @url /admin/run_cron/list
	 * @method GET
	 * @param .name:time_type type:int require:0 default:1 other: desc:时间跨度类型
	 */
	public function runCronList(\think\Request $request)
	{
		$param = $request->param();
		$datetime = $param["datetime"] ?: date("Ymd");
		$type_data = $this->CRON_TYPE_DATE;
		$model_run_map = new \app\admin\model\RunMapModel();
		$res_fail = $model_run_map->cronGetCountList($datetime, 0);
		$res_all = $model_run_map->cronGetCountList($datetime);
		$res_data = [];
		foreach ($type_data as $k => $v) {
			if (!$v["is_show"]) {
				continue;
			}
			$res_data[$k]["type"] = $v["type"];
			$res_data[$k]["name"] = $v["name"];
			$res_data[$k]["show_fail"] = $v["show_fail"];
			$res_data[$k]["keywords"] = $v["keywords"];
			$res_data[$k]["counts"]["all"] = \intval($res_all[$v["type"]]);
			$res_data[$k]["counts"]["fail"] = \intval($res_fail[$v["type"]]);
		}
		return jsonrule(["status" => 200, "msg" => "", "data" => $res_data]);
	}
}