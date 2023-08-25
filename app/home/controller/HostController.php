<?php

namespace app\home\controller;

/**
 * @title 前台产品功能及接口
 * @description 接口说明：前台产品功能及接口
 */
class HostController extends CommonController
{
	private $pid;
	private $imageaddress;
	private $allowSystem;
	private $system;
	private $osIco;
	private $ext = "svg";
	public function construct()
	{
		$this->allowSystem = config("allow_system");
		$this->system = config("system_list");
		$this->imageaddress = config("servers");
		$this->osIco = config("system");
	}
	public function getUpgradeHost()
	{
		$param = $this->request->param();
		if (!is_array($param["ids"])) {
			$ids = [$param["ids"]];
		} else {
			$ids = $param["ids"];
		}
		$order_amount = \think\Db::name("orders")->whereIn("id", $ids)->sum("amount");
		$data = ["order_amount" => $order_amount];
		return jsons(["status" => 200, "msg" => "请求成功", "data" => $data]);
	}
	/**
	 * @title 获取主机列表
	 * @description 接口说明:无
	 * @author 萧十一郎
	 * @url host/list
	 * @method GET
	 * @param .name:groupid type:string require:0 default:other other: desc:分组id
	 * @param .name:dcim_area type:string require:0 default:other other: desc:区域搜索(传名称)
	 * @param .name:domain_status type:string require:0 default:other other: desc:按状态搜索(数组方式传参)
	 * @param .name:orderby type:string require:0 default:id other: desc:排序方式('id','domainstatus','productname','regdate','nextduedate','amount'）
	 * @param .name:sort type:string require:0 default:id other:DESC desc:排序类型DESC/ASC
	 * @param .name:show_type type:string require:0 default:id other:DESC desc:首页index，列表页list
	 * @return list:主机列表数据@
	 * @list  id:主机id
	 * @list  domainstatus:机器状态(Pending待审核,Active已激活,Suspended暂停,Terminated已删除,Cancelled已取消,Fraud有欺诈,Completed已完成)
	 * @list  dedicatedip:主ip
	 * @list  assignedips:分配的IP
	 * @list  nextinvoicedate:下次续约日期
	 * @list  regdate:开通时间
	 * @list  nextduedate:到期时间
	 * @list  notes:备注
	 * @list  amount:续费金额
	 * @list  billingcycle:周期
	 * @list  productname:产品名称
	 * @list  cycle_desc:展示周期
	 * @list  price_desc:展示价格
	 * @list  os:操作系统
	 * @list  svg:操作系统图标
	 * @list  area_code:区域代码
	 * @list  area_name:区域名称
	 * @return page:当前页数
	 * @return limit:每页条数
	 * @return sum:总条数
	 * @return max_page:总页数
	 * @return orderby:排序字段
	 * @return sort:排序方向
	 * @return auth.traffic:流量图(on开启off关闭)
	 * @return auth.kvm:kvm(on开启off关闭)
	 * @return auth.ikvm:ikvm(on开启off关闭)
	 * @return auth.bmc:重置bmc(on开启off关闭)
	 * @return auth.reinstall:重装系统(on开启off关闭)
	 * @return auth.reboot:重启(on开启off关闭)
	 * @return auth.on:开机(on开启off关闭)
	 * @return auth.off:关机(on开启off关闭)
	 * @return auth.novnc:novnc(on开启off关闭)
	 * @return auth.rescue:救援系统(on开启off关闭)
	 * @return auth.crack_pass:重置密码(on开启off关闭)
	 * @return area:区域信息@
	 * @area  code:区域代码
	 * @area  name:区域名称
	 * @return domainstatus:产品状态
	 */
	public function getList(\think\Request $request)
	{
		$uid = $request->uid;
		$groupid = input("get.groupid", 0);
		$page = $request->page ?? input("get.page", 1, "intval");
		$limit = $request->limit ?? input("get.limit", 10, "intval");
		$orderby = $request->orderby ?? input("get.orderby", "id");
		$sort = $request->sort ?? input("get.sort", "DESC");
		$search = $request->search ?? "";
		$dcim_area = input("get.dcim_area", "");
		$domain_status = $request->domain_status ?? [];
		$page = $page > 0 ? $page : 1;
		$limit = $limit > 0 ? $limit : 10;
		if (!in_array($orderby, ["id", "domainstatus", "productname", "regdate", "nextduedate", "firstpaymentamount", "dedicatedip"])) {
			$orderby = "id";
		}
		if (!in_array($sort, ["asc", "desc"])) {
			$sort = "DESC";
		}
		if ($request->navRelid) {
			$where[] = ["p.id", "in", $request->navRelid];
		}
		if ($groupid != 0 && !$request->navRelid) {
			$where[] = ["p.groupid", "=", $groupid];
		}
		$where[] = ["h.uid", "=", $uid];
		if (!empty($search)) {
			$where[] = ["h.dedicatedip|h.assignedips|h.remark|p.name|h.domain", "LIKE", "%{$search}%"];
		}
		if (!empty($domain_status)) {
			$where[] = ["h.domainstatus", "in", $domain_status];
		}
		$developer_app_product_type = array_keys(config("developer_app_product_type"));
		$where[] = ["p.type", "not in", $developer_app_product_type];
		$type = $request->type ?? input("get.type", "list");
		if ($template_page != "service_ssl") {
			if ($type == "list") {
			} else {
				$where[] = ["h.domainstatus", "eq", "Active"];
			}
		}
		$where[] = ["h.agent_client", "=", 0];
		$where_search_area = "";
		if (!empty($dcim_area)) {
			$search_server = \think\Db::name("dcim_servers")->field("serverid,area")->where("area", "<>", "")->select()->toArray();
			foreach ($search_server as $v) {
				$a = json_decode($v["area"], true);
				foreach ($a as $vv) {
					if ($vv["name"] == $dcim_area) {
						$where_search_area .= "(h.serverid=" . $v["serverid"] . " AND h.dcim_area='{$vv["id"]}') OR ";
						break;
					}
				}
			}
			$where_search_area = substr($where_search_area, 0, -4);
		}
		if (!empty($where_search_area)) {
			$count = \think\Db::name("host")->alias("h")->leftJoin("products p", "p.id=h.productid")->where($where)->where($where_search_area)->count();
			$max_page = ceil($count / $page);
			$data = \think\Db::name("host")->field("h.orderid,p.api_type,p.zjmf_api_id")->field("h.id,h.domain,h.initiative_renew,h.domainstatus,h.regdate,h.dedicatedip,h.assignedips,h.nextduedate,h.remark  notes,h.nextinvoicedate,h.firstpaymentamount,h.amount,h.billingcycle,h.os,h.os_url,h.dcimid,h.dcim_os,h.dcim_area,b.os server_os,p.name as productname,b.area,b.auth,p.type as product_type,p.id as pid,p.pay_type")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("dcim_servers b", "h.serverid=b.serverid")->where($where)->where($where_search_area)->withAttr("assignedips", function ($value) {
				if (!empty($value)) {
					return explode(",", $value);
				} else {
					return [];
				}
			})->withAttr("pay_type", function ($value) {
				return json_decode($value, true);
			})->page($page)->limit($limit);
		} else {
			$count = \think\Db::name("host")->alias("h")->leftJoin("products p", "p.id=h.productid")->where($where)->count();
			$max_page = ceil($count / $page);
			$data = \think\Db::name("host")->field("h.orderid,p.api_type,p.zjmf_api_id")->field("h.id,h.domain,h.initiative_renew,h.domainstatus,h.regdate,h.dedicatedip,h.assignedips,h.nextduedate,h.remark  notes,h.nextinvoicedate,h.firstpaymentamount,h.amount,h.billingcycle,h.os,h.os_url,h.dcimid,h.dcim_os,h.dcim_area,b.os server_os,p.name as productname,b.area,b.auth,p.type as product_type,p.id as pid,p.pay_type")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("dcim_servers b", "h.serverid=b.serverid")->where($where)->withAttr("assignedips", function ($value) {
				if (!empty($value)) {
					return explode(",", $value);
				} else {
					return [];
				}
			})->withAttr("pay_type", function ($value) {
				return json_decode($value, true);
			})->group("h.id")->page($page)->limit($limit);
		}
		if ($orderby === "domainstatus") {
			$data = $data->orderField("domainstatus", ["Suspended", "Active", "Pending"], $sort)->select()->toArray();
		} else {
			$data = $data->order($orderby, $sort)->select()->toArray();
		}
		$currency = getUserCurrency($uid);
		$billing_cycle = config("billing_cycle");
		$cert_orderinfo = [];
		foreach ($data as $key => $val) {
			$host_cancel = \think\Db::name("cancel_requests")->field("type,reason")->where("relid", $val["id"])->find();
			if (!empty($host_cancel)) {
				if ($host_cancel["type"] == "Immediate") {
					$host_cancel["type"] = "立即停用";
				} else {
					$host_cancel["type"] = "到期时停用";
				}
			}
			$data[$key]["host_cancel"] = $host_cancel ?? "";
			$data[$key]["cycle_desc"] = $billing_cycle[$val["billingcycle"]];
			if ($val["product_type"] == "ssl") {
				$cert_data = [];
				if ($val["api_type"] == "zjmf_api") {
					$zjmf_data = zjmfCurl($val["zjmf_api_id"], "/provision/sslCertFunc", ["func" => "getAllInfo", "id" => $val["dcimid"]]);
					if ($zjmf_data["status"] == 200) {
						$cert_data = $zjmf_data["data"]["orderInfo"];
					}
				} else {
					$cert_data = \think\Db::name("certssl_orderinfo")->where("hostid", $val["id"])->find();
					if ($cert_data) {
						$cert_data["domainNames_arr"] = explode(PHP_EOL, $cert_data["domainNames"]);
						$cert_data["domainNames_arr"] = array_filter($cert_data["domainNames_arr"]);
					}
				}
				$data[$key]["cycle_desc"] = "-";
				if ($cert_data && isset($cert_data["due_time"])) {
					if ($cert_data["due_time"]) {
						if ($cert_data["due_time"] > time() && $cert_data["due_time"] < time() + 5184000) {
							$val["domainstatus"] = "Overdue_Active";
						}
						if ($cert_data["due_time"] <= time()) {
							$val["domainstatus"] = "Deleted";
						}
					}
					$data[$key]["cycle_desc"] = $cert_data["due_time"] ? date("Y-m-d", $cert_data["due_time"]) : "-";
				}
				$data[$key]["used_domainNames"] = $cert_data["used_domainNames"] ?? "-";
				$data[$key]["domainNames_arr"] = $cert_data["domainNames_arr"] ?? [];
				$data[$key]["domainstatus_desc"] = config("sslDomainStatus")[$val["domainstatus"]];
			} else {
				$data[$key]["domainstatus_desc"] = config("domainstatus")[$val["domainstatus"]];
			}
			if ($val["billingcycle"] == "onetime") {
				$data[$key]["price_desc"] = $currency["prefix"] . $val["firstpaymentamount"] . $currency["suffix"];
			} else {
				$data[$key]["price_desc"] = $currency["prefix"] . $val["amount"] . $currency["suffix"];
			}
			$data[$key]["auth"] = json_decode($val["auth"], true) ?: [];
			$data[$key]["notes"] = html_entity_decode($val["notes"]);
			unset($data[$key]["dcim_os"]);
			unset($data[$key]["server_os"]);
			if (!empty($val["dcim_area"])) {
				$area = json_decode($val["area"], true);
				foreach ($area as $k => $v) {
					if ($v["id"] == $val["dcim_area"]) {
						$data[$key]["area_code"] = $v["area"];
						$data[$key]["area_name"] = $v["name"] ?? "";
						break;
					}
				}
			} else {
				$data[$key]["area_code"] = "";
				$data[$key]["area_name"] = "";
			}
			unset($data[$key]["area"]);
			unset($data[$key]["dcim_area"]);
		}
		foreach ($data as $key => $val) {
			$all_options = \think\Db::name("host_config_options")->where("relid", $val["id"])->select()->toArray();
			foreach ($all_options as $k => $v) {
				$ssl_option = \think\Db::name("product_config_options")->where("id", $v["configid"])->value("option_name");
				$ssl_option_sub = \think\Db::name("product_config_options_sub")->where("id", $v["optionid"])->value("option_name");
				$ssl_option_key = explode("|", $ssl_option)[0];
				$ssl_option_sub = explode("|", $ssl_option_sub);
				$data[$key][$ssl_option_key] = $ssl_option_sub[1] ?? $ssl_option_sub[0];
			}
			$data[$key]["invoice_id"] = \think\Db::name("orders")->where("id", $val["orderid"])->value("invoiceid");
			$hco = \think\Db::name("host_config_options")->alias("h")->field("pco.option_name,pcos.option_name as option_names")->join("product_config_options pco", "pco.id=h.configid")->join("product_config_options_sub pcos", "pcos.id=h.optionid")->where("h.relid", $val["id"])->select()->toArray();
			if ($hco[0]["option_name"] == "添加配置项") {
				$hco = [];
			}
			if (!empty($hco)) {
				foreach ($hco as $k => $v) {
					$a = explode("^", explode("|", $v["option_name"]))[1];
					$a1 = explode("|", $v["option_names"]);
					$hco[$k]["option_name"] = str_replace(" ", "", $a[0]);
					$hco[$k]["option_names"] = $a1[1];
					if ($hco[$k]["option_names"] == "" || $hco[$k]["option_name"] == "") {
						$hco = [["option_name" => "os", "option_names" => ""], ["option_name" => "Memory", "option_names" => ""], ["option_name" => "DiskSpace", "option_names" => ""], ["option_name" => "CPU", "option_names" => ""]];
						break;
					}
				}
			} else {
				$hco = [["option_name" => "os", "option_names" => ""], ["option_name" => "Memory", "option_names" => ""], ["option_name" => "DiskSpace", "option_names" => ""], ["option_name" => "CPU", "option_names" => ""]];
			}
			$data[$key]["options"] = array_column($hco, "option_names", "option_name");
		}
		$grou = \think\Db::name("nav_group")->where("id", $groupid)->find();
		$result["group"] = $grou;
		$result["status"] = 200;
		$result["data"]["page"] = $page;
		$result["data"]["limit"] = $limit;
		$result["data"]["sum"] = $count;
		$result["data"]["max_page"] = $max_page;
		$result["data"]["orderby"] = $orderby;
		$result["data"]["sort"] = $sort;
		$result["data"]["list"] = $data;
		if ($type == "dcim") {
			$result["data"]["area"] = get_all_dcim_area();
		} else {
			$result["data"]["area"] = [];
		}
		$domainstatus = config("public.domainstatus");
		$result["data"]["domainstatus"] = $domainstatus;
		return jsons($result);
	}
	/**
	 * @time 2020-05-19
	 * @title 修改备注
	 * @description description
	 * @url /host/remark
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:hostID
	 * @param   .name:remark type:string require:1 desc:备注信息
	 */
	public function postRemark(\think\Request $request)
	{
		$uid = $request->uid;
		$id = input("post.id", 0, "intval");
		$remark = input("post.remark", "");
		$host = \think\Db::name("host")->where("uid", $uid)->where("id", $id)->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		$host = \think\Db::name("host")->field("remark")->where("id", $id)->find();
		\think\Db::name("host")->where("id", $id)->update(["remark" => $remark]);
		$result["status"] = 200;
		$result["msg"] = lang("UPDATE SUCCESS");
		active_log_final(sprintf($this->lang["Host_home_postRemark"], $id, $host["remark"], $remark), $uid, 2, $id, 2);
		return json($result);
	}
	/**
	 * @title 添加/修改分类
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/savecate
	 * @method POST
	 * @param .name:cate_id type:int require:0 default: other: desc:分类id，不传递时为添加
	 * @param .name:cate_name type:string require:1 default: other: desc:分类名
	 */
	public function postSaveCate(\think\Request $request)
	{
		$uid = $request->uid;
		$cate_id = $request->param("cate_id");
		$cate_name = $request->param("cate_name");
		if (empty($cate_name)) {
			return json(["status" => 406, "msg" => lang("CATEGORY_NAME_CANNOT_BE_EMPTY")]);
		}
		if (!empty($cate_id)) {
			$cate_data = \think\Db::name("host_category")->where("uid", $uid)->where("id", $cate_id)->find();
			if (empty($cate_data)) {
				return json(["status" => 406, "msg" => lang("THE_CATEGORY_WAS_NOT_FOUND")]);
			}
			\think\Db::name("host_category")->where("id", $cate_id)->update(["name" => $cate_name]);
		} else {
			\think\Db::name("host_category")->insert(["uid" => $uid, "name" => $cate_name]);
		}
		active_logs(sprintf($this->lang["Host_home_postSaveCate"], $uid, $cate_id, $cate_name), $uid);
		active_logs(sprintf($this->lang["Host_home_postSaveCate"], $uid, $cate_id, $cate_name), $uid, "", 2);
		return json(["status" => 200, "msg" => lang("SAVE_SUCCESSFULLY")]);
	}
	/**
	 * @title 删除分类
	 * @description 接口说明:删除分类会将分类下的产品转移到默认分类
	 * @author 萧十一郎
	 * @url host/cate
	 * @method DELETE
	 * @param .name:cate_id type:int require:0 default: other: desc:分类id，不传递时为添加
	 */
	public function deleteCate(\think\Request $request)
	{
		$uid = $request->uid;
		$cate_id = $request->param("cate_id");
		if (empty($cate_id)) {
			return json(["status" => 406, "msg" => lang("CATEGORY_NOT_FOUND")]);
		}
		$cate_data = \think\Db::name("host_category")->where("uid", $uid)->where("id", $cate_id)->find();
		if (empty($cate_data)) {
			return json(["status" => 406, "msg" => lang("CATEGORY_NOT_FOUND")]);
		}
		$host = \think\Db::name("host")->field("user_cate_id")->where("user_cate_id", $cate_id)->find();
		\think\Db::name("host")->where("user_cate_id", $cate_id)->update(["user_cate_id" => 0]);
		\think\Db::name("host_category")->where("id", $cate_id)->delete();
		active_logs(sprintf($this->lang["Host_home_postSaveCate"], $uid, $cate_id, $host["user_cate_id"], 0), $uid);
		active_logs(sprintf($this->lang["Host_home_postSaveCate"], $uid, $cate_id, $host["user_cate_id"], 0), $uid, "", 2);
		return json(["status" => 200, "msg" => lang("DELETE_CLASSIFICATION_SUCCEEDED")]);
	}
	/**
	 * @title 将主机转移到某个分类
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/transfercate
	 * @method POST
	 * @param .name:host_id type:int require:1 default: other: desc:主机id
	 * @param .name:cate_id type:int require:1 default: other: desc:分类id
	 */
	public function postTransferCate(\think\Request $request)
	{
		$uid = $request->uid;
		$param = $request->param();
		$host_id = $param["host_id"];
		$cate_id = $param["cate_id"];
		if (empty($host_id) || empty($cate_id)) {
			return json(["status" => 406, "msg" => lang("DATA_ERROR")]);
		}
		$host_data = \think\Db::name("host")->where("id", $host_id)->where("uid", $uid)->find();
		if (empty($host_data)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$cate_data = \think\Db::name("host_category")->where("uid", $uid)->where("id", $cate_id)->find();
		if (empty($cate_data)) {
			return json(["status" => 406, "msg" => lang("CATEGORY_NOT_FOUND")]);
		}
		$host = \think\Db::name("host")->field("user_cate_id")->where("id", $host_id)->find();
		\think\Db::name("host")->where("id", $host_id)->update(["user_cate_id" => $cate_id]);
		active_log_final(sprintf($this->lang["Host_home_postTransferCate"], $uid, $host_id, $cate_id, $host["user_cate_id"], $cate_id), $uid, 2, $host_id, 2);
		return json(["status" => 406, "msg" => lang("CATEGORY_MODIFIED_SUCCESSFULLY")]);
	}
	/**
	 * @title 产品内页数据
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/details
	 * @method GET
	 * @param .name:host_id type:int require:1 default: other: desc:产品id
	 * @return host_data:基础数据@
	 * @host_data  ordernum:订单id
	 * @host_data  productid:产品id
	 * @host_data  serverid:服务器id
	 * @host_data  regdate:产品开通时间
	 * @host_data  domain:主机名
	 * @host_data  payment:支付方式
	 * @host_data  firstpaymentamount:首付金额
	 * @host_data  firstpaymentamount_desc:首付金额
	 * @host_data  amount:续费金额
	 * @host_data  amount_desc:续费金额
	 * @host_data  billingcycle:付款周期
	 * @host_data  billingcycle_desc:付款周期
	 * @host_data  nextduedate:到期时间
	 * @host_data  nextinvoicedate:下次帐单时间
	 * @host_data  dedicatedip:独立ip
	 * @host_data  assignedips:附加ip
	 * @host_data  ip_num:IP数量
	 * @host_data  domainstatus:产品状态
	 * @host_data  domainstatus_desc:产品状态
	 * @host_data  username:服务器用户名
	 * @host_data  password:服务器密码
	 * @host_data  suspendreason:暂停原因
	 * @host_data  auto_terminate_end_cycle:是否到期取消
	 * @host_data  auto_terminate_reason:取消原因
	 * @host_data  productname:产品名
	 * @host_data  groupname:产品组名
	 * @host_data  bwusage:当前使用流量
	 * @host_data  bwlimit:当前使用流量上限(0表示不限)
	 * @host_data  os:操作系统
	 * @host_data  port:端口
	 * @host_data  remark:备注
	 * @return config_options:可配置选项@
	 * @config_options  name:配置名  
	 * @config_options  sub_name:配置项值
	 * @return custom_field_data:自定义字段@
	 * @custom_field_data  fieldname:字段名
	 * @custom_field_data  value:字段值
	 * @return download_data:可下载数据@
	 * @download_data  id:文件id
	 * @title  id:文件标题
	 * @down_link  id:下载链接
	 * @location  id:文件名
	 * @return module_button:模块按钮@
	 * @module_button  type:default:默认,custom:自定义
	 * @module_button  type:func:函数名
	 * @module_button  type:name:名称
	 * @return module_client_area:模块页面输出
	 * @return hook_output:钩子在本页面的输出，数组，循环显示的html
	 * @return dcim.flowpacket:当前产品可购买的流量包@
	 * @dcim.flowpacket  id:流量包ID
	 * @dcim.flowpacket  name:流量包名称
	 * @dcim.flowpacket  price:价格
	 * @dcim.flowpacket  sale_times:销售次数
	 * @dcim.flowpacket  stock:库存(0不限)
	 * @return dcim.auth:服务器各种操作权限控制(on有权限off没权限)
	 * @return dcim.area_code:区域代码
	 * @return dcim.area_name:区域名称
	 * @return dcim.os_group:操作系统分组@
	 * @dcim.os_group  id:分组ID
	 * @dcim.os_group  name:分组名称
	 * @dcim.os_group  svg:分组svg号
	 * @return dcim.os:操作系统数据@
	 * @dcim.os  id:操作系统ID
	 * @dcim.os  name:操作系统名称
	 * @dcim.os  ostype:操作系统类型(1windows0linux)
	 * @dcim.os  os_name:操作系统真实名称(用来判断具体的版本和操作系统)
	 * @dcim.os  group_id:所属分组ID
	 * @return  flow_packet_use_list:流量包使用情况@
	 * @flow_packet_use_list  name:流量包名称
	 * @flow_packet_use_list  capacity:流量包大小
	 * @flow_packet_use_list  price:价格
	 * @flow_packet_use_list  pay_time:支付时间
	 * @flow_packet_use_list  used:已用流量
	 * @flow_packet_use_list  used:已用流量
	 * @return  host_cancel: 取消请求数据,空对象
	 */
	public function getDetails(\think\Request $request)
	{
		$uid = $request->uid;
		$host_id = $request->host_id;
		if (empty($host_id)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$host_exists = \think\Db::name("host")->where("uid", $uid)->where("id", $host_id)->find();
		if (empty($host_exists)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$returndata = [];
		$host_data = \think\Db::name("host")->field("h.orderid,h.productid,h.serverid,h.regdate,h.domain,h.payment,
                h.firstpaymentamount,h.amount,h.billingcycle,h.nextduedate,h.nextinvoicedate,
                h.dedicatedip,h.assignedips,h.domainstatus,h.username,h.password,h.suspendreason,
                h.auto_terminate_end_cycle,h.auto_terminate_reason,h.bwusage,h.bwlimit,h.os,h.remark,h.dcim_area,h.dcim_os,h.port,
                p.type,p.name as productname,p.pay_method as payment_type,p.config_options_upgrade,p.config_option1,g.name as groupname,o.ordernum,p.api_type")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("product_groups g", "g.id=p.gid")->leftJoin("orders o", "o.id=h.orderid")->where("h.id", $host_id)->find();
		$domainstatus_config = config("domainstatus");
		$currency = getUserCurrency($uid);
		$billing_cycle = config("billing_cycle");
		$host_data["suspendreason_type"] = explode("-", $host_data["suspendreason"])[0] ? explode("-", $host_data["suspendreason"])[0] : "";
		$host_data["suspendreason"] = explode("-", $host_data["suspendreason"])[1] ? explode("-", $host_data["suspendreason"])[1] : "";
		$host_data["assignedips"] = !empty($host_data["assignedips"]) ? explode(",", $host_data["assignedips"]) : [];
		$host_data["domainstatus_desc"] = $domainstatus_config[$host_data["domainstatus"]];
		$host_data["password"] = cmf_decrypt($host_data["password"]);
		$host_data["firstpaymentamount_desc"] = $currency["prefix"] . $host_data["firstpaymentamount"] . $currency["suffix"];
		$host_data["amount_desc"] = $currency["prefix"] . $host_data["amount"] . $currency["suffix"];
		$host_data["billingcycle_desc"] = $billing_cycle[$host_data["billingcycle"]];
		$host_data["ip_num"] = count($host_data["assignedips"]);
		$host_data["bwusage"] = round($host_data["bwusage"], 2);
		$host_data["remark"] = html_entity_decode($host_data["remark"]);
		$returndata["host_data"] = $host_data;
		$productid = $host_data["productid"];
		$domainstatus = $host_data["domainstatus"];
		$returndata["server_data"] = "";
		$provision_logic = new \app\common\logic\Provision();
		$module_button = $provision_logic->clientButtonOutput($host_id);
		$module_client_area = $provision_logic->clientArea($hostid);
		$returndata["module_button"] = $module_button;
		$returndata["module_client_area"] = $module_client_area;
		$returndata["hook_output"] = hook("client_product_details_output", ["host_id" => $host_id], false);
		$returndata["currency"] = $currency;
		$upgrade_products_data = \think\Db::name("product_upgrade_products")->where("product_id", $productid)->select()->toArray();
		if (!empty($upgrade_products_data)) {
			$system_button["upgrade"] = ["name" => lang("UPGRADE_DOWNGRADE"), "func" => "upgrade"];
			if ($domainstatus == "Active") {
				$system_button["upgrade"]["disabled"] = false;
			} else {
				$system_button["upgrade"]["disabled"] = true;
			}
		}
		if ($host_data["config_options_upgrade"] == 1) {
			$system_button["upgrade_option"] = ["name" => lang("UPGRADE_DOWNGRADE_OPTIONS"), "func" => "upgrade_option"];
			if ($domainstatus == "Active") {
				$system_button["upgrade_option"]["disabled"] = false;
			} else {
				$system_button["upgrade_option"]["disabled"] = true;
			}
		}
		if ($domainstatus == "Active" && $host_data["payment_type"] == "prepayment" && !in_array($host_data["billingcycle"], ["ontrial", "hour", "day"])) {
			$system_button["product_transfer"] = ["name" => lang("PRODUCT_TRANSFER"), "func" => "product_transfer", "disabled" => false];
		}
		if (in_array($domainstatus, ["Active", "Suspended"])) {
			if ($host_data["payment_type"] == "prepayment" && !in_array($billingcycle, ["onetime", "free", "hour", "day"])) {
				$system_button["renew_cycle"] = ["name" => lang("RENEW"), "func" => "renew_cycle", "disabled" => false];
			} else {
				if ($host_data["payment_type"] == "postpaid" || in_array($billingcycle, ["hour", "day"])) {
					$system_button["pay_cycle"] = ["name" => lang("PAYMENT_CURRENT_PERIOD"), "func" => "pay_cycle", "disabled" => false];
				}
			}
		}
		if (in_array($domainstatus, ["Pending", "Active", "Suspended"])) {
			$system_button["request_cancel"] = ["name" => lang("UPGRADE_DOWNGRADE_OPTIONS"), "func" => "request_cancel", "disabled" => false];
		}
		$config_options = [];
		$config_logic = new \app\common\logic\ConfigOptions();
		$config_options = $config_logic->showInfo($productid, $host_id, $currency, $host_data["billingcycle"], false);
		$returndata["config_options"] = array_values($config_options);
		$custom_field_data = \think\Db::name("customfields")->field("id,fieldname")->where("type", "product")->where("relid", $productid)->where("adminonly", 0)->select()->toArray();
		foreach ($custom_field_data as &$cv) {
			$cv["value"] = \think\Db::name("customfieldsvalues")->where("fieldid", $cv["id"])->where("relid", $host_id)->value("value") ?? "";
		}
		$returndata["custom_field_data"] = $custom_field_data ?? [];
		$download_data = [];
		$download_data = \think\Db::name("downloads")->field("d.id,d.location,d.title,d.clientsonly,d.hidden,d.productdownload")->alias("d")->leftJoin("product_downloads p", "p.download_id=d.id")->where("p.product_id", $productid)->select()->toArray();
		foreach ($download_data as $key => $val) {
			if ($val["productdownload"] == 1 && !in_array($domainstatus, ["Active"])) {
				unset($download_data[$key]);
				continue;
			}
			$download_data[$key]["down_link"] = "download/product_file?id=" . $val["id"];
		}
		$returndata["download_data"] = $download_data;
		$returndata["dcim"]["flowpacket"] = [];
		$returndata["dcim"]["flow_packet_use_list"] = [];
		if ($host_data["bwlimit"] > 0) {
			$flowpacket = \think\Db::name("dcim_flow_packet")->field("id,name,capacity,price,sale_times,stock")->where("status", 1)->whereRaw("FIND_IN_SET('{$host_data["productid"]}', allow_products)")->select()->toArray();
			if (!empty($flowpacket)) {
				foreach ($flowpacket as $k => $v) {
					$flowpacket[$k]["leave"] = 1;
					if ($v["stock"] > 0 && $v["sale_times"] >= $v["stock"]) {
						$flowpacket[$k]["leave"] = 0;
					}
					unset($flowpacket[$k]["sale_times"]);
					unset($flowpacket[$k]["stock"]);
				}
				$returndata["dcim"]["flowpacket"] = $flowpacket;
			}
		}
		if ($host_data["bwlimit"] > 0) {
			if ($host_data["type"] == "dcim") {
				if ($host_data["api_type"] == "whmcs") {
					$returndata["host_data"]["show_traffic_usage"] = false;
				} else {
					$returndata["host_data"]["show_traffic_usage"] = $host_data["config_option1"] != "bms";
				}
			} elseif ($host_data["type"] == "dcimcloud") {
				$returndata["host_data"]["show_traffic_usage"] = true;
			} else {
				$returndata["host_data"]["show_traffic_usage"] = $provision_logic->checkDefineUsage($host_id);
			}
		} else {
			$returndata["host_data"]["show_traffic_usage"] = false;
		}
		if ($host_data["type"] == "dcim" && $host_data["config_option1"] != "bms") {
			$server = \think\Db::name("servers")->alias("a")->field("b.*")->leftJoin("dcim_servers b", "a.id=b.serverid")->where("a.id", $host_data["serverid"])->find();
			$returndata["dcim"]["auth"] = json_decode($server["auth"], true);
			if ($host_data["bwlimit"] > 0) {
				$returndata["dcim"]["flow_packet_use_list"] = get_dcim_traffic_usage_table($host_id, $uid, $server["bill_type"], $host_data["bwusage"], $host_data["bwlimit"]);
			}
			$os = json_decode($server["os"], true);
			$returndata["dcim"]["os_group"] = $os["group"];
			$returndata["dcim"]["os"] = $os["os"];
			if (!empty($host_data["dcim_area"])) {
				$area = json_decode($server["area"], true);
				foreach ($area as $v) {
					if ($v["id"] == $host_data["dcim_area"]) {
						$returndata["dcim"]["area_code"] = $v["area"];
						$returndata["dcim"]["area_name"] = $v["name"] ?? "";
						break;
					}
				}
			} else {
				$returndata["dcim"]["area_code"] = "";
				$returndata["dcim"]["area_name"] = "";
			}
			$os_info = get_dcim_os_info($host_data["dcim_os"], $os["os"], $os["group"]);
			$returndata["host_data"]["os_ostype"] = $os_info["ostype"] ?? "";
			$returndata["host_data"]["os_osname"] = $os_info["os_name"] ?? "";
			$returndata["host_data"]["disk_num"] = 1;
			$returndata["dcim"]["svg"] = $os_info["svg"];
		} else {
			if ($host_data["bwlimit"] > 0) {
				$returndata["dcim"]["flow_packet_use_list"] = get_dcim_traffic_usage_table($host_id, $uid, "", $host_data["bwusage"], $host_data["bwlimit"]);
			}
		}
		if ($host_data["type"] == "cloud" || $host_data["type"] == "dcimcloud") {
			$os_config_option_id = \think\Db::name("product_config_links")->alias("a")->leftJoin("product_config_options b", "a.gid=b.gid")->where("a.pid", $host_data["productid"])->where("b.option_type", 5)->value("b.id");
			$sub = \think\Db::name("product_config_options_sub")->field("id,option_name")->where("config_id", $os_config_option_id)->where("hidden", 0)->order("sort_order ASC")->order("id asc")->select()->toArray();
			$cloud_os = [];
			$cloud_os_group = [];
			foreach ($sub as $v) {
				$arr = explode("|", $v["option_name"]);
				if (strpos($arr[1], "^") !== false) {
					$arr2 = explode("^", $arr[1]);
					if (empty($arr2[0]) || empty($arr2[1])) {
						continue;
					}
					if (!in_array($arr2[0], $cloud_os_group)) {
						$cloud_os_group[] = $arr2[0];
					}
					$cloud_os[] = ["id" => $v["id"], "name" => $arr2[1], "group" => $arr2[0]];
				} else {
					$cloud_os[] = ["id" => $v["id"], "name" => $arr[1]];
				}
			}
			if (!empty($cloud_os_group)) {
				foreach ($cloud_os_group as $k => $v) {
					$cloud_os_group[$k] = ["id" => $v, "name" => $v];
				}
				foreach ($cloud_os as $k => $v) {
					if (empty($v["group"])) {
						unset($cloud_os[$k]);
					}
				}
				$cloud_os = array_values($cloud_os);
			}
			$returndata["cloud_os"] = $cloud_os;
			$returndata["cloud_os_group"] = $cloud_os_group;
			$os_info = \think\Db::name("host_config_options")->alias("a")->field("b.option_name")->leftJoin("product_config_options_sub b", "a.optionid=b.id")->where("a.relid", $host_id)->where("a.configid", $os_config_option_id)->find();
			if (empty($host_data["username"])) {
				if (stripos($os_info["option_name"], "win") !== false) {
					$returndata["host_data"]["username"] = "administrator";
				} else {
					$returndata["host_data"]["username"] = "root";
				}
			}
		}
		$host_cancel = \think\Db::name("cancel_requests")->field("type,reason")->where("relid", $host_id)->find();
		$returndata["host_cancel"] = $host_cancel ?? [];
		return jsons(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 产品内页数据
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/product
	 * @method GET
	 * @param .name:host_id type:int require:1 default: other: desc:产品id
	 * @return host_data:基础数据@
	 * @host_data  ordernum:订单id
	 * @host_data  productid:产品id
	 * @host_data  serverid:服务器id
	 * @host_data  regdate:产品开通时间
	 * @host_data  domain:主机名
	 * @host_data  payment:支付方式
	 * @host_data  firstpaymentamount:首付金额
	 * @host_data  firstpaymentamount_desc:首付金额
	 * @host_data  amount:续费金额
	 * @host_data  amount_desc:续费金额
	 * @host_data  billingcycle:付款周期
	 * @host_data  billingcycle_desc:付款周期
	 * @host_data  nextduedate:到期时间
	 * @host_data  nextinvoicedate:下次帐单时间
	 * @host_data  dedicatedip:独立ip
	 * @host_data  assignedips:附加ip
	 * @host_data  ip_num:IP数量
	 * @host_data  domainstatus:产品状态
	 * @host_data  domainstatus_desc:产品状态
	 * @host_data  username:服务器用户名
	 * @host_data  password:服务器密码
	 * @host_data  suspendreason:暂停原因
	 * @host_data  auto_terminate_end_cycle:是否到期取消
	 * @host_data  auto_terminate_reason:取消原因
	 * @host_data  productname:产品名
	 * @host_data  groupname:产品组名
	 * @host_data  bwusage:当前使用流量
	 * @host_data  bwlimit:当前使用流量上限(0表示不限)
	 * @host_data  os:操作系统
	 * @host_data  port:端口
	 * @host_data  remark:备注
	 * @host_data  allow_upgrade_config:是否输出“升级配置项”按钮：1是
	 * @host_data  allow_upgrade_product:是否输出“升级产品”按钮：1是
	 * @host_data  show_traffic_usage:是否显示用量图
	 * @return config_options:可配置选项@
	 * @config_options  name:配置名
	 * @config_options  sub_name:配置项值
	 * @return module_button:模块按钮@
	 * @module_button  type:default:默认,custom:自定义
	 * @module_button  type:func:函数名
	 * @module_button  type:name:名称
	 */
	public function getProduct(\think\Request $request)
	{
		$uid = $request->uid;
		$host_id = $request->host_id;
		if (empty($host_id)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$host_exists = \think\Db::name("host")->where("uid", $uid)->where("id", $host_id)->find();
		if (empty($host_exists)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$host_data = \think\Db::name("host")->field("h.orderid,h.id as host_id,h.uid,h.initiative_renew,h.productid,h.serverid,h.regdate,h.domain,h.payment,p.groupid,h.promoid,
                h.firstpaymentamount,h.amount,h.billingcycle,h.nextduedate,h.nextinvoicedate,
                h.dedicatedip,h.assignedips,h.domainstatus,h.username,h.password,h.suspendreason,p.id as pid,
                h.bwusage,h.bwlimit,h.os,h.remark,h.dcimid,h.dcim_area,h.dcim_os,h.port,p.type,p.name as productname,p.pay_method as payment_type,p.config_options_upgrade,p.api_type,p.zjmf_api_id,p.upper_reaches_id,p.upstream_price_type,p.upstream_price_value,g.name as groupname,o.ordernum")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("product_groups g", "g.id=p.gid")->leftJoin("orders o", "o.id=h.orderid")->where("h.id", $host_id)->find();
		$grou = \think\Db::name("nav_group")->where("id", $host_data["groupid"])->find();
		$host_data["group"] = $grou;
		$domainstatus_config = config("domainstatus");
		$currency = getUserCurrency($uid);
		$billing_cycle = config("billing_cycle");
		$host_data["suspendreason_type"] = explode("-", $host_data["suspendreason"])[0] ? explode("-", $host_data["suspendreason"])[0] : "";
		$host_data["suspendreason"] = explode("-", $host_data["suspendreason"])[1] ? explode("-", $host_data["suspendreason"])[1] : "";
		$host_data["assignedips"] = !empty($host_data["assignedips"]) ? explode(",", $host_data["assignedips"]) : [];
		$host_data["domainstatus_desc"] = $domainstatus_config[$host_data["domainstatus"]];
		$host_data["password"] = cmf_decrypt($host_data["password"]);
		$host_data["firstpaymentamount_desc"] = $currency["prefix"] . $host_data["firstpaymentamount"] . $currency["suffix"];
		$host_data["amount_desc"] = $currency["prefix"] . $host_data["amount"] . $currency["suffix"];
		$host_data["billingcycle_desc"] = $billing_cycle[$host_data["billingcycle"]];
		$host_data["ip_num"] = count($host_data["assignedips"]);
		$host_data["bwusage"] = round($host_data["bwusage"], 2);
		$host_data["remark"] = html_entity_decode($host_data["remark"]);
		$returndata["host_data"] = $host_data;
		$productid = $host_data["productid"];
		$config_options = [];
		$config_logic = new \app\common\logic\ConfigOptions();
		$config_options = $config_logic->showInfo($productid, $host_id, $currency, $host_data["billingcycle"], false);
		$returndata["config_options"] = array_values($config_options);
		$custom_field_data = \think\Db::name("customfields")->field("id,fieldname")->where("type", "product")->where("relid", $productid)->where("adminonly", 0)->select()->toArray();
		foreach ($custom_field_data as &$cv) {
			$cv["value"] = \think\Db::name("customfieldsvalues")->where("fieldid", $cv["id"])->where("relid", $host_id)->value("value") ?? "";
		}
		$returndata["custom_field_data"] = $custom_field_data ?? [];
		$upstream_data = [];
		if ($host_data["api_type"] == "zjmf_api") {
			$returndata["host_data"]["serverid"] = $returndata["host_data"]["zjmf_api_id"];
			$upstream_data = zjmfCurl($host_data["zjmf_api_id"], "/host/header", ["host_id" => $host_data["dcimid"]], 30, "GET");
			if ($upstream_data["status"] == 200) {
				$upstream_data = $upstream_data["data"];
			} else {
				$upstream_data = [];
			}
			$returndata["dcim"]["flowpacket"] = $upstream_data["dcim"]["flowpacket"] ?: [];
			$returndata["host_data"]["bwlimit"] = \intval($upstream_data["host_data"]["bwlimit"]);
			$returndata["host_data"]["bwusage"] = \floatval($upstream_data["host_data"]["bwusage"]);
		} elseif ($host_data["api_type"] == "manual") {
			$upper_reaches = \think\Db::name("zjmf_finance_api")->where("id", $host_data["upper_reaches_id"])->find();
			$returndata["manual"] = ["id" => $host_data["upper_reaches_id"], "name" => $upper_reaches["name"]];
			$upper_reaches_res = \think\Db::name("upper_reaches_res")->where("hid", $host_id)->find();
			$returndata["host_data"]["upper_reaches_res"] = $upper_reaches_res["id"] ?? "";
			$returndata["host_data"]["upper_reaches_control_mode"] = $upper_reaches_res["control_mode"] ?? "";
		}
		$os_config_option_id = \think\Db::name("product_config_links")->alias("a")->leftJoin("product_config_options b", "a.gid=b.gid")->where("a.pid", $host_data["productid"])->where("b.option_type", 5)->value("b.id");
		$returndata["host_data"]["os_config_option_id"] = $os_config_option_id;
		$os_info = \think\Db::name("host_config_options")->alias("a")->field("b.option_name")->leftJoin("product_config_options_sub b", "a.optionid=b.id")->where("a.relid", $host_id)->where("a.configid", $os_config_option_id)->find();
		if (empty($host_data["username"])) {
			if (stripos($os_info["option_name"], "win") !== false) {
				$returndata["host_data"]["username"] = "administrator";
			} else {
				$returndata["host_data"]["username"] = "root";
			}
		}
		$userinfo = db("clients")->field("second_verify")->where("id", $uid)->find();
		$returndata["second"]["second_verify"] = $userinfo["second_verify"];
		$returndata["second"]["allow_second_verify"] = intval(configuration("second_verify_home"));
		$returndata["second"]["second_verify_action_home"] = explode(",", configuration("second_verify_action_home"));
		return jsons(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 产品内页下载数据
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/down
	 * @method GET
	 * @param .name:productid type:int require:1 default: other: desc:产品id
	 * @param .name:domainstatus type:int require:1 default: other: desc:主机状态
	 * @return download_data:基础数据@
	 */
	public function getDown(\think\Request $request)
	{
		$productid = $request->productid;
		$domainstatus = $request->domainstatus;
		if (empty($productid)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$download_data = [];
		$download_data = \think\Db::name("downloads")->field("d.*")->alias("d")->leftJoin("product_downloads p", "p.download_id=d.id")->where("p.product_id", $productid)->select()->toArray();
		foreach ($download_data as $key => $val) {
			if ($val["productdownload"] == 1 && !in_array($domainstatus, ["Active"])) {
				unset($download_data[$key]);
				continue;
			}
			$download_data[$key]["down_link"] = "download/product_file?id=" . $val["id"];
		}
		$returndata["download_data"] = $download_data;
		return jsons(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 产品内页取消原因
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/cancel
	 * @method GET
	 * @param .name:host_id type:int require:1 default: other: desc:主机id
	 * @return download_data:基础数据@
	 */
	public function getCancel(\think\Request $request)
	{
		$host_id = $request->host_id;
		if (empty($host_id)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$cancelist = \think\Db::name("cancel_reason")->field("reason")->select()->toArray();
		$returndata["cancelist"] = $cancelist;
		$host_cancel = \think\Db::name("cancel_requests")->field("type,reason")->where("relid", $host_id)->where("delete_time", 0)->find();
		$returndata["host_cancel"] = $host_cancel ?? [];
		return jsons(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 产品内页系统
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/cloudos
	 * @method GET
	 * @param .name:productid type:int require:1 default: other: desc:产品id
	 * @param .name:os_config_option_id type:int require:1 default: other: desc:操作系统id
	 * @return  cloud_os:云操作系统@
	 * @cloud_os  id:操作系统ID
	 * @cloud_os  name:名称
	 * @cloud_os  group:分组id
	 * @return  cloud_os_group:云操作系统分组@
	 * @cloud_os_group  id:分组id
	 * @cloud_os_group  name:分组名称
	 */
	public function getCloudOs(\think\Request $request)
	{
		$productid = $request->productid;
		$os_config_option_id = $request->os_config_option_id;
		if (empty($productid)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$sub = \think\Db::name("product_config_options_sub")->field("id,option_name")->where("config_id", $os_config_option_id)->where("hidden", 0)->order("sort_order ASC")->order("id asc")->select()->toArray();
		$cloud_os = [];
		$cloud_os_group = [];
		foreach ($sub as $v) {
			$arr = explode("|", $v["option_name"]);
			if (strpos($arr[1], "^") !== false) {
				$arr2 = explode("^", $arr[1]);
				if (empty($arr2[0]) || empty($arr2[1])) {
					continue;
				}
				if (!in_array($arr2[0], $cloud_os_group)) {
					$cloud_os_group[] = $arr2[0];
				}
				$cloud_os[] = ["id" => $v["id"], "name" => $arr2[1], "group" => $arr2[0]];
			} else {
				$cloud_os[] = ["id" => $v["id"], "name" => $arr[1]];
			}
		}
		if (!empty($cloud_os_group)) {
			foreach ($cloud_os_group as $k => $v) {
				$cloud_os_group[$k] = ["id" => $v, "name" => $v];
			}
			foreach ($cloud_os as $k => $v) {
				if (empty($v["group"])) {
					unset($cloud_os[$k]);
				}
			}
			$cloud_os = array_values($cloud_os);
		}
		$returndata["cloud_os"] = $cloud_os;
		$returndata["cloud_os_group"] = $cloud_os_group;
		return jsons(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 产品内页图表
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/chart
	 * @method GET
	 * @param .name:host_id type:int require:1 default: other: desc:主机id
	 * @param .name:api_type type:int require:1 default: other: desc:api类型
	 * @param .name:domainstatus type:int require:1 default: other: desc:主机状态
	 * @param .name:type type:int require:1 default: other: desc:类型
	 * @param .name:zjmf_api_id type:int require:1 default: other: desc:zjmf_api_id
	 * @param .name:dcimid type:int require:1 default: other: desc:dcimid
	 * @return :基础数据@
	 */
	public function getChart(\think\Request $request)
	{
		$api_type = $request->api_type;
		$domainstatus = $request->domainstatus;
		$type = $request->type;
		$zjmf_api_id = $request->zjmf_api_id;
		$dcimid = $request->dcimid;
		$host_id = $request->host_id;
		if (empty($host_id)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$returndata["module_chart"] = [];
		$upstream_data = [];
		if ($api_type == "zjmf_api") {
			$returndata["host_data"]["serverid"] = $returndata["host_data"]["zjmf_api_id"];
			$upstream_data = zjmfCurl($zjmf_api_id, "/host/header", ["host_id" => $dcimid], 30, "GET");
			if ($upstream_data["status"] == 200) {
				$upstream_data = $upstream_data["data"];
			} else {
				$upstream_data = [];
			}
			$returndata["module_chart"] = $upstream_data["module_chart"] ?: [];
		} else {
			$provision_logic = new \app\common\logic\Provision();
			if ($domainstatus == "Active") {
				if ($type == "dcimcloud") {
					$dcimcloud = new \app\common\logic\DcimCloud();
					$returndata["module_chart"] = $dcimcloud->chart($dcimid, $host_id);
				} elseif ($type == "dcim") {
				} else {
					$returndata["module_chart"] = $provision_logic->chart($host_id);
				}
			}
		}
		return jsons(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 产品内页moudle
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/moudle
	 * @method GET
	 * @param .name:productid type:int require:1 default: other: desc:产品id
	 * @param .name:host_id type:int require:1 default: other: desc:主机id
	 * @param .name:api_type type:int require:1 default: other: desc:api类型
	 * @param .name:domainstatus type:int require:1 default: other: desc:主机状态
	 * @param .name:type type:int require:1 default: other: desc:类型
	 * @param .name:zjmf_api_id type:int require:1 default: other: desc:zjmf_api_id
	 * @param .name:dcimid type:int require:1 default: other: desc:dcimid
	 * @param .name:bwlimit type:int require:1 default: other: desc:bwlimit
	 * @return :基础数据@
	 */
	public function getMoudle(\think\Request $request)
	{
		$api_type = $request->api_type;
		$domainstatus = $request->domainstatus;
		$type = $request->type;
		$zjmf_api_id = $request->zjmf_api_id;
		$dcimid = $request->dcimid;
		$host_id = $request->host_id;
		$bwlimit = $request->bwlimit;
		if (empty($host_id)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$productid = $request->productid;
		$domainstatus = $request->domainstatus;
		if (empty($productid)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$download_data = [];
		$download_data = \think\Db::name("downloads")->field("*")->alias("d")->leftJoin("product_downloads p", "p.download_id=d.id")->where("p.product_id", $productid)->limit(1)->select()->toArray();
		if (empty($download_data[0])) {
			$returndata["download_data"] = false;
		} else {
			$returndata["download_data"] = true;
		}
		$config_option1 = \think\Db::name("products")->where("id", $productid)->value("config_option1");
		$returndata["module_button"] = ["control" => [], "console" => []];
		$returndata["module_client_area"] = [];
		$returndata["module_chart"] = [];
		$returndata["module_client_main_area"] = [];
		$returndata["control_view"]["module_power_status"] = false;
		$returndata["control_view"]["reinstall_random_port"] = false;
		$upstream_data = [];
		if ($api_type == "zjmf_api") {
			$returndata["host_data"]["serverid"] = $returndata["host_data"]["zjmf_api_id"];
			$upstream_data = zjmfCurl($zjmf_api_id, "/host/header", ["host_id" => $dcimid], 30, "GET");
			if ($upstream_data["status"] == 200) {
				$upstream_data = $upstream_data["data"];
			} else {
				$upstream_data = [];
			}
			$returndata["module_button"]["control"] = $upstream_data["module_button"]["control"] ?: [];
			$returndata["module_button"]["console"] = $upstream_data["module_button"]["console"] ?: [];
			$returndata["module_client_area"] = $upstream_data["module_client_area"] ?: [];
			$returndata["module_chart"] = $upstream_data["module_chart"] ?: [];
			$returndata["module_client_main_area"] = $upstream_data["module_client_main_area"] ?: [];
			$returndata["dcimcloud"]["nat_acl"] = $upstream_data["dcimcloud"]["nat_acl"] ?: "";
			$returndata["dcimcloud"]["nat_web"] = $upstream_data["dcimcloud"]["nat_web"] ?: "";
			$returndata["control_view"]["module_power_status"] = \boolval($upstream_data["module_power_status"]);
			$returndata["control_view"]["reinstall_random_port"] = \boolval($upstream_data["reinstall_random_port"]);
		} elseif ($api_type == "manual") {
			$UpperReaches = new \app\common\logic\UpperReaches();
			$returndata["control_view"]["module_power_status"] = $UpperReaches->modulePowerStatus($host_id);
			$returndata["module_button"] = $UpperReaches->moduleClientButton($host_id);
		} else {
			$provision_logic = new \app\common\logic\Provision();
			if ($domainstatus == "Active") {
				if ($type == "dcimcloud") {
					$dcimcloud = new \app\common\logic\DcimCloud();
					$returndata["module_button"] = $dcimcloud->moduleClientButton($dcimid);
					$returndata["module_client_area"] = $dcimcloud->moduleClientArea($host_id);
					$returndata["module_chart"] = $dcimcloud->chart($dcimid, $host_id);
					$returndata["control_view"]["module_power_status"] = true;
					$nat_info = $dcimcloud->getNatInfo($host_id);
					$returndata["dcimcloud"]["nat_acl"] = $nat_info["nat_acl"] ?: "";
					$returndata["dcimcloud"]["nat_web"] = $nat_info["nat_web"] ?: "";
					$returndata["control_view"]["reinstall_random_port"] = $dcimcloud->supportReinstallRandomPort($host_id);
				} elseif ($type == "dcim") {
					if ($config_option1 == "bms") {
						$dcim = new \app\common\logic\Dcim();
						$returndata["module_button"] = $dcim->moduleClientButton($dcimid);
						$returndata["module_client_area"] = $dcim->moduleClientArea($host_id);
						$returndata["control_view"]["module_power_status"] = true;
					} else {
						$returndata["control_view"]["module_power_status"] = true;
					}
				} else {
					$module_button = $provision_logic->clientButtonOutput($host_id);
					$module_client_area = $provision_logic->clientArea($host_id);
					$returndata["module_button"] = $module_button;
					$returndata["module_client_area"] = $module_client_area;
					$returndata["module_chart"] = $provision_logic->chart($host_id);
					$returndata["control_view"]["module_power_status"] = $provision_logic->checkDefineFunc($host_id, "Status");
					$returndata["module_client_main_area"] = $provision_logic->clientAreaMainOutput($host_id);
				}
			}
		}
		if ($api_type == "zjmf_api") {
			$returndata["control_view"]["show_traffic_usage"] = $upstream_data["host_data"]["show_traffic_usage"] ? true : false;
		} elseif ($api_type == "manual") {
			$returndata["control_view"]["show_traffic_usage"] = false;
		} else {
			if ($bwlimit > 0) {
				if ($type == "dcim") {
					$returndata["control_view"]["show_traffic_usage"] = $config_option1 != "bms";
				} elseif ($type == "dcimcloud") {
					$returndata["control_view"]["show_traffic_usage"] = true;
				} else {
					$returndata["control_view"]["show_traffic_usage"] = $provision_logic->checkDefineUsage($host_id);
				}
			} else {
				$returndata["control_view"]["show_traffic_usage"] = false;
			}
		}
		$upgrade_logic = new \app\common\logic\Upgrade();
		if ($upgrade_logic->judgeUpgradeConfig($host_id)) {
			$returndata["control_view"]["allow_upgrade_config"] = 1;
		} else {
			$returndata["control_view"]["allow_upgrade_config"] = 0;
		}
		if ($upgrade_logic->judgeUpgradeConfig($host_id, "product")) {
			$returndata["control_view"]["allow_upgrade_product"] = 1;
		} else {
			$returndata["control_view"]["allow_upgrade_product"] = 0;
		}
		return jsons(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 产品内页流量包数据
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/flowpacket
	 * @method GET
	 * @param .name:uid type:int require:1 default: other: desc:用户id
	 * @param .name:host_id type:int require:1 default: other: desc:主机id
	 * @param .name:productid type:int require:1 default: other: desc:产品id
	 * @param .name:serverid type:int require:1 default: other: desc:服务器id
	 * @param .name:api_type type:int require:1 default: other: desc:apitype
	 * @param .name:upstream_price_type type:int require:1 default: other: desc:upstream_price_type
	 * @param .name:zjmf_api_id type:int require:1 default: other: desc:zjmf_api_id
	 * @param .name:dcimid type:int require:1 default: other: desc:dcimid
	 * @param .name:upstream_price_value type:int require:1 default: other: desc:upstream_price_value
	 * @param .name:type type:int require:1 default: other: desc:类型
	 * @param .name:bwlimit type:int require:1 default: other: desc:bwlimit
	 * @param .name:bwusage type:int require:1 default: other: desc:bwusage
	 */
	public function getFlowpacket(\think\Request $request)
	{
		$uid = $request->uid;
		$host_id = $request->host_id;
		$api_type = $request->api_type;
		$upstream_price_type = $request->upstream_price_type;
		$zjmf_api_id = $request->zjmf_api_id;
		$dcimid = $request->dcimid;
		$upstream_price_value = $request->upstream_price_value;
		$type = $request->type;
		$bwlimit = $request->bwlimit;
		$bwusage = $request->bwusage;
		$productid = $request->productid;
		$serverid = $request->serverid;
		if (empty($host_id)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$config_option1 = \think\Db::name("products")->where("id", $productid)->value("config_option1");
		$returndata["dcim"]["flowpacket"] = [];
		$returndata["dcim"]["flow_packet_use_list"] = [];
		if ($api_type == "zjmf_api") {
			$upstream_data = zjmfCurl($zjmf_api_id, "/host/header", ["host_id" => $dcimid], 30, "GET");
			if ($upstream_data["status"] == 200) {
				$upstream_data = $upstream_data["data"];
			} else {
				$upstream_data = [];
			}
			$returndata["dcim"]["flowpacket"] = $upstream_data["dcim"]["flowpacket"] ?: [];
			if ($upstream_price_type == "percent") {
				foreach ($returndata["dcim"]["flowpacket"] as $k => $v) {
					$returndata["dcim"]["flowpacket"][$k]["price"] = round($v["price"] * $upstream_price_value / 100, 2);
				}
			}
			$returndata["dcim"]["flow_packet_use_list"] = $upstream_data["dcim"]["flow_packet_use_list"] ?: [];
			if ($type == "dcim" && $config_option1 != "bms") {
				$returndata["dcim"]["auth"] = $upstream_data["dcim"]["auth"] ?? ["bmc" => "off", "crack_pass" => "off", "ikvm" => "off", "kvm" => "off", "novnc" => "off", "off" => "off", "on" => "off", "reboot" => "off", "reinstall" => "off", "rescue" => "off", "traffic" => "off"];
				$returndata["dcim"]["svg"] = $upstream_data["dcim"]["svg"] ?? "";
				$returndata["host_data"]["os_ostype"] = $upstream_data["host_data"]["os_ostype"] ?? "";
				$returndata["host_data"]["os_osname"] = $upstream_data["host_data"]["os_osname"] ?? "";
				$returndata["host_data"]["disk_num"] = $upstream_data["host_data"]["disk_num"] ?? 1;
			}
		} else {
			if ($bwlimit > 0) {
				$flowpacket = \think\Db::name("dcim_flow_packet")->field("id,name,capacity,price,sale_times,stock")->where("status", 1)->whereRaw("FIND_IN_SET('{$productid}', allow_products)")->select()->toArray();
				if (!empty($flowpacket)) {
					foreach ($flowpacket as $k => $v) {
						$flowpacket[$k]["leave"] = 1;
						if ($v["stock"] > 0 && $v["sale_times"] >= $v["stock"]) {
							$flowpacket[$k]["leave"] = 0;
						}
						unset($flowpacket[$k]["sale_times"]);
						unset($flowpacket[$k]["stock"]);
					}
					$returndata["dcim"]["flowpacket"] = $flowpacket;
				}
			}
			if ($type == "dcim" && $config_option1 != "bms") {
				$server = \think\Db::name("servers")->alias("a")->field("b.*")->leftJoin("dcim_servers b", "a.id=b.serverid")->where("a.id", $serverid)->find();
				$returndata["dcim"]["auth"] = json_decode($server["auth"], true);
				if ($bwlimit > 0) {
					$returndata["dcim"]["flow_packet_use_list"] = get_dcim_traffic_usage_table($host_id, $uid, $server["bill_type"], $bwusage, $bwlimit);
				}
				$os = json_decode($server["os"], true);
				$returndata["dcim"]["os_group"] = $os["group"];
				$returndata["dcim"]["os"] = $os["os"];
				if (!empty($host_data["dcim_area"])) {
					$area = json_decode($server["area"], true);
					foreach ($area as $v) {
						if ($v["id"] == $host_data["dcim_area"]) {
							$returndata["dcim"]["area_code"] = $v["area"];
							$returndata["dcim"]["area_name"] = $v["name"] ?? "";
							break;
						}
					}
				} else {
					$returndata["dcim"]["area_code"] = "";
					$returndata["dcim"]["area_name"] = "";
				}
				$os_info = get_dcim_os_info($host_data["dcim_os"], $os["os"], $os["group"]);
				$returndata["host_data"]["os_ostype"] = $os_info["ostype"] ?? "";
				$returndata["host_data"]["os_osname"] = $os_info["os_name"] ?? "";
				$returndata["host_data"]["disk_num"] = 1;
				$returndata["dcim"]["svg"] = $os_info["svg"];
			} else {
				if ($bwlimit > 0) {
					$returndata["dcim"]["flow_packet_use_list"] = get_dcim_traffic_usage_table($host_id, $uid, "", $bwusage, $bwlimit);
				}
			}
		}
		return jsons(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 产品内页数据
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/header
	 * @method GET
	 * @param .name:host_id type:int require:1 default: other: desc:产品id
	 * @return host_data:基础数据@
	 * @host_data  ordernum:订单id
	 * @host_data  productid:产品id
	 * @host_data  serverid:服务器id
	 * @host_data  regdate:产品开通时间
	 * @host_data  domain:主机名
	 * @host_data  payment:支付方式
	 * @host_data  firstpaymentamount:首付金额
	 * @host_data  firstpaymentamount_desc:首付金额
	 * @host_data  amount:续费金额
	 * @host_data  amount_desc:续费金额
	 * @host_data  billingcycle:付款周期
	 * @host_data  billingcycle_desc:付款周期
	 * @host_data  nextduedate:到期时间
	 * @host_data  nextinvoicedate:下次帐单时间
	 * @host_data  dedicatedip:独立ip
	 * @host_data  assignedips:附加ip
	 * @host_data  ip_num:IP数量
	 * @host_data  domainstatus:产品状态
	 * @host_data  domainstatus_desc:产品状态
	 * @host_data  username:服务器用户名
	 * @host_data  password:服务器密码
	 * @host_data  suspendreason:暂停原因
	 * @host_data  auto_terminate_end_cycle:是否到期取消
	 * @host_data  auto_terminate_reason:取消原因
	 * @host_data  productname:产品名
	 * @host_data  groupname:产品组名
	 * @host_data  bwusage:当前使用流量
	 * @host_data  bwlimit:当前使用流量上限(0表示不限)
	 * @host_data  os:操作系统
	 * @host_data  port:端口
	 * @host_data  remark:备注
	 * @host_data  allow_upgrade_config:是否输出“升级配置项”按钮：1是
	 * @host_data  allow_upgrade_product:是否输出“升级产品”按钮：1是
	 * @host_data  show_traffic_usage:是否显示用量图
	 * @return config_options:可配置选项@
	 * @config_options  name:配置名  
	 * @config_options  sub_name:配置项值
	 * @return custom_field_data:自定义字段@
	 * @custom_field_data  fieldname:字段名
	 * @custom_field_data  value:字段值
	 * @return download_data:可下载数据@
	 * @download_data  id:文件id
	 * @title  id:文件标题
	 * @down_link  id:下载链接
	 * @location  id:文件名
	 * @return module_button:模块按钮@
	 * @module_button  type:default:默认,custom:自定义
	 * @module_button  type:func:函数名
	 * @module_button  type:name:名称
	 * @return module_client_area:模块页面输出@
	 * @module_client_area  key:键值用于获取内容
	 * @module_client_area  name:名称
	 * @return hook_output:钩子在本页面的输出，数组，循环显示的html
	 * @return dcim.flowpacket:当前产品可购买的流量包@
	 * @dcim.flowpacket  id:流量包ID
	 * @dcim.flowpacket  name:流量包名称
	 * @dcim.flowpacket  price:价格
	 * @dcim.flowpacket  sale_times:销售次数
	 * @dcim.flowpacket  stock:库存(0不限)
	 * @return dcim.auth:服务器各种操作权限控制(on有权限off没权限)
	 * @return dcim.area_code:区域代码
	 * @return dcim.area_name:区域名称
	 * @return dcim.os_group:操作系统分组@
	 * @dcim.os_group  id:分组ID
	 * @dcim.os_group  name:分组名称
	 * @dcim.os_group  svg:分组svg号
	 * @return dcim.os:操作系统数据@
	 * @dcim.os  id:操作系统ID
	 * @dcim.os  name:操作系统名称
	 * @dcim.os  ostype:操作系统类型(1windows0linux)
	 * @dcim.os  os_name:操作系统真实名称(用来判断具体的版本和操作系统)
	 * @dcim.os  group_id:所属分组ID
	 * @return  flow_packet_use_list:流量包使用情况@
	 * @flow_packet_use_list  name:流量包名称
	 * @flow_packet_use_list  capacity:流量包大小
	 * @flow_packet_use_list  price:价格
	 * @flow_packet_use_list  pay_time:支付时间
	 * @flow_packet_use_list  used:已用流量
	 * @flow_packet_use_list  leave:剩余流量
	 * @return  cloud_os:云操作系统@
	 * @cloud_os  id:操作系统ID
	 * @cloud_os  name:名称
	 * @cloud_os  group:分组id
	 * @return  cloud_os_group:云操作系统分组@
	 * @cloud_os_group  id:分组id
	 * @cloud_os_group  name:分组名称
	 * @return  system_config.company_name:系统公司名
	 * @return  dcimcloud.nat_acl:远程地址
	 * @return  dcimcloud.nat_web:建站解析
	 * @return  module_power_status:是否请求电源状态
	 */
	public function getHeader(\think\Request $request)
	{
		$uid = $request->uid;
		if ($request->source == "API") {
			$desc = "客户User ID:{$uid}在" . date("Y-m-d H:i:s") . "调取host/header接口,获取已购买产品信息";
			apiResourceLog($uid, $desc);
		}
		$host_id = $request->host_id;
		if (empty($host_id)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$host_exists = \think\Db::name("host")->where("uid", $uid)->where("id", $host_id)->find();
		if (empty($host_exists)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$returndata = [];
		$host_data = \think\Db::name("host")->field("p.cancel_control")->field("o.create_time as ocreate_time,o.amount as order_amount")->field("h.id,h.orderid,h.initiative_renew,h.productid,h.serverid,h.regdate,h.domain,h.payment,p.groupid,h.promoid,
                h.firstpaymentamount,h.amount,h.billingcycle,h.nextduedate,h.nextinvoicedate,
                h.dedicatedip,h.assignedips,h.domainstatus,h.username,h.password,h.suspendreason,p.id as pid,p.api_type,
                h.auto_terminate_end_cycle,h.auto_terminate_reason,h.bwusage,h.bwlimit,h.os,h.remark,h.dcimid,h.dcim_area,h.dcim_os,h.port,p.type,p.name as productname,p.pay_method as payment_type,p.config_options_upgrade,p.api_type,p.zjmf_api_id,p.upstream_price_type,p.upstream_price_value,p.upper_reaches_id,p.config_option1,p.password password_rule,g.name as groupname,o.ordernum")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("product_groups g", "g.id=p.gid")->leftJoin("orders o", "o.id=h.orderid")->where("h.id", $host_id)->find();
		if (!\is_profession()) {
			$host_data["cancel_control"] = 1;
		}
		$grou = \think\Db::name("nav_group")->where("id", $host_data["groupid"])->find();
		$host_data["group"] = $grou;
		$host = \think\Db::name("host")->alias("a")->field("a.initiative_renew,a.productid,a.uid,a.firstpaymentamount,a.amount,a.create_time,a.nextduedate,a.billingcycle,a.productid,c.status,c.id,a.domainstatus,a.regdate,a.flag,a.promoid")->leftJoin("orders b", "b.id = a.orderid")->leftJoin("invoices c", "b.invoiceid = c.id")->where("a.id", $host_id)->find();
		if ($host["billingcycle"] != "onetime" && $host["billingcycle"] != "free" && $host["billingcycle"] != "hour" && $host["billingcycle"] != "day") {
			if ($host["nextduedate"] - $host["regdate"] < 3600) {
				if ($host["status"] == "Cancelled") {
					$host["status"] = "Paid";
				} else {
					$host["status"] = "Unpaid";
				}
			} else {
				$host["status"] = "Paid";
			}
		}
		$host_data["status"] = $host["status"];
		$host_data["invoice_id"] = $host["id"];
		$host_data["password_rule"] = json_decode($host_data["password_rule"], true);
		if ($host_data["type"] == "ssl") {
			$domainstatus_config = config("sslDomainStatus");
			$this->setSslConfig($host_data);
		} else {
			$domainstatus_config = config("domainstatus");
		}
		$currency = getUserCurrency($uid);
		$billing_cycle = config("billing_cycle");
		$upgrade_logic = new \app\common\logic\Upgrade();
		if ($upgrade_logic->judgeUpgradeConfig($host_id)) {
			$this->request->hid = $host_id;
			$upgradeConfig = (new UpgradeController())->index();
			if (!is_array($upgradeConfig)) {
				$upgradeConfig = json_decode($upgradeConfig, true);
			}
			if (isset($upgradeConfig["data"]) && ($upgradeConfig["data"]["host_config_options"] || $upgradeConfig["data"]["host"])) {
				$host_data["allow_upgrade_config"] = 1;
			} else {
				$host_data["allow_upgrade_config"] = 0;
			}
		} else {
			$host_data["allow_upgrade_config"] = 0;
		}
		if ($upgrade_logic->judgeUpgradeConfig($host_id, "product")) {
			$host_data["allow_upgrade_product"] = 1;
		} else {
			$host_data["allow_upgrade_product"] = 0;
		}
		$code = \think\Db::name("promo_code")->where("id", $host_data["promoid"])->value("code");
		$host_data["promo_code"] = $code ?? "";
		$host_data["suspendreason_type"] = explode("-", $host_data["suspendreason"])[0] ? explode("-", $host_data["suspendreason"])[0] : "";
		$host_data["suspendreason"] = explode("-", $host_data["suspendreason"])[1] ? explode("-", $host_data["suspendreason"])[1] : "";
		$host_data["assignedips"] = !empty($host_data["assignedips"]) ? explode(",", $host_data["assignedips"]) : [];
		$host_data["domainstatus_desc"] = $domainstatus_config[$host_data["domainstatus"]];
		$host_data["password"] = cmf_decrypt($host_data["password"]);
		$host_data["firstpaymentamount_desc"] = $currency["prefix"] . $host_data["firstpaymentamount"] . $currency["suffix"];
		$host_data["amount_desc"] = $currency["prefix"] . $host_data["amount"] . $currency["suffix"];
		$host_data["billingcycle_desc"] = $billing_cycle[$host_data["billingcycle"]];
		$host_data["ip_num"] = count($host_data["assignedips"]);
		$host_data["bwusage"] = round($host_data["bwusage"], 2);
		$host_data["remark"] = html_entity_decode($host_data["remark"]);
		foreach (gateway_list() as $v) {
			if ($v["name"] == $host_data["payment"]) {
				$payment_zh = $v["title"];
			}
		}
		$host_data["payment_zh"] = $payment_zh ?? "";
		$host_data["ocreate_time"] = date("Y-m-d", $host_data["ocreate_time"]);
		$returndata["host_data"] = $host_data;
		$productid = $host_data["productid"];
		$domainstatus = $host_data["domainstatus"];
		$returndata["server_data"] = "";
		$custom_field_data = \think\Db::name("customfields")->field("id,fieldname,showdetail")->where("type", "product")->where("relid", $productid)->where("adminonly", 0)->select()->toArray();
		$reinstall_format_data_disk = false;
		foreach ($custom_field_data as &$cv) {
			if ($cv["fieldname"] == "port" || $cv["fieldname"] == "端口") {
				$reinstall_random_port = true;
			}
			if ($cv["fieldname"] == "format_data_disk" || $cv["fieldname"] == "格式化数据盘") {
				$reinstall_format_data_disk = true;
			}
			$cv["value"] = \think\Db::name("customfieldsvalues")->where("fieldid", $cv["id"])->where("relid", $host_id)->value("value") ?? "";
		}
		$returndata["custom_field_data"] = $custom_field_data ?? [];
		$returndata["module_button"] = ["control" => [], "console" => []];
		$returndata["module_client_area"] = [];
		$returndata["module_chart"] = [];
		$returndata["module_client_main_area"] = [];
		$returndata["module_power_status"] = false;
		$returndata["reinstall_random_port"] = false;
		$returndata["reinstall_format_data_disk"] = false;
		$upstream_data = [];
		if ($host_data["type"] != "ssl") {
			if ($host_data["api_type"] == "zjmf_api") {
				$returndata["host_data"]["serverid"] = $returndata["host_data"]["zjmf_api_id"];
				$zjmf_api_params = ["host_id" => $host_data["dcimid"]];
				if ($request->nat) {
					$zjmf_api_params["nat"] = true;
				}
				$upstream_data = zjmfCurl($host_data["zjmf_api_id"], "/host/header", $zjmf_api_params, 30, "GET");
				if ($upstream_data["status"] == 200) {
					$upstream_data = $upstream_data["data"];
				} else {
					$upstream_data = [];
				}
				if (!$returndata["host_data"]["dedicatedip"] && $upstream_data["host_data"]["dedicatedip"]) {
					$sync_info = ["dedicatedip" => $upstream_data["host_data"]["dedicatedip"], "assignedips" => implode(",", $upstream_data["host_data"]["assignedips"]) ?? "", "domain" => $upstream_data["host_data"]["domain"] ?? "", "username" => $upstream_data["host_data"]["username"] ?? "", "password" => cmf_encrypt($upstream_data["host_data"]["password"]), "port" => \intval($upstream_data["host_data"]["port"]), "os" => $upstream_data["host_data"]["os"]];
					\think\Db::name("host")->where("id", $host_id)->update($sync_info);
					$sync_info["assignedips"] = !empty($sync_info["assignedips"]) ? explode(",", $sync_info["assignedips"]) : [];
					$sync_info["password"] = cmf_decrypt($sync_info["password"]);
					$sync_info["ip_num"] = count($sync_info["assignedips"]);
					$returndata["host_data"] = array_merge($returndata["host_data"], $sync_info);
				}
				$returndata["module_button"]["control"] = $upstream_data["module_button"]["control"] ?: [];
				$returndata["module_button"]["console"] = $upstream_data["module_button"]["console"] ?: [];
				$returndata["module_client_area"] = $upstream_data["module_client_area"] ?: [];
				$returndata["module_chart"] = $upstream_data["module_chart"] ?: [];
				$returndata["module_client_main_area"] = $upstream_data["module_client_main_area"] ?: [];
				$returndata["dcimcloud"]["nat_acl"] = $upstream_data["dcimcloud"]["nat_acl"] ?: "";
				$returndata["dcimcloud"]["nat_web"] = $upstream_data["dcimcloud"]["nat_web"] ?: "";
				$returndata["module_power_status"] = \boolval($upstream_data["module_power_status"]);
				$returndata["reinstall_random_port"] = \boolval($upstream_data["reinstall_random_port"]);
				$returndata["reinstall_format_data_disk"] = \boolval($upstream_data["reinstall_format_data_disk"]);
				if ($zjmf_api_params["nat"]) {
					return jsons(["status" => 200, "data" => $returndata]);
				}
			} elseif ($host_data["api_type"] == "manual") {
				$UpperReaches = new \app\common\logic\UpperReaches();
				$returndata["module_power_status"] = $UpperReaches->modulePowerStatus($host_id);
				$returndata["module_button"] = $UpperReaches->moduleClientButton($host_id);
				$upper_reaches = \think\Db::name("zjmf_finance_api")->where("id", $host_data["upper_reaches_id"])->find();
				$returndata["manual"] = ["id" => $host_data["upper_reaches_id"], "name" => $upper_reaches["name"]];
				$upper_reaches_res = \think\Db::name("upper_reaches_res")->where("hid", $host_id)->find();
				$returndata["host_data"]["upper_reaches_res"] = $upper_reaches_res["id"] ?? "";
				$returndata["host_data"]["upper_reaches_control_mode"] = $upper_reaches_res["control_mode"] ?? "";
			} elseif ($host_data["api_type"] == "resource") {
				if (function_exists("resourceCurl")) {
					$post_data = [];
					$post_data["host_id"] = $host_data["dcimid"];
					$upstream_data = resourceCurl($host_data["productid"], "/host/header", $post_data, 30, "GET");
					if ($upstream_data["status"] == 200) {
						$upstream_data = $upstream_data["data"];
					} else {
						$upstream_data = [];
					}
					if (!$returndata["host_data"]["dedicatedip"] && $upstream_data["host_data"]["dedicatedip"]) {
						$sync_info = ["dedicatedip" => $upstream_data["host_data"]["dedicatedip"], "assignedips" => implode(",", $upstream_data["host_data"]["assignedips"]) ?? "", "domain" => $upstream_data["host_data"]["domain"] ?? "", "username" => $upstream_data["host_data"]["username"] ?? "", "password" => cmf_encrypt($upstream_data["host_data"]["password"]), "port" => \intval($upstream_data["host_data"]["port"]), "os" => $upstream_data["host_data"]["os"]];
						\think\Db::name("host")->where("id", $host_id)->update($sync_info);
						$sync_info["assignedips"] = !empty($sync_info["assignedips"]) ? explode(",", $sync_info["assignedips"]) : [];
						$sync_info["password"] = cmf_decrypt($sync_info["password"]);
						$sync_info["ip_num"] = count($sync_info["assignedips"]);
						$returndata["host_data"] = array_merge($returndata["host_data"], $sync_info);
					}
					$returndata["module_button"]["control"] = $upstream_data["module_button"]["control"] ?: [];
					$returndata["module_button"]["console"] = $upstream_data["module_button"]["console"] ?: [];
					$returndata["module_client_area"] = $upstream_data["module_client_area"] ?: [];
					$returndata["module_chart"] = $upstream_data["module_chart"] ?: [];
					$returndata["module_client_main_area"] = $upstream_data["module_client_main_area"] ?: [];
					$returndata["dcimcloud"]["nat_acl"] = $upstream_data["dcimcloud"]["nat_acl"] ?: "";
					$returndata["dcimcloud"]["nat_web"] = $upstream_data["dcimcloud"]["nat_web"] ?: "";
					$returndata["module_power_status"] = \boolval($upstream_data["module_power_status"]);
					$returndata["reinstall_random_port"] = \boolval($upstream_data["reinstall_random_port"]);
					$returndata["reinstall_format_data_disk"] = \boolval($upstream_data["reinstall_format_data_disk"]);
				}
			} elseif ($host_data["api_type"] == "whmcs") {
				$dcimid = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $host_id)->where("b.type", "product")->where("b.relid", $host_data["pid"])->where("b.fieldname", "hostid")->value("value");
				$returndata["host_data"]["dcimid"] = $dcimid;
			} else {
				$provision_logic = new \app\common\logic\Provision();
				if ($host_data["domainstatus"] == "Active") {
					if ($host_data["type"] == "dcimcloud") {
						$dcimcloud = new \app\common\logic\DcimCloud();
						$returndata["module_button"] = $dcimcloud->moduleClientButton($host_data["dcimid"]);
						$returndata["module_client_area"] = $dcimcloud->moduleClientArea($host_id);
						$returndata["module_chart"] = $dcimcloud->chart($host_data["dcimid"], $host_id);
						$returndata["module_power_status"] = true;
						$returndata["reinstall_random_port"] = $reinstall_random_port;
						$returndata["reinstall_format_data_disk"] = $reinstall_format_data_disk;
						if ($request->nat) {
							$nat_info = $dcimcloud->getNatInfo($host_id);
							$returndata["dcimcloud"]["nat_acl"] = $nat_info["nat_acl"] ?: "";
							$returndata["dcimcloud"]["nat_web"] = $nat_info["nat_web"] ?: "";
							return jsons(["status" => 200, "data" => $returndata]);
						} else {
							if (!$request->tplcloud) {
								$nat_info = $dcimcloud->getNatInfo($host_id);
								$returndata["dcimcloud"]["nat_acl"] = $nat_info["nat_acl"] ?: "";
								$returndata["dcimcloud"]["nat_web"] = $nat_info["nat_web"] ?: "";
							}
						}
					} elseif ($host_data["type"] == "dcim") {
						if ($host_data["config_option1"] == "bms") {
							$dcim = new \app\common\logic\Dcim();
							$returndata["module_button"] = $dcim->moduleClientButton($host_data["dcimid"]);
							$returndata["module_client_area"] = $dcim->moduleClientArea($host_id);
							$returndata["module_power_status"] = true;
						} else {
							$returndata["module_power_status"] = true;
						}
					} else {
						$module_button = $provision_logic->clientButtonOutput($host_id);
						$module_client_area = $provision_logic->clientArea($host_id);
						$returndata["module_button"] = $module_button;
						$returndata["module_client_area"] = $module_client_area;
						$returndata["module_chart"] = $provision_logic->chart($host_id);
						$returndata["module_power_status"] = $provision_logic->checkDefineFunc($host_id, "Status");
						$returndata["module_client_main_area"] = $provision_logic->clientAreaMainOutput($host_id);
					}
				}
			}
		}
		if ($host_data["api_type"] == "zjmf_api" || $host_data["api_type"] == "resource") {
			$returndata["host_data"]["show_traffic_usage"] = $upstream_data["host_data"]["show_traffic_usage"] ? true : false;
		} else {
			if ($host_data["api_type"] == "manual") {
				$returndata["host_data"]["show_traffic_usage"] = false;
			} else {
				if ($host_data["bwlimit"] > 0) {
					if ($host_data["type"] == "dcim") {
						if ($host_data["api_type"] == "whmcs") {
							$returndata["host_data"]["show_traffic_usage"] = false;
						} else {
							$returndata["host_data"]["show_traffic_usage"] = $host_data["config_option1"] != "bms";
						}
					} elseif ($host_data["type"] == "dcimcloud") {
						$returndata["host_data"]["show_traffic_usage"] = true;
					} else {
						$returndata["host_data"]["show_traffic_usage"] = $provision_logic->checkDefineUsage($host_id);
					}
				} else {
					$returndata["host_data"]["show_traffic_usage"] = false;
				}
			}
		}
		$returndata["hook_output"] = hook("client_product_details_output", ["host_id" => $host_id], false);
		$returndata["currency"] = $currency;
		$upgrade_products_data = \think\Db::name("product_upgrade_products")->where("product_id", $productid)->select()->toArray();
		if (!empty($upgrade_products_data)) {
			$system_button["upgrade"] = ["name" => lang("UPGRADE_DOWNGRADE"), "func" => "upgrade"];
			if ($domainstatus == "Active") {
				$system_button["upgrade"]["disabled"] = false;
			} else {
				$system_button["upgrade"]["disabled"] = true;
			}
		}
		if ($host_data["config_options_upgrade"] == 1) {
			$system_button["upgrade_option"] = ["name" => lang("UPGRADE_DOWNGRADE_OPTIONS"), "func" => "upgrade_option"];
			if ($domainstatus == "Active") {
				$system_button["upgrade_option"]["disabled"] = false;
			} else {
				$system_button["upgrade_option"]["disabled"] = true;
			}
		}
		if ($domainstatus == "Active" && $host_data["payment_type"] == "prepayment" && !in_array($host_data["billingcycle"], ["ontrial", "hour", "day"])) {
			$system_button["product_transfer"] = ["name" => lang("PRODUCT_TRANSFER"), "func" => "product_transfer", "disabled" => false];
		}
		if (in_array($domainstatus, ["Active", "Suspended"])) {
			if ($host_data["payment_type"] == "prepayment" && !in_array($billingcycle, ["onetime", "free", "hour", "day"])) {
				$system_button["renew_cycle"] = ["name" => lang("RENEW"), "func" => "renew_cycle", "disabled" => false];
			} else {
				if ($host_data["payment_type"] == "postpaid" || in_array($billingcycle, ["hour", "day"])) {
					$system_button["pay_cycle"] = ["name" => lang("PAYMENT_CURRENT_PERIOD"), "func" => "pay_cycle", "disabled" => false];
				}
			}
		}
		if (in_array($domainstatus, ["Pending", "Active", "Suspended"])) {
			$system_button["request_cancel"] = ["name" => lang("UPGRADE_DOWNGRADE_OPTIONS"), "func" => "request_cancel", "disabled" => false];
		}
		$config_options = [];
		$config_logic = new \app\common\logic\ConfigOptions();
		$config_options = $config_logic->showInfo($productid, $host_id, $currency, $host_data["billingcycle"], false);
		$returndata["config_options"] = array_values($config_options);
		$download_data = [];
		$download_data = \think\Db::name("downloads")->field("d.*")->alias("d")->leftJoin("product_downloads p", "p.download_id=d.id")->where("p.product_id", $productid)->select()->toArray();
		foreach ($download_data as $key => $val) {
			if ($val["productdownload"] == 1 && !in_array($domainstatus, ["Active"])) {
				unset($download_data[$key]);
				continue;
			}
			$download_data[$key]["down_link"] = "download/product_file?id=" . $val["id"];
		}
		$returndata["download_data"] = $download_data;
		$returndata["dcim"]["flowpacket"] = [];
		$returndata["dcim"]["flow_packet_use_list"] = [];
		if ($host_data["api_type"] == "zjmf_api") {
			$returndata["dcim"]["flowpacket"] = $upstream_data["dcim"]["flowpacket"] ?: [];
			$returndata["host_data"]["bwlimit"] = \intval($upstream_data["host_data"]["bwlimit"]);
			$returndata["host_data"]["bwusage"] = \floatval($upstream_data["host_data"]["bwusage"]);
			if ($host_data["upstream_price_type"] == "percent") {
				foreach ($returndata["dcim"]["flowpacket"] as $k => $v) {
					$returndata["dcim"]["flowpacket"][$k]["price"] = round($v["price"] * $host_data["upstream_price_value"] / 100, 2);
				}
			}
			$returndata["dcim"]["flow_packet_use_list"] = $upstream_data["dcim"]["flow_packet_use_list"] ?: [];
			if ($host_data["type"] == "dcim" && $host_data["config_option1"] != "bms") {
				$returndata["dcim"]["auth"] = $upstream_data["dcim"]["auth"] ?? ["bmc" => "off", "crack_pass" => "off", "ikvm" => "off", "kvm" => "off", "novnc" => "off", "off" => "off", "on" => "off", "reboot" => "off", "reinstall" => "off", "rescue" => "off", "traffic" => "off"];
				$returndata["dcim"]["svg"] = $upstream_data["dcim"]["svg"] ?? "";
				$returndata["host_data"]["os_ostype"] = $upstream_data["host_data"]["os_ostype"] ?? "";
				$returndata["host_data"]["os_osname"] = $upstream_data["host_data"]["os_osname"] ?? "";
				$returndata["host_data"]["disk_num"] = $upstream_data["host_data"]["disk_num"] ?? 1;
			}
		} else {
			if ($host_data["bwlimit"] > 0) {
				$flowpacket = \think\Db::name("dcim_flow_packet")->field("id,name,capacity,price,sale_times,stock")->where("status", 1)->whereRaw("FIND_IN_SET('{$host_data["productid"]}', allow_products)")->select()->toArray();
				if (!empty($flowpacket)) {
					foreach ($flowpacket as $k => $v) {
						$flowpacket[$k]["leave"] = 1;
						if ($v["stock"] > 0 && $v["sale_times"] >= $v["stock"]) {
							$flowpacket[$k]["leave"] = 0;
						}
						unset($flowpacket[$k]["sale_times"]);
						unset($flowpacket[$k]["stock"]);
					}
					$returndata["dcim"]["flowpacket"] = $flowpacket;
				}
			}
			if ($host_data["type"] == "dcim" && $host_data["config_option1"] != "bms") {
				$server = \think\Db::name("servers")->alias("a")->field("b.*")->leftJoin("dcim_servers b", "a.id=b.serverid")->where("a.id", $host_data["serverid"])->find();
				if ($host_data["api_type"] == "whmcs" && $host_data["domainstatus"] == "Active") {
					$returndata["dcim"]["auth"] = ["on" => "on", "off" => "on", "reboot" => "on", "bmc" => "on", "novnc" => "on", "reinstall" => "on", "crack_pass" => "on", "traffic" => "on"];
				} else {
					$returndata["dcim"]["auth"] = json_decode($server["auth"], true);
				}
				if ($host_data["bwlimit"] > 0) {
					$returndata["dcim"]["flow_packet_use_list"] = get_dcim_traffic_usage_table($host_id, $uid, $server["bill_type"], $host_data["bwusage"], $host_data["bwlimit"]);
				}
				$os = json_decode($server["os"], true);
				$returndata["dcim"]["os_group"] = $os["group"];
				$returndata["dcim"]["os"] = $os["os"];
				if (!empty($host_data["dcim_area"])) {
					$area = json_decode($server["area"], true);
					foreach ($area as $v) {
						if ($v["id"] == $host_data["dcim_area"]) {
							$returndata["dcim"]["area_code"] = $v["area"];
							$returndata["dcim"]["area_name"] = $v["name"] ?? "";
							break;
						}
					}
				} else {
					$returndata["dcim"]["area_code"] = "";
					$returndata["dcim"]["area_name"] = "";
				}
				$os_info = get_dcim_os_info($host_data["dcim_os"], $os["os"], $os["group"]);
				$returndata["host_data"]["os_ostype"] = $os_info["ostype"] ?? "";
				$returndata["host_data"]["os_osname"] = $os_info["os_name"] ?? "";
				$returndata["host_data"]["disk_num"] = 1;
				$returndata["dcim"]["svg"] = $os_info["svg"];
			} else {
				if ($host_data["bwlimit"] > 0) {
					if ($host_data["type"] == "dcimcloud") {
						$traffic_bill_type_config_options = (new \app\common\model\HostModel())->getConfigOption($host_id, "traffic_bill_type");
						$returndata["dcim"]["flow_packet_use_list"] = get_dcim_traffic_usage_table($host_id, $uid, $traffic_bill_type_config_options["sub_option_arr"][0], $host_data["bwusage"], $host_data["bwlimit"]);
					} else {
						$returndata["dcim"]["flow_packet_use_list"] = get_dcim_traffic_usage_table($host_id, $uid, "", $host_data["bwusage"], $host_data["bwlimit"]);
					}
				}
			}
		}
		$os_config_option_id = \think\Db::name("product_config_links")->alias("a")->leftJoin("product_config_options b", "a.gid=b.gid")->where("a.pid", $host_data["productid"])->where("b.option_type", 5)->value("b.id");
		$sub = \think\Db::name("product_config_options_sub")->field("id,option_name")->where("config_id", $os_config_option_id)->where("hidden", 0)->order("sort_order ASC")->order("id asc")->select()->toArray();
		$cloud_os = [];
		$cloud_os_group = [];
		$configoption_res = \think\Db::name("host_config_options")->where("relid", $host_id)->select()->toArray();
		$configoption = [];
		foreach ($configoption_res as $k => $v) {
			$configoption[$v["configid"]] = $v["qty"] ?: $v["optionid"];
		}
		$senior = new \app\common\logic\SeniorConf();
		$data_config_id = array_column($sub, "id");
		$senior->aloneCheckConf($host_data["productid"], $configoption, $os_config_option_id, $data_config_id);
		if ($host_data["api_type"] == "whmcs") {
			$os_res = whmcsCurlPost($host_id, "os");
			if ($os_res["os"]) {
				$whmcs_os = $os_res["os"];
				foreach ($whmcs_os as &$v1) {
					if (!empty($v1["group_name"])) {
						$v1["option_name"] = $v1["id"] . "|" . $v1["group_name"] . "^" . $v1["name"];
					} else {
						$split_1 = explode(" ", $v1["name"]);
						$split_2 = explode("-", $v1["name"]);
						$os_all = config("system_list");
						$os_filter = "";
						if (in_array(strtolower($split_1[0]), $os_all)) {
							$os_filter = $split_1[0];
						}
						if (in_array(strtolower($split_2[0]), $os_all)) {
							$os_filter = $split_2[0];
						}
						$v1["option_name"] = $v1["id"] . "|" . $os_filter . "^" . $v1["name"];
					}
					unset($v1["name"]);
				}
				$sub = $whmcs_os;
				$data_config_id = array_column($sub, "id");
				unset($whmcs_os);
			} else {
				$sub = [];
			}
		}
		foreach ($sub as $v) {
			if (!in_array($v["id"], $data_config_id)) {
				continue;
			}
			$arr = explode("|", $v["option_name"]);
			if (strpos($arr[1], "^") !== false) {
				$arr2 = explode("^", $arr[1]);
				if (empty($arr2[0]) || empty($arr2[1])) {
					continue;
				}
				if (!in_array($arr2[0], $cloud_os_group)) {
					$cloud_os_group[] = $arr2[0];
				}
				$cloud_os[] = ["id" => $v["id"], "name" => $arr2[1], "group" => $arr2[0]];
			} else {
				$cloud_os[] = ["id" => $v["id"], "name" => $arr[1]];
			}
		}
		if (!empty($cloud_os_group)) {
			foreach ($cloud_os_group as $k => $v) {
				$cloud_os_group[$k] = ["id" => $v, "name" => $v];
			}
			foreach ($cloud_os as $k => $v) {
				if (empty($v["group"])) {
					unset($cloud_os[$k]);
				}
			}
			$cloud_os = array_values($cloud_os);
		}
		$returndata["cloud_os"] = $cloud_os;
		$returndata["cloud_os_group"] = $cloud_os_group;
		$os_info = \think\Db::name("host_config_options")->alias("a")->field("b.option_name")->leftJoin("product_config_options_sub b", "a.optionid=b.id")->where("a.relid", $host_id)->where("a.configid", $os_config_option_id)->find();
		if (empty($host_data["username"])) {
			if (stripos($os_info["option_name"], "win") !== false) {
				$returndata["host_data"]["username"] = "administrator";
			} else {
				$returndata["host_data"]["username"] = "root";
			}
		}
		$returndata["system_config"]["company_name"] = configuration("company_name");
		$cancelist = \think\Db::name("cancel_reason")->field("reason")->select()->toArray();
		$returndata["cancelist"] = $cancelist;
		$host_cancel = \think\Db::name("cancel_requests")->field("type,reason")->where("relid", $host_id)->find();
		$returndata["host_cancel"] = $host_cancel ?? [];
		unset($returndata["host_data"]["zjmf_api_id"]);
		unset($returndata["host_data"]["api_type"]);
		$userinfo = db("clients")->field("second_verify")->where("id", $uid)->find();
		$returndata["second"]["second_verify"] = $userinfo["second_verify"];
		$returndata["second"]["allow_second_verify"] = intval(configuration("second_verify_home"));
		$returndata["second"]["second_verify_action_home"] = explode(",", configuration("second_verify_action_home"));
		$returndata["system_button"] = $system_button;
		return jsons(["status" => 200, "data" => $returndata]);
	}
	public function setSslConfig(&$host_data)
	{
		$host_data["certssl_orderinfo"] = [];
		if ($host_data["api_type"] == "zjmf_api") {
			$cert_info = zjmfCurl($host_data["zjmf_api_id"], "/provision/sslCertFunc", ["id" => $host_data["dcimid"], "func" => "getAllInfo"]);
			if ($cert_info["status"] == "200") {
				$host_data["certssl_orderinfo"] = $cert_info["data"]["orderInfo"];
			}
			if ($host_data["certssl_orderinfo"]) {
				$host_data["cert_pinfo"] = $cert_info["data"]["cert_pinfo"];
			}
		} else {
			$host_data["certssl_orderinfo"] = \think\Db::name("certssl_orderinfo")->where("hostid", $host_data["id"])->order("id", "desc")->find();
			if ($host_data["certssl_orderinfo"]) {
				$host_data["certssl_orderinfo"]["domainNames_arr"] = explode(PHP_EOL, $host_data["certssl_orderinfo"]["domainNames"]);
				$host_data["certssl_orderinfo"]["domainNames_arr"] = array_filter($host_data["certssl_orderinfo"]["domainNames_arr"]);
				$host_data["cert_pinfo"] = \think\Db::name("certssl_product")->where("p_id", $host_data["certssl_orderinfo"]["cert_pid"])->where("server_name", $host_data["certssl_orderinfo"]["server_name"])->find();
			}
		}
		if ($host_data["certssl_orderinfo"]) {
			if ($host_data["certssl_orderinfo"]["country"]) {
				$host_data["certssl_orderinfo"]["country_name"] = \think\Db::name("sms_country")->where("iso", $host_data["certssl_orderinfo"]["country"])->value("name_zh");
			}
			$host_data["certssl_orderinfo"]["telephone"] = $host_data["certssl_orderinfo"]["telephone"] ? substr_replace($host_data["certssl_orderinfo"]["telephone"], "****", 3, 4) : "";
			$host_data["certssl_orderinfo"]["company_phone"] = $host_data["certssl_orderinfo"]["company_phone"] ? substr_replace($host_data["certssl_orderinfo"]["company_phone"], "****", 3, 4) : "";
			$host_data["certssl_orderinfo"]["issus_time"] = $host_data["certssl_orderinfo"]["issus_time"] ? date("Y-m-d H:i", $host_data["certssl_orderinfo"]["issus_time"]) : "-";
			if ($host_data["certssl_orderinfo"]["due_time"]) {
				if ($host_data["certssl_orderinfo"]["due_time"] > time() && $host_data["certssl_orderinfo"]["due_time"] < time() + 5184000) {
					$host_data["domainstatus"] = "Overdue_Active";
				}
				if ($host_data["certssl_orderinfo"]["due_time"] <= time()) {
					$host_data["domainstatus"] = "Deleted";
				}
				$host_data["certssl_orderinfo"]["due_time_day"] = ceil(($host_data["certssl_orderinfo"]["due_time"] - time()) / 86400);
			}
			$host_data["certssl_orderinfo"]["due_time"] = $host_data["certssl_orderinfo"]["due_time"] ? date("Y-m-d H:i", $host_data["certssl_orderinfo"]["due_time"]) : "-";
		} else {
			$certifi_log = \think\Db::name("certifi_log")->where("uid", $this->request->uid)->where("type", 1)->find();
			if ($certifi_log) {
				$host_data["certssl_orderinfo"]["lastname"] = mb_substr($certifi_log["certifi_name"], 0, 1);
				$host_data["certssl_orderinfo"]["firstname"] = mb_substr($certifi_log["certifi_name"], 1);
				$host_data["certssl_orderinfo"]["orgName"] = $certifi_log["company_name"] ?: "";
				$host_data["certssl_orderinfo"]["creditCode"] = $certifi_log["company_organ_code"] ?: "";
			}
		}
		$host_data["iso_arr"] = \think\Db::name("sms_country")->field("iso,name_zh")->select()->toArray();
	}
	/**
	 * @title 产品转移根据邮箱或手机号获取用户信息
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/nametouser
	 * @method POST
	 * @param .name:tranfer_name type:string require:1 default: other: desc:查找到邮箱或者手机号
	 * @return id:用户id(调用转移时需要)
	 * @return username:用户名(展示给客户确认)
	 */
	public function postNameToUser(\think\Request $request)
	{
		$uid = $request->uid;
		$tranfer_name = $request->tranfer_name;
		if (\think\facade\Validate::isEmail($tranfer_name)) {
			$tranfer_info = \think\Db::name("clients")->field("id,username")->where("email", $tranfer_name)->find();
		} else {
			if (\think\facade\Validate::isMobile($tranfer_name)) {
				$tranfer_info = \think\Db::name("clients")->field("id,username")->where("phonenumber LIKE '%{$tranfer_name}' ")->find();
			}
		}
		if (empty($tranfer_info)) {
			return json(["status" => 400, "msg" => "账户不存在"]);
		}
		if ($tranfer_info["id"] == $uid) {
			return json(["status" => 406, "msg" => lang("YOU_CANNOT_TRANSFER_THE_PRODUC")]);
		}
		$system_certifi = configuration("certifi_open");
		$Cloents = new \app\home\model\ClientsModel();
		$user_certifi = $Cloents->getUserCertifi($tranfer_info["id"]);
		if ($system_certifi == 1 && empty($user_certifi)) {
			return json(["status" => 406, "msg" => lang("THE_SYSTEM_REQUIRES_THE_RECEIVER_TO_PASS_THE_REAL_NAME_VERIFICATION")]);
		}
		if (!empty($tranfer_info)) {
			return json(["status" => 200, "data" => $tranfer_info]);
		} else {
			return json(["status" => 406, "msg" => lang("USER_NOT_FOUND_PLEASE_CHECK")]);
		}
	}
	/**
	 * @title 提交转移请求
	 * @description 接口说明:提交转移请求
	 * @author 萧十一郎
	 * @url host/transfer
	 * @method POST
	 * @param .name:host_id type:number require:1 default: other: desc:主机id
	 * @param .name:remarks type:string require:1 default: other: desc:备注
	 * @param .name:transfer_uid type:number require:1 default: other: desc:接收人id
	 */
	public function postTransfer(\think\Request $request)
	{
		$uid = $request->uid;
		$param = $request->param();
		$host_id = intval($param["host_id"]);
		$remarks = $param["remarks"];
		$transfer_uid = intval($param["transfer_uid"]);
		if ($uid == $transfer_uid) {
			return json(["status" => 406, "msg" => lang("YOU_CANNOT_TRANSFER_THE_PRODUC")]);
		}
		if (empty($param["host_id"])) {
			return json(["status" => 406, "msg" => lang("TRANSFERRED_PRODUCT_CANNOT")]);
		}
		if (empty($remarks)) {
			return json(["status" => 406, "msg" => lang("NOTE_CANNOT_BE_EMPTY")]);
		}
		if (empty($transfer_uid)) {
			return json(["status" => 406, "msg" => lang("TRANSFER_TO_USER_CANNOT_BE_EMP")]);
		}
		$transfer_user_data = \think\Db::name("clients")->find($transfer_uid);
		if (empty($transfer_user_data)) {
			return json(["status" => 406, "msg" => lang("RECEIVER_DOES_NOT_EXIST")]);
		}
		$host_data = \think\Db::name("host")->field("h.id,h.productid,h.domainstatus,h.billingcycle,p.pay_method  as payment_type")->alias("h")->leftJoin("products p", "p.id=h.productid")->where("h.id", $host_id)->where("h.uid", $uid)->find();
		if (empty($host_data)) {
			return json(["status" => 406, "msg" => lang("TRANSFERRED_PRODUCT_NOT_FOUND")]);
		}
		if ($host_data["domainstatus"] != "Active") {
			return json(["status" => 406, "msg" => lang("PRODUCT_STATUS_MUST_BE_ACTIVE")]);
		}
		if (in_array($host_data["billingcycle"], ["ontrial", "hour", "day"]) || $host_data["payment_type"] == "postpaid") {
			return json(["status" => 406, "msg" => lang("TRIAL_OR_POSTPAID_PRODUCTS")]);
		}
		$has_transfer = \think\Db::name("transfer")->where("host_id", $host_id)->find();
		if (!empty($has_transfer)) {
			return json(["status" => 406, "msg" => lang("THE_TRANSFER_REQUEST_HAS_BEEN")]);
		}
		$idata = ["uid" => $uid, "host_id" => $host_id, "transfer_uid" => $transfer_uid, "remarks" => $remarks, "status" => 0, "create_time" => time()];
		$transfer_id = \think\Db::name("transfer")->insertGetId($idata);
		$description = "提交转移请求成功 - transfer ID:" . $transfer_id . "，Host ID:" . $host_id;
		active_logs($description, $uid);
		active_logs($description, $uid, "", 2);
		return json(["status" => 200, "msg" => lang("THE_TRANSFER_REQUEST_HAS_AUTO_CANCEL")]);
	}
	/**
	 * @title 取消产品转移(发起端)
	 * @description 接口说明:用户取消转移
	 * @author 萧十一郎
	 * @url host/canceltranfer
	 * @method POST
	 * @param .name:transfer_id type:number require:1 default: other: desc:转移id(有请求时会显示到产品内页)
	 */
	public function postCancelTranfer(\think\Request $request)
	{
		$uid = $request->uid;
		$transfer_id = $request->transfer_id;
		if (empty($transfer_id)) {
			return json(["status" => 406, "msg" => "取消的请求不存在"]);
		}
		$tranfer_data = \think\Db::name("transfer")->where("uid", $uid)->where("id", $transfer_id)->find();
		if (empty($tranfer_data)) {
			return json(["status" => 406, "msg" => "取消的请求不存在"]);
		}
		\think\Db::name("transfer")->delete($transfer_id);
		$description = "取消转移产品成功 - transfer ID:" . $transfer_id;
		active_logs($description, $uid);
		active_logs($description, $uid, "", 2);
		return json(["status" => 200, "msg" => "取消转移成功"]);
	}
	/**
	 * @title 接收产品转移(接收端)
	 * @description 接口说明:接收产品转移
	 * @author 萧十一郎
	 * @url host/teceivetranfer
	 * @method POST
	 * @param .name:transfer_id type:number require:1 default: other: desc:转移id
	 */
	public function postReceiveTranfer(\think\Request $request)
	{
		$uid = $request->uid;
		$transfer_id = $request->transfer_id;
		$transfer_data = \think\Db::name("transfer")->where("transfer_uid", $uid)->where("id", $transfer_id)->where("status", 0)->find();
		if (empty($transfer_data)) {
			return json(["status" => 406, "msg" => "转移请求未找到"]);
		}
		$host_id = $transfer_data["host_id"];
		\think\Db::startTrans();
		try {
			\think\Db::name("host")->where("id", $host_id)->update(["uid" => $uid]);
			\think\Db::name("transfer")->where("id", $transfer_id)->update(["status" => 1]);
			\think\Db::commit();
			$description = "接收产品成功 - transfer ID:" . $transfer_id . " - Host ID:" . $host_id;
			active_logs($description, $uid);
			active_logs($description, $uid, "", 2);
			return json(["status" => 200, "msg" => "接收产品成功"]);
		} catch (\think\Exception $e) {
			\think\Db::rollback();
			return json(["status" => 406, "msg" => "接收产品失败"]);
		}
	}
	/**
	 * @title 拒绝产品转移(接收端)
	 * @description 接口说明:拒绝产品转移
	 * @author 萧十一郎
	 * @url host/refusetranfer
	 * @method POST
	 * @param .name:transfer_id type:number require:1 default: other: desc:转移id
	 */
	public function postRefuseTranfer(\think\Request $request)
	{
		$uid = $request->uid;
		$transfer_id = $request->transfer_id;
		$transfer_data = \think\Db::name("transfer")->where("transfer_uid", $uid)->where("id", $transfer_id)->where("status", 0)->find();
		if (empty($transfer_data)) {
			return json(["status" => 406, "msg" => "转移请求未找到"]);
		}
		\think\Db::name("transfer")->where("id", $transfer_id)->update(["status" => 2]);
		return json(["status" => 200, "msg" => "已取消接收该产品"]);
	}
	/**
	 * @title 产品续费--页面
	 * @description 接口说明: 产品续费--页面
	 * @author wyh
	 * @url host/renewpage
	 * @method get
	 * @param  .name:hostid type:number require:1 other: desc:主机id,可传单个值hostid
	 * @param  .name:billingcycles type:number require:0 other: desc:周期
	 * @return  host:产品数据@
	 * @host firstpaymentamount：首次订购价格
	 * @host amount：续费价格
	 * @host create_time：订购时间
	 * @host nextduedate：到期时间
	 * @host billingcycle：产品周期
	 * @return  cycle:可用周期
	 * @return  accounts:充值记录@
	 * @accounts trans_id:交易流水
	 * @accounts amount_in:金额
	 * @accounts pay_time:交易日期
	 * @accounts type:来源
	 * @accounts gateway:支付方式
	 */
	public function getRenewPage()
	{
		$data = [];
		$params = $this->request->param();
		$hid = $params["hostid"];
		if (empty($hid)) {
			return json(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.initiative_renew,a.productid,a.uid,a.firstpaymentamount,a.amount,a.create_time,a.nextduedate,a.billingcycle,a.productid,c.status,c.id,a.domainstatus,a.regdate,a.flag,a.promoid")->leftJoin("orders b", "b.id = a.orderid")->leftJoin("invoices c", "b.invoiceid = c.id")->where("a.id", $hid)->find();
		if (empty($host)) {
			return jsons(["status" => 400, "msg" => lang("产品不存在")]);
		}
		if ($host["billingcycle"] != "onetime" && $host["billingcycle"] != "free" && $host["billingcycle"] != "hour" && $host["billingcycle"] != "day") {
			if ($host["nextduedate"] - $host["regdate"] < 3600) {
				if ($host["status"] == "Cancelled") {
					$host["status"] = "Paid";
				} else {
					$host["status"] = "Unpaid";
				}
			} else {
				$host["status"] = "Paid";
			}
		}
		$host["billingcycle_zh"] = config("billing_cycle")[$host["billingcycle"]];
		$data["host"] = $host;
		$uid = $this->request->uid;
		$currency_id = priorityCurrency($uid);
		$currency = getUserCurrency($uid);
		$data["currency"] = $currency;
		$product_model = new \app\common\model\ProductModel();
		$cycle = $product_model->getProductCycle($host["productid"], $currency_id, $hid, $host["billingcycle"], $host["amount"], "", "", "", $host["flag"]);
		$cycles = [];
		$pay_type = \think\Db::name("products")->where("id", $host["productid"])->value("pay_type");
		$pay_type = json_decode($pay_type, true);
		foreach ($cycle as $k => $v) {
			if (!in_array($v["billingcycle"], ["free", "ontrial"])) {
				$cycles[] = $v;
			}
		}
		$flag = getSaleProductUser($host["productid"], $host["uid"]);
		if (!$flag) {
			if ((new \app\common\logic\Renew())->unchangePrice($hid, $host["billingcycle"], $currency_id) != -1 && round((new \app\common\logic\Renew())->calculatedPrice($hid, $host["billingcycle"]), 2) != round($host["amount"], 2) && $host["promoid"] == 0) {
				$cycles = [];
			}
			if ($host["billingcycle"] != "ontrial" && !in_array($host["billingcycle"], array_column($cycles, "billingcycle"))) {
				$cycles[] = ["billingcycle" => $host["billingcycle"], "billingcycle_zh" => $host["billingcycle_zh"], "setup_fee" => 0, "price" => 0, "amount" => $host["amount"], "saleproducts" => 0];
			}
		}
		if (!empty($params["billingcycles"]) && in_array($params["billingcycles"], array_keys(config("billing_cycle")))) {
			if ($params["billingcycles"] != $host["billingcycle"]) {
				$renew = new \app\common\logic\Renew();
				if ((new \app\common\model\ProductModel())->checkProductPrice($host["productid"], $params["billingcycles"], $currency_id)) {
					$amount = $renew->calculatedPrice($hid, $params["billingcycles"], 1, $host["flag"]);
					$data["host"]["amount"] = bcsub($amount["price_cycle"], 0, 2);
					$data["host"]["saleproducts"] = bcsub($amount["price_sale_cycle"], 0, 2);
					$data["host"]["flags"] = $host["flag"];
				} else {
					$amount = $host["amount"];
					$data["host"]["amount"] = bcsub($amount, 0, 2);
					$data["host"]["saleproducts"] = bcsub(0, 0, 2);
					$data["host"]["flags"] = $host["flag"];
				}
				$data["host"]["billingcycle"] = $params["billingcycles"];
				$data["host"]["billingcycle_zh"] = config("billing_cycle")[$params["billingcycles"]];
			} else {
				$data["host"]["saleproducts"] = 0;
				$data["host"]["amount"] = $host["amount"];
				$data["host"]["flags"] = $host["flag"];
			}
		} else {
			if (!empty($cycles)) {
				if ($cycles[0]["billingcycle"] != $host["billingcycle"]) {
					$renew = new \app\common\logic\Renew();
					if ((new \app\common\model\ProductModel())->checkProductPrice($host["productid"], $cycles[0]["billingcycle"], $currency_id)) {
						$amount = $renew->calculatedPrice($hid, $cycles[0]["billingcycle"], 1, $host["flag"]);
						$data["host"]["amount"] = bcsub($amount["price_cycle"], 0, 2);
						$data["host"]["saleproducts"] = bcsub($amount["price_sale_cycle"], 0, 2);
						$data["host"]["flags"] = $host["flag"];
					} else {
						$amount = $host["amount"];
						$data["host"]["amount"] = bcsub($amount, 0, 2);
						$data["host"]["saleproducts"] = bcsub(0, 0, 2);
						$data["host"]["flags"] = $host["flag"];
					}
				} else {
					$data["host"]["saleproducts"] = 0;
					$data["host"]["amount"] = $host["amount"];
					$data["host"]["flags"] = $host["flag"];
				}
			}
		}
		$host["amount"] = sprintf("%.2f", number_format($data["host"]["amount"], 2));
		foreach ($cycles as &$vv) {
			$vv["duration"] = getNextTime($vv["billingcycle"], $pay_type["pay_" . $vv["billingcycle"] . "_cycle"], $host["nextduedate"], $pay_type["pay_ontrial_cycle_type"] ?: "day") - $host["nextduedate"];
		}
		$data["cycle"] = $cycles;
		$data["pay_type"] = $pay_type;
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 产品续费--页面(模板调用)
	 * @description 接口说明: 产品续费--页面
	 * @author wyh
	 * @url host/renewpageview
	 * @method get
	 * @param  .name:hostid type:number require:1 other: desc:主机id
	 * @return  host:产品数据@
	 * @host firstpaymentamount：首次订购价格
	 * @host amount：续费价格
	 * @host create_time：订购时间
	 * @host nextduedate：到期时间
	 * @host billingcycle：产品周期
	 * @return  cycle:可用周期
	 * @return  accounts:充值记录@
	 * @accounts trans_id:交易流水
	 * @accounts amount_in:金额
	 * @accounts pay_time:交易日期
	 * @accounts type:来源
	 * @accounts gateway:支付方式
	 */
	public function getRenewPageView()
	{
		$data = [];
		$params = $this->request->param();
		$hid = $params["hostid"];
		if (empty($hid)) {
			return json(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.initiative_renew,a.productid,a.uid,a.firstpaymentamount,a.amount,a.create_time,a.nextduedate,a.billingcycle,a.productid,c.status,c.id,a.domainstatus,a.regdate,a.flag,a.promoid")->leftJoin("orders b", "b.id = a.orderid")->leftJoin("invoices c", "b.invoiceid = c.id")->where("a.id", $hid)->find();
		if (empty($host)) {
			return jsons(["status" => 400, "msg" => lang("产品不存在")]);
		}
		if ($host["billingcycle"] != "onetime" && $host["billingcycle"] != "free" && $host["billingcycle"] != "hour" && $host["billingcycle"] != "day") {
			if ($host["nextduedate"] - $host["regdate"] < 3600) {
				if ($host["status"] == "Cancelled") {
					$host["status"] = "Paid";
				} else {
					$host["status"] = "Unpaid";
				}
			} else {
				$host["status"] = "Paid";
			}
		}
		$host["billingcycle_zh"] = config("billing_cycle")[$host["billingcycle"]];
		$host["flags"] = $host["flag"];
		$uid = $this->request->uid;
		$currency_id = priorityCurrency($uid);
		$currency = getUserCurrency($uid);
		$data["currency"] = $currency;
		$product_model = new \app\common\model\ProductModel();
		$cycle = $product_model->getProductCycle($host["productid"], $currency_id, $hid, $host["billingcycle"], $host["amount"], "", "", "", $host["flag"]);
		$cycles = [];
		foreach ($cycle as $k => $v) {
			if (!in_array($v["billingcycle"], ["free", "ontrial"])) {
				$cycles[] = $v;
			}
		}
		$flag = getSaleProductUser($host["productid"], $host["uid"]);
		if (!$flag) {
			if ((new \app\common\logic\Renew())->unchangePrice($hid, $host["billingcycle"], $currency_id) != -1 && round((new \app\common\logic\Renew())->calculatedPrice($hid, $host["billingcycle"]), 2) != round($host["amount"], 2) && $host["promoid"] == 0) {
				$cycles = [];
			}
			if ($host["billingcycle"] != "ontrial" && $host["billingcycle"] != "onetime" && !in_array($host["billingcycle"], array_column($cycles, "billingcycle"))) {
				$cycles[] = ["billingcycle" => $host["billingcycle"], "billingcycle_zh" => $host["billingcycle_zh"], "setup_fee" => 0, "price" => 0, "amount" => $host["amount"], "saleproducts" => 0];
			}
		}
		foreach ($cycles as &$v) {
			if ($v["billingcycle"] != $host["billingcycle"]) {
				$renew = new \app\common\logic\Renew();
				if ((new \app\common\model\ProductModel())->checkProductPrice($host["productid"], $v["billingcycle"], $currency_id)) {
					$amount = $renew->calculatedPrice($hid, $v["billingcycle"], 1, $host["flag"]);
					$v["amount"] = bcsub($amount["price_cycle"], 0, 2);
					$v["saleproducts"] = bcsub($amount["price_sale_cycle"], 0, 2);
				} else {
					$v["amount"] = bcsub($host["amount"], 0, 2);
					$v["saleproducts"] = bcsub(0, 0, 2);
				}
			} else {
				$v["amount"] = bcsub($host["amount"], 0, 2);
				$v["saleproducts"] = bcsub(0, 0, 2);
			}
		}
		$data["cycle"] = $cycles;
		$pay_type = \think\Db::name("products")->where("id", $host["productid"])->value("pay_type");
		$pay_type = json_decode($pay_type, true);
		$data["pay_type"] = $pay_type;
		$data["hostid"] = $hid;
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 产品续费--页面=--充值记录
	 * @description 接口说明: 产品续费--页面=--充值记录
	 * @author wyh
	 * @url host/hostrecharge
	 * @method get
	 * @param .name:hostid type:int require:0  other: desc:产品ID
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @return trans_id:流水单号
	 * @return amount_in:金额
	 * @return pay_time:交易日期
	 * @return type:来源
	 * @return gateway:支付方式
	 * @return amount_out:退款金额(取负数)
	 * @return refund:当refund>0时,表示退款，展示amount_in == amout_out的数据,并取负数
	 */
	public function getHostRecharge()
	{
		$data = [];
		$uid = request()->uid;
		$params = $this->request->only(["limit", "page", "hostid"]);
		$hostid = !empty($params["hostid"]) ? intval($params["hostid"]) : "";
		if (!$hostid) {
			return json(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$gateways = gateway_list();
		$accounts = \think\Db::name("accounts")->alias("a")->field("a.trans_id,a.pay_time,a.gateway,a.refund,a.amount_out,ii.type,a.amount_in")->leftJoin("invoice_items ii", "a.invoice_id = ii.invoice_id")->withAttr("type", function ($value) {
			if ($value == "renew") {
				return "续费";
			} elseif ($value == "host") {
				return "产品";
			} elseif ($value == "upgrade") {
				return "升降级";
			} else {
				return "";
			}
		})->withAttr("gateway", function ($value) use($gateways) {
			foreach ($gateways as $v) {
				if ($v["name"] == $value) {
					return $v["title"];
				}
			}
		})->where("ii.rel_id", $hostid)->where("a.uid", $uid)->where("a.delete_time", 0)->whereIn("ii.type", ["host", "renew", "upgrade"])->select()->toArray();
		$invoices = \think\Db::name("invoice_items")->distinct(true)->field("invoice_id")->where("rel_id", $hostid)->whereIn("type", ["host", "renew"])->select()->toArray();
		$ids = array_column($invoices, "invoice_id") ?? [];
		if (!empty($ids[0])) {
			$credit_log_uses = \think\Db::name("credit")->alias("f")->leftJoin("invoice_items ii", "f.relid = ii.invoice_id")->field("f.id as trans_id,f.create_time as pay_time,f.amount as amount_in,f.description as gateway,ii.type")->withAttr("type", function ($value) {
				if ($value == "renew") {
					return "续费";
				} elseif ($value == "host") {
					return "产品";
				} elseif ($value == "upgrade") {
					return "升降级";
				} else {
					return "";
				}
			})->whereIn("f.relid", $ids)->whereIn("ii.type", ["host", "renew", "upgrade"])->where(function (\think\db\Query $query) use($ids) {
				$query->where("f.description", "like", "%Credit Applied to Invoice #%");
				foreach ($ids as $vv) {
					$query->whereOr("f.description", "Credit Removed from Invoice #{$vv}");
					$query->whereOr("f.description", "Credit Applied to Renew Invoice #{$vv}");
				}
			})->select()->toArray();
			foreach ($credit_log_uses as &$credit_log_use) {
				if (preg_match("/Credit Applied to Invoice #/", $credit_log_use["gateway"])) {
					$credit_log_use["gateway"] = "余额支付";
				} elseif (preg_match("/Credit Removed from Invoice #/", $credit_log_use["gateway"])) {
					$credit_log_use["gateway"] = "移除余额";
				} elseif (preg_match("/Credit Applied to Renew Invoice #/", $credit_log_use["gateway"])) {
					$credit_log_use["gateway"] = "余额支付";
				}
				$credit_log_use["refund"] = 0;
				$credit_log_use["amount_out"] = 0;
				array_push($accounts, $credit_log_use);
			}
		}
		$count = count($accounts);
		$offset = ($page - 1) * $limit;
		$length = $limit;
		$accounts = array_slice($accounts, $offset, $length);
		$data["count"] = $count;
		$data["invoices"] = $accounts;
		$data["currency"] = getUserCurrency($uid);
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 产品续费
	 * @description 接口说明: 产品续费
	 * @author wyh
	 * @url host/renew
	 * @method post
	 * @param  .name:hostid type:number require:1 other: desc:主机id,可传单个值hostid
	 * @param  .name:billingcycles type:number require:0 other: desc:周期
	 */
	public function postRenew()
	{
		$params = $this->request->param();
		$hid = $params["hostid"];
		$billingcycle = isset($params["billingcycles"]) ? $params["billingcycles"] : "";
		$renew = new \app\common\logic\Renew();
		$res = $renew->renew($hid, $billingcycle);
		$payment = \think\Db::name("host")->where("id", $hid)->value("payment");
		$gateway_list = gateway_list("gateways");
		$payment_name_list = array_column($gateway_list, "name");
		$payment = $payment ?: $payment_name_list[0];
		if ($res["status"] == 200 || $res["status"] == 1001) {
			$data["invoiceid"] = $res["data"]["invoice_id"];
			$data["payment"] = $payment;
			return jsons(["status" => 200, "msg" => "续费成功", "data" => $data]);
		} else {
			return jsons($res);
		}
	}
	/**
	 * @title 产品设置自动续费
	 * @description 接口说明: 产品设置自动续费
	 * @author wyh
	 * @url host/autorenew
	 * @method post
	 * @param  .name:hostid type:number require:1 other: desc:主机id,可传单个值hostid
	 * @param  .name:initiative_renew type:number require:0 other: desc:是否自动续费
	 */
	public function postAutoRenew()
	{
		$params = $this->request->param();
		$hid = $params["hostid"];
		$initiative_renew = isset($params["initiative_renew"]) ? $params["initiative_renew"] : 0;
		if (strlen($initiative_renew) > 0) {
			\think\Db::name("host")->where("id", $hid)->update(["initiative_renew" => $initiative_renew]);
		}
		$text = ["关闭", "开启"];
		active_log_final("设置产品-Host ID:{$hid} 的自动续费功能为: {$text[$initiative_renew]}", $params["uid"], 2, $hid, 2);
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 结算批量续费（页面）
	 * @description 接口说明:返回状态码200,没有data.invoiceid:不需要跳转支付，跳转回产品列表页。存在data.invoiceid:跳转支付页面（获取网关支付页面数据 /start_pay）
	 * @author wyh
	 * @time 2020-06-18
	 * @url host/batchrenewpage
	 * @method POST
	 * @param .name:host_ids type:array require:1 default: other: desc:批量续费的产品数组
	 * @param .name:cycles[产品ID] type:array require:0 default: other: desc:(可选参数,第一次不传,在续费页面修改周期时传递此值)批量续费的产品周期:cycles[38] = 'monthly'
	 * @return currency:货币信息
	 * @return hosts:产品信息@
	 * @hosts id:产品ID
	 * @hosts name:名称
	 * @hosts nextduedate
	 * @hosts billingcycle
	 * @hosts amount
	 * @hosts nextduedate_renew
	 * @hosts allow_billingcycle:产品所有可用周期@
	 * @allow_billingcycle billingcycle:周期
	 * @allow_billingcycle billingcycle_zh:
	 * @return total:总价
	 */
	public function postBatchRenewPage()
	{
		$params = $this->request->param();
		$host_ids = $params["host_ids"];
		$cycles_param = isset($params["cycles"]) ? $params["cycles"] : [];
		if (empty($host_ids)) {
			return json(["status" => 400, "msg" => lang("Host_EMPTY")]);
		}
		if (!is_array($host_ids)) {
			return json(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$uid = request()->uid;
		$currency_id = priorityCurrency($uid);
		$currency = (new \app\common\logic\Currencies())->getCurrencies("id,prefix,suffix,code", $currency_id)[0];
		$hosts = \think\Db::name("host")->field("id")->where("uid", $uid)->whereIn("id", $host_ids)->select()->toArray();
		$host_ids = array_column($hosts, "id");
		$host_data = \think\Db::name("host")->alias("a")->field("a.productid,a.dedicatedip,a.uid,a.id,a.domainstatus,b.id as pid,b.name,b.pay_type,b.pay_method,a.nextduedate,a.billingcycle,a.amount,a.flag,b.groupid,a.promoid")->leftJoin("products b", "a.productid = b.id")->whereIn("a.id", $host_ids)->select()->toArray();
		$total = 0;
		$totalsale = 0;
		$host_data_filter = [];
		$billing_cycle = config("billing_cycle");
		foreach ($host_data as $k => $v) {
			if ($v["amount"] >= 0 && !in_array($v["billingcycle"], ["free", "onetime"]) && $v["pay_method"] == "prepayment" && in_array($v["domainstatus"], ["Active", "Suspended"])) {
				$nav_group = \think\Db::name("nav_group")->where("id", $v["groupid"])->find();
				$v["groupn"] = $nav_group;
				$renew_logic = new \app\common\logic\Renew();
				$hid = $v["id"];
				$amounts1 = $v["amount"];
				if ($cycles_param[$hid] && $cycles_param[$hid] != $v["billingcycle"]) {
					$billingcycle = $cycles_param[$hid];
					$s = $renew_logic->calculatedPrice($hid, $billingcycle, 1, $v["flag"]);
					$v["amount"] = $s["price_cycle"];
					$v["saleproducts"] = $s["price_sale_cycle"];
				} else {
					$billingcycle = $v["billingcycle"];
					$v["amount"] = $v["amount"];
					$v["saleproducts"] = 0.0;
				}
				$pay_type = json_decode($v["pay_type"], true);
				$pid = $v["pid"];
				$product_model = new \app\common\model\ProductModel();
				$allow_billingcycle = $product_model->getProductCycle($pid, $currency_id, $hid, $billingcycle, $v["amount"], "", $v["billingcycle"], $amounts1, $v["flag"]);
				foreach ($allow_billingcycle as $kk => $vv) {
					if ($vv["billingcycle"] == "ontrial") {
						unset($allow_billingcycle[$kk]);
					}
					if (empty($cycles_param[$hid])) {
						if ($billingcycle == $vv["billingcycle"]) {
							$v["saleproducts"] = $vv["saleproducts"];
							$totalsale = bcadd($totalsale, $v["saleproducts"], 2);
							break;
						}
					} else {
						if ($cycles_param[$hid] == $vv["billingcycle"]) {
							$v["saleproducts"] = $vv["saleproducts"];
							$totalsale = bcadd($totalsale, $vv["saleproducts"], 2);
						}
					}
					$allow_billingcycle[$kk]["flags"] = $v["flag"];
				}
				if ($billingcycle == "ontrial") {
					$billingcycle = $allow_billingcycle[0]["billingcycle"] ?? "";
					$s = $renew_logic->calculatedPrice($hid, $billingcycle, 1, $v["flag"]);
					$v["amount"] = $s["price_cycle"];
					$v["saleproducts"] = $s["price_sale_cycle"];
				}
				$cycles = [];
				foreach ($allow_billingcycle as $kk => $vv) {
					if (!in_array($vv["billingcycle"], ["free", "ontrial"])) {
						$cycles[] = $vv;
					}
				}
				$flag = getSaleProductUser($v["pid"], $v["uid"]);
				if (!$flag) {
					if ((new \app\common\logic\Renew())->unchangePrice($hid, $billingcycle, $currency_id) != -1 && round((new \app\common\logic\Renew())->calculatedPrice($hid, $billingcycle), 2) != round($v["amount"], 2) && $v["promoid"] == 0) {
						$cycles = [];
					}
					if (!in_array($billingcycle, array_column($cycles, "billingcycle"))) {
						$cycles[] = ["billingcycle" => $billingcycle, "billingcycle_zh" => $billing_cycle[$billingcycle], "setup_fee" => 0, "price" => 0, "amount" => $v["amount"], "saleproducts" => 0];
					}
					$allow_billingcycle = $cycles;
				}
				if ($billingcycle == "onetime" || $billingcycle == "free") {
					$next_time = 0;
				} else {
					$next_time = getNextTime($billingcycle, $pay_type["pay_" . $billingcycle . "_cycle"], $v["nextduedate"], $pay_type["pay_ontrial_cycle_type"] ?: "day");
				}
				$total = bcadd($total, $v["amount"], 2);
				$v["billingcycle"] = $billingcycle;
				$v["nextduedate_renew"] = $next_time;
				$v["allow_billingcycle"] = $allow_billingcycle;
				unset($v["domainstatus"]);
				unset($v["pay_method"]);
				unset($v["pid"]);
				unset($v["pay_type"]);
				if ($v["flag"] == 1) {
					$v["flags"] = 1;
				} else {
					$v["flags"] = 0;
				}
				$host_data_filter[] = $v;
			}
		}
		$data = [];
		$data["currency"] = $currency;
		$data["hosts"] = $host_data_filter;
		$data["total"] = $total;
		$data["totalsale"] = $totalsale;
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 结算批量续费（下单）
	 * @description 接口说明:返回状态码200,没有data.invoiceid:不需要跳转支付，跳转回产品列表页。存在data.invoiceid:跳转支付页面（获取网关支付页面数据 /start_pay）
	 * @author wyh
	 * @time 2020-06-18
	 * @url host/batchrenew
	 * @method POST
	 * @param .name:host_ids type:array require:1 default: other: desc:批量续费的产品数组
	 * @param .name:cycles[产品ID] type:array require:1 default: other: desc:相应周期数组 change_cycle[38] = 'monthly'
	 * @return invoiceid:(跳转到网关支付页面，可不携带支付方式)
	 */
	public function postBatchRenew()
	{
		if ($this->request->isPost()) {
			$params = $this->request->param();
			$host_ids = $params["host_ids"];
			$billincycles = $params["cycles"];
			$renew_logci = new \app\common\logic\Renew();
			$res = $renew_logci->batchRenew($host_ids, $billincycles);
			if ($res["status"] == 200 || $res["status"] == 1001) {
				$uid = \request()->uid;
				$payment = \think\Db::name("clients")->where("id", $uid)->value("defaultgateway");
				if (!$payment) {
					$gateway_list = gateway_list("gateways");
					$payment_name_list = array_column($gateway_list, "name");
					$payment = $payment_name_list[0];
				}
				$res["data"]["payment"] = $payment;
			}
			return json($res);
		}
		return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 按小时/天的产品续费（支付本周期费用）
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/hourdayrenew
	 * @method POST
	 * @param .name:host_id type:number require:1 default: other: desc:主机id
	 * @param .name:settlement type:number require:0 default:0 other: desc:0/1, 1：结算，返回账单id，跳转通用支付页
	 * @return hostname_desc:产品描述
	 * @return domainstatus:产品状态
	 * @return expire_date:当前到期时间
	 * @return cost:费用
	 * @return cost_desc:费用
	 * @return invoiceid:当传递settlement为1，结算时返回账单id，跳转到通用账单支付页面
	 */
	public function postHourDayRenew(\think\Request $request)
	{
		$uid = $request->uid;
		$param = $request->param();
		$host_id = intval($param["host_id"]);
		$currency = getUserCurrency($uid);
		$prefix = $currency["prefix"];
		$suffix = $currency["suffix"];
		$settlement = $param["settlement"];
		if (empty($host_id)) {
			return json(["status" => 406, "msg" => "产品未找到"]);
		}
		$host_data = \think\Db::name("host")->where("uid", $uid)->find($host_id);
		if (empty($host_data)) {
			return json(["status" => 406, "msg" => "产品未找到"]);
		}
		$domainstatus = $host_data["domainstatus"];
		$billingcycle = $host_data["billingcycle"];
		if (!in_array($domainstatus, ["Active", "Suspended"])) {
			return json(["status" => 406, "msg" => "产品状态需要为激活或暂停时才能操作"]);
		}
		if (!in_array($billingcycle, ["hour", "day"])) {
			return json(["status" => 406, "msg" => "产品周期需要为小时/天"]);
		}
		$productid = $host_data["productid"];
		$amount = $host_data["amount"];
		$billingcycle = $host_data["billingcycle"];
		$product_data = \think\Db::name("products")->find($productid);
		$returndata = [];
		if ($host_data["dedicatedip"]) {
			$returndata["hostname_desc"] = $product_data["name"] . " - " . $host_data["dedicatedip"];
		} else {
			$returndata["hostname_desc"] = $product_data["name"];
		}
		$returndata["expire_date"] = $host_data["nextduedate"];
		$returndata["domainstatus"] = $host_data["domainstatus"];
		$support_cycle = getSupportedCycles($productid, $currency["id"]);
		$settle_cycle = $support_cycle["cycle_condition"][$billingcycle]["cycle"] ?: 1;
		$unit = config("billing_cycle")["day"];
		$returndata["renewal_time"] = $settle_cycle . $unit;
		$returndata["cost"] = $amount;
		$returndata["cost_desc"] = $prefix . $amount . $suffix;
		if ($settlement) {
			$time = time();
			\think\Db::startTrans();
			try {
				$exists_data = \think\Db::name("invoice_items")->where("uid", $uid)->where("type", "renew")->where("rel_id", $host_id)->where("delete_time", "=", 0)->find();
				if (!empty($exists_data)) {
					\think\Db::name("invoices")->where("id", $exists_data["invoice_id"])->update(["delete_time" => $time]);
					\think\Db::name("invoice_items")->where("id", $exists_data["id"])->update(["delete_time" => $time]);
				}
				$insert_invoice_data = ["uid" => $uid, "create_time" => $time, "due_time" => strtotime("+1 hour"), "subtotal" => $amount, "total" => $amount, "status" => "Unpaid"];
				$r1 = \think\Db::name("invoices")->insertGetId($insert_invoice_data);
				$insert_item_data = ["invoice_id" => $r1, "uid" => $uid, "type" => "renew", "rel_id" => $host_id, "description" => $product_data["name"] . "续费，时长：" . $returndata["renewal_time"], "amount" => $amount, "due_time" => strtotime("+1 hour")];
				$r2 = \think\Db::name("invoice_items")->insertGetId($insert_item_data);
				\think\Db::commit();
				return json(["status" => 200, "msg" => "生成订单成功", "data" => ["invoiceid" => $r1]]);
			} catch (\think\Exception $e) {
				\think\Db::rollback();
				return json(["status" => 406, "msg" => "生成订单失败"]);
			}
		}
		return json(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 试用/小时/天 转包年包月
	 * @description 接口说明:
	 * @author 萧十一郎
	 * @url host/cycletomonyear
	 * @method POST
	 * @param .name:host_id type:number require:1 default: other: desc:主机id
	 * @param .name:change_cycle type:string require:0 default: other: desc:相应周期数组 change_cycle = 'monthly'
	 * @param .name:settlement type:number require:0 default:0 other: desc:0/1, 1：结算，返回账单id，跳转通用支付页
	 * @return hostname_desc:产品描述
	 * @return domainstatus:产品状态
	 * @return expire_date:当前到期时间
	 * @return cycle_desc_data:支持的转换周期
	 * @return cost:费用
	 * @return cost_desc:费用
	 * @return invoiceid:当传递settlement为1，结算时返回账单id，跳转到通用账单支付页面
	 */
	public function postCycleToMonYear(\think\Request $request)
	{
		$uid = $request->uid;
		$param = $request->param();
		$host_id = intval($param["host_id"]);
		$currency = getUserCurrency($uid);
		$prefix = $currency["prefix"];
		$suffix = $currency["suffix"];
		$settlement = intval($param["settlement"]);
		$change_cycle = $param["change_cycle"];
		$check_res = $this->checkHostCycle($uid, $host_id);
		if ($check_res["status"] == 200) {
			$host_data = $check_res["data"];
		} else {
			return json($check_res);
		}
		$productid = $host_data["productid"];
		$amount = $host_data["amount"];
		$billingcycle = $host_data["billingcycle"];
		$product_data = \think\Db::name("products")->find($productid);
		$returndata = [];
		if ($host_data["dedicatedip"]) {
			$returndata["hostname_desc"] = $product_data["name"] . " - " . $host_data["dedicatedip"];
		} else {
			$returndata["hostname_desc"] = $product_data["name"];
		}
		$returndata["expire_date"] = $host_data["nextduedate"];
		$returndata["domainstatus"] = $host_data["domainstatus"];
		$support_cycle = getSupportedCycles($productid, $currency["id"], 0);
		$returndata["cycle_desc_data"] = $support_cycle["cycle_desc_data"];
		$cost = 0;
		if (!empty($change_cycle) && in_array($change_cycle, $support_cycle["cycle_data"])) {
			if ($billingcycle == "hour" || $billingcycle == "day") {
				$cost += $amount;
			}
			$price_logic = new \app\common\logic\Pricing();
			$new_recurring_amout = $price_logic->calculatedPrice($host_id, $change_cycle);
			$cost += $new_recurring_amout;
			if ($settlement) {
				$time = time();
				\think\Db::startTrans();
				try {
					$exists_data = \think\Db::name("invoice_items")->where("rel_id", $host_id)->where("uid", $uid)->where("type", "cycle_to_mon_year")->where("delete_time", "=", 0)->find();
					if (!empty($exists_data)) {
						\think\Db::name("invoices")->where("id", $exists_data["invoice_id"])->where("status", "<>", "Paid")->update(["delete_time" => $time]);
						\think\Db::name("invoice_items")->where("id", $exists_data["id"])->update(["delete_time" => $time]);
						\think\Db::name("renew_cycle")->where("relid", $exists_data["id"])->where("paid", "=", "N")->update(["delete_time" => $time]);
					}
					$insert_invoice_data = ["uid" => $uid, "create_time" => $time, "due_time" => strtotime("+1 hour"), "subtotal" => $cost, "total" => $cost, "status" => "Unpaid"];
					$r1 = \think\Db::name("invoices")->insertGetId($insert_invoice_data);
					$insert_item_data = ["invoice_id" => $r1, "uid" => $uid, "type" => "cycle_to_mon_year", "rel_id" => $host_id, "description" => $product_data["name"] . "，付款周期转为" . config("billing_cycle")[$change_cycle], "amount" => $cost, "due_time" => strtotime("+1 hour")];
					$r2 = \think\Db::name("invoice_items")->insertGetId($insert_item_data);
					$renew_cycle_data = ["uid" => $uid, "type" => "cycle_to_mon_year", "relid" => $r2, "new_cycle" => $change_cycle, "new_recurring_amout" => $new_recurring_amout, "status" => "Pending", "paid" => "N", "create_time" => $time, "expire_time" => strtotime("+1 hour")];
					$r3 = \think\Db::name("renew_cycle")->insertGetId($renew_cycle_data);
					\think\Db::commit();
					return json(["status" => 200, "msg" => "下单成功，跳转支付", "data" => ["invoiceid" => $r1]]);
				} catch (\think\Exception $e) {
					\think\Db::rollback();
					return json(["status" => 406, 0 => "下单失败，请检查"]);
				}
			}
		}
		$returndata["cost"] = $cost;
		$returndata["cost_desc"] = $prefix . $cost . $suffix;
		return json(["status" => 406, "data" => $returndata]);
	}
	private function checkHostCycle($uid, $host_id)
	{
		if (empty($host_id)) {
			return json(["status" => 406, "msg" => "产品未找到"]);
		}
		$host_data = \think\Db::name("host")->field("productid,payment,amount,billingcycle,nextduedate,domainstatus,dedicatedip")->where("uid", $uid)->where("id", $host_id)->find();
		if (empty($host_data)) {
			return ["status" => 406, "msg" => "产品未找到"];
		}
		if (!in_array($host_data["billingcycle"], ["ontrial", "hour", "day"])) {
			return ["status" => 406, "msg" => "产品周期需要为试用，小时，天的产品才可转为包年包月"];
		}
		if (!in_array($host_data["domainstatus"], ["Active", "Suspended"])) {
			return ["status" => 406, "msg" => "产品状态需要为激活/暂停时才可操作"];
		}
		return ["status" => 200, "data" => $host_data];
	}
	/**
	 * @title 请求取消页面
	 * @description 接口说明:请求取消页面
	 * @author 萧十一郎
	 * @url host/cancelpage
	 * @method GET
	 * @param .name:id type:int require:1 default: other: desc:申请取消的产品id
	 * @return info:显示在页面上的提示信息
	 * @return reason:默认两个原因,可选，可填
	 * @return type:类型，Immediate(立即)，Endofbilling(等待账单周期结束)。写死在页面上
	 */
	public function getCancelPage(\think\Request $request)
	{
		$id = intval($request->id);
		$uid = $request->uid;
		if (empty($id)) {
			return json(["status" => 406, "msg" => "未找到该产品"]);
		}
		$host_data = \think\Db::name("host")->field("h.productid,h.billingcycle,h.domainstatus,h.dedicatedip,p.name as productname,g.name as groupname")->alias("h")->leftJoin("products p", "p.id = h.productid")->leftJoin("product_groups g", "g.id=p.gid")->where("h.id", $id)->where("h.uid", $uid)->find();
		if (empty($host_data)) {
			return json(["status" => 406, "msg" => "未找到该产品"]);
		}
		if (!in_array($host_data["domainstatus"], ["Active", "Suspended"])) {
			return json(["status" => 406, "msg" => "产品为已激活或者暂停的产品才能申请取消"]);
		}
		$info = "";
		$info .= "请求取消:" . $host_data["groupname"] . " - " . $host_data["productname"];
		if (!empty($host_data["dedicatedip"])) {
			$info .= "(" . $host_data["dedicatedip"] . ")";
		}
		$returndata = [];
		$returndata["info"] = $info;
		$reason = [];
		$cancel_reason = \think\Db::name("cancel_reason")->select()->toArray();
		foreach ($cancel_reason as $key => $value) {
			$reason[] = $value["reason"];
		}
		$returndata["reason"] = $reason;
		return jsons(["status" => 200, "msg" => "", "data" => $returndata]);
	}
	/**
	 * @title 提交请求取消请求
	 * @description 接口说明:提交请求取消请求
	 * @author 萧十一郎
	 * @url host/cancel
	 * @method POST
	 * @param .name:id type:int require:1 default: other: desc:申请取消的产品id
	 * @param .name:type type:string require:1 default: other: desc:Immediate(立即),Endofbilling(等待账单周期结束)
	 * @param .name:reason type:string require:1 default: other: desc:申请取消该产品的描述信息
	 * @return  other:成功后的描述信息：感謝您。 您的撤銷申請已經被送出，我們將盡速處理。若您不慎誤操作本功能，請立即建立服務單告知我們取消本次撤銷申請，以免您的服務帳戶被移除。
	 */
	public function postCancel(\think\Request $request)
	{
		$param = $request->param();
		$id = intval($param["id"]);
		$uid = $request->uid;
		$type = $param["type"];
		$reason = $param["reason"];
		if (empty($id)) {
			return json(["status" => 406, "msg" => "未找到该产品"]);
		}
		if (!in_array($type, ["Immediate", "Endofbilling"])) {
			return json(["status" => 406, "msg" => "取消类型错误"]);
		}
		if (empty($reason)) {
			return json(["status" => 406, "msg" => "您必须提交申请撤销的理由"]);
		}
		$host_data = \think\Db::name("host")->field("id,domainstatus,productid")->where("id", $id)->where("uid", $uid)->find();
		if (empty($host_data)) {
			return json(["status" => 406, "msg" => "产品未找到"]);
		}
		if (!in_array($host_data["domainstatus"], ["Active", "Suspended"])) {
			return json(["status" => 406, "msg" => "产品为已激活或者暂停的产品才能申请取消"]);
		}
		$product_data = \think\Db::name("products")->where("id", $host_data["productid"])->find();
		if (!$product_data["cancel_control"]) {
			return json(["status" => 406, "msg" => "产品不能申请取消"]);
		}
		$cancel_data = \think\Db::name("cancel_requests")->where("relid", $id)->where("delete_time", 0)->find();
		if (!empty($cancel_data)) {
			return json(["status" => 406, "msg" => "已存在该产品的取消请求"]);
		}
		$udata = ["relid" => $id, "type" => $type ?? "Immediate", "reason" => $reason, "create_time" => time()];
		\think\facade\Hook::listen("cancellation_request", ["uid" => $uid, "relid" => $id, "reason" => $reason, "type" => $type]);
		$cancelid = \think\Db::name("cancel_requests")->insertGetId($udata);
		if (!empty($cancelid)) {
			active_log_final("产品 #Host ID:{$id}进行停用", $uid, 2, $id, 2);
			return json(["status" => 200, "msg" => "请求成功"]);
		} else {
			return json(["status" => 406, "msg" => "请求失败，请重试或联系客服"]);
		}
	}
	/**
	 * @title 删除 产品取消请求
	 * @description 接口说明:删除 产品取消请求
	 * @author wyh
	 * @url host/cancel
	 * @method DELETE
	 * @param .name:id type:int require:1 default: other: desc:产品id
	 */
	public function deleteCancel()
	{
		$param = $this->request->param();
		$hid = intval($param["id"]);
		\think\Db::name("cancel_requests")->where("relid", $hid)->where("delete_time", 0)->delete();
		active_log_final("产品 #Host ID:{$hid} 取消停用请求成功", $param["uid"], 2, $hid, 2);
		return jsons(["status" => 200, "msg" => lang("取消停用请求成功")]);
	}
	/**
	 * @time 2020-07-12
	 * @title 获取用量信息
	 * @description 获取用量信息
	 * @url host/trafficusage
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:start type:string require:0 desc:开始日期(YYYY-MM-DD)
	 * @param   .name:end type:string require:0 desc:结束日期(YYYY-MM-DD)
	 * @return  0:流量数据@
	 * @0  time:横坐标值
	 * @0  value:纵坐标值(单位Mbps)
	 */
	public function getTrafficUsage(\think\Request $request)
	{
		$id = input("get.id");
		$uid = $request->uid;
		$host = \think\Db::name("host")->alias("a")->field("a.regdate,a.serverid,a.dcimid,b.type,b.api_type,b.zjmf_api_id,b.config_option1")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $uid)->where("a.id", $id)->whereIn("domainstatus", "Active,Suspended")->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		$end = input("get.end");
		$start = input("get.start");
		$end = strtotime($end) ? date("Y-m-d", strtotime($end)) : date("Y-m-d");
		$start = strtotime($start) ? date("Y-m-d", strtotime($start)) : date("Y-m-d", strtotime("-30 days"));
		if (str_replace("-", "", $start) < str_replace("-", "", date("Y-m-d", $host["regdate"]))) {
			$start = date("Y-m-d", $host["regdate"]);
		}
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$post_data["start"] = $start;
			$post_data["end"] = $end;
			$result = zjmfCurl($host["zjmf_api_id"], "/host/trafficusage", $post_data, 30, "GET");
		} else {
			if ($host["type"] == "dcim") {
				$dcim = new \app\common\logic\Dcim();
				$result = $dcim->getTrafficUsage($id, $start, $end);
			} elseif ($host["type"] == "dcimcloud") {
				$dcimcloud = new \app\common\logic\DcimCloud();
				$result = $dcimcloud->getTrafficUsage($id, $start, $end);
			} else {
				$provision = new \app\common\logic\Provision();
				$result = $provision->trafficUsage($id, $start, $end);
			}
		}
		return json($result);
	}
	/**
	 * @time 2020-08-27
	 * @title 二次验证页面
	 * @description 二次验证页面
	 * @url host/secondverifypage
	 * @method  POST
	 * @author wyh
	 * @version v1
	 * @param   .name:type type:int require:1 desc:验证方式：email、phone等
	 * @param   .name:type type:int require:1 desc:验证方式：email、phone等
	 */
	public function postSecondVerify()
	{
		return 1;
	}
	/**
	 * 时间 2020-09-30
	 * @title 关联下游ID
	 * @description 关联下游ID
	 * @url host/setdownstream
	 * @method  POST
	 * @author hh
	 * @version v1
	 */
	public function postSetDownStream(\think\Request $request)
	{
		$uid = $request->uid;
		$params = input("post.");
		$host = \think\Db::name("host")->where("id", $params["id"])->where("uid", $uid)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "产品ID错误";
			return json($result);
		}
		if ($host["productid"] != $params["pid"]) {
			$result["status"] = 406;
			$result["msg"] = "不是同一商品";
			return json($result);
		}
		if ((strpos($params["downstream_url"], "https://") === 0 || strpos($params["downstream_url"], "http://") === 0) && strlen($params["downstream_token"]) == 32 && is_numeric($params["downstream_id"])) {
			$stream_info = json_decode($host["stream_info"], true) ?: [];
			$stream_info["downstream_url"] = $params["downstream_url"];
			$stream_info["downstream_token"] = $params["downstream_token"];
			$stream_info["downstream_id"] = $params["downstream_id"];
			\think\Db::name("host")->where("id", $params["id"])->update(["stream_info" => json_encode($stream_info)]);
			$result["status"] = 200;
			$result["msg"] = "关联成功";
		} else {
			$result["status"] = 400;
			$result["msg"] = "参数错误";
		}
		return json($result);
	}
	/**
	 * @title 产品内页数据
	 * @description 接口说明:
	 * @author wyh
	 * @time 2020-12-16
	 * @url host/dedicatedserver
	 * @method GET
	 * @param .name:host_id type:int require:1 default: other: desc:产品id
	 * @return host_data:基础数据@
	 * @host_data  ordernum:订单id
	 * @host_data  productid:产品id
	 * @host_data  serverid:服务器id
	 * @host_data  regdate:产品开通时间
	 * @host_data  domain:主机名
	 * @host_data  payment:支付方式
	 * @host_data  firstpaymentamount:首付金额
	 * @host_data  firstpaymentamount_desc:首付金额
	 * @host_data  amount:续费金额
	 * @host_data  amount_desc:续费金额
	 * @host_data  billingcycle:付款周期
	 * @host_data  billingcycle_desc:付款周期
	 * @host_data  nextduedate:到期时间
	 * @host_data  nextinvoicedate:下次帐单时间
	 * @host_data  dedicatedip:独立ip
	 * @host_data  assignedips:附加ip
	 * @host_data  ip_num:IP数量
	 * @host_data  domainstatus:产品状态
	 * @host_data  domainstatus_desc:产品状态
	 * @host_data  username:服务器用户名
	 * @host_data  password:服务器密码
	 * @host_data  suspendreason:暂停原因
	 * @host_data  auto_terminate_end_cycle:是否到期取消
	 * @host_data  auto_terminate_reason:取消原因
	 * @host_data  productname:产品名
	 * @host_data  groupname:产品组名
	 * @host_data  bwusage:当前使用流量
	 * @host_data  bwlimit:当前使用流量上限(0表示不限)
	 * @host_data  os:操作系统
	 * @host_data  port:端口
	 * @host_data  remark:备注
	 * @host_data  allow_upgrade_config:是否输出“升级配置项”按钮：1是
	 * @host_data  allow_upgrade_product:是否输出“升级产品”按钮：1是
	 * @host_data  show_traffic_usage:是否显示用量图
	 * @return config_options:可配置选项@
	 * @config_options  name:配置名
	 * @config_options  sub_name:配置项值
	 * @return custom_field_data:自定义字段@
	 * @custom_field_data  fieldname:字段名
	 * @custom_field_data  value:字段值
	 * @return download_data:可下载数据@
	 * @download_data  id:文件id
	 * @title  id:文件标题
	 * @down_link  id:下载链接
	 * @location  id:文件名
	 * @return module_button:模块按钮@
	 * @module_button  type:default:默认,custom:自定义
	 * @module_button  type:func:函数名
	 * @module_button  type:name:名称
	 * @return module_client_area:模块页面输出@
	 * @module_client_area  key:键值用于获取内容
	 * @module_client_area  name:名称
	 * @return hook_output:钩子在本页面的输出，数组，循环显示的html
	 * @return dcim.flowpacket:当前产品可购买的流量包@
	 * @dcim.flowpacket  id:流量包ID
	 * @dcim.flowpacket  name:流量包名称
	 * @dcim.flowpacket  price:价格
	 * @dcim.flowpacket  sale_times:销售次数
	 * @dcim.flowpacket  stock:库存(0不限)
	 * @return dcim.auth:服务器各种操作权限控制(on有权限off没权限)
	 * @return dcim.area_code:区域代码
	 * @return dcim.area_name:区域名称
	 * @return dcim.os_group:操作系统分组@
	 * @dcim.os_group  id:分组ID
	 * @dcim.os_group  name:分组名称
	 * @dcim.os_group  svg:分组svg号
	 * @return dcim.os:操作系统数据@
	 * @dcim.os  id:操作系统ID
	 * @dcim.os  name:操作系统名称
	 * @dcim.os  ostype:操作系统类型(1windows0linux)
	 * @dcim.os  os_name:操作系统真实名称(用来判断具体的版本和操作系统)
	 * @dcim.os  group_id:所属分组ID
	 * @return  flow_packet_use_list:流量包使用情况@
	 * @flow_packet_use_list  name:流量包名称
	 * @flow_packet_use_list  capacity:流量包大小
	 * @flow_packet_use_list  price:价格
	 * @flow_packet_use_list  pay_time:支付时间
	 * @flow_packet_use_list  used:已用流量
	 * @flow_packet_use_list  leave:剩余流量
	 * @return  cloud_os:云操作系统@
	 * @cloud_os  id:操作系统ID
	 * @cloud_os  name:名称
	 * @cloud_os  group:分组id
	 * @return  cloud_os_group:云操作系统分组@
	 * @cloud_os_group  id:分组id
	 * @cloud_os_group  name:分组名称
	 * @return  system_config.company_name:系统公司名
	 * @return  dcimcloud.nat_acl:远程地址
	 * @return  dcimcloud.nat_web:建站解析
	 * @return  module_power_status:是否请求电源状态
	 */
	public function getDedicatedServer(\think\Request $request)
	{
		$uid = $request->uid;
		$host_id = $request->host_id;
		if (empty($host_id)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$host_exists = \think\Db::name("host")->where("uid", $uid)->where("id", $host_id)->find();
		if (empty($host_exists)) {
			return json(["status" => 406, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$returndata = [];
		$host_data = \think\Db::name("host")->field("h.orderid,h.initiative_renew,h.productid,h.serverid,h.regdate,h.domain,h.payment,p.groupid,h.promoid,
                h.firstpaymentamount,h.amount,h.billingcycle,h.nextduedate,h.nextinvoicedate,
                h.dedicatedip,h.assignedips,h.domainstatus,h.username,h.password,h.suspendreason,p.id as pid,
                h.auto_terminate_end_cycle,h.auto_terminate_reason,h.bwusage,h.bwlimit,h.os,h.remark,h.dcimid,h.dcim_area,h.dcim_os,h.port,p.type,p.name as productname,p.pay_method as payment_type,p.config_options_upgrade,p.api_type,p.zjmf_api_id,p.upstream_price_type,p.upstream_price_value,p.upper_reaches_id,p.config_option1,g.name as groupname,o.ordernum,p.api_type")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("product_groups g", "g.id=p.gid")->leftJoin("orders o", "o.id=h.orderid")->where("h.id", $host_id)->find();
		$grou = \think\Db::name("nav_group")->where("id", $host_data["groupid"])->find();
		$host_data["group"] = $grou;
		$domainstatus_config = config("domainstatus");
		$currency = getUserCurrency($uid);
		$billing_cycle = config("billing_cycle");
		$upgrade_logic = new \app\common\logic\Upgrade();
		if ($upgrade_logic->judgeUpgradeConfig($host_id)) {
			$host_data["allow_upgrade_config"] = 1;
		} else {
			$host_data["allow_upgrade_config"] = 0;
		}
		if ($upgrade_logic->judgeUpgradeConfig($host_id, "product")) {
			$host_data["allow_upgrade_product"] = 1;
		} else {
			$host_data["allow_upgrade_product"] = 0;
		}
		$code = \think\Db::name("promo_code")->where("id", $host_data["promoid"])->value("code");
		$host_data["promo_code"] = $code ?? "";
		$host_data["suspendreason_type"] = explode("-", $host_data["suspendreason"])[0] ? explode("-", $host_data["suspendreason"])[0] : "";
		$host_data["suspendreason"] = explode("-", $host_data["suspendreason"])[1] ? explode("-", $host_data["suspendreason"])[1] : "";
		$host_data["assignedips"] = !empty($host_data["assignedips"]) ? explode(",", $host_data["assignedips"]) : [];
		$host_data["domainstatus_desc"] = $domainstatus_config[$host_data["domainstatus"]];
		$host_data["password"] = cmf_decrypt($host_data["password"]);
		$host_data["firstpaymentamount_desc"] = $currency["prefix"] . $host_data["firstpaymentamount"] . $currency["suffix"];
		$host_data["amount_desc"] = $currency["prefix"] . $host_data["amount"] . $currency["suffix"];
		$host_data["billingcycle_desc"] = $billing_cycle[$host_data["billingcycle"]];
		$host_data["ip_num"] = count($host_data["assignedips"]);
		$host_data["bwusage"] = round($host_data["bwusage"], 2);
		$host_data["remark"] = html_entity_decode($host_data["remark"]);
		foreach (gateway_list() as $v) {
			if ($v["name"] == $host_data["payment"]) {
				$payment_zh = $v["title"];
			}
		}
		$host_data["payment_zh"] = $payment_zh ?? "";
		$returndata["host_data"] = $host_data;
		$productid = $host_data["productid"];
		$domainstatus = $host_data["domainstatus"];
		$returndata["server_data"] = "";
		$returndata["module_button"] = ["control" => [], "console" => []];
		$returndata["module_client_area"] = [];
		$returndata["module_chart"] = [];
		$returndata["module_client_main_area"] = [];
		$returndata["module_power_status"] = false;
		$returndata["reinstall_random_port"] = false;
		$upstream_data = [];
		if ($host_data["api_type"] == "zjmf_api") {
			$returndata["host_data"]["serverid"] = $returndata["host_data"]["zjmf_api_id"];
			$upstream_data = zjmfCurl($host_data["zjmf_api_id"], "/host/header", ["host_id" => $host_data["dcimid"]], 30, "GET");
			if ($upstream_data["status"] == 200) {
				$upstream_data = $upstream_data["data"];
			} else {
				$upstream_data = [];
			}
			$returndata["module_button"]["control"] = $upstream_data["module_button"]["control"] ?: [];
			$returndata["module_button"]["console"] = $upstream_data["module_button"]["console"] ?: [];
			$returndata["module_client_area"] = $upstream_data["module_client_area"] ?: [];
			$returndata["module_chart"] = $upstream_data["module_chart"] ?: [];
			$returndata["module_client_main_area"] = $upstream_data["module_client_main_area"] ?: [];
			$returndata["dcimcloud"]["nat_acl"] = $upstream_data["dcimcloud"]["nat_acl"] ?: "";
			$returndata["dcimcloud"]["nat_web"] = $upstream_data["dcimcloud"]["nat_web"] ?: "";
			$returndata["module_power_status"] = \boolval($upstream_data["module_power_status"]);
			$returndata["reinstall_random_port"] = \boolval($upstream_data["reinstall_random_port"]);
		} elseif ($host_data["api_type"] == "manual") {
			$UpperReaches = new \app\common\logic\UpperReaches();
			$returndata["module_power_status"] = $UpperReaches->modulePowerStatus($host_id);
			$returndata["module_button"] = $UpperReaches->moduleClientButton($host_id);
			$upper_reaches = \think\Db::name("zjmf_finance_api")->where("id", $host_data["upper_reaches_id"])->find();
			$returndata["manual"] = ["id" => $host_data["upper_reaches_id"], "name" => $upper_reaches["name"]];
			$upper_reaches_res = \think\Db::name("upper_reaches_res")->where("hid", $host_id)->find();
			$returndata["host_data"]["upper_reaches_res"] = $upper_reaches_res["id"] ?? "";
			$returndata["host_data"]["upper_reaches_control_mode"] = $upper_reaches_res["control_mode"] ?? "";
		} else {
			$provision_logic = new \app\common\logic\Provision();
			if ($host_data["domainstatus"] == "Active") {
				if ($host_data["type"] == "dcimcloud") {
					$dcimcloud = new \app\common\logic\DcimCloud();
					$returndata["module_button"] = $dcimcloud->moduleClientButton($host_data["dcimid"]);
					$returndata["module_client_area"] = $dcimcloud->moduleClientArea($host_id);
					$returndata["module_chart"] = $dcimcloud->chart($host_data["dcimid"], $host_id);
					$returndata["module_power_status"] = true;
					$nat_info = $dcimcloud->getNatInfo($host_id);
					$returndata["dcimcloud"]["nat_acl"] = $nat_info["nat_acl"] ?: "";
					$returndata["dcimcloud"]["nat_web"] = $nat_info["nat_web"] ?: "";
					$returndata["reinstall_random_port"] = $dcimcloud->supportReinstallRandomPort($host_id);
				} elseif ($host_data["type"] == "dcim") {
					if ($host_data["config_option1"] == "bms") {
						$dcim = new \app\common\logic\Dcim();
						$returndata["module_button"] = $dcim->moduleClientButton($host_data["dcimid"]);
						$returndata["module_client_area"] = $dcim->moduleClientArea($host_id);
						$returndata["module_power_status"] = true;
					} else {
						$returndata["module_power_status"] = true;
					}
				} else {
					$module_button = $provision_logic->clientButtonOutput($host_id);
					$module_client_area = $provision_logic->clientArea($host_id);
					$returndata["module_button"] = $module_button;
					$returndata["module_client_area"] = $module_client_area;
					$returndata["module_chart"] = $provision_logic->chart($host_id);
					$returndata["module_power_status"] = $provision_logic->checkDefineFunc($host_id, "Status");
					$returndata["module_client_main_area"] = $provision_logic->clientAreaMainOutput($host_id);
				}
			}
		}
		if ($host_data["api_type"] == "zjmf_api") {
			$returndata["host_data"]["show_traffic_usage"] = $upstream_data["host_data"]["show_traffic_usage"] ? true : false;
		} elseif ($host_data["api_type"] == "manual") {
			$returndata["host_data"]["show_traffic_usage"] = false;
		} else {
			if ($host_data["bwlimit"] > 0) {
				if ($host_data["type"] == "dcim") {
					if ($host_data["api_type"] == "whmcs") {
						$returndata["host_data"]["show_traffic_usage"] = false;
					} else {
						$returndata["host_data"]["show_traffic_usage"] = $host_data["config_option1"] != "bms";
					}
				} elseif ($host_data["type"] == "dcimcloud") {
					$returndata["host_data"]["show_traffic_usage"] = true;
				} else {
					$returndata["host_data"]["show_traffic_usage"] = $provision_logic->checkDefineUsage($host_id);
				}
			} else {
				$returndata["host_data"]["show_traffic_usage"] = false;
			}
		}
		$returndata["hook_output"] = hook("client_product_details_output", ["host_id" => $host_id]);
		$returndata["currency"] = $currency;
		$upgrade_products_data = \think\Db::name("product_upgrade_products")->where("product_id", $productid)->select()->toArray();
		if (!empty($upgrade_products_data)) {
			$system_button["upgrade"] = ["name" => lang("UPGRADE_DOWNGRADE"), "func" => "upgrade"];
			if ($domainstatus == "Active") {
				$system_button["upgrade"]["disabled"] = false;
			} else {
				$system_button["upgrade"]["disabled"] = true;
			}
		}
		if ($host_data["config_options_upgrade"] == 1) {
			$system_button["upgrade_option"] = ["name" => lang("UPGRADE_DOWNGRADE_OPTIONS"), "func" => "upgrade_option"];
			if ($domainstatus == "Active") {
				$system_button["upgrade_option"]["disabled"] = false;
			} else {
				$system_button["upgrade_option"]["disabled"] = true;
			}
		}
		if ($domainstatus == "Active" && $host_data["payment_type"] == "prepayment" && !in_array($host_data["billingcycle"], ["ontrial", "hour", "day"])) {
			$system_button["product_transfer"] = ["name" => lang("PRODUCT_TRANSFER"), "func" => "product_transfer", "disabled" => false];
		}
		if (in_array($domainstatus, ["Active", "Suspended"])) {
			if ($host_data["payment_type"] == "prepayment" && !in_array($host_data["billingcycle"], ["onetime", "free", "hour", "day"])) {
				$system_button["renew_cycle"] = ["name" => lang("RENEW"), "func" => "renew_cycle", "disabled" => false];
			} else {
				if ($host_data["payment_type"] == "postpaid" || in_array($host_data["billingcycle"], ["hour", "day"])) {
					$system_button["pay_cycle"] = ["name" => lang("PAYMENT_CURRENT_PERIOD"), "func" => "pay_cycle", "disabled" => false];
				}
			}
		}
		if (in_array($domainstatus, ["Pending", "Active", "Suspended"])) {
			$system_button["request_cancel"] = ["name" => lang("UPGRADE_DOWNGRADE_OPTIONS"), "func" => "request_cancel", "disabled" => false];
		}
		$config_options = [];
		$config_logic = new \app\common\logic\ConfigOptions();
		$config_options = $config_logic->showInfo($productid, $host_id, $currency, $host_data["billingcycle"], false);
		$returndata["config_options"] = array_values($config_options);
		$custom_field_data = \think\Db::name("customfields")->field("id,fieldname")->where("type", "product")->where("relid", $productid)->where("adminonly", 0)->select()->toArray();
		foreach ($custom_field_data as &$cv) {
			$cv["value"] = \think\Db::name("customfieldsvalues")->where("fieldid", $cv["id"])->where("relid", $host_id)->value("value") ?? "";
		}
		$returndata["custom_field_data"] = $custom_field_data ?? [];
		$download_data = [];
		$download_data = \think\Db::name("downloads")->field("d.*")->alias("d")->leftJoin("product_downloads p", "p.download_id=d.id")->where("p.product_id", $productid)->select()->toArray();
		foreach ($download_data as $key => $val) {
			if ($val["productdownload"] == 1 && !in_array($domainstatus, ["Active"])) {
				unset($download_data[$key]);
				continue;
			}
			$download_data[$key]["down_link"] = "download/product_file?id=" . $val["id"];
		}
		$returndata["download_data"] = $download_data;
		$returndata["dcim"]["flowpacket"] = [];
		$returndata["dcim"]["flow_packet_use_list"] = [];
		if ($host_data["api_type"] == "zjmf_api") {
			$returndata["dcim"]["flowpacket"] = $upstream_data["dcim"]["flowpacket"] ?: [];
			$returndata["host_data"]["bwlimit"] = \intval($upstream_data["host_data"]["bwlimit"]);
			$returndata["host_data"]["bwusage"] = \floatval($upstream_data["host_data"]["bwusage"]);
			if ($host_data["upstream_price_type"] == "percent") {
				foreach ($returndata["dcim"]["flowpacket"] as $k => $v) {
					$returndata["dcim"]["flowpacket"][$k]["price"] = round($v["price"] * $host_data["upstream_price_value"] / 100, 2);
				}
			}
			$returndata["dcim"]["flow_packet_use_list"] = $upstream_data["dcim"]["flow_packet_use_list"] ?: [];
			if ($host_data["type"] == "dcim" && $host_data["config_option1"] != "bms") {
				$returndata["dcim"]["auth"] = $upstream_data["dcim"]["auth"] ?? ["bmc" => "off", "crack_pass" => "off", "ikvm" => "off", "kvm" => "off", "novnc" => "off", "off" => "off", "on" => "off", "reboot" => "off", "reinstall" => "off", "rescue" => "off", "traffic" => "off"];
				$returndata["dcim"]["svg"] = $upstream_data["dcim"]["svg"] ?? "";
				$returndata["host_data"]["os_ostype"] = $upstream_data["host_data"]["os_ostype"] ?? "";
				$returndata["host_data"]["os_osname"] = $upstream_data["host_data"]["os_osname"] ?? "";
				$returndata["host_data"]["disk_num"] = $upstream_data["host_data"]["disk_num"] ?? 1;
			}
		} else {
			if ($host_data["bwlimit"] > 0) {
				$flowpacket = \think\Db::name("dcim_flow_packet")->field("id,name,capacity,price,sale_times,stock")->where("status", 1)->whereRaw("FIND_IN_SET('{$host_data["productid"]}', allow_products)")->select()->toArray();
				if (!empty($flowpacket)) {
					foreach ($flowpacket as $k => $v) {
						$flowpacket[$k]["leave"] = 1;
						if ($v["stock"] > 0 && $v["sale_times"] >= $v["stock"]) {
							$flowpacket[$k]["leave"] = 0;
						}
						unset($flowpacket[$k]["sale_times"]);
						unset($flowpacket[$k]["stock"]);
					}
					$returndata["dcim"]["flowpacket"] = $flowpacket;
				}
			}
			if ($host_data["type"] == "dcim" && $host_data["config_option1"] != "bms") {
				$server = \think\Db::name("servers")->alias("a")->field("b.*")->leftJoin("dcim_servers b", "a.id=b.serverid")->where("a.id", $host_data["serverid"])->find();
				$returndata["dcim"]["auth"] = json_decode($server["auth"], true);
				if ($host_data["bwlimit"] > 0) {
					$returndata["dcim"]["flow_packet_use_list"] = get_dcim_traffic_usage_table($host_id, $uid, $server["bill_type"], $host_data["bwusage"], $host_data["bwlimit"]);
				}
				$os = json_decode($server["os"], true);
				$returndata["dcim"]["os_group"] = $os["group"];
				$returndata["dcim"]["os"] = $os["os"];
				if (!empty($host_data["dcim_area"])) {
					$area = json_decode($server["area"], true);
					foreach ($area as $v) {
						if ($v["id"] == $host_data["dcim_area"]) {
							$returndata["dcim"]["area_code"] = $v["area"];
							$returndata["dcim"]["area_name"] = $v["name"] ?? "";
							break;
						}
					}
				} else {
					$returndata["dcim"]["area_code"] = "";
					$returndata["dcim"]["area_name"] = "";
				}
				$os_info = get_dcim_os_info($host_data["dcim_os"], $os["os"], $os["group"]);
				$returndata["host_data"]["os_ostype"] = $os_info["ostype"] ?? "";
				$returndata["host_data"]["os_osname"] = $os_info["os_name"] ?? "";
				$returndata["host_data"]["disk_num"] = 1;
				$returndata["dcim"]["svg"] = $os_info["svg"];
			} else {
				if ($host_data["bwlimit"] > 0) {
					$returndata["dcim"]["flow_packet_use_list"] = get_dcim_traffic_usage_table($host_id, $uid, "", $host_data["bwusage"], $host_data["bwlimit"]);
				}
			}
		}
		$os_config_option_id = \think\Db::name("product_config_links")->alias("a")->leftJoin("product_config_options b", "a.gid=b.gid")->where("a.pid", $host_data["productid"])->where("b.option_type", 5)->value("b.id");
		$sub = \think\Db::name("product_config_options_sub")->field("id,option_name")->where("config_id", $os_config_option_id)->where("hidden", 0)->order("sort_order ASC")->order("id asc")->select()->toArray();
		$cloud_os = [];
		$cloud_os_group = [];
		$configoption_res = \think\Db::name("host_config_options")->where("relid", $host_id)->select()->toArray();
		$configoption = [];
		foreach ($configoption_res as $k => $v) {
			$configoption[$v["configid"]] = $v["qty"] ?: $v["optionid"];
		}
		$senior = new \app\common\logic\SeniorConf();
		$data_config_id = array_column($sub, "id");
		$senior->aloneCheckConf($host_data["productid"], $configoption, $os_config_option_id, $data_config_id);
		foreach ($sub as $v) {
			if (!in_array($v["id"], $data_config_id)) {
				continue;
			}
			$arr = explode("|", $v["option_name"]);
			if (strpos($arr[1], "^") !== false) {
				$arr2 = explode("^", $arr[1]);
				if (empty($arr2[0]) || empty($arr2[1])) {
					continue;
				}
				if (!in_array($arr2[0], $cloud_os_group)) {
					$cloud_os_group[] = $arr2[0];
				}
				$cloud_os[] = ["id" => $v["id"], "name" => $arr2[1], "group" => $arr2[0]];
			} else {
				$cloud_os[] = ["id" => $v["id"], "name" => $arr[1]];
			}
		}
		if (!empty($cloud_os_group)) {
			foreach ($cloud_os_group as $k => $v) {
				$cloud_os_group[$k] = ["id" => $v, "name" => $v];
			}
			foreach ($cloud_os as $k => $v) {
				if (empty($v["group"])) {
					unset($cloud_os[$k]);
				}
			}
			$cloud_os = array_values($cloud_os);
		}
		$returndata["cloud_os"] = $cloud_os;
		$returndata["cloud_os_group"] = $cloud_os_group;
		$os_info = \think\Db::name("host_config_options")->alias("a")->field("b.option_name")->leftJoin("product_config_options_sub b", "a.optionid=b.id")->where("a.relid", $host_id)->where("a.configid", $os_config_option_id)->find();
		if (empty($host_data["username"])) {
			if (stripos($os_info["option_name"], "win") !== false) {
				$returndata["host_data"]["username"] = "administrator";
			} else {
				$returndata["host_data"]["username"] = "root";
			}
		}
		$returndata["system_config"]["company_name"] = configuration("company_name");
		$returndata["host_data"]["os_config_option_id"] = $os_config_option_id;
		$cancelist = \think\Db::name("cancel_reason")->field("reason")->select()->toArray();
		$returndata["cancelist"] = $cancelist;
		$host_cancel = \think\Db::name("cancel_requests")->field("type,reason")->where("relid", $host_id)->find();
		$returndata["host_cancel"] = $host_cancel ?? [];
		unset($returndata["host_data"]["zjmf_api_id"]);
		unset($returndata["host_data"]["api_type"]);
		$returndata["second_verify"] = configuration("second_verify") ?? 1;
		$returndata["second_verify_action"] = explode(",", configuration("second_verify_action"));
		$returndata["second_verify_action_type"] = explode(",", configuration("second_verify_action_type"));
		return jsons(["status" => 200, "data" => $returndata]);
	}
	public function getHostStatus(\think\Request $request)
	{
		try {
			$param = $request->param();
			if (!$param["hid"]) {
				return jsons(["status" => 200, "msg" => "success", "data" => 0]);
			}
			$host = \think\Db::name("host")->where("id", $param["hid"])->find();
			if (!$host) {
				return jsons(["status" => 200, "msg" => "success", "data" => 0]);
			}
			return jsons(["status" => 200, "msg" => "success", "data" => $host["domainstatus"] == "Pending" ? 0 : 1]);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage(), "data" => 0]);
		}
	}
}