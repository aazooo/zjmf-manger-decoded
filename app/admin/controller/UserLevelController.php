<?php

namespace app\admin\controller;

/**
 * @title 客户等级
 * @description 接口说明：客户等级规则创建、编辑等
 */
class UserLevelController extends GetUserController
{
	/**
	 * @title 客户等级列表
	 * @description 接口说明:客户等级列表
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @return  total:客户总数
	 * @return  list:客户等级列表@
	 * @list  id:
	 * @list  level_name:客户等级名称
	 * @list  expense:min最小值,max最大值(下同)，day天
	 * @list  buy_num:
	 * @list  login_times:
	 * @list  last_login_times:
	 * @list  renew_times:
	 * @list  last_renew_times:
	 * @author wyh
	 * @time 2020-11-23
	 * @url /admin/user_level/list
	 * @method GET
	 */
	public function getList()
	{
		$params = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		$total = \think\Db::name("clients_level_rule")->count();
		$list = \think\Db::name("clients_level_rule")->field("id,level_name,expense,buy_num,login_times,last_login_times,renew_times,last_renew_times")->withAttr("expense", function ($value) {
			return json_decode($value, true);
		})->withAttr("expense", function ($value) {
			return json_decode($value, true);
		})->withAttr("buy_num", function ($value) {
			return json_decode($value, true);
		})->withAttr("login_times", function ($value) {
			return json_decode($value, true);
		})->withAttr("last_login_times", function ($value) {
			return json_decode($value, true);
		})->withAttr("renew_times", function ($value) {
			return json_decode($value, true);
		})->withAttr("last_renew_times", function ($value) {
			return json_decode($value, true);
		})->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		$data = ["total" => $total, "list" => $list];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 编辑规则页面
	 * @description 接口说明:规则页面
	 * @param .name:id type:int require:0 default:1 other: desc:规则ID(非必传参数,编辑时才传)
	 * @return level_name:客户等级
	 * @return expense:min最小值，max最大值
	 * @return buy_num:min最小值，max最大值
	 * @return login_times:min最小值，max最大值
	 * @return last_login_times:min最小值，max最大值,day天数
	 * @return renew_times:min最小值，max最大值
	 * @return last_renew_times:min最小值，max最大值,day天数
	 * @author wyh
	 * @time 2020-11-23
	 * @url /admin/user_level/levelpage
	 * @method GET
	 */
	public function getLevelPage()
	{
		$param = $this->request->param();
		$data = [];
		if (isset($param["id"])) {
			$id = intval($param["id"]);
			$tmp = \think\Db::name("clients_level_rule")->where("id", $id)->find();
			if (empty($tmp)) {
				return jsonrule(["status" => 400, "msg" => "规则不存在"]);
			}
			$tmp["expense"] = json_decode($tmp["expense"], true);
			$tmp["buy_num"] = json_decode($tmp["buy_num"], true);
			$tmp["login_times"] = json_decode($tmp["login_times"], true);
			$tmp["last_login_times"] = json_decode($tmp["last_login_times"], true);
			$tmp["renew_times"] = json_decode($tmp["renew_times"], true);
			$tmp["last_renew_times"] = json_decode($tmp["last_renew_times"], true);
		}
		$data["level_rule"] = $tmp ?: [];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 创建/编辑规则
	 * @description 接口说明:创建/编辑规则
	 * @param .name:id type:int require:0 default:1 other: desc:规则ID(非必传参数,编辑时才传)
	 * @param .name:level_name type:string require:1 default:1 other: desc:等级名称
	 * @param .name:expense_min type:int require:0 default:1 other: desc:支出最小值
	 * @param .name:expense_max type:int require:0 default:1 other: desc:支出最大值
	 * @param .name:buy_num_min type:int require:0 default:1 other: desc:购买商品数量最小值
	 * @param .name:buy_num_max type:int require:0 default:1 other: desc:购买商品数量最大值
	 * @param .name:login_times_min type:int require:0 default:1 other: desc:累计登陆次数最小值
	 * @param .name:login_times_max type:int require:0 default:1 other: desc:累计登陆次数最大值
	 * @param .name:last_login_times_min type:int require:0 default:1 other: desc:最近X天登陆次数 最小值
	 * @param .name:last_login_times_max type:int require:0 default:1 other: desc:最近X天登陆次数 最大值
	 * @param .name:last_login_times_day type:int require:0 default:1 other: desc:最近X天登陆次数 X天
	 * @param .name:renew_times_min type:int require:0 default:1 other: desc:续费次数最小值
	 * @param .name:renew_times_max type:int require:0 default:1 other: desc:续费次数最大值
	 * @param .name:last_renew_times_min type:int require:0 default:1 other: desc:最近X天续费次数 最小值
	 * @param .name:last_renew_times_max type:int require:0 default:1 other: desc:最近X天续费次数 最大值
	 * @param .name:last_renew_times_day type:int require:0 default:1 other: desc:最近X天续费次数 X天
	 * @return total:客户总数
	 * @author wyh
	 * @time 2020-11-23
	 * @url /admin/user_level/level
	 * @method POST
	 */
	public function postLevel()
	{
		$param = $this->request->param();
		$validate = new \app\admin\validate\UserLevelValidate();
		if (isset($param["id"])) {
			$id = intval($param["id"]);
			if (!$validate->scene("edit")->check($param)) {
				return jsonrule(["status" => 400, "msg" => $validate->getError()]);
			}
		} else {
			if (!$validate->scene("create")->check($param)) {
				return jsonrule(["status" => 400, "msg" => $validate->getError()]);
			}
		}
		$expense = ["min" => floatval($param["expense_min"]), "max" => floatval($param["expense_max"])];
		$buy_num = ["min" => intval($param["buy_num_min"]), "max" => intval($param["buy_num_max"])];
		$login_times = ["min" => intval($param["login_times_min"]), "max" => intval($param["login_times_max"])];
		$last_login_times = ["min" => intval($param["last_login_times_min"]), "max" => intval($param["last_login_times_max"]), "day" => intval($param["last_login_times_day"])];
		$renew_times = ["min" => intval($param["renew_times_min"]), "max" => intval($param["renew_times_max"])];
		$last_renew_times = ["min" => intval($param["last_renew_times_min"]), "max" => intval($param["last_renew_times_max"]), "day" => intval($param["last_renew_times_day"])];
		$insert = ["level_name" => $param["level_name"], "expense" => json_encode($expense), "buy_num" => json_encode($buy_num), "login_times" => json_encode($login_times), "last_login_times" => json_encode($last_login_times), "renew_times" => json_encode($renew_times), "last_renew_times" => json_encode($last_renew_times)];
		if ($id) {
			$insert["update_time"] = time();
			$tmp = \think\Db::name("clients_level_rule")->where("id", $id)->update($insert);
		} else {
			$insert["create_time"] = time();
			$tmp = \think\Db::name("clients_level_rule")->insertGetId($insert);
		}
		if ($tmp) {
			return jsonrule(["status" => 200, "msg" => lang("EDIT SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("EDIT FAIL")]);
		}
	}
	/**
	 * @title 删除规则
	 * @description 接口说明:删除规则
	 * @param .name:id type:int require:1 default:1 other: desc:规则ID
	 * @author wyh
	 * @time 2020-11-23
	 * @url /admin/user_level/level
	 * @method DELETE
	 */
	public function deleteLevel()
	{
		$param = $this->request->param();
		$id = intval($param["id"]);
		$tmp = \think\Db::name("clients_level_rule")->where("id", $id)->find();
		if (empty($tmp)) {
			return jsonrule(["status" => 400, "msg" => "规则不存在"]);
		}
		\think\Db::name("clients_level_rule")->where("id", $id)->delete();
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
}