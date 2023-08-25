<?php

namespace app\admin\model;

class RunMapModel extends \think\Model
{
	public function add($data)
	{
		$data_i["user_id"] = $data["user_id"];
		$data_i["user"] = $data["user"];
		$data_i["host_id"] = $data["host_id"];
		$data_i["description"] = $data["description"];
		$data_i["from_type"] = $data["from_type"];
		$data_i["active_user"] = $data["active_user"];
		$data_i["active_type"] = $data["active_type"];
		$data_i["active_type_param"] = $data["active_type_param"];
		$data_i["status"] = $data["status"];
		$data_i["last_execute_time"] = $data_i["create_time"] = time();
		\think\Db::name("run_maping")->insert($data_i);
		return true;
	}
	public function edit($data)
	{
		$data_i["user_id"] = $data["user_id"];
		$data_i["user"] = $data["user"];
		$data_i["description"] = $data["description"];
		$data_i["from_type"] = $data["from_type"];
		$data_i["active_user"] = $data["active_user"];
		$data_i["active_type_param"] = $data["active_type_param"];
		$data_i["status"] = $data["status"];
		$data_i["last_execute_time"] = $data_i["create_time"] = time();
		\think\Db::name("run_maping")->where("host_id", $data["host_id"])->where("active_type", $data["active_type"])->update($data_i);
		return true;
	}
	public function getAllPage($data, $page, $limit, $orderby, $sorting, $supplier_id = 0)
	{
		$keywords = $data["keywords"];
		$from_type = $data["from_type"];
		$status = $data["status"];
		$user = $data["user"];
		$active_type = $data["active_type"];
		$where = function (\think\db\Query $query) use($keywords, $from_type, $status, $user, $active_type, $supplier_id) {
			if (!empty($keywords)) {
				$query->where("r.description", "like", "%{$keywords}%");
			}
			if (!empty($user)) {
				$query->where("r.user", "like", "%{$user}%");
			}
			if (!empty($from_type)) {
				$query->where("r.from_type", $from_type);
			}
			if (!empty($active_type)) {
				$query->where("r.active_type", $active_type);
			}
			if (isset($status)) {
				$query->where("r.status", $status);
			}
			if (!empty($supplier_id)) {
				$query->where("z.id", $supplier_id);
			}
		};
		if (empty($supplier_id)) {
			$list = \think\Db::name("run_maping")->alias("r")->leftJoin("host h", "r.host_id = h.id")->leftJoin("products p", "h.productid = p.id")->field("r.*,h.domain,p.name,h.dedicatedip")->where($where)->withAttr("last_execute_time", function ($value) {
				return date("Y-m-d H:i", $value);
			})->order($orderby, $sorting)->page($page)->limit($limit)->select()->toArray();
			$count = \think\Db::name("run_maping")->alias("r")->leftJoin("host h", "r.host_id = h.id")->leftJoin("products p", "h.productid = p.id")->field("r.id")->where($where)->count();
		} else {
			$list = \think\Db::name("run_maping")->alias("r")->leftJoin("host h", "r.host_id = h.id")->leftJoin("products p", "h.productid = p.id")->leftJoin("zjmf_finance_api z", "p.zjmf_api_id = z.id")->where("z.is_resource", 0)->field("r.*,h.domain,p.name,h.dedicatedip")->where($where)->withAttr("last_execute_time", function ($value) {
				return date("Y-m-d H:i", $value);
			})->order($orderby, $sorting)->page($page)->limit($limit)->select()->toArray();
			$count = \think\Db::name("run_maping")->alias("r")->leftJoin("host h", "r.host_id = h.id")->leftJoin("products p", "h.productid = p.id")->leftJoin("zjmf_finance_api z", "p.zjmf_api_id = z.id")->where("z.is_resource", 0)->field("r.id")->where($where)->count();
		}
		return ["list" => $list, "count" => $count];
	}
	public function getOne($filed, $value)
	{
		if (empty($filed)) {
			return [];
		}
		return \think\Db::name("run_maping")->where($filed, $value)->find();
	}
	public function editStatus($ID, $status = false)
	{
		$value = \intval($status);
		return \think\Db::name("run_maping")->where("id", $ID)->update(["status" => $value, "last_execute_time" => time()]);
	}
	public function checkOnlyOne($host_id, $active_type)
	{
		if (empty($host_id)) {
			return false;
		}
		$res = \think\Db::name("run_maping")->where("host_id", $host_id)->where("active_type", $active_type)->value("id");
		if ($res) {
			return false;
		} else {
			return true;
		}
	}
	public function cronGetCountTrend($start_time = 0, $end_time = 0, $status = null)
	{
		$where = function (\think\db\Query $query) use($start_time, $end_time, $status) {
			$query->where("status", $status);
			$query->where("datetime", ">=", $start_time);
			$query->where("datetime", "<=", $end_time);
		};
		$query_sql = \think\Db::name("run_croning")->field("unique_tab,datetime")->where($where)->group("datetime,unique_tab")->buildSql();
		$res = \think\Db::table($query_sql)->alias("x")->field("count(unique_tab) as sum,datetime")->group("datetime")->order("datetime", "asc")->select()->toArray();
		return $res;
	}
	public function cronGetCountList($datetime, $status = null)
	{
		$where = function (\think\db\Query $query) use($datetime, $status) {
			if (!is_null($status)) {
				$query->where("status", $status);
			}
			$query->where("datetime", $datetime);
		};
		$query_sql = \think\Db::name("run_croning")->field("cron_type,unique_tab")->where($where)->group("cron_type,unique_tab")->buildSql();
		$res = \think\Db::table($query_sql)->alias("x")->field("count(cron_type) as sum,cron_type")->group("cron_type")->select()->toArray();
		$res = array_column($res, "sum", "cron_type");
		return $res;
	}
	public function cronEditStatus($unique_tab, $status = true)
	{
		$value = \intval($status);
		return \think\Db::name("run_croning")->where("unique_tab", $unique_tab)->update(["status" => $value]);
	}
}