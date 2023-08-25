<?php

namespace app\admin\controller;

/**
 * @title 代理商
 */
class AgentController extends GetUserController
{
	private $_config = ["tmp_url" => CMF_ROOT . "public/upload/tmp/"];
	private $resource_url = "http://w2.test.idcsmart.com";
	/**
	 * 时间 2021-09-02
	 * @title 资源池登录信息
	 * @desc 资源池登录信息
	 * @url /admin/agent/resourceinfo
	 * @method GET
	 * @author wyh
	 * @return  username:账号
	 * @return  password:密码
	 */
	public function getResourceInfo()
	{
		$info = \think\Db::name("zjmf_finance_api")->field("id,username,password,ticket_open")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		if ($info["password"]) {
			$info["password"] = aesPasswordDecode($info["password"]);
		}
		$data = ["info" => $info ?: []];
		return jsonrule(["status" => 200, "msg" => "请求成功", "data" => $data]);
	}
	/**
	 * 时间 2021-09-02
	 * @title 提交资源池登录信息
	 * @desc 提交资源池登录信息
	 * @url /admin/agent/resourceinfo
	 * @method POST
	 * @author wyh
	 * @param  .name:id type:int require:1 desc:API ID（编辑时才传）
	 * @param  .name:username type:string require:1 desc:账号
	 * @param  .name:password type:string require:1 desc:API密钥
	 */
	public function postResourceInfo()
	{
		$param = $this->request->param();
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("username", trim($param["username"]))->find();
		$data = ["hostname" => $this->resource_url, "username" => trim($param["username"]), "password" => $param["password"] ? aesPasswordEncode($param["password"]) : "", "name" => "资源池", "is_using" => 1];
		\think\Db::startTrans();
		try {
			\think\Db::name("zjmf_finance_api")->where("is_resource", 1)->update(["is_using" => 0]);
			if (!empty($api)) {
				\think\Db::name("zjmf_finance_api")->where("id", $api["id"])->update($data);
			} else {
				$data["create_time"] = time();
				$data["type"] = "zjmf_api";
				$data["is_resource"] = 1;
				$data["ticket_open"] = 0;
				\think\Db::name("zjmf_finance_api")->insertGetId($data);
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
		return jsonrule(["status" => 200, "msg" => "请求成功"]);
	}
	/**
	 * 时间 2021-09-02
	 * @title 工单传递
	 * @desc 工单传递
	 * @url /admin/agent/resourceticketopen
	 * @method POST
	 * @author wyh
	 * @param  .name:id type:int require:1 desc:API ID（编辑时才传）
	 * @param  .name:ticket_open type:int require:1 desc:是否开启工单传递:1是,0否
	 */
	public function postResourceTicketOpen()
	{
		$param = $this->request->param();
		$id = intval($param["id"]);
		$exist = \think\Db::name("zjmf_finance_api")->where("id", $id)->find();
		if (empty($exist)) {
			return jsonrule(["status" => 400, "msg" => "账号不存在,请先填写账号"]);
		}
		\think\Db::name("zjmf_finance_api")->where("id", $id)->update(["ticket_open" => intval($param["ticket_open"])]);
		return jsonrule(["status" => 200, "msg" => "请求成功"]);
	}
	/**
	 * 时间 2021-09-02
	 * @title 测试链接
	 * @desc 测试链接
	 * @url /admin/agent/linktoresource
	 * @method POST
	 * @author wyh
	 */
	public function postLinkToResource()
	{
		$info = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		if (empty($info)) {
			return jsonrule(["status" => 400, "msg" => "账号错误"]);
		}
		$url = rtrim($info["hostname"], "/");
		$url = $url . "/resource_login";
		$post_data = ["username" => $info["username"], "password" => aesPasswordDecode($info["password"]), "type" => "agent"];
		$id = $info["id"];
		$res = zjmfApiLogin($id, $url, $post_data, true);
		if ($res["status"] == 200) {
			\think\Db::startTrans();
			try {
				\think\Db::name("zjmf_finance_api")->where("id", $id)->update(["status" => 1]);
				$result["status"] = 200;
				$result["data"]["status"] = 1;
				$result["data"]["desc"] = "连接成功";
				$res = zjmfCurl($id, "/cart/all", [], 15, "GET");
				if ($res["status"] == 200) {
					\think\Db::name("zjmf_finance_api")->where("id", $id)->update(["product_num" => $res["data"]["count"]]);
				}
				$post_data = ["hostname" => request()->domain(), "admin_url" => adminAddress()];
				$res = zjmfCurl($id, "/resource/agentinfo", $post_data);
				if ($result["status"] != 200) {
					throw new \think\Exception($res["msg"]);
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				$result["status"] = 200;
				$result["data"]["status"] = 0;
				$result["data"]["desc"] = $e->getMessage();
			}
		} else {
			\think\Db::name("zjmf_finance_api")->where("id", $id)->update(["status" => 0]);
			$result["status"] = 200;
			$result["data"]["status"] = 0;
			$result["data"]["desc"] = $res["msg"];
		}
		return jsonrule($result);
	}
	/**
	 * 时间 2021-09-01
	 * @title 商品列表
	 * @desc 商品列表
	 * @url /admin/agent/products
	 * @param  .name:keyword type:mixed require:0 desc:关键字搜索
	 * @method GET
	 * @author wyh
	 * @return products:列表@
	 * @products  id:
	 * @products  name：产品名称
	 * @products  gname:分类名称
	 * @products  qty:本地 库存
	 * @products  upstream_qty:上游 库存
	 * @products  host_count:数量 总
	 * @products  host_active:激活
	 * @products  type_zh：类型
	 * @products  billingcycle_zh:周期
	 * @products  product_price：价格
	 * @products  product_shopping_url：链接 本地
	 * @products  upstream_product_shopping_url：链接 上游
	 * @return  product_count:产品 总数
	 * @return  local_qty:本地 库存 总
	 * @return  upstream_qty:上游 库存 总
	 * @return  host_total:数量 总
	 * @return  host_active:激活 总
	 */
	public function getProducts()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$res = zjmfCurl($id, "resource/agentproductsarray", [], 30, "GET");
		if ($res["status"] == 200) {
			$resource_currency = $res["currency"] ?: [];
			$pids = array_column($res["data"], "pid") ?: [];
			$username = array_column($res["data"], "username", "pid") ?: [];
			$percent = array_column($res["data"], "percent", "pid") ?: [];
			$current_rate = array_column($res["data"], "current_rate", "pid") ?: [];
			$param["id"] = $id;
			$where = function (\think\db\Query $query) use($param, $pids, $id) {
				if (!empty($param["keyword"])) {
					$query->where("b.name|c.name", "like", "%{$param["keyword"]}%");
				}
				if (!empty($param["id"])) {
					$query->where("a.zjmf_api_id|b.zjfm_api_id|c.zjmf_api_id|c.upper_reaches_id", $param["id"]);
				} else {
					$query->where("a.is_upstream", 1)->whereOr("b.is_upstream", 1)->whereOr("c.zjmf_api_id|c.upper_reaches_id", ">", 0);
				}
			};
			$products = \think\Db::name("product_first_groups")->field("c.zjmf_api_id,c.id,c.name,b.name as gname,b.id as gid,a.id as fgid,a.name as fgname,c.qty,
                c.upstream_qty,c.product_shopping_url,c.upstream_product_shopping_url,c.type,c.pay_type,c.api_type,
                c.upstream_version,c.upstream_price_type,c.upstream_price_value,c.upstream_pid")->alias("a")->leftJoin("product_groups b", "a.id=b.gid")->leftJoin("products c", "b.id=c.gid")->where($where)->select()->toArray();
			$currencyid = getDefaultCurrencyId();
			$prefix = \think\Db::name("currencies")->where("id", $currencyid)->value("prefix");
			$product_count = count($products);
			$local_qty = $upstream_qty = $host_total = $host_active = 0;
			foreach ($products as &$v) {
				array_map(function ($value) {
					return is_string($value) ? htmlspecialchars_decode($value, ENT_QUOTES) : $value;
				}, $v);
				$v["type_zh"] = config("product_type")[$v["type"]];
				$paytype = (array) json_decode($v["pay_type"]);
				$pricing = \think\Db::name("pricing")->where("type", "product")->where("relid", $v["id"])->where("currency", $currencyid)->find();
				if (!empty($paytype["pay_ontrial_status"])) {
					if ($pricing["ontrial"] >= 0) {
						$v["product_price"] = $pricing["ontrial"];
						$v["setup_fee"] = $pricing["ontrialfee"];
						$v["billingcycle"] = "ontrial";
						$v["billingcycle_zh"] = lang("ONTRIAL");
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
					}
				}
				if ($paytype["pay_type"] == "free") {
					$v["product_price"] = 0;
					$v["setup_fee"] = 0;
					$v["billingcycle"] = "free";
					$v["billingcycle_zh"] = lang("FREE");
				} elseif ($paytype["pay_type"] == "onetime") {
					if ($pricing["onetime"] >= 0) {
						$v["product_price"] = $pricing["onetime"];
						$v["setup_fee"] = $pricing["osetupfee"];
						$v["billingcycle"] = "onetime";
						$v["billingcycle_zh"] = lang("ONETIME");
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
					}
				} else {
					if (!empty($pricing) && $paytype["pay_type"] == "recurring") {
						if ($pricing["hour"] >= 0) {
							$v["product_price"] = $pricing["hour"];
							$v["setup_fee"] = $pricing["hsetupfee"];
							$v["billingcycle"] = "hour";
							$v["billingcycle_zh"] = lang("HOUR");
						} elseif ($pricing["day"] >= 0) {
							$v["product_price"] = $pricing["day"];
							$v["setup_fee"] = $pricing["dsetupfee"];
							$v["billingcycle"] = "day";
							$v["billingcycle_zh"] = lang("DAY");
						} elseif ($pricing["monthly"] >= 0) {
							$v["product_price"] = $pricing["monthly"];
							$v["setup_fee"] = $pricing["msetupfee"];
							$v["billingcycle"] = "monthly";
							$v["billingcycle_zh"] = lang("MONTHLY");
						} elseif ($pricing["quarterly"] >= 0) {
							$v["product_price"] = $pricing["quarterly"];
							$v["setup_fee"] = $pricing["qsetupfee"];
							$v["billingcycle"] = "quarterly";
							$v["billingcycle_zh"] = lang("QUARTERLY");
						} elseif ($pricing["semiannually"] >= 0) {
							$v["product_price"] = $pricing["semiannually"];
							$v["setup_fee"] = $pricing["ssetupfee"];
							$v["billingcycle"] = "semiannually";
							$v["billingcycle_zh"] = lang("SEMIANNUALLY");
						} elseif ($pricing["annually"] >= 0) {
							$v["product_price"] = $pricing["annually"];
							$v["setup_fee"] = $pricing["asetupfee"];
							$v["billingcycle"] = "annually";
							$v["billingcycle_zh"] = lang("ANNUALLY");
						} elseif ($pricing["biennially"] >= 0) {
							$v["product_price"] = $pricing["biennially"];
							$v["setup_fee"] = $pricing["bsetupfee"];
							$v["billingcycle"] = "biennially";
							$v["billingcycle_zh"] = lang("BIENNIALLY");
						} elseif ($pricing["triennially"] >= 0) {
							$v["product_price"] = $pricing["triennially"];
							$v["setup_fee"] = $pricing["tsetupfee"];
							$v["billingcycle"] = "triennially";
							$v["billingcycle_zh"] = lang("TRIENNIALLY");
						} elseif ($pricing["fourly"] >= 0) {
							$v["product_price"] = $pricing["fourly"];
							$v["setup_fee"] = $pricing["foursetupfee"];
							$v["billingcycle"] = "fourly";
							$v["billingcycle_zh"] = lang("FOURLY");
						} elseif ($pricing["fively"] >= 0) {
							$v["product_price"] = $pricing["fively"];
							$v["setup_fee"] = $pricing["fivesetupfee"];
							$v["billingcycle"] = "fively";
							$v["billingcycle_zh"] = lang("FIVELY");
						} elseif ($pricing["sixly"] >= 0) {
							$v["product_price"] = $pricing["sixly"];
							$v["setup_fee"] = $pricing["sixsetupfee"];
							$v["billingcycle"] = "sixly";
							$v["billingcycle_zh"] = lang("SIXLY");
						} elseif ($pricing["sevenly"] >= 0) {
							$v["product_price"] = $pricing["sevenly"];
							$v["setup_fee"] = $pricing["sevensetupfee"];
							$v["billingcycle"] = "sevenly";
							$v["billingcycle_zh"] = lang("SEVENLY");
						} elseif ($pricing["eightly"] >= 0) {
							$v["product_price"] = $pricing["eightly"];
							$v["setup_fee"] = $pricing["eightsetupfee"];
							$v["billingcycle"] = "eightly";
							$v["billingcycle_zh"] = lang("EIGHTLY");
						} elseif ($pricing["ninely"] >= 0) {
							$v["product_price"] = $pricing["ninely"];
							$v["setup_fee"] = $pricing["ninesetupfee"];
							$v["billingcycle"] = "ninely";
							$v["billingcycle_zh"] = lang("NINELY");
						} elseif ($pricing["tenly"] >= 0) {
							$v["product_price"] = $pricing["tenly"];
							$v["setup_fee"] = $pricing["tensetupfee"];
							$v["billingcycle"] = "tenly";
							$v["billingcycle_zh"] = lang("TENLY");
						} else {
							$v["product_price"] = 0;
							$v["setup_fee"] = 0;
							$v["billingcycle"] = "";
							$v["billingcycle_zh"] = lang("PRICE_CONFIG_ERROR");
						}
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
					}
				}
				if ($paytype["pay_type"] == "recurring" && in_array($v["type"], array_keys(config("developer_app_product_type")))) {
					if ($pricing["annually"] > 0) {
						$v["product_price"] = $pricing["annually"];
						$v["setup_fee"] = $pricing["asetupfee"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					}
				}
				$v["product_price"] = bcadd($v["setup_fee"], $v["product_price"], 2);
				$cart_logic = new \app\common\logic\Cart();
				$rebate_total = 0;
				$config_total = $cart_logic->getProductDefaultConfigPrice($v["id"], $currencyid, $v["billingcycle"], $rebate_total);
				$v["product_price"] = $v["product_count"] = $v["product_price_show"] = bcadd($v["product_price"], $config_total, 2);
				$v["product_count"] = bcmul($v["product_count"], $percent[$v["upstream_pid"]], 2);
				if ($v["api_type"] == "zjmf_api" && $v["upstream_version"] > 0 && $v["upstream_price_type"] == "percent") {
					$v["product_price"] = bcmul($v["product_price"], $v["upstream_price_value"], 2) / 100;
				}
				$v["product_price"] = bcsub($v["product_price"], 0, 2);
				$v["profit"] = bcsub($v["product_price"], $v["product_count"], 2);
				$v["host_count"] = \think\Db::name("host")->where("productid", $v["id"])->count() ?: 0;
				$v["host_active"] = \think\Db::name("host")->where("productid", $v["id"])->where("domainstatus", "Active")->count() ?: 0;
				$v["upstream_product_shopping_url"] = $v["upstream_product_shopping_url"] ?? "";
				$v["server_name"] = strSubstrOmit($username[$v["upstream_pid"]]);
				$v["current_rate"] = $current_rate[$v["upstream_pid"]];
				$v["resource_price"] = $resource_currency["prefix"] . bcdiv($v["product_price_show"], $v["current_rate"], 2) . $resource_currency["suffix"];
				$local_qty += $v["qty"];
				$upstream_qty += $v["upstream_qty"];
				$host_total += $v["host_count"];
				$host_active += $v["host_active"];
			}
			$products_filter = [];
			foreach ($products as $vv) {
				if (!isset($products_filter[$vv["fgname"]])) {
					$products_filter[$vv["fgname"]] = [];
				}
				$products_filter[$vv["fgname"]][] = $vv;
			}
			$filter = [];
			foreach ($products_filter as $k3 => $vvv) {
				foreach ($vvv as $v4) {
					if (!isset($filter[$k3][$v4["gname"]])) {
						$filter[$k3][$v4["gname"]] = [];
					}
					$v4 = array_map(function ($v) {
						return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
					}, $v4);
					if (empty($v4["gid"])) {
						$filter[$k3] = [];
					}
					if (!empty($v4["id"]) && in_array($v4["upstream_pid"], $pids)) {
						$filter[$k3][$v4["gname"]][] = $v4;
					}
				}
			}
			$data = ["products" => $filter, "product_count" => $product_count, "local_qty" => $local_qty, "upstream_qty" => $upstream_qty, "host_count" => $host_total, "host_active" => $host_active, "prefix" => $prefix];
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
		}
		return jsonrule($res);
	}
	/**
	 * @title 订单列表搜索页面
	 * @description 接口说明:
	 * @author xiong
	 * @url admin/agent/ordersearchpage
	 * @method get
	 * @param .name:status type:int require:0 other: desc:订单状态
	 * @param .name:order_type type:int require:0 other: desc:订购类型
	 * @param .name:supplier_username type:string require:0  other: desc:卖家
	 */
	public function getOrderSearchPage()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$param = $this->request->param();
		$res = zjmfCurl($id, "resource/agentorderssearch", $param, 30, "GET");
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $res["data"]]);
	}
	/**
	 * @title 订单列表
	 * @description 接口说明:订单列表
	 * @author wyh
	 * @time 2021-09-03
	 * @url admin/agent/order
	 * @method GET
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @param .name:type type:string require:0  other: desc:订单类型order_type
	 * @param .name:value type:string require:0  other: desc:传返回的type的键
	 * @return  count:总数
	 * @return  rows:订单列表信息@
	 * @rows  id:资源池订单ID
	 * @rows  local_id:本地订单ID(跳转至订单内页以这个为准)
	 * @rows  local_hostid:本地产品ID(跳转至产品内页以这个为准)
	 * @rows  name:商品名称
	 * @rows  domain:主机名
	 * @rows  local_name:本地商品
	 * @rows  local_domain:本地主机
	 * @rows  product_type:产品类型
	 * @rows  dedicatedip:IP
	 * @rows  supplier_username:供应商用户名(卖家)
	 * @rows  agent_username:代理商用户名(买家?用户?)
	 * @rows  local_username:在代理商购买商品的客户 的 用户名(用户?)
	 * @rows  order_status:订单状态
	 * @rows  create_time:订购时间
	 * @rows  invoice_type_zh:订单类型
	 * @rows  billingcycle_zh:周期
	 * @rows  payment:代理商 在资源池处的支付方式
	 * @rows  local_payment: 客户在代理商处的支付方式
	 * @rows  order_notes:代理商 在资源池处的  客户备注
	 * @rows  local_order_notes:客户在代理商处的 客户备注
	 * @rows  i_status:订单支付状态：除了Unpaid外，其他都有退款按钮
	 * @rows  evaluation_id:评论ID,大于0代表已评论
	 * @rows  resource_amount:资源池购买价格
	 * @rows  current_rate:相对资源池货币汇率
	 * @rows  after_sale:售后记录信息(有此值，则显示 撤销申请；否则显示 售后申请)@
	 * @after_sale  id:售后申请记录ID，撤销申请传此值
	 * @rows  hosts:产品信息@
	 * @hosts  hostid:产品ID
	 * @hosts  local_hostid:本地产品ID(跳转以至产品内页以这个为准)
	 * @hosts  name:产品名称
	 * @hosts  domain:主机名
	 * @hosts  dedicatedip:IP
	 * @hosts  billingcycle:周期
	 * @hosts  firstpaymentamount:金额
	 * @hosts  type:产品类型
	 */
	public function getOrder()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$param = $this->request->param();
		$res = zjmfCurl($id, "resource/agentorders", $param, 30, "GET");
		if ($res["status"] == 200) {
			$gateways = gateway_list1("gateways", 0);
			$currency = \think\Db::name("currencies")->where("default", 1)->find();
			$rows = [];
			foreach ($res["data"]["rows"] as $row) {
				$hids = array_column($row["hosts"], "hostid");
				$where = function (\think\db\Query $query) use($row) {
					if ($row["invoice_type"] == "upgrade") {
						$query->where("e.type", "upgrade");
					}
				};
				$local_order = \think\Db::name("orders")->field("a.amount,a.id,b.uid,b.domain,c.name,d.username,a.payment,e.payment as i_payment,
                    e.status as i_status,e.use_credit_limit,e.subtotal as sub,e.credit,a.notes,b.id as hostid,e.id as invoiceid,
                    c.upstream_price_value,b.percent_value")->alias("a")->leftJoin("upgrades i", "i.order_id=a.id")->leftJoin("host b", "a.id=b.orderid OR i.relid=b.id")->leftJoin("products c", "b.productid=c.id")->leftJoin("clients d", "a.uid=d.id")->leftJoin("invoices e", "a.invoiceid=e.id")->where($where)->whereIn("b.dcimid", $hids)->where("c.zjmf_api_id", $id)->withAttr("payment", function ($value, $data) use($gateways) {
					$i_value = $data["i_payment"] ?: $value;
					if ($data["i_status"] == "Paid") {
						if ($data["use_credit_limit"] == 1) {
							return "信用额支付";
						} else {
							if ($data["sub"] == $data["credit"]) {
								return "余额支付";
							} else {
								if ($data["sub"] > $data["credit"] && $data["credit"] > 0) {
									foreach ($gateways as $v) {
										if ($v["name"] == $i_value) {
											return "部分余额支付+" . $v["title"];
										}
									}
								} else {
									foreach ($gateways as $v) {
										if ($v["name"] == $i_value) {
											return $v["title"];
										}
									}
								}
							}
						}
					} else {
						foreach ($gateways as $v) {
							if ($v["name"] == $i_value) {
								return $v["title"];
							}
						}
					}
				})->group("a.id")->find();
				if ($row["invoice_type"] == "product") {
					$amount = \think\Db::name("invoice_items")->where("invoice_id", $local_order["invoiceid"])->whereIn("rel_id", $local_order["hostid"])->where("type", "<>", "upgrade")->sum("amount");
				} elseif ($row["invoice_type"] == "upgrade") {
					$amount = bcmul(bcdiv(bcmul($row["amount"], $row["current_rate"], 20), $row["agent_grade"] / 100, 2), $local_order["percent_value"] / 100, 2);
				}
				$row["local_invoiceid"] = $local_order["invoiceid"];
				$row["local_id"] = $local_order["id"] ?: "";
				$row["local_uid"] = $local_order["uid"] ?: "";
				$row["local_hostid"] = $local_order["hostid"] ?: "";
				$row["local_name"] = $local_order["name"] ?: "";
				$row["local_domain"] = $local_order["domain"] ?: "";
				$row["local_username"] = $local_order["username"] ?: "";
				$row["local_username"] = strSubstrOmit($row["local_username"]);
				$row["local_payment"] = $local_order["payment"] ?: "";
				$row["local_order_notes"] = $local_order["notes"] ?: "";
				$resource_amount = $row["amount"];
				$local_amount = bcmul($resource_amount, $row["current_rate"], 2);
				$row["local_amount"] = $currency["prefix"] . $local_amount . $currency["suffix"];
				$row["local_profit"] = $currency["prefix"] . bcsub($amount, $local_amount, 2) . $currency["suffix"];
				$row["resource_amount"] = $row["prefix"] . $resource_amount . $row["suffix"];
				$row["sub"] = $row["amount"];
				$row["local_sub"] = $local_order["sub"] ?: "";
				$rows[] = $row;
			}
			$res["data"]["rows"] = array_values($rows);
			$res["data"]["count"] = count($res["data"]["rows"]);
		}
		return jsonrule($res);
	}
	/**
	 * @title 续费订单列表搜索页面
	 * @description 接口说明:
	 * @author xiong
	 * @url admin/agent/renewsearchpage
	 * @method get
	 * @param .name:status type:int require:0 other: desc:订单状态
	 * @param .name:supplier_username type:string require:0  other: desc:卖家
	 */
	public function getRenewSearchPage()
	{
	}
	/**
	 * @title 续费订单列表
	 * @description 接口说明:续费订单列表
	 * @author xiong
	 * @time 2021-09-03
	 * @url admin/agent/renew
	 * @method GET
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @param .name:type type:string require:0  other: desc:订单类型order_type
	 * @param .name:value type:string require:0  other: desc:传返回的type的键
	 * @return  count:总数
	 * @return  rows:订单列表信息@
	 * @rows  id:资源池账单ID
	 * @rows  name:商品名称
	 * @rows  domain:主机名
	 * @rows  dedicatedip:IP
	 * @rows  paid_time:续费时间
	 * @rows  amount:金额
	 * @rows  supplier_username:供应商用户名(卖家)
	 * @rows  payment:代理商 在资源池处的支付方式	 
	 * @rows  local_hostid:本地产品ID(跳转至产品内页以这个为准)
	 * @rows  local_name:本地商品
	 * @rows  local_domain:本地主机
	 * @rows  local_invoiceid:本地账单ID(跳转至账单内页以这个为准)
	 * @rows  local_username:在代理商购买商品的客户 的 用户名(用户?)
	 * @rows  local_payment: 客户在代理商处的支付方式
	 */
	public function getRenew()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$param = $this->request->param();
		$res = zjmfCurl($id, "resource/agentrenew", $param, 30, "GET");
		if ($res["status"] == 200) {
			$gateways = gateway_list1("gateways", 0);
			$currency = \think\Db::name("currencies")->where("default", 1)->find();
			foreach ($res["data"]["rows"] as &$row) {
				$local_invoices = \think\Db::name("invoices")->field("b.amount,i.id,b.uid,b.domain,c.name,d.username,i.payment,i.use_credit_limit,i.subtotal as sub,i.credit,b.id as hostid,i.status")->alias("i")->leftJoin("invoice_items ii", "ii.invoice_id = i.id")->leftJoin("host b", "ii.rel_id = b.id")->leftJoin("products c", "b.productid=c.id")->leftJoin("clients d", "i.uid=d.id")->where("b.dcimid", $row["hostid"])->where("c.zjmf_api_id", $id)->withAttr("payment", function ($value, $data) use($gateways) {
					$i_value = $data["payment"] ?: $value;
					if ($data["status"] == "Paid") {
						if ($data["use_credit_limit"] == 1) {
							return "信用额支付";
						} else {
							if ($data["sub"] == $data["credit"]) {
								return "余额支付";
							} else {
								if ($data["sub"] > $data["credit"] && $data["credit"] > 0) {
									foreach ($gateways as $v) {
										if ($v["name"] == $i_value) {
											return "部分余额支付+" . $v["title"];
										}
									}
								} else {
									foreach ($gateways as $v) {
										if ($v["name"] == $i_value) {
											return $v["title"];
										}
									}
								}
							}
						}
					} else {
						foreach ($gateways as $v) {
							if ($v["name"] == $i_value) {
								return $v["title"];
							}
						}
					}
				})->group("i.id")->find();
				$row["local_id"] = $local_invoices["id"] ?: "";
				$row["local_uid"] = $local_invoices["uid"] ?: "";
				$row["local_hostid"] = $local_invoices["hostid"] ?: "";
				$row["local_name"] = $local_invoices["name"] ?: "";
				$row["local_domain"] = $local_invoices["domain"] ?: "";
				$row["local_username"] = $local_invoices["username"] ?: "";
				$row["local_username"] = strSubstrOmit($row["local_username"]);
				$row["local_payment"] = $local_invoices["payment"] ?: "";
				$resource_amount = $row["amount"];
				$prefix = $currency["prefix"];
				$suffix = $currency["suffix"];
				$local_amount = bcmul($resource_amount, $row["current_rate"], 2);
				$row["local_amount"] = $prefix . $local_amount . $suffix;
				$row["local_profit"] = $prefix . bcsub($local_invoices["amount"], $local_amount, 2) . $suffix;
				$row["resource_amount"] = $row["prefix"] . $resource_amount . $row["suffix"];
				$row["amount"] = $row["local_amount"];
			}
		}
		return jsonrule($res);
	}
	/**
	 * 时间 2021-09-03
	 * @title 售后详情
	 * @desc 售后详情
	 * @url /admin/agent/afterSaleDetail
	 * @param .name:id type:int require:1 default:1 other: desc:after_sale.id
	 * @method GET
	 * @author xue
	 */
	public function getAfterSaleDetail()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$param = $this->request->param();
		$res = zjmfCurl($id, "resource/afterSaleDetail", $param, 30, "GET");
		return jsonrule($res);
	}
	/**
	 * 时间 2021-09-03
	 * @title 退款详情
	 * @desc 退款详情
	 * @url /admin/agent/refundDetail
	 * @param .name:id type:int require:1 default:1 other: desc:账单ID
	 * @method GET
	 * @author xue
	 */
	public function getRefundDetail()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$param = $this->request->param();
		$res = zjmfCurl($id, "resource/refundDetail", $param, 30, "GET");
		if ($res["status"] == 200) {
			$refund = $res["data"]["refund"];
			$amount = $refund["amount"];
			$subtotal = $refund["subtotal"];
			$percent = bcdiv($amount, $subtotal, 20);
			$hids = [$refund["hostid"], $refund["vhostid"]];
			$invoice = \think\Db::name("invoices")->alias("a")->field("a.subtotal")->leftJoin("invoice_items b", "a.id=b.invoice_id")->leftJoin("upgrades c", "b.rel_id=c.id")->leftJoin("host d", "(b.rel_id=d.id AND b.type in ('host','renew')) OR (c.relid=d.id AND b.type in ('upgrade'))")->leftJoin("products e", "d.productid=e.id")->where("e.zjmf_api_id", $id)->whereIn("d.dcimid", $hids)->find();
			$res["data"]["refund"]["amount"] = $refund["agent_amount"];
			$res["data"]["refund"]["subtotal"] = $invoice["subtotal"];
		}
		return jsonrule($res);
	}
	/**
	 * 时间 2021-09-03
	 * @title 产品列表
	 * @desc 产品列表
	 * @url /admin/agent/host
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @param .name:keywords type:mix require:0 default: other: desc:关键字搜索
	 * @param .name:status type:mix require:0 default: other: desc:状态搜索
	 * @method GET
	 * @author wyh
	 * @return  status:状态,搜索使用
	 * @return  rows:产品信息@
	 * @rows  id:
	 * @rows  name:商品名称
	 * @rows  domain:主机名
	 * @rows  dedicatedip:IP
	 * @rows  supplier_username:卖家
	 * @rows  agent_username:买家(工单先不要)
	 * @rows  order_status:状态
	 * @rows  create_time:订购时间
	 * @rows  amount:金额
	 * @rows  billingcycle_zh:周期
	 * @rows  local_hostid:本地产品ID(跳转至产品内页需要此参数)
	 * @rows  local_uid:本地客户ID(跳转至产品内页需要此参数)
	 * @rows  shop_name:店铺名称
	 */
	public function getHost()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$param = $this->request->param();
		$res = zjmfCurl($id, "resource/agenthosts", $param, 30, "GET");
		if ($res["status"] == 200) {
			$currency = \think\Db::name("currencies")->where("default", 1)->find();
			foreach ($res["data"]["rows"] as &$row) {
				$dcimid = $row["id"];
				$local_host = \think\Db::name("host")->field("a.id,a.uid,a.amount,a.firstpaymentamount")->alias("a")->leftJoin("products b", "a.productid=b.id")->where("b.zjmf_api_id", $id)->where("a.dcimid", $dcimid)->find();
				$row["local_hostid"] = $local_host["id"];
				$row["local_uid"] = $local_host["uid"];
				$row["amount"] = $currency["prefix"] . ($local_host["amount"] ?: "0.00") . $currency["suffix"];
				$row["firstpaymentamount"] = $currency["prefix"] . ($local_host["firstpaymentamount"] ?: "0.00") . $currency["suffix"];
			}
		}
		return jsonrule($res);
	}
	/**
	 * @title 协查列表
	 * @description 接口说明:协查列表
	 * @author wyh
	 * @time 2021-08-03
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:ASC,DESC
	 * @param .name:keywords type:string require:0 default:1 other: desc:按关键字搜索
	 * @url /admin/agent/inspectionlists
	 * @method GET
	 * @return  lists:商品列表@
	 * @lists  id:
	 * @lists  username:买家
	 * @lists  dedicatedip:独立ip
	 * @lists  assignedips:分配ip
	 * @lists  create_time:提交时间
	 * @lists  status_zh:状态
	 * @lists  reason:驳回原因，当status==Cancelled时，才显示
	 */
	public function getInspectionLists()
	{
		$param = $this->request->param();
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$res = zjmfCurl($id, "resource/resourceinspectionlists", $param, 30, "GET");
		return jsonrule($res);
	}
	/**
	 * @title 上传图片
	 * @description 一般图片上传
	 * @author wyh
	 * @url         admin/agent/upload
	 * @method      POST
	 * @time        2020-09-02
	 * @param       .name:image|file type:file require:1 default:0 other: desc:图片
	 * @return      savename:上传的文件路径
	 */
	public function postUpload()
	{
		$file = request()->file("file");
		if (is_object($file)) {
			$is_file = true;
		}
		$image = request()->file("image");
		if (is_object($image)) {
			$is_file = false;
		}
		$upload = new \app\common\logic\Upload();
		$re = $upload->uploadHandle($image, $is_file);
		if ($re["status"] == 200) {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "originname" => $re["origin_name"], "savename" => $re["savename"]]);
		} else {
			return jsonrule(["status" => 400, "msg" => $re["msg"]]);
		}
	}
	/**
	 * @title 协查申请
	 * @description 接口说明:协查申请
	 * @author wyh
	 * @time 2021-08-18
	 * @url /admin/agent/resourceinspection
	 * @method POST
	 * @param .name:police type:int require:1 default:1 other: desc:警官姓名
	 * @param .name:agency type:int require:1 default:1 other: desc:执法机构
	 * @param .name:ip type:int require:1 default:1 other: desc:调取IP
	 * @param .name:orderid type:int require:1 default:1 other: desc:订单ID(这里只能选一个)
	 * @param .name:email type:int require:1 default:1 other: desc:邮件地址
	 * @param .name:phone type:int require:1 default:1 other: desc:联系电话
	 * @param .name:police_card type:int require:1 default:1 other: desc:警官证((调上传文件接口admin/agent/upload取返回值savename))
	 * @param .name:law type:int require:1 default:1 other: desc:法律文书(调上传文件接口admin/agent/upload取返回值savename)
	 */
	public function postResourceInspection()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$param = $this->request->param();
		$data = ["police" => $param["police"], "agency" => $param["agency"], "ip" => $param["ip"], "orderid" => $param["orderid"], "email" => $param["email"], "phone" => $param["phone"]];
		$file = [];
		if (!empty($param["police_card"])) {
			$upload = new \app\common\logic\Upload();
			$ret = $upload->moveTo($param["police_card"], $this->_config["tmp_url"]);
			if (isset($ret["error"])) {
				return jsons(["status" => 400, "mag" => "文件上传失败"]);
			}
			$police_card = $this->_config["tmp_url"] . $param["police_card"];
			$file["police_card"] = $police_card;
		}
		if (!empty($param["law"])) {
			$upload = new \app\common\logic\Upload();
			$ret = $upload->moveTo($param["law"], $this->_config["tmp_url"]);
			if (isset($ret["error"])) {
				return jsons(["status" => 400, "mag" => "文件上传失败"]);
			}
			$law = $this->_config["tmp_url"] . $param["law"];
			$file["law"] = $law;
		}
		if (!empty($file)) {
			$res = zjmfCurlHasFile($id, "resource/resourceinspection", $data, 30, $file);
		} else {
			$res = zjmfCurl($id, "resource/resourceinspection", $data);
		}
		if ($res["status"] == 200) {
			if ($police_card && file_exists($police_card)) {
				unlink($police_card);
			}
			if ($law && file_exists($law)) {
				unlink($law);
			}
			return jsonrule(["status" => 200, "msg" => "请求成功"]);
		} else {
			return jsonrule(["status" => 400, "msg" => $res["msg"]]);
		}
	}
	/**
	 * @title 匹配ip订单
	 * @description 接口说明:匹配ip订单
	 * @author wyh
	 * @time 2021-08-18
	 * @url /admin/agent/inspectionip
	 * @method GET
	 * @param .name:ip type:int require:1 default:1 other: desc:调取IP
	 * @return orders:订单信息@
	 * @orders id:订单ID
	 * @orders username:客户
	 * @orders companyname:公司名
	 * @orders dedicatedip:ip
	 * @orders assignedips:ip
	 * @orders create_time:订购时间
	 * @orders status_zh:状态，颜色
	 */
	public function getInspectionIp()
	{
		$param = $this->request->param();
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$res = zjmfCurl($id, "resource/resourceinspectionip", $param, 30, "GET");
		return jsonrule($res);
	}
	/**
	 * @title 协查详情
	 * @description 接口说明:协查详情
	 * @author wyh
	 * @time 2021-08-18
	 * @url /admin/agent/inspectiondetail
	 * @method GET
	 * @param .name:id type:int require:1 default:1 other: desc:协查ID
	 * @return  inspection:协查信息@
	 * @inspection  police:警官姓名
	 * @inspection  agency:执法机构
	 * @inspection  ip:调取IP
	 * @inspection  email:邮件地址
	 * @inspection  phone:联系电话
	 * @inspection  police_card:警官证
	 * @inspection  law:法律文书
	 * @inspection  status:状态：Pending待审核（默认），Active通过，Cancelled驳回
	 * @inspection  status_zh:状态：Pending待审核（默认），Active通过，Cancelled驳回
	 * @inspection  reason:驳回原因
	 * @return client:基础信息@
	 * @client  username:用户名
	 * @client  create_time:创建时间
	 * @client  lastlogin:最后登录时间
	 * @client  lastloginip:最后登录ip
	 * @client  register:注册天数
	 * @return agent:认证信息@
	 * @agent  name:姓名
	 * @agent  email:邮箱
	 * @agent  phone:电话
	 * @agent  address:地址
	 * @agent  company:公司名称
	 * @agent  company_address:公司地址
	 * @agent  business_license:营业执照
	 * @agent  img_one:身份证1
	 * @agent  img_two:身份证2
	 * @return  order:购买信息@
	 * @order  id:
	 * @order  name:名称
	 * @order  domain:主机名
	 * @order  type_zh:产品类型
	 * @order  dedicatedip:IP
	 * @order  status_zh:订单状态
	 * @order  create_time:订购时间
	 * @order  invoice_type_zh:订单类型
	 * @order  amount:金额
	 * @order  billingcycle_zh:周期
	 * @order  payment:付款方式
	 * @return  login:登录信息@
	 * @login  id:
	 * @login  create_time:
	 * @login  description:
	 * @login  user:
	 * @login  orgin:
	 */
	public function getInspectionDetail()
	{
		$param = $this->request->param();
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$res = zjmfCurl($id, "resource/resourceinspectiondetail", $param, 30, "GET");
		return jsonrule($res);
	}
	/**
	 * @title 退款
	 * @description 接口说明:退款
	 * @author wyh
	 * @time 2021-09-03
	 * @url /admin/agent/refund
	 * @method POST
	 * @param .name:invoiceid type:int require:1 default:1 other: desc:账单ID
	 * @param .name:amount type:int require:1 default:1 other: desc:退款金额
	 * @param .name:subtotal type:int require:1 default:1 other: desc:总金额
	 * @param .name:refund_reason type:string require:1 default:1 other: desc:退款原因
	 */
	public function postRefund()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$param = $this->request->param();
		$res = zjmfCurl($id, "resource/resourcerefundfromagent", $param);
		return jsonrule($res);
	}
	/**
	 * @title 申请售后
	 * @description 接口说明:申请售后
	 * @author wyh
	 * @time 2021-09-03
	 * @url /admin/agent/aftersale
	 * @method POST
	 * @param .name:orderid type:int require:1 default:1 other: desc:订单ID
	 * @param .name:reason type:string require:1 default:1 other: desc:申请原因
	 * @param .name:agent_img[] type:array require:0 default:1 other: desc:申请附件(调上传文件接口admin/agent/upload取返回值savename),拼接成数组
	 */
	public function postAfterSale()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$param = $this->request->param();
		$file = [];
		if (!empty($param["agent_img"]) && is_array($param["agent_img"])) {
			$upload = new \app\common\logic\Upload();
			foreach ($param["agent_img"] as $item) {
				$ret = $upload->moveTo($item, $this->_config["tmp_url"]);
				if (isset($ret["error"])) {
					return jsons(["status" => 400, "mag" => "文件上传失败"]);
				}
				$agent_img = $this->_config["tmp_url"] . $item;
				$file["agent_img"][] = $agent_img;
			}
		}
		unset($param["agent_img"]);
		if (!empty($file["agent_img"])) {
			$res = zjmfCurlHasFile($id, "resource/resourceaftersalefromagent", $param, 30, $file);
		} else {
			$res = zjmfCurl($id, "resource/resourceaftersalefromagent", $param);
		}
		if ($res["status"] == 200) {
			foreach ($file["agent_img"] as $item2) {
				if ($item2 && file_exists($item2)) {
					unlink($item2);
				}
			}
			return jsonrule(["status" => 200, "msg" => "请求成功"]);
		} else {
			return jsonrule(["status" => 400, "msg" => $res["msg"]]);
		}
	}
	/**
	 * @title 撤销申请
	 * @description 接口说明:撤销申请
	 * @author wyh
	 * @time 2021-09-03
	 * @url /admin/agent/unaftersale
	 * @method POST
	 * @param .name:id type:int require:1 default:1 other: desc:订单ID
	 */
	public function postUnAfterSale()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$param = $this->request->param();
		$res = zjmfCurl($id, "resource/resourceunaftersalefromagent", $param);
		return jsonrule($res);
	}
	/**
	 * @title 基础信息
	 * @description 接口说明:基础信息
	 * @author wyh
	 * @time 2021-09-03
	 * @url /admin/agent/baseinfo
	 * @method GET
	 * @return total:总利润
	 * @return month:本月利润
	 * @return host_total:产品总数
	 * @return host_active:激活产品数
	 * @return currency:货币
	 * @return client:@
	 * @client credit:余额
	 * @client credit_limit:信用额
	 * @client credit_limit_balance:信用额余额
	 * @client prefix:货币前缀
	 */
	public function getBaseInfo()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$res = zjmfCurl($id, "resource/agentbaseinfo", [], 30, "GET");
		if ($res["status"] == 200) {
			$pids = $res["data"]["pids"] ?: [];
			$total = $this->getTotal($pids);
			$total_cost = $res["data"]["total"];
			$month = $this->getTotal($pids, strtotime(date("Y-m-01"), time()), time());
			$month_cost = $res["data"]["month"];
			$host_total = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid=b.id")->whereIn("b.upstream_pid", $pids)->where("b.api_type", "zjmf_api")->where("b.zjmf_api_id", $id)->count();
			$host_active = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid=b.id")->whereIn("b.upstream_pid", $pids)->where("b.api_type", "zjmf_api")->where("b.zjmf_api_id", $id)->where("a.domainstatus", "Active")->count();
			$currency = \think\Db::name("currencies")->field("id,code,prefix,suffix")->where("default", 1)->find();
			$data = ["total" => bcsub($total, $total_cost, 2), "month" => bcsub($month, $month_cost, 2), "host_total" => $host_total, "host_active" => $host_active, "currency" => $currency, "client" => [], "resource_url" => $this->resource_url . "/credit", "grade" => $res["data"]["grade"], "refunded" => $res["data"]["refunded"]];
			$res = zjmfCurl($id, "resource/agentcredit", [], 30, "GET");
			if ($res["status"] == 200) {
				$data["client"] = $res["data"]["client"] ?: [];
			}
			return jsonrule(["status" => 200, "msg" => "请求成功", "data" => $data]);
		}
		return jsonrule($res);
	}
	/**
	 * @title 代理商日志
	 * @description 接口说明:代理商日志 TODO
	 * @author wyh
	 * @time 2021-09-01
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:desc type:int require:0 default:'' other: desc:描述
	 * @param .name:timer type:string require:0 default:'' other: desc:时间
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:ASC,DESC
	 * @url /admin/agent/agentLogs
	 * @method GET
	 * @return count:数量
	 * @return logs:日志@
	 * @logs id:id
	 * @logs create_time:时间
	 * @logs desc:描述
	 * @logs ip:ip
	 * @logs referer:来源
	 * @logs active_name:操作人
	 * @logs user_type_cn:操作人角色
	 */
	public function getAgentLogs()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$param = $this->request->param();
		$res = zjmfCurl($id, "resource/agentlogs", $param, 30, "GET");
		return jsonrule($res);
	}
	private function getTotal($pids, $start = "", $end = "")
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$total = 0;
		$ids = \think\Db::name("invoices")->alias("a")->leftJoin("invoice_items b", "a.id=b.invoice_id")->leftJoin("upgrades c", "b.rel_id=c.id")->leftJoin("host d", "(b.rel_id=d.id AND b.type in ('host','renew')) OR (c.relid=d.id) AND b.type in ('upgrade')")->leftJoin("products e", "d.productid=e.id")->whereIn("e.upstream_pid", $pids)->where("e.api_type", "zjmf_api")->where("e.zjmf_api_id", $id)->column("a.id");
		$session_currency = session("currency");
		if ($session_currency) {
			$currency = $session_currency;
		} else {
			$currency = \think\Db::name("currencies")->field("id,code,prefix,suffix")->where("default", 1)->find();
			session("currency", $currency);
		}
		$where = function (\think\db\Query $query) use($ids, $start, $end, $currency) {
			$query->whereIn("invoice_id", $ids)->where("delete_time", 0)->where("currency", $currency["code"]);
			if ($start && $end) {
				$query->whereBetweenTime("create_time", $start, $end);
			}
		};
		$sum = \think\Db::name("accounts")->where($where)->field("sum(amount_in - amount_out) as total ")->find();
		$total += $sum["total"];
		if (!empty($ids[0])) {
			$apply = \think\Db::name("credit")->alias("a")->where(function (\think\db\Query $query) use($start, $end) {
				if ($start && $end) {
					$query->where("a.create_time", ">=", $start)->where("a.create_time", "<=", $end);
				}
				$query->where("a.description", "like", "%Credit Applied to Invoice #%");
			})->whereIn("a.relid", $ids)->sum("a.amount");
			$renew_apply_total = 0;
			foreach ($ids as $v) {
				$renew_apply = \think\Db::name("credit")->alias("a")->where(function (\think\db\Query $query) use($start, $end, $v) {
					if ($start && $end) {
						$query->where("a.create_time", ">=", $start)->where("a.create_time", "<=", $end);
					}
					$query->where("a.description", "Credit Applied to Renew Invoice #{$v}");
				})->sum("a.amount") ?? 0;
				$renew_apply_total += $renew_apply;
			}
			$remove = \think\Db::name("credit")->alias("a")->where(function (\think\db\Query $query) use($start, $end) {
				if ($start && $end) {
					$query->where("a.create_time", ">=", $start)->where("a.create_time", "<=", $end);
				}
			})->where("a.description", "like", "%Credit Removed from Invoice #%")->whereIn("a.relid", $ids)->sum("a.amount");
		} else {
			$apply = 0;
			$remove = 0;
			$renew_apply_total = 0;
		}
		$total += $apply + $remove + $renew_apply_total;
		return bcsub($total, 0, 2);
	}
	private function getEveryDayTotal($pids, $month_start)
	{
		$days = date("t", $month_start);
		$month_every_day_total = [];
		for ($i = 0; $i <= $days - 1; $i++) {
			${$i + 1 . "_start"} = strtotime("+" . $i . " days", $month_start);
			${$i + 1 . "_end"} = strtotime("+" . ($i + 1) . " days -1 seconds", $month_start);
			${$i + 1 . "_total"} = $this->getTotal($pids, ${$i + 1 . "_start"}, ${$i + 1 . "_end"});
			array_push($month_every_day_total, ${$i + 1 . "_total"});
		}
		return $month_every_day_total;
	}
	private function getEveryMonthTotal($pids, $year_start)
	{
		$year_every_month_total = [];
		for ($i = 0; $i <= 11; $i++) {
			${$i + 1 . "_start"} = strtotime("+" . $i . " month", $year_start);
			${$i + 1 . "_end"} = strtotime("+" . ($i + 1) . " month -1 seconds", $year_start);
			${$i + 1 . "_total"} = $this->getTotal($pids, ${$i + 1 . "_start"}, ${$i + 1 . "_end"});
			array_push($year_every_month_total, ${$i + 1 . "_total"});
		}
		return $year_every_month_total;
	}
	/**
	 * @title 消费数据
	 * @description 接口说明:消费数据
	 * @author wyh
	 * @time 2021-09-07
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:ASC,DESC
	 * @param .name:start type:int require:1 default:10 other: desc:开始时间
	 * @param .name:end type:int require:1 default:10 other: desc:结束时间
	 * @param .name:type type:string require:1 default:10 other: desc:消费类型
	 * @param .name:gateway type:string require:1 default:10 other: desc:支付方式,传支付列表的name
	 * @url /admin/agent/consumption
	 * @method GET
	 * @return  rows:商品列表@
	 * @rows  id:账单
	 * @rows  subtotal:金额
	 * @rows  payment:支付方式
	 * @rows  paid_time:支付时间
	 * @rows  type:消费类型
	 * @rows  notes:备注
	 * @return gateways:支付列表@
	 */
	public function getConsumption()
	{
		$param = $this->request->param();
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$res = zjmfCurl($id, "resource/agentproductsarray", [], 30, "GET");
		if ($res["status"] == 200) {
			$pids = array_column($res["data"], "pid") ?: [];
			$page = !empty($param["page"]) ? intval($param["page"]) : 1;
			$limit = !empty($param["limit"]) ? intval($param["limit"]) : 10;
			$order = !empty($param["order"]) ? trim($param["order"]) : "a.id";
			$sort = !empty($param["sort"]) ? trim($param["sort"]) : "desc";
			$where = function (\think\db\Query $query) use($param, $pids, $id) {
				$query->where("d.api_type", "zjmf_api")->where("d.zjmf_api_id", $id)->whereIn("d.upstream_pid", $pids);
				$start = $param["start"];
				$end = $param["end"];
				if ($start && $end) {
					$query->where("a.create_time", ">=", $start)->where("a.create_time", "<=", $end);
				}
				if (!empty($param["type"]) && $param["type"] != "all") {
					$query->where("b.type", $param["type"]);
				}
				if (!empty($param["gateway"])) {
					$query->where("a.payment", $param["gateway"]);
				}
			};
			$count = \think\Db::name("invoices")->alias("a")->leftJoin("invoice_items b", "a.id=b.invoice_id")->leftJoin("host c", "b.rel_id=c.id AND b.type in ('host','renew')")->leftJoin("upgrades e", "b.rel_id=e.id AND b.type in ('upgrade')")->leftJoin("host f", "e.relid=f.id")->leftJoin("products d", "f.productid=d.id OR c.productid=d.id")->where($where)->group("a.id")->count();
			$gateways = gateway_list();
			$rows = \think\Db::name("invoices")->field("a.id,a.subtotal,a.payment,a.paid_time,b.type,b.type as type_zh,a.notes,a.uid,a.use_credit_limit,a.credit")->alias("a")->leftJoin("invoice_items b", "a.id=b.invoice_id")->leftJoin("host c", "b.rel_id=c.id AND b.type in ('host','renew')")->leftJoin("upgrades e", "b.rel_id=e.id AND b.type in ('upgrade')")->leftJoin("host f", "e.relid=f.id")->leftJoin("products d", "f.productid=d.id OR c.productid=d.id")->where($where)->withAttr("payment", function ($value, $data) use($gateways) {
				$count = \think\Db::name("accounts")->where("invoice_id", $data["id"])->where("delete_time", 0)->count();
				if ($data["use_credit_limit"] == 1 && $this->getInterfacePay($data["id"], $data["uid"]) == 0 && $data["credit"] == 0) {
					return "信用额支付";
				}
				$gateway = \think\Db::name("accounts")->where("invoice_id", $data["id"])->where("refund", 0)->order("id", "desc")->value("gateway");
				if ($data["credit"] > 0 && $count > 0) {
					if (!empty($gateway)) {
						foreach ($gateways as $v) {
							if ($v["name"] == $gateway) {
								return "部分余额支付+" . $v["title"];
							}
						}
					} else {
						foreach ($gateways as $v) {
							if (!empty($gateway)) {
								if ($v["name"] == $gateway) {
									return "部分余额支付+" . $v["title"];
								}
							} else {
								if ($v["name"] == $value) {
									return "部分余额支付+" . $v["title"];
								}
							}
						}
					}
				}
				if ($data["credit"] > 0) {
					return "余额支付";
				}
				if ($count > 0) {
					foreach ($gateways as $v) {
						if (!empty($gateway)) {
							if ($v["name"] == $gateway) {
								return $v["title"];
							}
						} else {
							if ($v["name"] == $value) {
								return $v["title"];
							}
						}
					}
				}
				foreach ($gateways as $v) {
					if ($v["name"] == $value) {
						return $v["title"];
					}
				}
				return $value;
			})->withAttr("type_zh", function ($value) {
				return config("invoice_type")[$value];
			})->limit($limit)->page($page)->order($order, $sort)->group("a.id")->select()->toArray();
			$data = ["count" => $count, "rows" => $rows, "gateways" => $gateways, "type" => ["all" => "全部", "host" => "产品", "renew" => "续费", "upgrade" => "升降级"]];
			return jsonrule(["status" => 200, "msg" => "请求成功", "data" => $data]);
		}
		return jsonrule($res);
	}
	private function getInterfacePay($id, $uid)
	{
		$gateways = gateway_list();
		$accounts = \think\Db::name("accounts")->alias("a")->field("a.id,a.pay_time,a.gateway,a.trans_id,a.amount_in,a.amount_out,a.fees")->withAttr("gateway", function ($value) use($gateways) {
			foreach ($gateways as $v) {
				if ($value == $v["name"]) {
					return $v["title"];
				}
			}
			return $value;
		})->where("a.invoice_id", $id)->where("a.delete_time", 0)->select()->toArray();
		$money = 0;
		foreach ($accounts as $key => $val) {
			if (isset($val["amount_in"])) {
				$money += $val["amount_in"];
				$money -= $val["amount_out"];
			}
		}
		return $money;
	}
	/**
	 * @title 收入概览
	 * @description 接口说明:收入概览
	 * @author wyh
	 * @time 2021-09-06
	 * @param .name:type type:string require:1 default:1 other: desc:week近一周,month本月,year全年
	 * @url /admin/agent/income
	 * @method GET
	 * @return  arr:收入数据,需要根据类型,进行相应显示
	 */
	public function getIncome()
	{
		$param = $this->request->param();
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$res = zjmfCurl($id, "resource/agentproductsarray", [], 30, "GET");
		if ($res["status"] == 200) {
			$pids = array_column($res["data"], "pid") ?: [];
			$type = $param["type"];
			if ($type == "week") {
				$begin = strtotime(date("Y-m-d", strtotime("-6 days", time())));
				$arr = [];
				for ($i = 1; $i <= 7; $i++) {
					$week_start = $week_end ?? $begin;
					$week_end = strtotime("+{$i} days -1 seconds", $begin);
					$total = $this->getTotal($pids, $week_start, $week_end);
					$arr[date("Y-m-d", $begin + ($i - 1) * 24 * 3600)] = $total;
				}
			}
			if ($type == "month") {
				$month_start = strtotime(date("Y-m", time()));
				$arr = $this->getEveryDayTotal($pids, $month_start);
			}
			if ($type == "year") {
				$year_start = strtotime(date("Y-01-01", time()));
				$arr = $this->getEveryMonthTotal($pids, $year_start);
			}
			$data = ["arr" => $arr ?: []];
			return jsonrule(["status" => 200, "msg" => "请求成功", "data" => $data]);
		}
		return jsonrule($res);
	}
	/**
	 * @title 产品信息
	 * @description 接口说明:产品信息
	 * @author wyh
	 * @time 2021-09-07
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:ASC,DESC
	 * @url /admin/agent/hostlists
	 * @method GET
	 * @return  rows:商品列表@
	 * @rows  id:
	 * @rows  name:产品名称
	 * @rows  dedicatedip:ip
	 * @rows  paid_time:支付时间
	 * @rows  nextduedate:到期时间
	 * @rows  billingcycle_zh:周期
	 * @rows  amount:售价
	 * @rows  cost:成本
	 * @rows  notes:备注
	 */
	public function getHostLists()
	{
		$param = $this->request->param();
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$res = zjmfCurl($id, "resource/agentproductsarray", [], 30, "GET");
		if ($res["status"] == 200) {
			$pids = array_column($res["data"], "pid") ?: [];
			$percent = array_column($res["data"], "percent", "pid");
			$page = !empty($param["page"]) ? intval($param["page"]) : 1;
			$limit = !empty($param["limit"]) ? intval($param["limit"]) : 10;
			$order = !empty($param["order"]) ? trim($param["order"]) : "a.id";
			$sort = !empty($param["sort"]) ? trim($param["sort"]) : "desc";
			$where = function (\think\db\Query $query) use($param, $pids, $id) {
				$query->where("d.type", "host")->where("b.api_type", "zjmf_api")->where("b.zjmf_api_id", $id)->whereIn("b.upstream_pid", $pids);
			};
			$count = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid=b.id")->leftJoin("invoice_items d", "a.id=d.rel_id AND d.type in ('host','renew') ")->leftJoin("upgrades u", "a.id=u.relid AND d.rel_id=u.id AND d.type in ('upgrade')")->leftJoin("invoices e", "d.invoice_id=e.id")->where($where)->count();
			$rows = \think\Db::name("host")->field("a.id,b.name,a.dedicatedip,e.paid_time,a.nextduedate,a.billingcycle,a.billingcycle as billingcycle_zh,b.upstream_pid,
            a.amount,a.amount as cost,a.amount as profit,a.notes,b.upstream_price_value,b.upstream_price_type")->alias("a")->leftJoin("products b", "a.productid=b.id")->leftJoin("invoice_items d", "a.id=d.rel_id AND d.type in ('host','renew') ")->leftJoin("upgrades u", "a.id=u.relid AND d.rel_id=u.id AND d.type in ('upgrade')")->leftJoin("invoices e", "d.invoice_id=e.id")->where($where)->withAttr("billingcycle_zh", function ($value) {
				return config("billing_cycle")[$value];
			})->withAttr("cost", function ($value, $data) use($percent) {
				if ($data["upstream_price_type"] == "percent") {
					return bcmul(bcdiv($value, $data["upstream_price_value"] / 100, 2), $percent[$data["upstream_pid"]], 2);
				}
				return $value;
			})->limit($limit)->page($page)->order($order, $sort)->group("a.id")->select()->toArray();
			$data = ["count" => $count, "rows" => $rows];
			return jsonrule(["status" => 200, "msg" => "请求成功", "data" => $data]);
		}
		return jsonrule($res);
	}
	/**
	 * @title 工单列表
	 * @description 工单列表
	 * @param       .name:tid type:string require:0 default: other: desc:tid
	 * @param       .name:email type:string require:0 default: other: desc:邮件地址
	 * @param       .name:content type:string require:0 default: other: desc:主题/内容
	 * @param       .name:priority type:string require:0 default:all other: desc:优先级
	 * @param       .name:dptid type:int require:0 default: other: desc:部门id
	 * @param       .name:uid type:int require:0 default: other: desc:客户id
	 * @param       .name:status type:string require:0 default:all other: desc:状态
	 * @param       .name:limit type:int require:0 default:10 other: desc:条数
	 * @param       .name:page type:int require:0 default:1 other: desc:页数
	 * @return      .limit:每页条数
	 * @return      .page:当前页数
	 * @return      .sum:总条数
	 * @return      .max_page:最大页数
	 * @return      .list.id:工单id
	 * @return      .list.tid:工单tid
	 * @return      .list.uid:发起工单的用户id
	 * @return      .list.title:工单标题
	 * @return      .list.status:工单状态
	 * @return      .list.last_reply_time:最后回复时间戳
	 * @return      .list.flag_admin:标记的管理员名称
	 * @return      .list.department_name:部门名称
	 * @return      .list.user_name:发起工单的用户名
	 * @return      .list.format_time:格式化的最后回复时间
	 * @author wyh
	 * @url         admin/agent/tickets
	 * @method      GET
	 * @time        2021-09-08
	 */
	public function getTickets()
	{
		$params = $this->request->param();
		$limit = input("get.limit", 50, "int");
		$page = input("get.page", 1, "int");
		$order = isset($params["order"][0]) ? trim($params["order"]) : "a.last_reply_time";
		$sort = isset($params["sort"][0]) ? trim($params["sort"]) : "DESC";
		$where[] = ["merged_ticket_id", "=", "0"];
		$tmp = model("TicketDepartmentAdmin")->getAllow();
		if (isset($params["dptid"][0]) && in_array($params["dptid"], $tmp)) {
			$where[] = ["dptid", "=", $params["dptid"], "AND"];
		} else {
			$where[] = ["dptid", "in", $tmp, "AND"];
		}
		if (isset($params["uid"][0]) && $params["uid"] > 0) {
			$where[] = ["a.uid", "=", \intval($params["uid"]), "AND"];
		}
		if (isset($params["priority"][0])) {
			$where[] = ["priority", "=", $params["priority"], "AND"];
		}
		if (isset($params["email"][0])) {
			$where[] = ["email", "=", $params["email"], "AND"];
		}
		if (isset($params["content"][0])) {
			$tmp = sprintf("%%%s%%", $params["content"]);
			$where[] = ["content|a.title", "like", $tmp, "OR"];
		}
		if (isset($params["tid"][0]) && $params["tid"] > 0) {
			$where[] = ["tid", "=", \intval($params["tid"]), "AND"];
		}
		if (isset($params["status"]) && $params["status"] > 0) {
			if ($params["status"] == 1) {
				$params["status"] = [1, 3];
			}
			$params["status"] = is_array($params["status"]) ? $params["status"] : [$params["status"]];
			$where[] = ["a.status", "in", $params["status"], "AND"];
		} else {
			if (!isset($params["status"]) || $params["status"] == "pending") {
				$where[] = ["a.status", "in", [1, 3, 5], "AND"];
			}
		}
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$api_id = intval($api["id"]);
		$other_where = function (\think\db\Query $query) use($api_id) {
			$query->where("p.api_type", "zjmf_api")->where("p.zjmf_api_id", $api_id);
		};
		$data = \think\Db::name("ticket")->alias("a")->field("a.id,a.tid,a.uid,a.admin_unread,a.title,a.name,a.status,a.create_time,a.is_deliver,a.is_receive,a.handle,t.color as statusColor,t.title as status_title,a.last_reply_time,b.user_login flag_admin,c.name department_name,d.username user_name,e.user_login handle_name")->leftJoin("user b", "a.flag=b.id")->leftJoin("ticket_department c", "a.dptid=c.id")->leftJoin("clients d", "a.uid=d.id")->leftJoin("ticket_status t", "a.status = t.id")->leftJoin("user e", "a.handle=e.id")->leftJoin("host h", "a.host_id = h.id")->leftJoin("products p", "h.productid = p.id")->where($where)->where($other_where)->page($page)->order($order, $sort)->limit($limit)->select()->toArray();
		$count = \think\Db::name("ticket")->alias("a")->leftJoin("user b", "a.flag=b.id")->leftJoin("ticket_department c", "a.dptid=c.id")->leftJoin("clients d", "a.uid=d.id")->leftJoin("ticket_status t", "a.status = t.id")->leftJoin("user e", "a.handle=e.id")->leftJoin("host h", "a.host_id = h.id")->leftJoin("products p", "h.productid = p.id")->where($where)->where($other_where)->count();
		foreach ($data as $k => $v) {
			if ($v["status"] == 3 && $v["is_receive"] == 1) {
				$ticket_reply = \think\Db::name("ticket_reply")->where("tid", $v["id"])->order("id", "desc")->find();
				if ($ticket_reply["is_receive"] == 1 && $ticket_reply["source"] == 0) {
					$data[$k]["deliver_status"] = 1;
					$data[$k]["deliver_status_title"] = "下游已回复";
				}
			} else {
				if ($v["status"] == 2 && $v["is_deliver"] == 1) {
					$ticket_reply = \think\Db::name("ticket_reply")->where("tid", $v["id"])->order("id", "desc")->find();
					if ($ticket_reply["is_receive"] == 1 && $ticket_reply["source"] == 1) {
						$data[$k]["deliver_status"] = 2;
						$data[$k]["deliver_status_title"] = "上游已回复";
					}
				}
			}
			$data[$k]["user_name"] = !empty($v["uid"]) ? $v["user_name"] : $v["name"];
			$data[$k]["format_time"] = date("Y-m-d H:i:s", $v["last_reply_time"]);
			$data[$k]["flag_admin"] = $v["flag_admin"] ?: "";
			unset($data[$k]["name"]);
		}
		$status = \think\Db::name("ticket_status")->select();
		$result["status"] = 200;
		$result["msg"] = lang("SUCCESS MESSAGE");
		$result["data"] = ["limit" => $limit, "ticket_status" => $status, "page" => $page, "sum" => $count, "max_page" => ceil($count / $limit), "list" => $data];
		return jsonrule($result);
	}
	/**
	 * @title 供应商任务队列列表
	 * @description 接口说明: 供应商任务队列列表(重试调系统的重试接口)
	 * @url admin/agent/runmaplists
	 * @method GET
	 * @param .name:keywords type:string require:0 default: other: desc:搜索关键字
	 * @param .name:user type:string require:0 default: other: desc:用户名关键字
	 * @param .name:from_type type:int require:0 default: other: desc:来源类型 （100定时任务、200手动任务、300异步触发【订单】、400对接上游、500下游发起）
	 * @param .name:status type:int require:0 default: other: desc:状态 （1成功、0失败）
	 * @param .name:active_type type:int require:0 default: other: desc:来源类型 （1开通、2暂停、3解除暂停、4删除、5续费、6升降级）
	 * @param .name:status type:int require:0 default: other: desc:执行状态
	 * @param .name:page type:int require:0 default:1 other: desc:页码
	 * @param .name:limit type:int require:0 default:1 other: desc:每页条数
	 * @param .name:order type:string  require:0  default:create_time other: desc:排序字段
	 * @param .name:sort type:string  require:0  default:desc other: desc:desc/asc，倒叙/顺序
	 */
	public function getRunMapLists()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$param = $this->request->param();
		$page = !empty($param["page"]) ? intval($param["page"]) : 1;
		$limit = !empty($param["limit"]) ? intval($param["limit"]) : 10;
		$order = !empty($param["order"]) ? trim($param["order"]) : "r.id";
		$sort = !empty($param["sort"]) ? trim($param["sort"]) : "desc";
		$where = function (\think\db\Query $query) use($param, $api) {
			$query->where("z.id", intval($api["id"]));
			$keywords = $param["keywords"];
			$from_type = $param["from_type"];
			$status = $param["status"];
			$user = $param["user"];
			$active_type = $param["active_type"];
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
		};
		$list = \think\Db::name("run_maping")->alias("r")->leftJoin("host h", "r.host_id = h.id")->leftJoin("products p", "h.productid = p.id")->leftJoin("zjmf_finance_api z", "p.zjmf_api_id = z.id")->field("r.*,h.domain")->where($where)->withAttr("last_execute_time", function ($value) {
			return date("Y-m-d H:i", $value);
		})->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		$count = \think\Db::name("run_maping")->alias("r")->leftJoin("host h", "r.host_id = h.id")->leftJoin("products p", "h.productid = p.id")->leftJoin("zjmf_finance_api z", "p.zjmf_api_id = z.id")->field("r.*,h.domain")->where($where)->count();
		$data = ["count" => $count, "list" => $list];
		return jsonrule(["status" => 200, "msg" => "请求成功", "data" => $data]);
	}
	/**
	 * @title 订单评价
	 * @description 接口说明:订单评价
	 * @author xj
	 * @url admin/agent/evaluation
	 * @method POST
	 * @param   .name:content type:string require:1 desc:评论内容
	 * @param   .name:type type:string require:1 desc:类型great好评middle中评bad差评
	 * @param   .name:score type:string require:1 desc:综合评价
	 * @param   .name:netword_score type:string require:1 desc:网络质量
	 * @param   .name:hardware_score type:string require:1 desc:硬件年限
	 * @param   .name:img type:array require:1 desc:图片
	 * @param   .name:id type:int require:1 desc:订单id
	 */
	public function postEvaluation()
	{
		$api = \think\Db::name("zjmf_finance_api")->where("is_resource", 1)->where("is_using", 1)->order("id", "desc")->find();
		$id = $api["id"];
		$param = $this->request->param();
		$file = [];
		if (!empty($param["img"]) && is_array($param["img"])) {
			$upload = new \app\common\logic\Upload();
			foreach ($param["img"] as $item) {
				$ret = $upload->moveTo($item, $this->_config["tmp_url"]);
				if (isset($ret["error"])) {
					return jsons(["status" => 400, "mag" => "文件上传失败"]);
				}
				$img = $this->_config["tmp_url"] . $item;
				$file["img"][] = $img;
			}
		}
		unset($param["img"]);
		if (!empty($file["img"])) {
			$res = zjmfCurlHasFile($id, "resource/evaluation", $param, 30, $file);
		} else {
			$res = zjmfCurl($id, "resource/evaluation", $param, 30, "POST");
		}
		if ($res["status"] == 200) {
			foreach ($file["img"] as $item2) {
				if ($item2 && file_exists($item2)) {
					unlink($item2);
				}
			}
			return jsonrule(["status" => 200, "msg" => "请求成功"]);
		} else {
			return jsonrule(["status" => 400, "msg" => $res["msg"]]);
		}
	}
	/**
	 * 时间 2021-09-02
	 * @title 生成资源池jwt
	 * @desc 生成资源池jwt,并登录资源池
	 * @url /admin/agent/token
	 * @method GET
	 * @author wyh
	 * @return jwt:
	 */
	public function getToken()
	{
		if (cache("?resource_token")) {
			$token = cache("resource_token");
		} else {
			$token = randStr(12);
			cache("resource_token", $token, 300);
		}
		$result = ["status" => 200, "resource_url" => $this->resource_url . "/resource/shop/login?from=" . request()->domain() . "/" . adminAddress() . "&token=" . $token . "&time=" . time()];
		return jsonrule($result);
	}
}