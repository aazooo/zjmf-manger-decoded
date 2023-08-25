<?php

namespace app\admin\controller;

/**
 * @title 销售管理
 * @description 接口说明
 */
class SaleController extends GetUserController
{
	private $validate;
	public function initialize()
	{
		parent::initialize();
		$this->validate = new \app\admin\validate\SaleValidate();
	}
	/**
	 * @title 销售管理列表页
	 * @description 接口说明:销售管理列表页(含搜索)
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @param .name:group_name type:string require:0 default:1 other: desc:按分组名搜索
	 * @param .name:bates type:string require:0 default:1 other: desc:按比例搜索
	 * @return total:销售管理列表总数
	 * @return list:销售管理列表表数据@
	 * @list  group_name:分组名
	 * @list  bates:比例
	 * @list  renew_bates:续费比例
	 * @list  upgrade_bates:升级比例
	 * @list  is_renew:是否包含续费计算
	 * @list  updategrade:是否计算升降级
	 * @list  pids:产品组列表
	 * @url /admin/salegroup
	 * @method GET
	 */
	public function groupList()
	{
		$params = $data = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "ASC";
		$data["group_name"] = !empty($params["group_name"]) ? trim($params["group_name"]) : "";
		$data["bates"] = !empty($params["bates"]) ? trim($params["bates"]) : "";
		$total = \think\Db::name("sales_product_groups")->where(function (\think\db\Query $query) use($data) {
			if (!empty($data["group_name"])) {
				$query->where("group_name", "like", "%" . trim($data["group_name"]) . "%");
			}
			if (!empty($data["bates"])) {
				$query->where("bates", "like", "%" . trim($data["bates"]) . "%");
			}
		})->count();
		$list = \think\Db::name("sales_product_groups")->where(function (\think\db\Query $query) use($data) {
			if (!empty($data["group_name"])) {
				$query->where("group_name", "like", "%" . trim($data["group_name"]) . "%");
			}
			if (!empty($data["bates"])) {
				$query->where("bates", "like", "%" . trim($data["bates"]) . "%");
			}
		})->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "total" => $total, "list" => $list]);
	}
	/**
	 * @title 获取时间类型
	 * @description 接口说明: 获取时间类型
	 * @author lgd
	 * @url /admin/aff/get_timetype
	 * @method GET
	 * @return data:基础数据(搜索区)@
	 * @base  nextduedate:时间周期
	 * @base  name:时间周期
	 */
	public function getTimetype(\think\Request $request)
	{
		$returndata["time_type"] = config("time_type2");
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 添加分组页面
	 * @description 接口说明:添加分组页面
	 * @author 刘国栋
	 * @url /admin/sale/add_salegrouppage
	 * @method GET
	 * @return group:产品组@
	 * @group id:组id name:组名 product:产品@
	 * @product id:产品id type:类型 gid:组id name:产品名 description:描述 pay_method:付款类型 tax:税
	 */
	public function addSalegroupPage()
	{
		$groups = getProductLists();
		$data = ["group" => $groups];
		return jsonrule(["status" => 200, "data" => $data, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 添加分组
	 * @description 接口说明:添加分组
	 * @author 刘国栋
	 * @url /admin/sale/add_salegroup
	 * @method POST
	 * @param .name:group_name type:string require:1 default:1 other: desc:分组名称
	 * @param .name:bates type:float require:1 default:0 other: desc:比例
	 * @param .name:is_renew type:int require:1 default:0 other: desc:是否包含续费计算
	 * @param .name:updategrade type:int require:1 default:0 other: desc:是否计算升降级
	 * @param .name:pids type:float require:1 default:1 other: desc:产品集(1,2,3)
	 */
	public function addSalegroup()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			unset($param["request_time"]);
			unset($param["languagesys"]);
			$sale = array_map("trim", $param);
			if (!$this->validate->scene("add_salegroup")->check($sale)) {
				return json(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$pids = $param["pids"];
			$param["pids"] = implode(",", $param["pids"]);
			$cid = \think\Db::name("sales_product_groups")->insertGetId($param);
			foreach ($pids as $item) {
				$data = ["pid" => $item, "gid" => $cid];
				\think\Db::name("sale_products")->insertGetId($data);
			}
			if (!$cid) {
				return json(["status" => 400, "msg" => lang("ADD FAIL")]);
			}
			active_log(sprintf($this->lang["Sale_admin_addSalegroup"], $cid));
			return json(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		}
		return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 编辑分组页面
	 * @description 接口说明:编辑分组页面
	 * @param .name:id type:int require:1  other: desc:分组ID
	 * @throws
	 * @author 刘国栋
	 * @url /admin/sale/edit_salegrouppage
	 * @method get
	 * @return group:产品组@
	 * @group id:组id name:组名 product:产品@
	 * @product id:产品id type:类型 gid:组id name:产品名 description:描述 pay_method:付款类型 tax:税
	 * @return spg:分组@
	 * @spg id:组id groupname:组名 bates:比例 is_renew:是否包含续费计算 updategrade:是否计算升降级 pids:产品集(1,2,3)@
	 */
	public function editSalegroupPage()
	{
		$params = $this->request->param();
		$id = intval($params["id"]);
		$groups = getProductListss($id);
		$spg = \think\Db::name("sales_product_groups")->where("id", $id)->find();
		$spg["pids"] = explode(",", $spg["pids"]);
		foreach ($spg["pids"] as $key => $val) {
			$spg["pids"][$key] = \intval($val);
		}
		$data = ["group" => $groups, "spg" => $spg];
		return jsonrule(["status" => 200, "data" => $data, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 编辑分组
	 * @description 接口说明:编辑分组
	 * @param .name:id type:int require:1  other: desc:分组ID
	 * @param .name:group_name type:string require:1 default:1 other: desc:分组名称
	 * @param .name:bates type:float require:1 default:0 other: desc:比例
	 * @param .name:renew_bates type:float require:1 default:0 other: desc:比例
	 * @param .name:upgrade_bates type:float require:1 default:0 other: desc:比例
	 * @param .name:is_renew type:int require:1 default:0 other: desc:是否包含续费计算
	 * @param .name:updategrade type:int require:1 default:0 other: desc:是否计算升降级
	 * @param .name:pids type:数组 require:1 default:1 other: desc:产品集(1,2,3)
	 * @throws
	 * @author 刘国栋
	 * @url /admin/sale/edit_salegroup
	 * @method post
	 */
	public function editSalegroup()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$sale = array_map("trim", $param);
			if (empty($param["id"])) {
				return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
			}
			if (!$this->validate->scene("add_salegroup")->check($sale)) {
				return jsonrule(["status" => 400, "msg" => $this->validate->getError()]);
			}
			$desc = "";
			$spg = \think\Db::name("sales_product_groups")->where("id", $param["id"])->find();
			$data["group_name"] = $param["group_name"];
			if ($spg["group_name"] != $param["group_name"]) {
				$desc .= "分组名由“" . $spg["group_name"] . "”改为“" . $param["group_name"] . "”，";
			}
			$data["bates"] = $param["bates"];
			if ($spg["bates"] != $param["bates"]) {
				$desc .= "提成比例由“" . $spg["bates"] . "”改为“" . $param["bates"] . "”，";
			}
			$data["renew_bates"] = $param["renew_bates"];
			if ($spg["renew_bates"] != $param["renew_bates"]) {
				$desc .= "续费提成比例由“" . $spg["bates"] . "”改为“" . $param["bates"] . "”，";
			}
			$data["upgrade_bates"] = $param["upgrade_bates"];
			if ($spg["upgrade_bates"] != $param["upgrade_bates"]) {
				$desc .= "升降级提成比例由“" . $spg["bates"] . "”改为“" . $param["bates"] . "”，";
			}
			$data["is_renew"] = $param["is_renew"];
			if ($spg["is_renew"] != $param["is_renew"]) {
				if ($spg["is_renew"] == 1) {
					$desc .= "续费计算由“关闭”改为“开启”，";
				} else {
					$desc .= "续费计算由“开启”改为“关闭”，";
				}
			}
			$data["updategrade"] = $param["updategrade"];
			if ($spg["updategrade"] != $param["updategrade"]) {
				if ($spg["updategrade"] == 1) {
					$desc .= "计算升降级由“关闭”改为“开启”，";
				} else {
					$desc .= "计算升降级由“开启”改为“关闭”，";
				}
			}
			$data["pids"] = implode(",", $param["pids"]);
			\think\Db::startTrans();
			try {
				\think\Db::name("sale_products")->where("gid", $param["id"])->delete();
				\think\Db::name("sales_product_groups")->where("id", $param["id"])->update($data);
				foreach ($param["pids"] as $item) {
					$datas = ["pid" => $item, "gid" => $param["id"]];
					\think\Db::name("sale_products")->insertGetId($datas);
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
			}
			if (empty($desc)) {
				$desc .= "没有任何修改";
			}
			active_log(sprintf($this->lang["Sale_admin_editSalegroup"], $param["id"], $desc));
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 删除分组
	 * @description 接口说明:删除分组
	 * @param .name:id type:int require:1  other: desc:分组ID
	 * @throws
	 * @author 刘国栋
	 * @url /admin/sale/del_salegroup
	 * @method get
	 */
	public function delSalegroup()
	{
		$params = $this->request->param();
		$id = intval($params["id"]);
		$res = \think\Db::name("sales_product_groups")->where("id", $id)->find();
		if (!empty($res)) {
			\think\Db::name("sale_products")->where("gid", $id)->delete();
			\think\Db::name("sales_product_groups")->where("id", $id)->delete();
			active_log(sprintf($this->lang["Sale_admin_delSalegroup"], $id));
			return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
	}
	/**
	 * @title 阶梯列表页
	 * @description 接口说明:阶梯列表页(含搜索)
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @return total:阶梯列表页总数
	 * @return list:阶梯列表页数据@
	 * @list  group_name:营业额
	 * @list  bates:比例
	 * @list  is_flag:是否开启
	 * @url /admin/saleladder
	 * @method GET
	 */
	public function ladderList()
	{
		$params = $data = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "turnover";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "ASC";
		$total = \think\Db::name("sale_ladder")->count();
		$list = \think\Db::name("sale_ladder")->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "total" => $total, "list" => $list]);
	}
	/**
	 * @title 添加阶梯
	 * @description 接口说明:添加阶梯
	 * @author 刘国栋
	 * @url /admin/sale/add_saleladder
	 * @method POST
	 * @param .name:turnover type:string require:1 default:0 other: desc:营业额
	 * @param .name:bates type:float require:1 default:0 other: desc:比例
	 * @param .name:is_flag type:int require:1 default:0 other: desc:是否开启
	 */
	public function addSaleLadder()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$data["turnover"] = isset($param["turnover"]) ? intval($param["turnover"]) : 0;
			$data["bates"] = isset($param["bates"]) ? intval($param["bates"]) : 0;
			$data["is_flag"] = isset($param["is_flag"]) ? intval($param["is_flag"]) : 0;
			$cid = \think\Db::name("sale_ladder")->insertGetId($data);
			if (!$cid) {
				return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
			}
			active_log(sprintf($this->lang["Sale_admin_addSaleladder"], $cid));
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 编辑阶梯页面
	 * @description 接口说明:编辑阶梯页面
	 * @param .name:id type:int require:1  other: desc:阶梯ID
	 * @throws
	 * @author 刘国栋
	 * @url /admin/sale/edit_saleladderpage
	 * @method get
	 * @return spg:分组@
	 * @spg id:组id turnover:营业额 bates:比例 is_flag:是否开启@
	 */
	public function editSaleLadderPage()
	{
		$params = $this->request->param();
		$id = intval($params["id"]);
		if (empty($id)) {
			return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
		$spg = \think\Db::name("sale_ladder")->where("id", $id)->find();
		$data = ["ladder" => $spg];
		return jsonrule(["status" => 200, "data" => $data, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 编辑阶梯
	 * @description 接口说明: 编辑阶梯
	 * @param .name:id type:int require:1  other: desc:分组ID
	 * @param .name:turnover type:string require:1 default:1 other: desc:营业额
	 * @param .name:bates type:float require:1 default:0 other: desc:提成比例
	 * @param .name:is_flag type:int require:1 default:0 other: desc:是否开启
	 * @throws
	 * @author 刘国栋
	 * @url /admin/sale/edit_saleladder
	 * @method post
	 */
	public function editSaleLadder()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$data["turnover"] = isset($param["turnover"]) ? floatval($param["turnover"]) : 0;
			$data["bates"] = isset($param["bates"]) ? floatval($param["bates"]) : 0;
			$data["is_flag"] = isset($param["is_flag"]) ? intval($param["is_flag"]) : 0;
			$desc = "";
			$spg = \think\Db::name("sale_ladder")->where("id", $param["id"])->find();
			if ($spg["turnover"] != $param["turnover"]) {
				$desc .= "营业额由“" . $spg["turnover"] . "”改为“" . $param["turnover"] . "”，";
			}
			if ($spg["bates"] != $param["bates"]) {
				$desc .= " 提成比例" . $spg["bates"] . "”改为“" . $param["bates"] . "”，";
			}
			if ($spg["is_flag"] != $param["is_flag"]) {
				if ($spg["is_flag"] == 1) {
					$desc .= "阶梯提成由“关闭”改为“开启”，";
				} else {
					$desc .= "阶梯提成由“开启”改为“关闭”，";
				}
			}
			\think\Db::name("sale_ladder")->where("id", $param["id"])->update($data);
			if (empty($desc)) {
				$desc .= "没有任何修改";
			}
			active_log(sprintf($this->lang["Sale_admin_editSaleladder"], $param["id"], $desc));
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 删除阶梯
	 * @description 接口说明:删除阶梯
	 * @param .name:id type:int require:1  other: desc:阶梯ID
	 * @throws
	 * @author 刘国栋
	 * @url /admin/sale/del_saleladder
	 * @method get
	 */
	public function delSaleLadder()
	{
		$params = $this->request->param();
		$id = intval($params["id"]);
		$res = \think\Db::name("sale_ladder")->where("id", $id)->find();
		if (!empty($res)) {
			\think\Db::name("sale_ladder")->where("id", $id)->delete();
			active_log(sprintf($this->lang["Sale_admin_delSaleladder"], $id));
			return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
	}
	/**
	 * @title 销售管理统计页  测试id默认传3
	 * @description 接口说明:销售管理统计页
	 * @param .name:id type:int require:0 default:1 other: desc:销售id
	 * @param .name:start_time type:int require:0 default: other: desc:时间
	 * @param .name:time type:int require:0 default: other: desc:时间类型
	 * @param .name:type type:int require:0 default:1 other: desc:类型1顶部  2图形
	 * @return today:今日订单和业绩@
	 * @today  ordercount:订单数
	 * @today  total:业绩
	 * @return week:这周订单和业绩
	 * @return month:这月订单和业绩
	 * @return last_month:上月订单和业绩
	 * @return ladder:当前阶梯@
	 * @ladder turnover:当前（没有就默认）
	 * @ladder last:下一级
	 * @return array:图表@
	 * @url /admin/sale/sale_statistics
	 * @method GET
	 */
	public function saleStatistics()
	{
		$params = $data = $this->request->param();
		$id = !empty($params["id"]) ? intval($params["id"]) : session("ADMIN_ID");
		$start_time = $params["start_time"];
		$type = $params["type"];
		if ($type == 2) {
			$array = [];
			if ($params["time"] == 1) {
				$month = getMonths();
				foreach ($month as $key => $value) {
					$array[$value] = $this->getLaddersaleStatistics($value, $id);
				}
			} elseif ($params["time"] == 2) {
				$month = getLastMonths();
				foreach ($month as $key => $value) {
					$array[$value] = $this->getLaddersaleStatistics($value, $id);
				}
			} elseif ($params["time"] == 3) {
				$month = getAllStartMonths($start_time);
				foreach ($month as $key => $value) {
					$array[$value] = $this->getLaddersaleStatistics("allmonth", $id, $value);
				}
			} elseif ($params["time"] == 4) {
				$month = getStartMonths($start_time);
				foreach ($month as $key => $value) {
					$array[$value] = $this->getLaddersaleStatistics($value, $id);
				}
			} else {
				$month = getMonths();
				foreach ($month as $key => $value) {
					$array[$value] = $this->getLaddersaleStatistics($value, $id);
				}
			}
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "array" => $array]);
		}
		if (!empty($params["time"])) {
			$month = $last_month = [];
			if ($params["time"] == 1) {
				$month = $this->getLaddersaleStatistics("this_month", $id);
				$last_month = $this->getLaddersaleStatistics("last_month", $id);
			} elseif ($params["time"] == 2) {
				$month = $this->getLaddersaleStatistics("last_three_month", $id);
				$last_month = $this->getLaddersaleStatistics("last_six_month", $id);
			} elseif ($params["time"] == 4) {
				$month = $this->getLaddersaleStatistics("diy_time", $id, $start_time);
				$last_month = $this->getLaddersaleStatistics("diy_time", $id, $start_time);
			} elseif ($params["time"] == 3) {
				$month = $this->getLaddersaleStatistics("alltime", $id);
				$last_month = [];
			}
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "today" => $this->getLaddersaleStatistics("today", $id), "week" => $this->getLaddersaleStatistics("week", $id), "month" => $month, "last_month" => $last_month]);
		} else {
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "today" => $this->getLaddersaleStatistics("today", $id), "week" => $this->getLaddersaleStatistics("week", $id), "month" => $this->getLaddersaleStatistics("month", $id), "last_month" => $this->getLaddersaleStatistics("last_month", $id)]);
		}
	}
	/**
	 * @title 销售管理统计页 提现记录 测试id默认传3
	 * @description 接口说明:销售管理统计页提现记录
	 * @param .name:name type:int require:0 default:1 other: desc:姓名
	 * @param .name:pname type:int require:0 default:1 other: desc:商品
	 * @param .name:type type:int require:0 default:1 other: desc:类型
	 * @param .name:id type:int require:0 default:1 other: desc:销售id
	 * @param .name:search_time type:int  require:0  default: other: desc:传入时间戳，返回当月日志
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @return total:销售管理列表总数
	 * @return record:销售管理列表表数据@
	 * @list  amount:金额
	 * @list  batesamount:提成
	 * @list  name:产品名
	 * @list  username:用户名
	 * @list  type:类型
	 * @url /admin/sale/sale_records
	 * @method POST
	 */
	public function saleRecords()
	{
		$params = $data = $this->request->param();
		$name = !empty($params["name"]) ? trim($params["name"]) : "";
		$pname = !empty($params["pname"]) ? trim($params["pname"]) : "";
		$type = !empty($params["type"]) ? trim($params["type"]) : "";
		$id = !empty($params["id"]) ? intval($params["id"]) : session("ADMIN_ID");
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "h.id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "ASC";
		$where = [];
		$ladder = $this->getLadderforall($id);
		if (!empty($name)) {
			$where[] = ["c.username", "like", "%" . $name . "%"];
		}
		if (!empty($pname)) {
			$where[] = ["p.name", "like", "%" . $pname . "%"];
		}
		if (!empty($type)) {
			$where[] = ["in.type", "=", $type];
		}
		if (!empty($param["search_time"])) {
			$where[] = ["i.paid_time", ">=", strtotime(date("Y-m", $param["search_time"]))];
			$where[] = ["i.paid_time", "<", strtotime(date("Y-m", $param["search_time"]) . "+1 month")];
		} else {
			$where[] = ["i.paid_time", ">=", strtotime(date("Y-m", time()))];
			$where[] = ["i.paid_time", "<", strtotime(date("Y-m", time()) . "+1 month")];
		}
		try {
			$count = \think\Db::name("invoice_items")->alias("in")->join("host h", "h.id=in.rel_id")->join("products p", "p.id=h.productid")->leftJoin("sale_products sp", "p.id=sp.pid")->leftJoin("sales_product_groups spg", "spg.id=sp.gid")->join("invoices i", "i.id=in.invoice_id")->join("clients c", "i.uid=c.id")->join("currencies cu", "cu.id = c.currency")->field("i.uid,in.invoice_id")->where("i.status", "=", "Paid")->where("c.sale_id", $id)->where("in.type", "neq", "upgrade")->where($where)->select()->toArray();
			foreach ($count as $k => $vs) {
				$fl = false;
				$ii = \think\Db::name("invoice_items")->where("invoice_id", $vs["invoice_id"])->select();
				foreach ($ii as $vs1) {
					if ($vs1["type"] == "upgrade") {
						$fl = true;
						continue;
					}
				}
				if ($fl) {
					unset($count[$k]);
				}
			}
			$count1 = \think\Db::name("invoice_items")->alias("in")->join("upgrades u", "u.id=in.rel_id")->join("host h", "h.id=u.relid")->join("products p", "p.id=h.productid")->leftJoin("sale_products sp", "p.id=sp.pid")->leftJoin("sales_product_groups spg", "spg.id=sp.gid")->join("invoices i", "i.id=in.invoice_id")->join("clients c", "i.uid=c.id")->join("currencies cu", "cu.id = c.currency")->field("i.uid,in.invoice_id")->where("i.status", "=", "Paid")->where("c.sale_id", $id)->where("in.type='upgrade' OR in.type='discount'")->where($where)->select()->toArray();
			$counts = array_merge($count, $count1);
			$arrs = [];
			foreach ($counts as $key => $val) {
				if (!empty($arrs[$val["invoice_id"]])) {
					$arrs[$val["invoice_id"]]["child"][] = $val;
				} else {
					$arrs[$val["invoice_id"]]["child"][] = $val;
				}
			}
			$total = count($arrs);
			unset($arrs);
			unset($counts);
			unset($count1);
			unset($count);
		} catch (\think\Exception $e) {
			var_dump($e->getMessage());
		}
		$list = \think\Db::name("invoice_items")->alias("in")->join("host h", "h.id=in.rel_id")->join("products p", "p.id=h.productid")->leftJoin("sale_products sp", "p.id=sp.pid")->leftJoin("sales_product_groups spg", "spg.id=sp.gid")->join("invoices i", "i.id=in.invoice_id")->join("clients c", "i.uid=c.id")->join("currencies cu", "cu.id = c.currency")->field("i.uid,h.id as hostid,in.id,in.invoice_id,in.amount,spg.bates,p.name,c.username,c.companyname,in.type,i.type as typess,spg.is_renew,spg.updategrade,spg.renew_bates,spg.upgrade_bates,cu.suffix,h.domain,h.dedicatedip")->where("i.status", "=", "Paid")->where("c.sale_id", $id)->where("in.type", "neq", "upgrade")->where($where)->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		foreach ($list as $k => $vs) {
			$fl = false;
			$ii = \think\Db::name("invoice_items")->where("invoice_id", $vs["invoice_id"])->select();
			foreach ($ii as $vs1) {
				if ($vs1["type"] == "upgrade") {
					$fl = true;
					continue;
				}
			}
			if ($fl) {
				unset($list[$k]);
			}
		}
		$list1 = \think\Db::name("invoice_items")->alias("in")->join("upgrades u", "u.id=in.rel_id")->join("host h", "h.id=u.relid")->join("products p", "p.id=h.productid")->leftJoin("sale_products sp", "p.id=sp.pid")->leftJoin("sales_product_groups spg", "spg.id=sp.gid")->join("invoices i", "i.id=in.invoice_id")->join("clients c", "i.uid=c.id")->join("currencies cu", "cu.id = c.currency")->field("i.uid,h.id as hostid,in.id,in.invoice_id,in.amount,spg.bates,p.name,c.username,c.companyname,in.type,i.type as typess,spg.is_renew,spg.updategrade,spg.renew_bates,spg.upgrade_bates,cu.suffix,h.domain,h.dedicatedip")->where("i.status", "=", "Paid")->where("c.sale_id", $id)->where("in.type='upgrade' OR in.type='discount'")->where($where)->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		$list = array_merge($list, $list1);
		$arr = [];
		foreach ($list as $key => $val) {
			$str = $list[$key]["username"] . "(" . $list[$key]["companyname"] . ")";
			$list[$key]["username"] = "<a class=\"el-link el-link--primary is-underline\" 
            href=\"#/customer-view/abstract?id=" . $val["uid"] . "\">
            <span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
			$str = $list[$key]["name"] . "(" . $list[$key]["domain"] . ")";
			$list[$key]["name"] = "<a class=\"el-link el-link--primary is-underline\" 
                href=\"#/customer-view/product-innerpage?hid=" . $val["hostid"] . "&id=" . $val["uid"] . "\">
                <span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
			if (!empty($ladder["turnover"]["turnover"])) {
				if ($val["is_renew"] == 0 && $val["type"] == "renew" || $val["is_renew"] == 0 && $val["typess"] == "renew" && $val["type"] == "discount") {
					$list[$key]["bates"] = 0;
					$list[$key]["batesamount"] = "0.00+" . bcmul($ladder["turnover"]["bates"] / 100, $val["amount"], 2);
					if (bcmul($ladder["turnover"]["bates"] / 100, $val["amount"], 2) == 0) {
						$list[$key]["batesamount"] = "0.00";
					}
				} elseif ($val["updategrade"] == 0 && $val["type"] == "upgrade" || $val["updategrade"] == 0 && $val["typess"] == "upgrade" && $val["type"] == "discount") {
					$list[$key]["bates"] = 0;
					$list[$key]["batesamount"] = "0.00+" . bcmul($ladder["turnover"]["bates"] / 100, $val["amount"], 2);
					if (bcmul($ladder["turnover"]["bates"] / 100, $val["amount"], 2) == 0) {
						$list[$key]["batesamount"] = "0.00";
					}
				} elseif ($val["is_renew"] == 1 && $val["type"] == "renew" || $val["is_renew"] == 1 && $val["typess"] == "renew" && $val["type"] == "discount") {
					$list[$key]["batesamount"] = round(bcmul($val["amount"], $val["renew_bates"] / 100, 2), 2) . "+" . round(bcmul($ladder["turnover"]["bates"] / 100, $val["amount"], 2), 2);
					if (round(bcmul($val["amount"], $val["renew_bates"] / 100, 2), 2) == 0 && round(bcmul($ladder["turnover"]["bates"] / 100, $val["amount"], 2), 2) == 0) {
						$list[$key]["batesamount"] = "0.00";
					}
				} elseif ($val["updategrade"] == 1 && $val["type"] == "upgrade" || $val["updategrade"] == 1 && $val["typess"] == "upgrade" && $val["type"] == "discount") {
					$list[$key]["batesamount"] = round(bcmul($val["amount"], $val["upgrade_bates"] / 100, 2), 2) . "+" . round(bcmul($ladder["turnover"]["bates"] / 100, $val["amount"], 2), 2);
					if (round(bcmul($val["amount"], $val["upgrade_bates"] / 100, 2), 2) == 0 && round(bcmul($ladder["turnover"]["bates"] / 100, $val["amount"], 2), 2) == 0) {
						$list[$key]["batesamount"] = "0.00";
					}
				} else {
					$list[$key]["batesamount"] = round(bcmul($val["amount"], $val["bates"] / 100, 2), 2) . "+" . round(bcmul($ladder["turnover"]["bates"] / 100, $val["amount"], 2), 2);
					if (round(bcmul($val["amount"], $val["bates"] / 100, 2), 2) == 0 && round(bcmul($ladder["turnover"]["bates"] / 100, $val["amount"], 2), 2) == 0) {
						$list[$key]["batesamount"] = "0.00";
					}
				}
			} elseif ($val["is_renew"] == 0 && $val["type"] == "renew" || $val["is_renew"] == 0 && $val["typess"] == "renew" && $val["type"] == "discount") {
				$list[$key]["batesamount"] = "0.00";
				$list[$key]["bates"] = 0;
			} elseif ($val["updategrade"] == 0 && $val["type"] == "upgrade" || $val["updategrade"] == 0 && $val["typess"] == "upgrade" && $val["type"] == "discount") {
				$list[$key]["batesamount"] = "0.00";
				$list[$key]["bates"] = 0;
			} elseif ($val["is_renew"] == 1 && $val["type"] == "renew" || $val["is_renew"] == 1 && $val["typess"] == "renew" && $val["type"] == "discount") {
				$list[$key]["batesamount"] = round(bcmul($val["amount"], $val["renew_bates"] / 100, 2), 2);
			} elseif ($val["updategrade"] == 1 && $val["type"] == "upgrade" || $val["updategrade"] == 1 && $val["typess"] == "upgrade" && $val["type"] == "discount") {
				$list[$key]["batesamount"] = round(bcmul($val["amount"], $val["upgrade_bates"] / 100, 2), 2);
			} else {
				$list[$key]["batesamount"] = round(bcmul($val["amount"], $val["bates"] / 100, 2), 2);
			}
			switch ($val["type"]) {
				case "renew":
					$list[$key]["type"] = "续费";
					break;
				case "discount":
					$list[$key]["type"] = "客户折扣";
					break;
				case "promo":
					$list[$key]["type"] = "优惠码";
					break;
				case "setup":
					$list[$key]["type"] = "初装";
					break;
				case "host":
					$list[$key]["type"] = "新订购";
					break;
				case "custom":
					$list[$key]["type"] = "新流量包订购";
					break;
				case "upgrade":
					$list[$key]["type"] = "升级";
					break;
				case "zjmf_flow_packet":
					$list[$key]["type"] = "流量包订购";
					break;
				case "zjmf_reinstall_times":
					$list[$key]["type"] = "重装次数";
					break;
			}
		}
		$list = array_values($list);
		foreach ($list as $key => $val) {
			if (!empty($arr[$val["invoice_id"]])) {
				if ($val["type"] == "新订购") {
					$val["type"] = $val["name"];
				}
				$val["item_id"] = $val["invoice_id"] . "_" . rand(10000, 99999);
				$val["name"] = $val["type"];
				$c = explode("+", $val["batesamount"]);
				$val["batesamount"] = $c[0];
				$val["batesamount1"] = $c[1];
				$arr[$val["invoice_id"]]["child"][] = $val;
			} else {
				$val["item_id"] = $val["invoice_id"];
				if ($val["type"] == "初装") {
					$val["type"] = "新订购 ";
				}
				$arr[$val["invoice_id"]] = $val;
				if ($val["type"] == "新订购 ") {
					$val["type"] = "初装";
				}
				$val["item_id"] = $val["invoice_id"] . "_" . rand(10000, 99999);
				$c = explode("+", $val["batesamount"]);
				$val["batesamount"] = $c[0];
				$val["batesamount1"] = $c[1];
				$arr[$val["invoice_id"]]["child"][] = $val;
			}
		}
		$arr1 = [];
		foreach ($arr as $key => $val) {
			$arr1[] = $val;
		}
		foreach ($arr1 as $key => $val) {
			if ($val["type"] == "客户折扣") {
				foreach ($val["child"] as $k => $v) {
					if ($v["type"] != "客户折扣") {
						$arr1[$key]["type"] = $v["type"];
						break;
					}
				}
			}
			$c3 = 0;
			$c1 = 0;
			$c2 = 0;
			foreach ($val["child"] as $key1 => $val1) {
				$c3 = bcadd($val1["amount"], $c3, 2);
				$c1 = bcadd($c1, $val1["batesamount"], 2);
				$c2 = bcadd($c2, $val1["batesamount1"], 2);
			}
			$arr1[$key]["amount"] = $c3;
			if (!empty($ladder["turnover"]["turnover"])) {
				if ($val["type"] != "续费" && $val["type"] != "升级") {
					$arr1[$key]["batesamount"] = $c1 . "+" . $c2;
				}
			} else {
				if ($val["type"] != "续费" && $val["type"] != "升级") {
					$arr1[$key]["batesamount"] = $c1;
				}
			}
		}
		foreach ($arr1 as $key => $val) {
			$refund = 0;
			$refund1 = 0;
			$refunds = 0;
			$bates = 0;
			foreach ($val["child"] as $keys => $vals) {
				if ($vals["type"] === "discount") {
					$arr1[$key]["child"][$keys]["type"] = "客户折扣";
				}
				if ($vals["is_renew"] == 1 && $vals["type"] == "续费") {
					if ($vals["renew_bates"] / 100 != 0) {
						$bates = $vals["renew_bates"] / 100;
						continue;
					}
				} else {
					if ($vals["updategrade"] == 1 && $vals["type"] == "升级") {
						if ($vals["upgrade_bates"] / 100 != 0) {
							$bates = $vals["upgrade_bates"] / 100;
							continue;
						}
					} else {
						if ($vals["bates"] / 100 != 0) {
							$bates = $vals["bates"] / 100;
							continue;
						}
					}
				}
			}
			$accounts = \think\Db::name("accounts")->field("id,amount_out")->where("invoice_id", $val["invoice_id"])->where("refund", ">", 0)->select()->toArray();
			if (!empty($accounts)) {
				foreach ($accounts as $val2) {
					$refund = bcadd($refund, bcmul($bates, $val2["amount_out"], 4), 4);
					$refunds = bcadd($refunds, $val2["amount_out"], 4);
					if (!empty($ladder["turnover"]["turnover"])) {
						$refund1 = bcadd($refund1, bcmul($ladder["turnover"]["bates"] / 100, $val2["amount_out"], 4), 4);
					}
				}
				if (bcadd($refund, $refund1, 4) > 0) {
					$count = explode("+", $val["batesamount"]);
					if (bcsub(bcadd($refund, $refund1, 4), bcadd($count[1], $count[0], 2), 2) > 0) {
						$arr1[$key]["refound"] = "-" . round($refunds, 2) . $val["suffix"] . ",提成-" . round($count[0], 2);
						$arr1[$key]["batesamount"] = "0.00+0.00";
					} else {
						$arr1[$key]["refound"] = "-" . round($refunds, 2) . $val["suffix"] . ",提成-" . round($refund, 2);
						if (empty($count[1])) {
							$arr1[$key]["batesamount"] = round(bcsub($count[0], $refund, 2), 2);
						} else {
							$arr1[$key]["batesamount"] = round(bcsub($count[0], $refund, 2), 2) . "+" . round(bcsub($count[1], $refund1, 2), 2);
						}
					}
				}
			}
		}
		foreach ($arr1 as $kk => $vv) {
			$base = 0;
			foreach ($vv["child"] as $vvv) {
				$base = bcadd($base, $vvv["batesamount"], 2);
				$arr1[$kk]["batesamount"] = $base;
			}
		}
		$arrss = [["label" => "续费", "name" => "renew"], ["label" => "新订购", "name" => "host"], ["label" => "升级", "name" => "upgrade"], ["label" => "流量包订购", "name" => "zjmf_flow_packet"], ["label" => "重装次数", "name" => "zjmf_reinstall_times"]];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "total" => $total, "record" => $arr1, "type" => $arrss]);
	}
	/**
	 * @title 销售管理统计页 提现记录 测试id默认传3
	 * @description 接口说明:销售管理统计页提现记录，zhoufei新版
	 * @param .name:name type:int require:0 default:1 other: desc:姓名
	 * @param .name:pname type:int require:0 default:1 other: desc:商品
	 * @param .name:type type:int require:0 default:1 other: desc:类型
	 * @param .name:id type:int require:0 default:1 other: desc:销售id
	 * @param .name:search_time type:int  require:0  default: other: desc:传入时间戳，返回当月日志
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @param .name:time type:int require:0 default: other: desc:时间类型
	 * @param .name:type type:int require:0 default:1 other: desc:类型1顶部  2图形
	 * @return total:销售管理列表总数
	 * @return record:销售管理列表表数据@
	 * @list  amount:金额
	 * @list  batesamount:提成
	 * @list  name:产品名
	 * @list  username:用户名
	 * @list  type:类型
	 * @url /admin/sale/sale_records
	 * @method POST
	 */
	public function saleRecordsNew()
	{
		error_reporting(0);
		$params = $data = $this->request->param();
		if (!isset($params["time"])) {
			$params["time"] = 1;
		}
		$record_list = $this->searchSaleRecordInfo("get_list", "", "", $params, false);
		$record_count = count($record_list);
		$record_list_stat = $this->searchSaleRecordInfo("get_list", "", "", $params, true);
		$this_month_sale = $this->get_this_month_sale($record_list_stat);
		$this_month_commission_total = $this_month_sale["this_month_commission_total"];
		$this_month_sale_total = $this_month_sale["this_month_sale_total"];
		$data = [];
		if ($record_list) {
			foreach ($record_list as $key => $item) {
				$currency_info = $this->getInvoicesCurrencyInfo($item);
				$item["suffix"] = $currency_info["suffix"];
				$item["prefix"] = $currency_info["prefix"];
				$temp["pay_time"] = empty($item["paid_time"]) ? "N/A" : date("Y-m-d H:i", $item["paid_time"]);
				$item = $this->getProductInoviceDetail($item, $this_month_sale_total);
				$temp["invoice_id"] = $item["invoice_id"];
				$temp["username"] = $item["username"];
				$temp["name"] = $item["name"];
				$temp["amount"] = $item["total"] ?? 0;
				$temp["refound"] = $item["refund"] ?? 0;
				$temp["batesamount"] = $item["commission_sum"] ?? 0;
				$temp["type"] = $item["type_string"] ?? "";
				$temp["child"] = [];
				if ($item["child_invoice"]) {
					foreach ($item["child_invoice"] as &$child_item) {
						$child_temp["type"] = $child_item["label"];
						$child_temp["amount"] = $child_item["amount"] ?? 0;
						$child_temp["batesamount"] = round($child_item["commision_amount"], 2) ?? 0;
						$child_temp["suffix"] = $child_item["suffix"] ?? "元";
						$temp["child"][] = $child_temp;
					}
				}
				$data[] = $temp;
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "record" => $data, "total" => $record_count, "this_month_commission_total" => $this_month_commission_total, "this_month_sale_total" => $this_month_sale_total, "type" => $this->getCommisionInvoice(), "last" => $this->getLastLadder($this_month_sale_total), "now_ladder" => $this->getLastLadder($this_month_sale_total, "now")]);
	}
	/**
	 * 获取阶梯提成信息
	 * @param int $this_month_sale_total 销售额
	 * @param string $now_or_last 上一级或者下一级：now 当前级，last  下一级
	 */
	private function getLastLadder($this_month_sale_total = 0, $now_or_last = "last")
	{
		$ladder["turnover"] = 0;
		$ladder["bates"] = 0;
		$sale_ladder = \think\Db::name("sale_ladder")->order("turnover", $now_or_last == "last" ? "asc" : "desc")->select()->toArray();
		if ($sale_ladder) {
			foreach ($sale_ladder as $key => $item) {
				if ($now_or_last == "last") {
					if ($this_month_sale_total < $item["turnover"]) {
						$ladder["turnover"] = $item["turnover"];
						$ladder["bates"] = $item["bates"];
						break;
					}
				} elseif ($now_or_last == "now") {
					if ($item["turnover"] <= $this_month_sale_total) {
						$ladder["turnover"] = $item["turnover"];
						$ladder["bates"] = $item["bates"];
						break;
					}
				}
			}
		}
		$ladder["suffix"] = \think\Db::name("currencies")->where("default", 1)->value("suffix");
		return $ladder;
	}
	/**
	 * 获取本月销售情况
	 */
	private function get_this_month_sale($record_list)
	{
		$this_month_commission_total = 0;
		$this_month_sale_total = 0;
		if ($record_list) {
			foreach ($record_list as $key => &$item) {
				$item = $this->getProductInoviceDetail($item);
				$this_month_sale_total = bcadd($this_month_sale_total, $item["sale_total"], 2);
			}
			foreach ($record_list as $key => &$item) {
				$item = $this->getProductInoviceDetail($item, $this_month_sale_total);
				$this_month_commission_total = bcadd($this_month_commission_total, $item["commission_sum_true_num"], 2);
			}
		}
		return ["this_month_commission_total" => $this_month_commission_total, "this_month_sale_total" => $this_month_sale_total];
	}
	/**
	 * 查询账单的货币单位
	 * 这种就不联合查询了
	 */
	private function getInvoicesCurrencyInfo($item)
	{
		$info = \think\Db::name("accounts")->alias("a")->leftJoin("currencies cu", "cu.code = a.currency")->where("a.invoice_id", $item["invoice_id"])->field("suffix,prefix")->find();
		return $info;
	}
	/**
	 * 获取产品账单明细
	 * @param string $item ['invoce_id'] 账单id
	 * @param string $item ['gid'] 账单产品所属的产品分组
	 * @param string $item ['productid'] 账单产品id
	 * @return array
	 */
	private function getProductInoviceDetail($item, $this_month_sale_total = 0)
	{
		if (empty($item["invoice_id"]) || empty($item["productid"])) {
			return [];
		}
		$item["child_invoice"] = [];
		$item["refund"] = "";
		$item["refund_sum"] = 0;
		$item["commission_sum"] = 0;
		$allow_invoice = $this->getCommisionInvoice();
		$allow_invoice_types = array_column($allow_invoice, "name");
		$allow_invoice_types_label = array_column($allow_invoice, "label", "name");
		$item["type_string"] = $allow_invoice_types_label[$item["type"]];
		if ($item["type"] === "product") {
			$item["type_string"] = "新订购";
		}
		$item["name"] = $this->packageNewHostLabel($item, $item);
		$item["username"] = $this->packageClientLabel($item);
		if ($item["type"] != "upgrade") {
			$invoice_item_list = \think\Db::name("invoice_items")->alias("im")->join("host h", "h.id = im.rel_id")->join("products p", "p.id = h.productid")->leftJoin("accounts ac", "ac.invoice_id = im.invoice_id")->leftJoin("currencies cu", "ac.currency = cu.code")->field("h.id as host_id,h.domain")->field("p.id as productid,p.name as product_name")->field("im.invoice_id,im.type,im.amount")->field("cu.prefix,cu.suffix")->group("im.id")->where("im.invoice_id", $item["invoice_id"])->where("im.delete_time", 0)->select()->toArray();
		} else {
			if ($item["type"] == "upgrade") {
				$invoice_item_list = \think\Db::name("invoice_items")->alias("im")->join("upgrades ug", "im.rel_id = ug.id")->join("host h", "h.id = ug.relid")->join("products p", "p.id = h.productid")->leftJoin("accounts ac", "ac.invoice_id = im.invoice_id")->leftJoin("currencies cu", "ac.currency = cu.code")->field("h.id as host_id,h.domain")->field("p.id as productid,p.name as product_name")->field("im.invoice_id,im.type,im.amount")->field("cu.prefix,cu.suffix")->group("im.id")->where("im.invoice_id", $item["invoice_id"])->where("im.delete_time", 0)->select()->toArray();
			}
		}
		$last_suffix = "";
		if ($invoice_item_list) {
			foreach ($invoice_item_list as $key => $child_item) {
				$sales_product_groups = $this->getCommissionSet($item, $child_item["productid"]);
				$child_invoice["label"] = $this->packageCommissionLabel($item, $child_item);
				$child_invoice["amount"] = $child_item["amount"];
				$child_invoice["commision_amount"] = $this->calculateCommisionAmount($child_item, $sales_product_groups);
				$child_invoice["commision_amount_str"] = round($child_invoice["commision_amount"], 2) . $item["suffix"];
				$item["commission_sum"] = bcadd($item["commission_sum"], $child_invoice["commision_amount"], 4);
				$child_invoice["suffix"] = $child_item["suffix"];
				$child_invoice["prefix"] = $child_item["prefix"];
				$last_suffix = $child_item["suffix"];
				$item["child_invoice"][] = $child_invoice;
			}
		}
		$item["commission_sum"] = round($item["commission_sum"], 2);
		$product_refund_bates = $this->getProductRefundBates($item["productid"], $item["type"], $item);
		$refund_info = \think\Db::name("accounts")->where("invoice_id", $item["invoice_id"])->where("refund", ">", 0)->find();
		$item["refund_sum"] = 0;
		if ($refund_info) {
			$item["refund_sum"] = bcadd($refund_info["amount_out"], $item["refund_sum"], 2);
			$refund_commission = bcmul($item["refund_sum"] / 100, $product_refund_bates, 2);
			$item["refund"] = "-" . $item["refund_sum"] . $last_suffix . "，" . "提成-" . $refund_commission . $last_suffix;
			$item["commission_sum"] = bcsub($item["commission_sum"], $refund_commission, 2);
			if ($item["commission_sum"] < 0) {
				$item["commission_sum"] = 0;
			}
		}
		$item["sale_total"] = round($item["total"] - $item["refund_sum"], 2);
		$item["commission_sum_true_num"] = $item["commission_sum"];
		$ladder_commission = $this->getLadderCommission($item["total"], $item["refund_sum"], $this_month_sale_total);
		if ($ladder_commission > 0) {
			$item["commission_sum"] .= "+" . $ladder_commission;
			$item["commission_sum_true_num"] = bcadd($ladder_commission, $item["commission_sum_true_num"], 2);
		}
		return $item;
	}
	/**
	 * 计算阶梯营业额提成
	 * @param $this_month_sale_total 本月销售总额
	 */
	private function getLadderCommission($item_amount = 0, $refund_sum = 0, $this_month_sale_total = 0)
	{
		$ladder_commission = 0;
		$true_sale_amount = $item_amount - $refund_sum;
		$sale_ladder = \think\Db::name("sale_ladder")->order("turnover", "desc")->select()->toArray();
		if ($sale_ladder) {
			foreach ($sale_ladder as $item) {
				if ($item["turnover"] <= $this_month_sale_total) {
					$ladder_commission = bcmul($true_sale_amount, $item["bates"] / 100, 2);
					break;
				}
			}
		}
		return $ladder_commission;
	}
	/**
	 * 获取产品的退款计算比例
	 * @param string $productid 产品id
	 * @param string $product_type 主账单类型
	 * @param array $item 主账单信息
	 * @return int
	 */
	private function getProductRefundBates($productid = "", $product_type = "", $item)
	{
		if (empty($productid) || empty($product_type)) {
			return 0;
		}
		$refund_bates = 0;
		$bates_info = $this->getCommissionSet($item, $productid);
		switch ($product_type) {
			case "product":
				$refund_bates = $bates_info["bates"];
				break;
			case "renew":
				$refund_bates = $bates_info["is_renew"] ? $bates_info["renew_bates"] : 0;
				break;
			case "upgrade":
				$refund_bates = $bates_info["updategrade"] ? $bates_info["upgrade_bates"] : 0;
				break;
			case "zjmf_flow_packet":
				$refund_bates = $bates_info["zjmf_flow_packet_bates"];
				break;
			case "zjmf_reinstall_times":
				$refund_bates = $bates_info["zjmf_reinstall_times_bates"];
				break;
			case "setup":
				$refund_bates = $bates_info["setup_bates"];
				break;
			case "discount":
				$refund_bates = $bates_info["discount_bates"];
				break;
			case "promo":
				$refund_bates = $bates_info["promo_bates"];
				break;
			default:
				break;
		}
		return $refund_bates;
	}
	/**
	 * 封装提成明细的名称
	 */
	private function packageCommissionLabel($item, $child_item)
	{
		$label = "";
		switch ($child_item["type"]) {
			case "renew":
				$label = "续费";
				break;
			case "discount":
				$label = "客户折扣";
				break;
			case "host":
				$label = $this->packageNewHostLabel($item, $child_item);
				break;
			case "promo":
				$label = "优惠码";
				break;
			case "recharge":
				$label = "充值";
				break;
			case "setup":
				$label = "初装";
				break;
			case "upgrade":
				$label = "升级";
				break;
			case "zjmf_flow_packet":
				$label = "流量包";
				break;
			case "zjmf_reinstall_times":
				$label = "重装次数";
				break;
		}
		return $label;
	}
	/**
	 * 封装【提成列表/明细】客户 名称的链接
	 */
	private function packageClientLabel($item)
	{
		$client_username_str = $item["client_username"] . "(" . $item["companyname"] . ")";
		$label = "<a class=\"el-link el-link--primary is-underline\" 
            href=\"#/customer-view/abstract?id=" . $item["client_id"] . "\">
            <span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $client_username_str . "</span></a>";
		return $label;
	}
	/**
	 * 封装【提成列表/明细】新订购 产品 名称的链接
	 */
	private function packageNewHostLabel($item, $child_item)
	{
		$host_name_str = $child_item["product_name"] . "(" . $child_item["domain"] . ")";
		$label = "<a class=\"el-link el-link--primary is-underline\" 
                href=\"#/customer-view/product-innerpage?hid=" . $child_item["host_id"] . "&id=" . $item["client_id"] . "\">
                <span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $host_name_str . "</span></a>";
		return $label;
	}
	/**
	 * 根据提成比例计算提成金额
	 * @param $child_item 子账单明细
	 * @param $sales_product_groups 产品提成比例配置
	 */
	private function calculateCommisionAmount($child_item, $sales_product_groups)
	{
		if (empty($child_item) || empty($sales_product_groups)) {
			return 0;
		}
		if ($child_item["type"] === "host" || $child_item["type"] === "product") {
			$bates_key = "bates";
		} else {
			$bates_key = $child_item["type"] . "_bates";
		}
		$sales_product_groups_bates = $sales_product_groups[$bates_key] ?? 0;
		$commision_amount = bcmul($child_item["amount"], $sales_product_groups_bates / 100, 4);
		return $commision_amount;
	}
	/**
	 * 获取产品提成比例配置
	 * @param string $productid 主账单产品id（第一个产品）
	 * @param array $item 主账单信息
	 * @param int $child_invoice_productid 子账单产品id
	 */
	private function getCommissionSet($item, $child_invoice_productid = 0)
	{
		$where = "find_in_set('" . $child_invoice_productid . "',pids)";
		$sales_product_groups = \think\Db::name("sales_product_groups")->where($where)->find();
		if ($sales_product_groups) {
			if ($sales_product_groups["is_renew"] == 0) {
				$sales_product_groups["renew_bates"] = 0;
			}
			if ($sales_product_groups["updategrade"] == 0) {
				$sales_product_groups["upgrade_bates"] = 0;
			}
			$sales_product_groups["zjmf_flow_packet_bates"] = $sales_product_groups["bates"];
			$sales_product_groups["zjmf_reinstall_times_bates"] = $sales_product_groups["bates"];
			$sales_product_groups["setup_bates"] = $sales_product_groups["bates"];
			$sales_product_groups["discount_bates"] = $this->getDiscountProductBates($item, $sales_product_groups);
			$sales_product_groups["promo_bates"] = $this->getPromoProductBates($item, $sales_product_groups);
		}
		return $sales_product_groups;
	}
	/**
	 * 获取折扣产品的提成比例
	 */
	private function getDiscountProductBates($item, $sales_product_groups)
	{
		if ($item["type"] === "host" || $item["type"] === "product") {
			$bates_key = "bates";
		} else {
			$bates_key = $item["type"] . "_bates";
		}
		return $sales_product_groups[$bates_key];
	}
	/**
	 * 获取使用优惠券产品的提成比例
	 */
	private function getPromoProductBates($item, $sales_product_groups)
	{
		if ($item["type"] === "host" || $item["type"] === "product") {
			$bates_key = "bates";
		} else {
			$bates_key = $item["type"] . "_bates";
		}
		return $sales_product_groups[$bates_key];
	}
	/**
	 * 查找销售记录基础列表，不含提成等信息
	 * @param string $search_type 查询类型：get_list 获取列表（默认）,get_count 获取记录总数
	 * @param string $statistics 是否统计数据
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	private function searchSaleRecordInfo($search_type = "get_list", $start_time = "", $end_time = "", $params = [], $statistics = false)
	{
		$name = !empty($params["name"]) ? trim($params["name"]) : "";
		$pname = !empty($params["pname"]) ? trim($params["pname"]) : "";
		$type = !empty($params["type"]) ? trim($params["type"]) : "";
		$id = !empty($params["id"]) ? intval($params["id"]) : session("ADMIN_ID");
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "i.id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "desc";
		$start_time = $params["start_time"];
		$where = [];
		if (!empty($params["time"])) {
			if ($params["time"] == 1) {
				$where[] = ["i.paid_time", ">=", strtotime(date("Y-m-01", time()))];
				$where[] = ["i.paid_time", "<", strtotime(date("Y-m", time()) . "+1 month")];
			} elseif ($params["time"] == 2) {
				$where[] = ["i.paid_time", ">=", strtotime(date("Y-m-d", strtotime("-0 year -3 month -0 day")))];
				$where[] = ["i.paid_time", "<", time()];
			} elseif ($params["time"] == 4) {
				if ($start_time[0]) {
					$where[] = ["i.paid_time", "egt", $start_time[0]];
				}
				if ($start_time[1]) {
					$where[] = ["i.paid_time", "elt", $start_time[1]];
				}
			}
		}
		$allow_invoice = $this->getCommisionInvoice();
		unset($allow_invoice[2]);
		$allow_invoice_types = array_column($allow_invoice, "name");
		$search_obj = \think\Db::name("invoices")->alias("i")->join("invoice_items im", "im.invoice_id = i.id")->join("host h", "h.id = im.rel_id")->join("products p", "p.id = h.productid")->join("clients c", "c.id = i.uid")->field("i.id as invoice_id,i.subtotal as total,i.type,i.paid_time")->field("p.id as productid,p.name as product_name")->field("h.id as host_id,h.domain")->field("c.id as client_id,c.username  as  client_username,c.companyname")->group("invoice_id")->where($where)->where("i.status", "Paid")->where("i.delete_time", 0)->where("c.sale_id", $id)->where("im.type", "in", $allow_invoice_types);
		if ($name) {
			$info = $search_obj->where("c.username", "like", "%{$name}%");
		}
		if ($type) {
			$info = $search_obj->where("im.type", $type);
		}
		if ($pname) {
			$info = $search_obj->where("p.name", "like", "%{$pname}%");
		}
		if ($search_type === "get_list") {
			if ($statistics) {
				$info = $search_obj->order($order, $sort)->order("im.id", "asc")->select()->toArray();
			} else {
				$info = $search_obj->order($order, $sort)->order("im.id", "asc")->page($page)->limit($limit)->select()->toArray();
			}
			if ($page == 1) {
				$invoice_upgrade_lists = \think\Db::name("invoices")->alias("i")->join("invoice_items im", "im.invoice_id = i.id")->join("upgrades ug", "ug.id = im.rel_id")->join("host h", "ug.relid = h.id")->join("products p", "h.productid = p.id")->join("clients c", "c.id = i.uid")->join("user u", "u.id = c.sale_id")->where("i.status", "Paid")->where("i.delete_time", 0)->where("c.sale_id", $id)->where("i.type", "upgrade")->where($where)->field("i.id as invoice_id,i.subtotal as total,i.type,i.paid_time")->field("p.id as productid,p.name as product_name")->field("h.id as host_id,h.domain")->field("c.id as client_id,c.username  as  client_username,c.companyname")->group("invoice_id")->order("invoice_id", "desc")->select()->toArray();
				$info = array_merge_recursive($info, $invoice_upgrade_lists);
			}
		} elseif ($search_type === "get_count") {
			$info = $search_obj->count();
		}
		return $info;
	}
	private function getCommisionInvoice()
	{
		return [["label" => "续费", "name" => "renew"], ["label" => "新订购", "name" => "host"], ["label" => "升级", "name" => "upgrade"], ["label" => "流量包订购", "name" => "zjmf_flow_packet"], ["label" => "重装次数", "name" => "zjmf_reinstall_times"]];
	}
	/**
	 * @title 销售管理统计页 销售列表
	 * @description 接口说明: 销售管理统计页 销售列表
	 * @return list:销售列表@
	 * @list  user_nickname:昵称
	 * @list  id:id
	 * @url /admin/sale/sale_users
	 * @method GET
	 */
	public function saleUsers()
	{
		$params = $data = $this->request->param();
		$list = \think\Db::name("user")->field("id,user_nickname")->where("is_sale", 1)->select()->toArray();
		$sessionAdminId = session("ADMIN_ID");
		$user = \think\Db::name("user")->field("is_sale,sale_is_use,only_mine,all_sale")->where("id", $sessionAdminId)->find();
		if ($user["is_sale"] == 1) {
			if ($user["all_sale"] == 1) {
				$list = $list;
			} else {
				$list = [];
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "all_sale" => $user["all_sale"], "list" => $list]);
	}
	/**
	 * @title 销售设置
	 * @description 管理员列表
	 * @author lgd
	 * @url         admin/sale/adminlist
	 * @method      GET
	 * @time        2020-06-29
	 * @param       .name:page type:int require:0 default:1 other: desc:页数
	 * @param       .name:search type:string require:0 default: other: desc:搜索
	 * @param       .name:limit type:int require:0 default:50 other: desc:每页条数
	 * @return      page:当前页数
	 * @return      limit:每页条数
	 * @return      count:总条数
	 * @return      max_page:总页数
	 * @return      list:管理员列表@
	 * @list        id:管理员用户id
	 * @list        user_login:管理员用户名
	 * @list        list.user_nickname:管理员姓名
	 * @list        list.user_email:邮箱
	 * @list        list.create_time:创建时间
	 * @list        list.user_status:状态0禁用1可用
	 * @list        list.last_login_time:上次登录时间
	 * @list        list.last_login_ip:上次登录ip
	 * @list        list.role:管理员角色
	 * @list        list.dept:工单部门
	 * @list        is_sale:是否销售0=默认1=是
	 * @list        sale_is_use:销售是否启用0=默认1=启用
	 * @list        only_mine:只允许查看自己的客户
	 * @list        all_sale:查看所有销售
	 */
	public function adminList()
	{
		$page = input("get.page", 1, "intval");
		$search = input("get.search", "");
		$limit = input("get.limit", 10, "intval");
		$page = $page >= 1 ? $page : config("page");
		$limit = $limit >= 1 ? $limit : config("limit");
		$params = $this->request->param();
		$order = isset($params["order"][0]) ? trim($params["order"]) : "a.id";
		$sort = isset($params["sort"][0]) ? trim($params["sort"]) : "DESC";
		$count = \think\Db::name("user")->where("user_nickname LIKE '%{$search}%' OR user_login LIKE '%{$search}%'")->count();
		$data = \think\Db::name("user")->alias("a")->leftJoin("role_user b", "b.user_id = a.id")->leftJoin("role c", "c.id = b.role_id")->field("a.cat_ownerless")->field("a.id,a.user_login,a.user_nickname,a.user_email,a.create_time,a.user_status,a.last_login_time,a.last_login_ip,c.name as role,a.is_sale,a.sale_is_use,a.only_mine,a.all_sale,a.only_oneself_notice")->where("user_nickname LIKE '%{$search}%' OR user_login LIKE '%{$search}%'")->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		foreach ($data as &$val) {
			$val["cat_ownerless"] = $val["is_sale"] ? $val["cat_ownerless"] : 0;
		}
		$res["status"] = 200;
		$res["msg"] = lang("SUCCESS MESSAGE");
		$res["count"] = $count;
		$res["list"] = $data;
		return jsonrule($res);
	}
	/**
	 * @title 编辑销售设置
	 * @description 接口说明: 编辑销售设置
	 * @param .name:id type:int require:1  other: desc:用户id
	 * @param .name:is_sale type:int require:0 default:1 other: desc:是否销售0=默认1=是
	 * @param .name:sale_is_use type:int require:0 default:0 other: desc:销售是否启用0=默认1=启用
	 * @param .name:only_mine type:int require:0 default:0 other: desc:只允许查看自己的客户
	 * @param .name:all_sale type:int require:0 default:0 other: desc:查看所有销售
	 * @param .name:only_oneself_notice type:int require:0 default:0 other: desc:
	 * @throws
	 * @author lgd
	 * @url /admin/sale/edit_adminlist
	 * @method post
	 * @time 2020-06-29
	 */
	public function editAdminList()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$data["is_sale"] = isset($param["is_sale"]) ? floatval($param["is_sale"]) : 0;
			$data["sale_is_use"] = isset($param["sale_is_use"]) ? floatval($param["sale_is_use"]) : 0;
			$data["only_mine"] = isset($param["only_mine"]) ? intval($param["only_mine"]) : 0;
			$data["all_sale"] = isset($param["all_sale"]) ? intval($param["all_sale"]) : 0;
			$data["only_oneself_notice"] = isset($param["only_oneself_notice"]) ? intval($param["only_oneself_notice"]) : 0;
			$data["cat_ownerless"] = isset($param["cat_ownerless"]) ? intval($param["cat_ownerless"]) : 0;
			$desc = "";
			$spg = \think\Db::name("user")->where("id", $param["id"])->find();
			if ($spg["is_sale"] != $param["is_sale"]) {
				if ($spg["is_sale"] == 1) {
					$desc .= " 是销售";
				} else {
					$data["cat_ownerless"] = $param["cat_ownerless"] = 1;
					$desc .= " 不是销售";
				}
			}
			if ($spg["sale_is_use"] != $param["sale_is_use"]) {
				if ($spg["sale_is_use"] == 1) {
					$desc .= "销售启用";
				} else {
					$desc .= " 销售不启用";
				}
			}
			if ($spg["only_mine"] != $param["only_mine"]) {
				if ($spg["only_mine"] == 1) {
					$desc .= " 只能查看自己的客户";
				} else {
					$desc .= " 可以不查看自己的客户";
				}
			}
			if ($spg["all_sale"] != $param["all_sale"]) {
				if ($spg["all_sale"] == 1) {
					$desc .= " 只能查看自己的客户";
				} else {
					$desc .= " 可以不查看自己的客户";
				}
			}
			if ($spg["cat_ownerless"] != $param["cat_ownerless"]) {
				if (!getEdition() && $param["cat_ownerless"] == 0) {
					return jsonrule(["status" => 400, "msg" => "请购买专业版本"]);
				}
				if ($spg["cat_ownerless"] == 1) {
					$desc .= " 可以查看未分配的客户";
				} else {
					$desc .= " 不可以查看未分配的客户";
				}
			}
			if ($spg["only_oneself_notice"] != $param["only_oneself_notice"]) {
				if ($spg["only_oneself_notice"] == 1) {
					$desc .= " 仅自己客户的工单提醒邮件";
				} else {
					$desc .= " 仅自己客户的工单提醒邮件";
				}
			}
			\think\Db::name("user")->where("id", $param["id"])->update($data);
			active_log(sprintf($this->lang["Aff_admin_editAdminList"], $param["id"], $desc));
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 销售配置
	 * @description 接口说明:销售配置
	 * @author 萧十一郎
	 * @url /admin/sale/get_sale_enble
	 * @method GET
	 * @return sale_setting:设置0,1，2
	 * @return sale_reg_setting:设置0,1，2
	 * @return sale_auto_setting:分配设置1，2
	 * @return only_oneself_notice:分配设置1，2
	 */
	public function getSaleEnble()
	{
		$returndata = [];
		$config_files = ["sale_setting"];
		$config_files1 = ["sale_reg_setting"];
		$config_files2 = ["sale_auto_setting"];
		$config_files3 = ["only_oneself_notice"];
		$config_data = \think\Db::name("configuration")->whereIn("setting", $config_files)->select()->toArray();
		$config_data1 = \think\Db::name("configuration")->whereIn("setting", $config_files1)->select()->toArray();
		$config_data2 = \think\Db::name("configuration")->whereIn("setting", $config_files2)->select()->toArray();
		$config_data3 = \think\Db::name("configuration")->whereIn("setting", $config_files3)->select()->toArray();
		if (empty($config_data) && empty($config_data1)) {
			$config_value["sale_setting"] = 0;
			$config_value["sale_reg_setting"] = 0;
			$config_value["sale_auto_setting"] = 0;
			$config_value["only_oneself_notice"] = 0;
		} else {
			$config_value = [];
			foreach ($config_data as $key => $val) {
				$config_value[$val["setting"]] = $val["value"];
			}
			foreach ($config_data1 as $key => $val) {
				$config_value[$val["setting"]] = $val["value"];
			}
			foreach ($config_data2 as $key => $val) {
				$config_value[$val["setting"]] = $val["value"];
			}
			foreach ($config_data3 as $key => $val) {
				$config_value[$val["setting"]] = $val["value"];
			}
		}
		$returndata["config_value"] = $config_value;
		return jsonrule(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 销售配置提交
	 * @description 接口说明:
	 * @param .name:sale_setting type:int require:0 default:1 other: desc:设置
	 * @param .name:sale_reg_setting type:int require:0 default:1 other: desc:注册设置
	 * @param .name:sale_auto_setting type:int require:0 default:1 other: desc:分配设置
	 * @param .name:only_oneself_notice type:int require:0 default:1 other: desc:仅自己客户的工单提醒邮件
	 * @author lgd
	 * @url /admin/sale/sale_enble
	 * @method post
	 */
	public function saleEnblePost()
	{
		if ($this->request->isPost()) {
			$param = $this->request->param();
			$trim = array_map("trim", $param);
			$param = array_map("htmlspecialchars", $trim);
			if ($param["sale_setting"] != null) {
				updateConfiguration("sale_setting", $param["sale_setting"]);
			}
			if ($param["sale_reg_setting"] != null) {
				updateConfiguration("sale_reg_setting", $param["sale_reg_setting"]);
			}
			if ($param["sale_auto_setting"] != null) {
				updateConfiguration("sale_auto_setting", $param["sale_auto_setting"]);
			}
			if ($param["only_oneself_notice"] != null) {
				updateConfiguration("only_oneself_notice", $param["only_oneself_notice"]);
			}
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
}