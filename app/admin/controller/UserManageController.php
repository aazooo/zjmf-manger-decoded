<?php

namespace app\admin\controller;

/**
 * @title 后台用户管理
 * @description 接口说明
 */
class UserManageController extends GetUserController
{
	private $saveImage;
	private $getImage;
	private $cardType;
	private $getAvatar;
	private $saveAvatar;
	public function initialize()
	{
		parent::initialize();
		$this->saveImage = config("certificate");
		$this->getImage = $this->request->host() . config("certificate_url");
		$this->saveAvatar = config("client_avatar");
		$this->getAvatar = config("client_avatar");
		$this->cardType = [0, 1];
	}
	public function getUser()
	{
		$params = $this->request->param();
		$uid = isset($params["uid"]) ? intval($params["uid"]) : "";
		if (!$uid) {
			return json(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		if (!$this->check1($uid)) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		$client = \think\Db::name("clients")->field("id,currency,username,companyname,activation,is_open_credit_limit")->where("id", $uid)->find();
		$developer = \think\Db::name("developer")->where("uid", $uid)->where("status", "Active")->order("id", "desc")->find();
		$client["developer"] = !empty($developer) ? 1 : 0;
		$client["edition"] = getEdition();
		$client["uncertifi"] = checkCertify($uid) ? 1 : 0;
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $client]);
	}
	/**
	 * @title 客户列表
	 * @description 接口说明:客户列表页(含搜索)
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @param .name:username type:string require:0 default:1 other: desc:按客户名搜索
	 * @param .name:companyname type:string require:0 default:1 other: desc:按公司名搜索
	 * @param .name:email type:string require:0 default:1 other: desc:按邮件搜索
	 * @param .name:phonenumber type:string require:0 default:1 other: desc:按手机号搜索
	 * @param .name:status type:string require:0 default:1 other: desc:按客户状态搜索
	 * @param .name:qq type:string require:0 default:1 other: desc:按qq搜索
	 * @param .name:custom[自定义字段ID] type:array require:0 default:1 other: desc:值
	 * @param .name:level type:int require:0 default:1 other: desc:等级ID
	 * @return  total:客户总数
	 * @return  list:客户列表数据@
	 * @return  search:搜索字段+自定义字段搜索 键为ID的是自定义字段
	 * @return  search_level:客户等级搜索@
	 * @search_level  id:等级ID
	 * @search_level  level_name:名称
	 * @list  id:客户ID
	 * @list  username:客户用户名
	 * @list  company:公司
	 * @list  phonenumber:手机号
	 * @list  email:邮件
	 * @list  sale:所属销售
	 * @list  amount_in:收入
	 * @list  amount_out:支出
	 * @list  credit:余额
	 * @list  create_time:创建时间
	 * @list  company_status:企业认证状态（空：未认证，1已认证，2未通过，3待审核）
	 * @list  person_status:个人认证状态（空：未认证，1已认证，2未通过，3待审核）
	 * @list  wechat_id:微信绑定（空：未绑定；有值：绑定）
	 * @list  lastlogin:最后登录时间
	 * @list  client_status:客户状态(1激活，0未激活，2关闭)
	 * @list  level:客户等级 新增 20201124
	 * @list  track_status:跟踪状态 新增 20201124
	 * @list  track_status_zh:跟踪状中文 新增 20201124
	 * @list  group_name:客户分类
	 * @list  group_colour:客户分类颜色
	 * @list  credit_limit:信用额
	 * @list  api_open:api状态,1开启，0关闭，2锁定 api_open_zh:对应中文
	 * @list  free_products:豁免产品数量
	 * @return api_status:第一个搜索框选择API状态时，对应的第二个搜索框数据
	 * @return allow_resource_api:当该值为0时,不显示api状态
	 * @author wyh
	 *
	 * @url /admin/client_list
	 * @method POST
	 */
	public function clientList()
	{
		$search1 = ["username" => "姓名", "companyname" => "公司名", "email" => "邮件", "phonenumber" => "手机号", "status" => "客户状态", "qq" => "QQ", "api_status" => "API状态", "client_groups" => "客户分组", "sale" => "销售"];
		$api_status = ["关闭", "开启", "锁定"];
		$clientGroups = \think\Db::name("client_groups")->field("id,group_name,group_colour")->select()->toArray();
		$clientGroupsFilter = [];
		foreach ($clientGroups as $key => $clientGroup) {
			$clientGroupsFilter[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $clientGroup);
		}
		array_unshift($clientGroupsFilter, ["id" => 0, "group_name" => lang("NULL")]);
		$customfields = \think\Db::name("customfields")->field("id,fieldname")->where("type", "client")->select()->toArray();
		$search2 = [];
		foreach ($customfields as $customfield) {
			$search2[$customfield["id"]] = $customfield["fieldname"];
		}
		$search = $search1 + $search2;
		$level_search = \think\Db::name("clients_level_rule")->field("id,level_name")->select()->toArray();
		$params = $data = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		if ($order != "amount_in" && $order != "amount_out") {
			$order = "cli." . $order;
		}
		$fun = function (\think\db\Query $query) use($data) {
			if (!empty($data["username"])) {
				$certifi_uid = \think\Db::name("certifi_log")->where("certifi_name", "like", "%" . trim($data["username"]) . "%")->where("status", 1)->column("uid");
				if (empty($certifi_uid)) {
					$certifi_uid = [0];
				}
				$query->where("cli.username like \"%" . trim($data["username"]) . "%\" or cli.id in (" . implode(",", array_unique($certifi_uid)) . ")");
			}
			if (!empty($data["companyname"])) {
				$query->where("cli.companyname", "like", "%" . trim($data["companyname"]) . "%");
			}
			if (!empty($data["email"])) {
				$query->where("cli.email", "like", "%" . trim($data["email"]) . "%");
			}
			if (!empty($data["phonenumber"])) {
				$query->where("cli.phonenumber", "like", "%" . trim($data["phonenumber"]) . "%");
			}
			if (isset($data["status"]) && $data["status"] != "") {
				$status = $data["status"];
				$query->where("cli.status", $status);
			}
			if ($this->user["id"] != 1 && $this->user["is_sale"]) {
				$query->whereIn("cli.id", $this->str);
			}
			if (!empty($data["qq"])) {
				$query->where("cli.qq", "like", "%" . trim($data["qq"]) . "%");
			}
			if (isset($data["client_groups"]) && $data["client_groups"] != "") {
				$query->where("cli.groupid", $data["client_groups"]);
			}
			if (isset($data["sale"]) && $data["sale"] != "") {
				$query->where("cli.sale_id", $data["sale"]);
			}
			if (isset($data["api_status"]) && configuration("allow_resource_api")) {
				$query->where("cli.api_open", intval($data["api_status"]));
			}
			$query->where("cli.usertype", "<>", 3);
			if (isset($data["certifi"])) {
				if ($data["certifi"] == 1) {
					$query->where("cc.status|cp.status", 1);
				} elseif ($data["certifi"] == 0) {
					$query->where("cc.status<>1 OR cc.status is null")->where("cp.status<>1 OR cp.status is null");
				}
			}
		};
		$obj1 = \think\Db::name("clients")->alias("cli")->leftJoin("certifi_company cc", "cc.auth_user_id = cli.id")->leftJoin("certifi_person cp", "cp.auth_user_id = cli.id")->leftJoin("wechat_user wu", "cli.wechat_id = wu.id")->leftJoin("client_groups cg", "cg.id = cli.groupid")->leftJoin("user u", "cli.sale_id=u.id");
		if (isset($params["custom"]) && !empty($params["custom"])) {
			$obj1 = $obj1->leftJoin("customfieldsvalues cfv", "cfv.relid = cli.id")->leftJoin("customfields cf", "cf.id = cfv.fieldid")->where("cf.type", "client")->where("cf.id", array_keys($params["custom"])[0])->where("cfv.value", "like", "%" . array_values($params["custom"])[0] . "%");
		}
		$total = $obj1->group("cli.id")->where($fun)->count();
		if ($order == "status") {
			$order = "client_status";
		}
		if ($order == "sale") {
			$order = "user_nickname";
		}
		$obj = \think\Db::name("clients")->alias("cli")->field("cli.id,cli.username,cli.sale_id,cg.group_colour,cli.companyname,cli.phonenumber,cli.email,cli.create_time,cli.qq,
            cc.status as company_status,cp.status as person_status,wu.id as wechat_id,cli.lastlogin,cli.status as client_status,
            u.user_nickname,cli.credit,cu.prefix,cu.id as currency_id,cli.track_status,cg.group_name,cg.group_colour,cli.api_open,cli.credit_limit,cli.api_create_time")->leftJoin("currencies cu", "cli.currency=cu.id")->leftJoin("certifi_company cc", "cc.auth_user_id = cli.id")->leftJoin("certifi_person cp", "cp.auth_user_id = cli.id")->leftJoin("wechat_user wu", "cli.wechat_id = wu.id")->leftJoin("client_groups cg", "cg.id = cli.groupid")->leftJoin("user u", "cli.sale_id=u.id");
		if (isset($params["custom"]) && !empty($params["custom"])) {
			$cli_ids = [];
			foreach ($params["custom"] as $key => $val) {
				$relid = \think\Db::name("customfieldsvalues")->where("fieldid", $key)->whereLike("value", "%" . $val . "%")->column("relid");
				$cli_ids[] = $relid;
			}
			if (count($cli_ids) == 1) {
				$cli_ids = $cli_ids[0];
			} else {
				$cli_ids = array_intersect(...$cli_ids);
			}
			$obj->whereIn("cli.id", $cli_ids);
		}
		if (isset($params["level"])) {
			$list = $obj->where($fun)->order($order, $sort)->group("cli.id")->select()->toArray();
		} else {
			$list = $obj->where($fun)->order($order, $sort)->group("cli.id")->page($page)->limit($limit)->select()->toArray();
		}
		$listFilter = [];
		$sale = array_column(get_sale(), "user_nickname", "id");
		foreach ($list as $key => $value) {
			$accounts = \think\Db::name("accounts")->field("amount_in,amount_out")->where("uid", $value["id"])->where("delete_time", 0)->select();
			if (!empty($accounts)) {
				$inTotal = $outTotal = 0;
				foreach ($accounts as $account) {
					$inTotal += $account["amount_in"];
					$outTotal += $account["amount_out"];
				}
				$value["amount_in"] = round($inTotal, 2);
				$value["amount_out"] = round($outTotal, 2);
			} else {
				$value["amount_in"] = 0.0;
				$value["amount_out"] = 0.0;
			}
			$host_total = \think\Db::name("host")->where("uid", $value["id"])->count();
			$host_active = \think\Db::name("host")->where("uid", $value["id"])->where("domainstatus", "Active")->count();
			$value["host_total"] = $host_active . "(" . $host_total . ")";
			$value["status"] = $value["client_status"];
			$value["sale"] = $sale[$value["sale_id"]] ?? (object) [];
			$value["client_status"] = config("client_status")[$value["client_status"]]["status"];
			$value["company_status"] = config("client_certifi_status")[$value["company_status"]];
			$value["person_status"] = config("client_certifi_status")[$value["person_status"]];
			$value["wechat_id"] = empty($value["wechat_id"]) ? lang("USER_WECHAT_NO") : lang("USER_WECHAT_IS");
			$value["track_status_zh"] = config("client_track_status")[$value["track_status"]];
			$value["api_open_zh"] = $api_status[$value["api_open"]];
			$free_products = \think\Db::name("api_user_product")->field("pid")->distinct(true)->where("uid", $value["id"])->where("ontrial|qty", ">", 0)->count();
			$value["free_products"] = intval($free_products);
			$value["amount_to_be_settled"] = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", $value["id"])->sum("total");
			$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", $value["id"])->sum("total");
			$value["credit_limit_used"] = round($value["amount_to_be_settled"] + $unpaid, 2);
			$listFilter[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $value);
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "total" => $total, "list" => $listFilter, "search" => $search ?? [], "level_search" => $level_search ?? [], "api_status" => $api_status, "seachData" => ["sale" => get_sale(), "client_groups" => $clientGroupsFilter], "allow_resource_api" => configuration("allow_resource_api") ?? 0]);
	}
	/**
	 * @title 单个客户资料详情
	 * @description 接口说明:单个客户资料详情
	 * @param .name:client_id type:int require:1 default:1 other: desc:客户ID
	 * @return  summary:客户资料@
	 * @summary  username:用户名
	 * @summary  companyname:公司
	 * @summary  country:国家
	 * @summary  province:省
	 * @summary  city:城市
	 * @summary  address1:地址1
	 * @summary  know_us:从何了解我们
	 * @summary  phonenumber:手机号
	 * @summary  email:邮箱
	 * @summary  sale_id:销售id
	 * @summary  initiative_renew:余额是否自动续费(1是0否)
	 * @summary  certifi_status:认证情况
	 * @return  invoices:财务/账单@
	 * @invoices  paid:已支付@
	 * @paid  status_zh:中文名
	 * @paid  num:数量
	 * @paid  total:总价
	 * @return  intotal:总收入
	 * @return  outtotal:总支出
	 * @return  income:收入
	 * @return  credit:余额
	 * @return  other_info:其他信息@
	 * @other_info  status:用户状态
	 * @other_info  groupname:用户组
	 * @other_info  create_time:创建时间
	 * @other_info  register_time:注册时长
	 * @other_info  last_login:最后登录时间
	 * @other_info  last_login_ip:最后登录IP
	 * @other_info  host:最后登录主机
	 * @return  hsot_server:产品/服务@
	 * @hsot_server  server:VPS/独服@
	 * @server  total:总量
	 * @server  active:激活数
	 * @server  type_zh:中文名
	 * @return  hosts:产品/服务列表@
	 * @hosts  hid:产品/服务ID
	 * @hosts  hostname:产品/服务
	 * @hosts  amount:金额
	 * @hosts  billingcycle:付款周期
	 * @hosts  regdate:开通时间
	 * @hosts  nextduedate:到期时间
	 * @hosts  domainstatus:状态('Pending':待审核,'Active'已激活,'Suspended'已暂停,'Terminated'已终止,'Cancelled'已取消,'Fraud'欺诈,'Completed'已完成)
	 * @return  hid:hostid
	 * @return  accounts_count:总收入 笔数
	 * @return  accounts_out_count:总支出 笔数
	 * @return  being_due_host:即将过期 count笔数，total总计
	 * @return  due_host:已过期 count笔数，total总计
	 * @return  ticket:工单，total总数，reply已回复，deal处理中，close已关闭
	 * @author wyh
	 * @url /admin/summary
	 * @method GET
	 */
	public function summary()
	{
		$re = [];
		$re["status"] = 200;
		$re["msg"] = lang("SUCCESS MESSAGE");
		$clientId = input("param.client_id", 0, "intval");
		if (!$this->check1($clientId)) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		$summary = \think\Db::name("clients")->alias("c")->leftJoin("client_groups cg", "c.groupid = cg.id")->field("username,companyname,country,province,city,address1,know_us,phone_code,phonenumber,email,currency,credit
            ,status,create_time,lastlogin,lastloginip,host,cg.group_name,qq,sale_id,notes,api_open")->where("c.id", $clientId)->withAttr("phonenumber", function ($value, $data) {
			if (empty($value)) {
				return "";
			} else {
				return "+" . $data["phone_code"] . "." . $value;
			}
		})->find();
		$aff_id = \think\Db::name("affiliates_user")->where("uid", $clientId)->value("affid");
		if ($aff_id > 0) {
			$aff_id_uid = \think\Db::name("affiliates")->where("id", $aff_id)->value("uid");
		} else {
			$aff_id_uid = \think\Db::name("affiliates_user_temp")->where("uid", $clientId)->value("affid_uid");
		}
		if ($aff_id_uid > 0) {
			$promoter = \think\Db::name("clients")->where("id", $aff_id_uid)->field("id,username")->find();
		}
		$summary["promoter"] = $promoter ?: [];
		$summary["home_url"] = configuration("domain") . "/#/user-center";
		$summary["saler"] = \think\Db::name("user")->where("id", $summary["sale_id"])->value("user_nickname");
		if (!isset($summary["username"])) {
			active_log(sprintf($this->lang["UserManage_user_summary_fail"], $clientId, lang("USER_NOT")));
			return jsonrule(["status" => 404, "msg" => lang("USER_NOT"), "data" => (object) []]);
		}
		$summary["status"] = config("client_status")[$summary["status"]]["status"];
		$summary["certifi_status"] = getClientCeritfi($clientId);
		$currencyId = $summary["currency"];
		if ($currencyId) {
			$currency = \think\Db::name("currencies")->field("id,code,prefix,suffix")->where("id", $currencyId)->find();
		} else {
			$currency = \think\Db::name("currencies")->field("id,code,prefix,suffix")->where("default", 1)->find();
		}
		$currencyId = $currency["id"];
		$re["currency"] = $currency;
		$invoices = [];
		$unpaid = $this->getInvoice2($clientId, "Unpaid", $currency);
		$invoices["unpaid"] = $unpaid;
		$re["invoices"] = $invoices;
		$accounts = \think\Db::name("accounts")->field("amount_in,amount_out,fees")->where("uid", $clientId)->where("delete_time", 0)->select();
		$inTotal = $outTotal = $feeTotal = 0;
		foreach ($accounts as $account) {
			$inTotal += $account["amount_in"];
			$outTotal += $account["amount_out"];
			$feeTotal += $account["fees"];
		}
		$accounts_count = \think\Db::name("accounts")->where("uid", $clientId)->where("amount_in", ">", 0)->where("delete_time", 0)->count();
		$re["accounts_count"] = $accounts_count;
		$accounts_out_count = \think\Db::name("accounts")->where("uid", $clientId)->where("amount_out", ">", 0)->where("delete_time", 0)->count();
		$re["accounts_out_count"] = $accounts_out_count;
		$income = $inTotal - $outTotal - $feeTotal;
		$credit = $summary["credit"];
		$re["intotal"] = $currency["prefix"] . number_format(round($inTotal, 2), 2) ?? "0" . $currency["suffix"];
		$re["outtotal"] = $currency["prefix"] . number_format(round($outTotal, 2), 2) ?? "0" . $currency["suffix"];
		$re["income"] = $currency["prefix"] . number_format(round($income, 2), 2) ?? "0" . $currency["suffix"];
		$re["credit"] = $currency["prefix"] . number_format(round($credit, 2), 2) ?? "0" . $currency["suffix"];
		$otherInfo = [];
		$otherInfo["status"] = $summary["status"];
		$otherInfo["groupname"] = !empty($summary["group_name"]) ? $summary["group_name"] : config("custom_brokerage")["none"];
		$otherInfo["create_time"] = $summary["create_time"];
		$otherInfo["register_time"] = floor((time() - $summary["create_time"]) / 24 / 60 / 60) . config("billing_cycle")["day"];
		$otherInfo["last_login"] = $summary["lastlogin"];
		$otherInfo["last_login_ip"] = $summary["lastloginip"];
		$otherInfo["host"] = $summary["host"];
		$re["other_info"] = $otherInfo;
		$due_host_total = \think\Db::name("host")->field("sum(firstpaymentamount) as firstpaymentamount,count(id) as due_host_count")->where("uid", $clientId)->where("nextduedate", ">", 0)->where("nextduedate", "<=", time() + 604800)->find();
		$re["being_due_host"] = ["count" => $due_host_total["due_host_count"] ?? 0, "total" => $currency["prefix"] . number_format(round($due_host_total["firstpaymentamount"] ?? 0.0, 2), 2)];
		$due_total = \think\Db::name("host")->field("sum(firstpaymentamount) as firstpaymentamount,count(id) as due_host_count")->where("uid", $clientId)->where("nextduedate", ">", 0)->where("nextduedate", "<=", time())->where("domainstatus", "<>", "Pending")->find();
		$re["due_host"] = ["count" => $due_total["due_host_count"] ?? 0, "total" => $currency["prefix"] . number_format(round($due_total["firstpaymentamount"] ?? 0.0, 2), 2)];
		$hostserver = [];
		$gethost = $this->getHost1($clientId);
		$hostserver = $gethost;
		$ticket = [];
		$ticketTotal = \think\Db::name("ticket")->where("uid", $clientId)->count();
		$active = \think\Db::name("ticket")->where("uid", $clientId)->where("status", "<>", "Closed")->count();
		$ticket["total"] = "总数量 " . $ticketTotal;
		$ticket["active"] = $active;
		$ticket["type_zh"] = "工单";
		$hostserver["ticket"] = $ticket;
		$ticket_total = \think\Db::name("ticket")->where("uid", $clientId)->count();
		$deal_total = \think\Db::name("ticket")->where("uid", $clientId)->where("status", 1)->count();
		$reply_total = \think\Db::name("ticket")->where("uid", $clientId)->where("status", 2)->count();
		$close_total = \think\Db::name("ticket")->where("uid", $clientId)->where("status", 4)->count();
		$re["ticket"] = ["total" => $ticket_total, "deal" => $deal_total, "reply" => $reply_total, "close" => $close_total];
		$re["host_server"] = $hostserver;
		$contacts = \think\Db::name("contacts")->field("username,email,concat(username,\"-\",email) as contact")->where("uid", $clientId)->limit(5)->select();
		$re["contacts"] = $contacts;
		unset($summary["currency"]);
		unset($summary["credit"]);
		unset($summary["status"]);
		unset($summary["group_name"]);
		unset($summary["create_time"]);
		unset($summary["lastlogin"]);
		unset($summary["lastloginip"]);
		unset($summary["host"]);
		$re["summary"] = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $summary);
		$rh = \think\Db::name("host")->field("id")->where("uid", $clientId)->where("domainstatus", "Active")->order("id", "desc")->find();
		if (!empty($rh)) {
			$re["hid"] = $rh["id"];
		} else {
			$re["hid"] = 0;
		}
		$customfields = \think\Db::name("customfields")->alias("a")->field("a.fieldname,b.value")->leftJoin("customfieldsvalues b", "a.id = fieldid")->where("a.type", "client")->where("b.relid", $clientId)->select()->toArray();
		$re["customfields"] = $customfields;
		$plugins_oauth = [];
		$list = \think\Db::name("plugin")->where(["module" => "oauth", "status" => 1])->order("order", "asc")->select();
		$clients_oauth = \think\Db::name("clients_oauth")->where(["uid" => $clientId])->select()->toArray();
		$clients_oauth = array_column($clients_oauth, "oauth", "type");
		foreach ($list as $k => $plugin) {
			if (!$plugin["config"]) {
				continue;
			}
			if (!empty($clients_oauth[$plugin["name"]])) {
				$oauth = json_decode($clients_oauth[$plugin["name"]], true);
				$plugins_oauth[$k]["username"] = $oauth["username"];
				$plugins_oauth[$k]["oauth"] = "bind";
				$plugins_oauth[$k]["name"] = $plugin["title"];
				$plugins_oauth[$k]["dirName"] = $plugin["name"];
				$class = "oauth\\{$plugin["name"]}\\{$plugin["name"]}";
				$obj = new $class();
				$meta = $obj->meta();
				$oauth_img = CMF_ROOT . "modules/oauth/{$plugin["name"]}/{$meta["logo_url"]}";
				if (!file_exists($oauth_img)) {
					$oauth_img = CMF_ROOT . "public/plugins/oauth/{$plugin["name"]}/{$meta["logo_url"]}";
				}
				if (stripos($oauth_img, ".svg") === false) {
					$plugins_oauth[$k]["img"] = "<img width=30 height=30 src=\"" . base64EncodeImage($oauth_img) . "\" />";
				} else {
					$plugins_oauth[$k]["img"] = file_get_contents($oauth_img);
				}
			}
		}
		$re["plugins_oauth"] = array_merge($plugins_oauth);
		return jsonrule($re);
	}
	/**
	 * @title 客户资源列表
	 * @description 接口说明:客户资源列表(含搜索)
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @param .name:username type:string require:0 default:1 other: desc:按客户名搜索
	 * @param .name:companyname type:string require:0 default:1 other: desc:按公司名搜索
	 * @param .name:email type:string require:0 default:1 other: desc:按邮件搜索
	 * @param .name:phonenumber type:string require:0 default:1 other: desc:按手机号搜索
	 * @param .name:status type:string require:0 default:1 other: desc:按客户状态搜索
	 * @param .name:qq type:string require:0 default:1 other: desc:按qq搜索
	 * @return total:客户总数
	 * @return list:客户列表数据@
	 * @list  id:客户ID
	 * @list  username:客户用户名
	 * @list  amount_in:收入
	 * @list  credit:余额
	 * @list  create_time:创建时间
	 * @author wyh
	 *
	 * @url /admin/client_list_resource
	 * @method GET
	 */
	public function clientListRe()
	{
		$params = $data = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		if ($order != "amount_in" && $order != "amount_out") {
			$order = "cli." . $order;
		}
		$total = \think\Db::name("clients")->alias("cli")->group("cli.id")->where(function (\think\db\Query $query) use($data) {
			if (!empty($data["username"])) {
				$query->where("cli.username", "like", "%" . trim($data["username"]) . "%");
			}
			if (!empty($data["companyname"])) {
				$query->where("cli.companyname", "like", "%" . trim($data["companyname"]) . "%");
			}
			if (!empty($data["email"])) {
				$query->where("cli.email", "like", "%" . trim($data["email"]) . "%");
			}
			if (!empty($data["phonenumber"])) {
				$query->where("cli.phonenumber", "like", "%" . trim($data["phonenumber"]) . "%");
			}
			if (isset($data["status"]) && $data["status"] != "") {
				$status = $data["status"];
				$query->where("cli.status", $status);
			}
			if ($this->user["id"] != 1 && $this->user["is_sale"]) {
				$query->whereIn("cli.id", $this->str);
			}
			if (!empty($data["qq"])) {
				$query->where("cli.qq", "like", "%" . trim($data["qq"]) . "%");
			}
		})->where(["cli.sale_id" => 0])->count();
		$list = \think\Db::name("clients")->alias("cli")->field("cli.id,cli.credit,cli.username,cli.create_time")->where(function (\think\db\Query $query) use($data) {
			if (!empty($data["username"])) {
				$query->where("cli.username", "like", "%" . trim($data["username"]) . "%");
			}
			if (!empty($data["companyname"])) {
				$query->where("cli.companyname", "like", "%" . trim($data["companyname"]) . "%");
			}
			if (!empty($data["email"])) {
				$query->where("cli.email", "like", "%" . trim($data["email"]) . "%");
			}
			if (!empty($data["phonenumber"])) {
				$query->where("cli.phonenumber", "like", "%" . trim($data["phonenumber"]) . "%");
			}
			if (isset($data["status"]) && $data["status"] != "") {
				$status = $data["status"];
				$query->where("cli.status", $status);
			}
			if ($this->user["id"] != 1 && $this->user["is_sale"]) {
				$query->whereIn("cli.id", $this->str);
			}
			if (!empty($data["qq"])) {
				$query->where("cli.qq", "like", "%" . trim($data["qq"]) . "%");
			}
		})->where(["cli.sale_id" => 0])->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		$listFilter = [];
		foreach ($list as $key => $value) {
			$accounts = \think\Db::name("accounts")->field("amount_in")->where("uid", $value["id"])->where("delete_time", 0)->select();
			if (!empty($accounts)) {
				$inTotal = $outTotal = 0;
				foreach ($accounts as $account) {
					$inTotal += $account["amount_in"];
				}
				$value["amount_in"] = round($inTotal, 2);
			} else {
				$value["amount_in"] = 0.0;
			}
			$listFilter[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $value);
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "total" => $total, "list" => $listFilter]);
	}
	/**
	 * @title 绑定销售
	 * @description 接口说明:绑定销售
	 * @param .name:uid type:int require:1 default:1 other: desc:客户ID
	 * @param .name:sale_id type:int require:1 default:1 other: desc:销售ID
	 * @author lgd
	 * @url /admin/bind_sale
	 * @method POST
	 */
	public function hostBindSale()
	{
		$params = $this->request->param();
		$uid = $params["uid"];
		$sale_id = $params["sale_id"];
		if (empty($uid) || empty($sale_id)) {
			return jsonrule(["status" => 400, "msg" => "用户id或者销售id为空"]);
		}
		$re = \think\Db::name("clients")->where("id", $uid)->update(["sale_id" => $sale_id]);
		if ($re) {
			return jsonrule(["status" => 200, "msg" => "绑定成功"]);
		} else {
			return jsonrule(["status" => 400, "msg" => "绑定失败"]);
		}
	}
	/**
	 * @title 单个客户产品
	 * @description 接口说明:单个客户产品
	 * @param .name:uid type:int require:1 default:1 other: desc:客户ID
	 * @param .name:currency type:int require:1 default:1 other: desc:货币id
	 * @param .name:search_all type:int require:1 default:1 other: desc:1是否查询全部
	 * @param .name:source type:string require:1 default:1 other: desc:工单ticket
	 * @param .name:hostid type:int require:1 default:1 other: desc:hostid
	 * @return  :客户资料@
	 * @return  hosts:产品/服务列表@
	 * @hosts  hid:产品/服务ID
	 * @hosts  hostname:产品/服务
	 * @hosts  amount:金额
	 * @hosts  billingcycle:付款周期
	 * @hosts  regdate:开通时间
	 * @hosts  nextduedate:到期时间
	 * @hosts  domainstatus:状态('Pending':待审核,'Active'已激活,'Suspended'已暂停,'Terminated'已终止,'Cancelled'已取消,'Fraud'欺诈,'Completed'已完成)
	 * @return  hid:hostid
	 * @author wyh
	 * @url /admin/hostbyuid
	 * @method GET
	 */
	public function hostByUid()
	{
		$params = $this->request->param();
		$uid = $params["uid"];
		$page = intval($params["page"]);
		$uid = intval($params["uid"]);
		$page = $page ?? config("page");
		$limit = $params["limit"] ?? config("limit");
		$where = [];
		if ($params["source"] == "ticket") {
			$search_all = intval($params["search_all"]);
			if ($search_all != 1) {
				$fun = function (\think\db\Query $query) use($params) {
					$query->where("h.domainstatus", "not in", "Cancelled,Deleted");
				};
			}
			$order = $params["order"] ?? "h.id";
			$sort = $params["sort"] ?? "desc";
			$hostid = intval($params["hostid"]);
			$host_list = \think\Db::name("host")->field("h.id,h.initiative_renew,h.domain,h.uid,h.regdate as create_time,h.productid,h.billingcycle,h.domain,h.payment,h.nextduedate,h.firstpaymentamount,h.amount,h.domainstatus,c.username,p.name as productname,c.currency,p.type,h.serverid")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("clients c", "c.id=h.uid")->where($fun)->where("h.uid", $uid)->order("h.id", "desc")->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
			$count = \think\Db::name("host")->field("h.id,h.initiative_renew,h.domain,h.uid,h.regdate as create_time,h.productid,h.billingcycle,h.domain,h.payment,h.nextduedate,h.firstpaymentamount,h.amount,h.domainstatus,c.username,p.name as productname,c.currency,p.type,h.serverid")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("clients c", "c.id=h.uid")->where($fun)->where("h.uid", $uid)->order("h.id", "desc")->count();
			$tmp = \think\Db::name("currencies")->select()->toArray();
			$currency = array_column($tmp, null, "id");
			foreach ($host_list as &$v) {
				if ($v["id"] == $hostid) {
					$v["related"] = 1;
				} else {
					$v["related"] = 0;
				}
				$v["domainstatus_en"] = $v["domainstatus"];
				$v["billingcycle"] = config("billing_cycle")[$v["billingcycle"]];
				$v["billingcycle_en"] = [$v["billingcycle"]];
				if ($v["type"] == "ssl") {
					$v["status_color"] = config("public.sslDomainStatus")[$v["domainstatus"]]["color"];
					$v["domainstatus"] = config("public.sslDomainStatus")[$v["domainstatus"]]["name"];
				} else {
					$v["status_color"] = config("public.domainstatus")[$v["domainstatus"]]["color"];
					$v["domainstatus"] = config("public.domainstatus")[$v["domainstatus"]]["name"];
				}
				$v["amount"] = $currency[$v["currency"]]["prefix"] . $v["amount"] . $currency[$v["currency"]]["suffix"];
				$v["firstpaymentamount"] = $currency[$v["currency"]]["prefix"] . $v["firstpaymentamount"] . $currency[$v["currency"]]["suffix"];
			}
			$result["status"] = 200;
			$result["hosts"] = $host_list;
			$result["total"] = $count;
			return jsonrule($result);
			exit;
		}
		if (!empty($params["order"])) {
			$order = "h." . $params["order"];
		} else {
			$order = "h.id";
		}
		$sort = $params["sort"] ?? "desc";
		$currencyId = $params["currency"];
		if ($currencyId) {
			$currency = \think\Db::name("currencies")->field("id,code,prefix,suffix")->where("id", $currencyId)->find();
		} else {
			$currency = \think\Db::name("currencies")->field("id,code,prefix,suffix")->where("default", 1)->find();
		}
		$product_type = config("product_type");
		$hosts = \think\Db::name("host")->alias("h")->field("h.id as hid,p.name,h.domain,h.amount,h.firstpaymentamount,h.dedicatedip,h.billingcycle,h.regdate,h.nextduedate,h.domainstatus,p.type as host_type,p.type as ptype")->leftJoin("products p", "h.productid = p.id")->where("uid", $uid)->withAttr("host_type", function ($value) use($product_type) {
			return $product_type[$value];
		})->where($where)->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		$count = \think\Db::name("host")->alias("h")->field("h.id as hid,concat(p.name,\"-\",h.domain) as hostname,h.amount,h.firstpaymentamount,h.dedicatedip,h.billingcycle,h.regdate,h.nextduedate,h.domainstatus,p.type as host_type")->leftJoin("products p", "h.productid = p.id")->where($where)->where("uid", $uid)->count();
		$hostsFilter = [];
		foreach ($hosts as $hkey => $host) {
			$host["billingcycle"] = config("billing_cycle")[$host["billingcycle"]];
			if ($host["billingcycle"] == "一次性") {
				$host["amount"] = $currency["prefix"] . $host["firstpaymentamount"] . $currency["suffix"];
			} else {
				$host["amount"] = $currency["prefix"] . $host["amount"] . $currency["suffix"];
			}
			$host["domainstatus_en"] = $host["domainstatus"];
			if ($host["ptype"] == "ssl") {
				$host["status_color"] = config("public.sslDomainStatus")[$host["domainstatus"]]["color"];
				$host["domainstatus"] = config("sslDomainStatus")[$host["domainstatus"]];
			} else {
				$host["status_color"] = config("public.domainstatus")[$host["domainstatus"]]["color"];
				$host["domainstatus"] = config("domainstatus")[$host["domainstatus"]];
			}
			$hostsFilter[$hkey] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $host);
		}
		$re["status"] = 200;
		$re["total"] = $count;
		$re["hosts"] = $hostsFilter;
		return jsonrule($re);
	}
	private function getHost($clientId, $type)
	{
		$total = \think\Db::name("host")->alias("h")->leftJoin("products p", "p.id = h.productid")->where("h.uid", $clientId)->where("p.type", $type)->count();
		$active = \think\Db::name("host")->alias("h")->leftJoin("products p", "p.id = h.productid")->where("h.uid", $clientId)->where("p.type", $type)->where("h.domainstatus", "Active")->count();
		$re["total"] = "总数量 " . $total;
		$re["active"] = $active;
		$re["type_zh"] = config("product_type")[$type];
		return $re;
	}
	private function getHost1($clientId)
	{
		$active1 = \think\Db::name("host")->alias("h")->field("count(h.id) as count,p.type")->leftJoin("products p", "p.id = h.productid")->where("h.uid", $clientId)->group("p.type")->select()->toArray();
		$active = \think\Db::name("host")->alias("h")->field("count(h.id) as count,p.type")->leftJoin("products p", "p.id = h.productid")->where("h.uid", $clientId)->whereIn("h.domainstatus", ["Active", "Verifiy_Active", "Issue_Active"])->group("p.type")->select()->toArray();
		$hostserver = ["server", "hostingaccount", "other", "dcimcloud", "dcim", "cdn", "software", "cloud", "ssl"];
		$arr = [];
		foreach ($hostserver as $value) {
			$flag = false;
			$flag1 = false;
			foreach ($active as $k => $v) {
				if ($v["type"] == $value) {
					$flag = true;
					$re["active"] = $v["count"];
					$re["type_zh"] = config("product_type")[$value];
					break;
				}
			}
			foreach ($active1 as $k => $v) {
				if ($v["type"] == $value) {
					$flag1 = true;
					$re["total"] = "总数量 " . $v["count"];
					break;
				}
			}
			if (!$flag) {
				$re["active"] = 0;
				$re["type_zh"] = config("product_type")[$value];
			}
			if (!$flag1) {
				$re["total"] = "总数量 0";
			}
			$arr[$value] = $re;
		}
		return $arr;
	}
	private function getInvoice2($clientId, $status, $currency)
	{
		$prefix = $currency["prefix"];
		$suffix = $currency["suffix"];
		$re = [];
		$re["status_zh"] = config("invoice_payment_status")[$status];
		$subtotals = \think\Db::name("invoices")->field("count(id) as count,sum(subtotal) as subtotal,status")->where("uid", $clientId)->where("status", $status)->where("delete_time", 0)->find();
		$re["num"] = $subtotals["count"];
		$re["total"] = $prefix . number_format(round($subtotals["subtotal"], 2), 2);
		return $re;
	}
	private function getInvoice1($clientId, $currency)
	{
		$prefix = $currency["prefix"];
		$suffix = $currency["suffix"];
		$re = [];
		$subtotals = \think\Db::name("invoices")->field("count(id) as count,sum(subtotal) as subtotal,status")->where("uid", $clientId)->group("status")->where("delete_time", 0)->select()->toArray();
		$arr = [];
		foreach (config("invoice_payment_status") as $key => $value) {
			$flag = false;
			foreach ($subtotals as $k => $v) {
				if ($v["status"] == $key) {
					$flag = true;
					$re["status_zh"] = $value["name"];
					$re["num"] = $v["count"];
					$re["total"] = $prefix . number_format(round($v["subtotal"], 2), 2) . $suffix;
					break;
				}
			}
			if (!$flag) {
				$re["status_zh"] = $value["name"];
				$re["num"] = 0;
				$re["total"] = $prefix . number_format(round(0, 2), 2) . $suffix;
			}
			$arr[$key] = $re;
		}
		return $arr;
	}
	private function getInvoices($clientId, $status, $currencyId)
	{
		if ($currencyId) {
			$currency = \think\Db::name("currencies")->field("code,prefix,suffix")->where("id", $currencyId)->find();
		} else {
			$currency = \think\Db::name("currencies")->field("code,prefix,suffix")->where("default", 1)->find();
		}
		$prefix = $currency["prefix"];
		$suffix = $currency["suffix"];
		$re = [];
		$re["status_zh"] = config("invoice_payment_status")[$status];
		$num = \think\Db::name("invoices")->where("uid", $clientId)->where("status", $status)->where("delete_time", 0)->count();
		$subtotals = \think\Db::name("invoices")->field("subtotal")->where("uid", $clientId)->where("status", $status)->where("delete_time", 0)->select();
		$total = 0;
		foreach ($subtotals as $subtotal) {
			$total += $subtotal["subtotal"];
		}
		$re["num"] = $num;
		$re["total"] = $prefix . number_format(round($total, 2), 2) . $suffix;
		return $re;
	}
	/**
	 * @title 客户资料修改页
	 * @description 接口说明:客户资料修改页
	 * @param  .name:client_id type:int require:1 default:1 other: desc:客户ID
	 * @return profile:客户基本信息@
	 * @profile  username:用户名
	 * @profile  sex:性别
	 * @profile  avatar:头像
	 * @profile  profession:职业
	 * @profile  signature:个性签名
	 * @profile  companyname:所在公司
	 * @profile  email:邮件
	 * @profile  country:国家
	 * @profile  province:省份
	 * @profile  city:城市
	 * @profile  region:区
	 * @profile  address1:具体地址1
	 * @profile  postcode:邮编
	 * @profile  phone_code:电话区号
	 * @profile  phonenumber:电话
	 * @profile  currency:使用货币ID
	 * @profile  defaultgateway:选择默认支付接口
	 * @profile  notes:管理员备注
	 * @profile  groupid:用户组ID
	 * @profile  status:状态（1激活，0未激活，2关闭）
	 * @profile  language:语言
	 * @profile  sale_id:销售id
	 * @profile  know_us:了解途径
	 * @return currencies:货币种类(所有默认值都在profile中找)
	 * @return country:国家列表
	 * @return areas:区域列表
	 * @return sms_country:国际电话区号:nicename(名称)+phone_code(区号)
	 * @return gateway:支付方式
	 * @return language:语言列表
	 * @return sale:销售列表
	 * @return client_groups:用户组列表
	 * @return client_status:用户状态列表
	 * @return customs:用户自定义字段@
	 * @customs  id:自定义字段ID
	 * @customs  fieldname:字段名称
	 * @customs  fieldtype:类型:text 文本框 link 链接 password 密码 dropdown 下拉 tickbox 选项框 textarea 文本区
	 * @customs  description:描述
	 * @customs  fieldoptions:选项
	 * @customs  regexpr:正则匹配
	 * @customs  required:1必填，0非必填
	 * @customs  sortorder:排序
	 * @return custom_value:用户自定义字段的值@
	 * @custom_value  id:自定义字段ID
	 * @custom_value  value:值
	 * @author wyh
	 * @url /admin/profile/:client_id
	 * @method GET
	 */
	public function profile()
	{
		$re = [];
		$clientId = $this->request->param("client_id");
		if (!$this->check1($clientId)) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		$customs = \think\Db::name("customfields")->field("id,fieldname,fieldtype,description,fieldoptions,regexpr,required,sortorder")->where("type", "client")->select()->toArray();
		$client_customs = \think\Db::name("customfields")->alias("a")->field("a.id,b.value")->leftJoin("customfieldsvalues b", "a.id = b.fieldid")->where("a.type", "client")->where("b.relid", $clientId)->select()->toArray();
		$profile = \think\Db::name("clients")->field("username,sex,profession,signature,companyname,country,province,address1,postcode,phone_code,phonenumber,email,currency,
            defaultgateway,notes,groupid,status,language,know_us,qq,sale_id,marketing_emails_opt_in,send_close")->where("id", $clientId)->find();
		$profileFilter = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $profile);
		$profileFilter["currency"] = empty($profileFilter["currency"]) ? "" : $profileFilter["currency"];
		$aff_id = \think\Db::name("affiliates_user")->where("uid", $clientId)->value("affid");
		if ($aff_id > 0) {
			$profileFilter["aff_id_uid"] = \think\Db::name("affiliates")->where("id", $aff_id)->value("uid");
		} else {
			$profileFilter["aff_id_uid"] = \think\Db::name("affiliates_user_temp")->where("uid", $clientId)->value("affid_uid");
		}
		if ($profileFilter["aff_id_uid"] > 0) {
			$profileFilter["aff_id_username"] = \think\Db::name("clients")->where("id", $profileFilter["aff_id_uid"])->value("username");
		}
		$profileFilter["aff_id_uid"] = $profileFilter["aff_id_uid"] ?: 0;
		$profileFilter["aff_id_username"] = $profileFilter["aff_id_username"] ?: "";
		$country = config("country.country");
		$language = get_language_list();
		$re["sale"] = get_sale();
		$re["status"] = 200;
		$re["msg"] = lang("SUCCESS MESSAGE");
		$re["profile"] = $profileFilter;
		$re["language"] = $language;
		$re["client_status"] = config("client_status");
		$re["custom"] = $customs;
		$re["custom_value"] = $client_customs;
		return jsonrule($re);
	}
	/**
	 * @title 获取推介人
	 * @description 接口说明:获取除了自己以外的用户
	 * @param .name:username type:int require:0  other: desc:用户名
	 * @author xiong
	 * @url /admin/profile/getclients/:client_id
	 * @method get
	 */
	public function getClients()
	{
		$clientId = $this->request->param("client_id");
		if (!$this->check1($clientId)) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		$params = $this->request->param();
		$aff_id = \think\Db::name("affiliates_user")->where("uid", $clientId)->value("affid");
		if ($aff_id > 0) {
			$aff_id_uid = \think\Db::name("affiliates")->where("id", $aff_id)->value("uid");
		} else {
			$aff_id_uid = \think\Db::name("affiliates_user_temp")->where("uid", $clientId)->value("affid_uid");
		}
		$username = $params["username"];
		$where[] = ["id", "<>", $clientId];
		$clents = (new \app\common\model\ClientModel())->checkclient($username, $where);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $clents]);
	}
	/**
	 * @title 客户资料修改页提交
	 * @description 接口说明:客户资料修改页提交(文件name:idfront,idback,license)
	 * @param .name:client_id type:int require:1 default:1 other: desc:客户ID
	 * @param .name:username type:string require:1 default:1 other: desc:用户名
	 * @param .name:sex type:int require:1 default:1 other: desc:性别（0未知，1男，2女）
	 * @param .name:avatar type:string require:0 default:1 other: desc:头像
	 * @param .name:profession type:string require:0 default:1 other: desc:职业
	 * @param .name:signature type:string require:0 default:1 other: desc:个性签名
	 * @param .name:companyname type:string require:0 default:1 other: desc:所在公司
	 * @param .name:email type:string require:0 default:0 other: desc:邮件
	 * @param .name:qq type:string require:0 default:0 other: desc:qq
	 * @param .name:sale_id type:string require:0 default:0 other: desc:销售id
	 * @param .name:country type:string require:0 default:0 other: desc:国家
	 * @param .name:province type:string require:0 default:0 other: desc:省份
	 * @param .name:city type:string require:0 default:0 other: desc:城市
	 * @param .name:region type:string require:0 default:0 other: desc:区
	 * @param .name:address1 type:string require:0 default:1 other: desc:具体地址1
	 * @param .name:postcode type:string require:0 default:1 other: desc:邮编
	 * @param .name:phone_code type:int require:0 default:1 other: desc:电话区号
	 * @param .name:phonenumber type:string require:0 default:1 other: desc:电话
	 * @param .name:currency type:int require:0 default:1 other: desc:使用货币ID
	 * @param .name:defaultgateway type:string require:1 default:1 other: desc:选择默认支付接口
	 * @param .name:notes type:string require:0 default:0 other: desc:管理员备注
	 * @param .name:groupid type:int require:0 default:0 other: desc:用户组ID
	 * @param .name:status type:int require:0 default:0 other: desc:状态（0未激活，1激活，2关闭）
	 * @param .name:language type:string require:0 default:0 other: desc:语言(传zh_cn/zh_xg/en_us等)
	 * @param .name:know_us type:string require:0 default:0 other: desc:了解途径
	 * @param .name:custom[id] type:string require:1 default:0 other: desc:自定义字段值.形式：custom[id] = value;此参数必传，没有值传custom[];
	 * @author wyh
	 * @url /admin/profile_post
	 * @method POST
	 */
	public function profilePost()
	{
		if ($this->request->isPost()) {
			$params = $this->request->only(["client_id", "username", "password", "sex", "avatar", "profession", "signature", "companyname", "email", "country", "province", "city", "region", "address1", "postcode", "phone_code", "phonenumber", "currency", "defaultgateway", "notes", "qq", "sale_id", "groupid", "status", "language", "know_us", "custom", "marketing_emails_opt_in", "aff_id_uid", "send_close"]);
			$uid = isset($params["client_id"]) ? intval($params["client_id"]) : "";
			$resultuid = \think\Db::name("clients")->where("id", $uid)->find();
			if (!$uid) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			if (!$this->check1($uid)) {
				return jsonrule(["status" => 409, "msg" => lang("NOT_USER_ID")]);
			}
			$validate = new \app\admin\validate\UserManageValidate();
			if (!$validate->scene("put")->check($params)) {
				return jsonrule(["status" => 400, "msg" => $validate->getError()]);
			}
			if (empty($params["email"]) && empty($params["phonenumber"])) {
				return jsonrule(["status" => 400, "msg" => lang("SERVER_USER_PHONE_EMAIL")]);
			}
			if (!empty($params["phonenumber"])) {
				$phone_exist = \think\Db::name("clients")->where("id", "<>", $uid)->where("phonenumber", $params["phonenumber"])->count();
				if ($phone_exist > 0) {
					return jsonrule(["status" => 400, "msg" => lang("手机已存在用户使用")]);
				}
			}
			if (!empty($params["email"])) {
				$email_exist = \think\Db::name("clients")->where("id", "<>", $uid)->where("email", $params["email"])->count();
				if ($email_exist > 0) {
					return jsonrule(["status" => 400, "msg" => lang("邮箱已存在用户使用")]);
				}
			}
			if (!empty($params["password"])) {
				$params["password"] = cmf_password($params["password"]);
			}
			$default_id = \think\Db::name("currencies")->where("default", 1)->value("id");
			$currency = isset($params["currency"]) ? intval($params["currency"]) : $default_id;
			$currencyValid = \think\Db::name("currencies")->where("id", $currency)->find();
			if (!$currencyValid) {
				return jsonrule(["status" => 400, "msg" => lang("CURRENCY_INVALID")]);
			}
			$group = $params["groupid"] ?? 0;
			if ($group != 0) {
				$groupValid = \think\Db::name("client_groups")->where("id", $group)->find();
				if (!$groupValid) {
					return jsonrule(["status" => 400, "msg" => lang("GROUP_INVALID")]);
				}
			}
			$language = isset($params["language"]) ? trim($params["language"]) : "zh_cn";
			if (!in_array($language, array_keys(get_language_list()))) {
				return jsonrule(["status" => 400, "msg" => lang("LANUAGE_INVALID")]);
			}
			$defaultgateway = isset($params["defaultgateway"]) ? $params["defaultgateway"] : gateway_list()[0]["name"];
			if (!in_array($defaultgateway, array_column(gateway_list(), "name"))) {
				return jsonrule(["status" => 400, "msg" => lang("无效支付方式")]);
			}
			$clients = \think\Db::name("clients")->field("credit,currency")->where("id", $params["client_id"])->find();
			if ($clients["currency"] != $params["currency"] && $clients["credit"] > 0) {
				return jsonrule(["status" => 400, "msg" => lang("USER_CREDIT_GREATER_ZERO")]);
			}
			if (isset($params["aff_id_uid"])) {
				admin_update_aff($uid, $params["aff_id_uid"]);
			}
			unset($params["client_id"]);
			unset($params["aff_id_uid"]);
			if (isset($params["custom"]) && is_array($params["custom"])) {
				$customs = $params["custom"];
				unset($params["custom"]);
			}
			if (isset($params["avatar"][0])) {
				$upload = new \app\common\logic\Upload();
				$avatar = $upload->moveTo($params["avatar"], config("client_avatar"));
				if (isset($avatar["error"])) {
					return jsonrule(["status" => 400, "msg" => $avatar["error"]]);
				}
			} else {
				unset($params["avatar"]);
			}
			$params["update_time"] = time();
			$result = \think\Db::name("clients")->where("id", $uid)->update($params);
			hook("client_edit", ["userid" => $params["client_id"], "sex" => $params["sex"], "avatar" => $params["avatar"], "profession" => $params["profession"], "signature" => $params["signature"], "companyname" => $params["companyname"], "email" => $params["email"], "country" => $params["country"], "province" => $params["province"], "city" => $params["city"], "region" => $params["region"], "address1" => $params["address1"], "postcode" => $params["postcode"], "phone_code" => $params["phone_code"], "phonenumber" => $params["phonenumber"], "notes" => $params["notes"], "groupid" => $params["groupid"]]);
			if ($result) {
				if (isset($params["password"])) {
					\think\facade\Cache::set("client_user_update_pass_" . $uid, $this->request->time(), 7200);
				}
				if ($customs) {
					$custom_model = new \app\common\model\CustomfieldsModel();
					$client_customs = \think\Db::name("customfields")->field("id,fieldname,fieldtype,fieldoptions,required,regexpr")->where("type", "client")->select()->toArray();
					$res = $custom_model->check($client_customs, $customs);
					if ($res["status"] == "error") {
						return jsonrule(["status" => 400, "msg" => $res["msg"]]);
					}
					$custom_model->updateCustomValue(0, $uid, $customs, "client");
				}
				$dec = "";
				if (!empty($params["username"]) && $params["username"] != $resultuid["username"]) {
					$dec .= "客户名由“" . $resultuid["username"] . "”改为“" . $params["username"] . "”，";
				}
				if (!empty($params["password"]) && $params["password"] != $resultuid["password"]) {
					$dec .= "修改密码，";
				}
				if (!empty($params["sex"]) && $params["sex"] != $resultuid["sex"]) {
					$arr = ["未知", "男", "女"];
					$dec .= "性别由“" . $arr[$resultuid["sex"]] . "”改为“" . $arr[$params["sex"]] . "”，";
				}
				if (!empty($params["qq"]) && $params["qq"] != $resultuid["qq"]) {
					$dec .= "qq由“" . $resultuid["qq"] . "”改为“" . $params["qq"] . "”，";
				}
				if (!empty($params["avatar"]) && $params["avatar"] != $resultuid["avatar"]) {
					$dec .= "头像由“" . $resultuid["avatar"] . "”改为“" . $params["avatar"] . "”，";
				}
				if (!empty($params["profession"]) && $params["profession"] != $resultuid["profession"]) {
					$dec .= "职业由“" . $resultuid["profession"] . "”改为“" . $params["profession"] . "”，";
				}
				if (!empty($params["signature"]) && $params["signature"] != $resultuid["signature"]) {
					$dec .= "个性签名由”" . $resultuid["signature"] . "“改为”" . $params["signature"] . "“，";
				}
				if (!empty($params["companyname"]) && $params["companyname"] != $resultuid["companyname"]) {
					$dec .= "所在公司由“" . $resultuid["companyname"] . "”改为“" . $params["companyname"] . "”，";
				}
				if (!empty($params["email"]) && $params["email"] != $resultuid["email"]) {
					$dec .= "邮件由“" . $resultuid["email"] . "”改为“" . $params["email"] . "”，";
				}
				if (!empty($params["country"]) && $params["country"] != $resultuid["country"]) {
					$dec .= "国家由“" . $resultuid["country"] . "”改为“" . $params["country"] . "”，";
				}
				if (!empty($params["province"]) && $params["province"] != $resultuid["province"]) {
					$dec .= "省份由“" . $resultuid["province"] . "”改为“" . $params["province"] . "”，";
				}
				if (!empty($params["city"]) && $params["city"] != $resultuid["city"]) {
					$dec .= "城市由“" . $resultuid["city"] . "”改为“" . $params["city"] . "”，";
				}
				if (!empty($params["region"]) && $params["region"] != $resultuid["region"]) {
					$dec .= "区由“" . $resultuid["region"] . "”改为“" . $params["region"] . "”，";
				}
				if (!empty($params["address1"]) && $params["address1"] != $resultuid["address1"]) {
					$dec .= "具体地址1由“" . $resultuid["address1"] . "”改为“" . $params["address1"] . "”，";
				}
				if (!empty($params["address2"]) && $params["address2"] != $resultuid["address2"]) {
					$dec .= "具体地址2由“" . $resultuid["address2"] . "”改为“" . $params["address2"] . "”，";
				}
				if (!empty($params["postcode"]) && $params["postcode"] != $resultuid["postcode"]) {
					$dec .= "邮编由“" . $resultuid["postcode"] . "”改为“" . $params["postcode"] . "”，";
				}
				if (!empty($params["phone_code"]) && $params["phone_code"] != $resultuid["phone_code"]) {
					$dec .= "国际电话区号由“" . $resultuid["phone_code"] . "”改为“" . $params["phone_code"] . "”，";
				}
				if (!empty($params["phonenumber"]) && $params["phonenumber"] != $resultuid["phonenumber"]) {
					$dec .= "电话由“" . $resultuid["phonenumber"] . "”改为“" . $params["phonenumber"] . "”，";
				}
				if (!empty($params["defaultgateway"]) && $params["defaultgateway"] != $resultuid["defaultgateway"]) {
					$arr = gateway_list();
					$arr = array_column($arr, "title", "name");
					$dec .= "选择默认支付接口由“" . $arr[$resultuid["defaultgateway"]] . "”改为“" . $arr[$params["defaultgateway"]] . "”，";
				}
				if (!empty($params["notes"]) && $params["notes"] != $resultuid["notes"]) {
					$dec .= "管理员备注由“" . $resultuid["notes"] . "”改为“" . $params["notes"] . "”，";
				}
				if (!empty($params["groupid"]) && $params["groupid"] != $resultuid["groupid"]) {
					$clientGroups = \think\Db::name("client_groups")->field("id,group_name")->where("id", $resultuid["groupid"])->find();
					$clientGroups1 = \think\Db::name("client_groups")->field("id,group_name")->where("id", $params["groupid"])->find();
					$dec .= "客户分组由“" . $clientGroups["group_name"] . "”改为“" . $clientGroups1["group_name"] . "”，";
				}
				if ($params["status"] != $resultuid["status"]) {
					$str = "";
					switch ($resultuid["status"]) {
						case 1:
							$str = "正常";
							break;
						case 2:
							$str = "关闭";
							break;
						default:
							$str = "禁用";
							break;
					}
					if ($params["status"] == 1) {
						$dec .= "状态由\"" . $str . "\"改为\"正常\"，";
					} elseif ($params["status"] == 2) {
						$dec .= "状态由\"" . $str . "\"改为\"关闭\"，";
					} else {
						$dec .= "状态由\"" . $str . "\"改为\"禁用\"，";
					}
				}
				if (!empty($params["language"]) && $params["language"] != $resultuid["language"]) {
					$arr = ["zh-xg" => "中文繁体", "zh-cn" => "中文简体", "en-us" => "英语"];
					$dec .= "语言由“" . $arr[$resultuid["language"]] . "”改为“" . $arr[$params["language"]] . "”，";
				}
				if (!empty($params["know_us"]) && $params["know_us"] != $resultuid["know_us"]) {
					$dec .= "了解途径由“" . $resultuid["know_us"] . "”改为“" . $params["know_us"] . "”，";
				}
				if (!empty($params["sale_id"]) && $params["sale_id"] != $resultuid["sale_id"]) {
					$uspre = \think\Db::name("user")->where("id", $resultuid["sale_id"])->field("user_nickname")->find();
					$us = \think\Db::name("user")->where("id", $params["sale_id"])->field("user_nickname")->find();
					$dec .= "销售由“" . $uspre["user_nickname"] . "”改为“" . $us["user_nickname"] . "”，";
				}
				if ($params["initiative_renew"] != $resultuid["initiative_renew"]) {
					$str = "";
					switch ($resultuid["initiative_renew"]) {
						case 1:
							$str = "开启";
							break;
						case 0:
							$str = "关闭";
							break;
					}
				}
				if ($dec == "") {
					$dec = "什么也没修改";
				}
				active_log(sprintf($this->lang["UserManage_user_profilePost_success"], $uid, $dec), $uid, "", 1, 1, true);
				active_log(sprintf($this->lang["UserManage_user_profilePost_success_home"]), $uid, "", 2);
				unset($dec);
				return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
			} else {
				return jsonrule(["status" => 400, "msg" => lang("UPDATE FAIL")]);
			}
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 创建客户页面
	 * @description 接口说明:创建客户页面
	 * @return currencies:货币种类(所有默认值都在profile中找)
	 * @return country:国家列表
	 * @return areas:区域列表
	 * @return sms_country:国际电话区号:nicename(名称)+phone_code(区号)
	 * @return gateway:支付方式
	 * @return language:语言列表
	 * @return client_groups:用户组列表
	 * @return client_status:用户状态列表（0未激活，1激活，2关闭）
	 * @return customs:用户自定义字段@
	 * @customs  id:自定义字段ID
	 * @customs  fieldname:字段名称
	 * @customs  fieldtype:类型:text 文本框 link 链接 password 密码 dropdown 下拉 tickbox 选项框 textarea 文本区
	 * @customs  description:描述
	 * @customs  fieldoptions:选项
	 * @customs  regexpr:正则匹配
	 * @customs  required:1必填，0非必填
	 * @customs  sortorder:排序
	 * @author wyh
	 * @url /admin/create_client
	 * @method GET
	 */
	public function createClient()
	{
		$re = [];
		$currencies = \think\Db::name("currencies")->field("id,code")->select();
		foreach ($currencies as $key => $currency) {
			$currenciesFilter[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $currency);
		}
		$country = config("country.country");
		$language = get_language_list();
		$customs = \think\Db::name("customfields")->field("id,fieldname,fieldtype,description,fieldoptions,regexpr,required,sortorder")->where("type", "client")->select()->toArray();
		$re["status"] = 200;
		$re["msg"] = lang("SUCCESS MESSAGE");
		$re["currencies"] = $currenciesFilter;
		$re["language"] = $language;
		$re["sale"] = get_sale();
		$re["client_status"] = config("client_status");
		$re["country"] = $country;
		$re["customs"] = $customs;
		return jsonrule($re);
	}
	/**
	 * @title 创建客户页面提交
	 * @description 接口说明:创建客户页面提交
	 * @param .name:username type:string require:1 default:1 other: desc:用户名
	 * @param .name:password type:string require:1 default:1 other: desc:密码
	 * @param .name:sex type:int require:1 default:1 other: desc:性别（0未知，1男，2女）
	 * @param .name:avatar type:string require:0 default:1 other: desc:头像
	 * @param .name:profession type:string require:0 default:1 other: desc:职业
	 * @param .name:signature type:string require:0 default:1 other: desc:个性签名
	 * @param .name:companyname type:string require:0 default:1 other: desc:所在公司
	 * @param .name:email type:string require:0 default:0 other: desc:邮件
	 * @param .name:country type:string require:0 default:0 other: desc:国家
	 * @param .name:province type:string require:0 default:0 other: desc:省份
	 * @param .name:city type:string require:0 default:0 other: desc:城市
	 * @param .name:region type:string require:0 default:0 other: desc:区
	 * @param .name:address1 type:string require:0 default:1 other: desc:具体地址1
	 * @param .name:postcode type:string require:0 default:1 other: desc:邮编
	 * @param .name:phone_code type:int require:0 default:1 other: desc:电话区号
	 * @param .name:phonenumber type:string require:0 default:1 other: desc:电话
	 * @param .name:currency type:int require:0 default:1 other: desc:使用货币ID
	 * @param .name:defaultgateway type:string require:1 default:1 other: desc:选择默认支付接口
	 * @param .name:notes type:string require:0 default:0 other: desc:管理员备注
	 * @param .name:groupid type:int require:0 default:0 other: desc:用户组ID
	 * @param .name:status type:int require:0 default:0 other: desc:状态（0未激活，1激活，2关闭）
	 * @param .name:language type:string require:0 default:0 other: desc:语言(传zh_cn/zh_xg/en_us等)
	 * @param .name:know_us type:string require:0 default:0 other: desc:了解途径
	 * @param .name:custom[id] type:string require:0 default:0 other: desc:自定义字段值.形式：custom[id] = value;
	 * @param .name:is_sale type:string require:0 default:0 other: desc:是否是销售;
	 * @param .name:sale_is_use type:string require:0 default:0 other: desc:销售是否启用;
	 * @author wyh
	 * @url /admin/create_client_post
	 * @method POST
	 */
	public function createClientPost()
	{
		if ($this->request->isPost()) {
			$params = $this->request->only(["username", "password", "sex", "avatar", "profession", "signature", "companyname", "email", "country", "province", "city", "region", "address1", "postcode", "phone_code", "phonenumber", "currency", "defaultgateway", "notes", "qq", "sale_id", "groupid", "status", "language", "know_us", "sale_is_use", "only_mine", "marketing_emails_opt_in", "custom"]);
			$hookreturns = hook("client_details_validate", ["username" => $params["username"], "sex" => $params["sex"], "avatar" => $params["avatar"], "profession" => $params["profession"], "signature" => $params["signature"], "companyname" => $params["companyname"], "email" => $params["email"], "country" => $params["country"], "province" => $params["province"], "city" => $params["city"], "region" => $params["region"], "address1" => $params["address1"], "postcode" => $params["postcode"], "phone_code" => $params["phone_code"], "phonenumber" => $params["phonenumber"], "notes" => $params["notes"], "groupid" => $params["groupid"], "marketing_emails_opt_in" => $params["marketing_emails_opt_in"]]);
			if (is_array($hookreturns) && !empty($hookreturns[0])) {
				active_log(sprintf($this->lang["UserManage_user_createClientPost_fail"], implode("\r\n", $hookreturns)));
				return jsonrule(["status" => 400, "msg" => implode("\r\n", $hookreturns)]);
			}
			$validate = new \app\admin\validate\UserManageValidate();
			if (!$validate->scene("usermanage")->check($params)) {
				active_log(sprintf($this->lang["UserManage_user_createClientPost_fail"], $validate->getError()));
				return jsonrule(["status" => 400, "msg" => $validate->getError()]);
			}
			if (empty($params["email"]) && empty($params["phonenumber"])) {
				active_log(sprintf($this->lang["UserManage_user_createClientPost_fail"], lang("SERVER_USER_PHONE_EMAIL")));
				return jsonrule(["status" => 400, "msg" => lang("SERVER_USER_PHONE_EMAIL")]);
			}
			if (!empty($params["password"])) {
				$params["password"] = cmf_password($params["password"]);
			}
			if (!empty($params["phonenumber"])) {
				$phone_exist = \think\Db::name("clients")->where("phonenumber", $params["phonenumber"])->count();
				if ($phone_exist > 0) {
					active_log(sprintf($this->lang["UserManage_user_createClientPost_fail_phone"]));
					return jsonrule(["status" => 400, "msg" => lang("手机已存在用户使用")]);
				}
			}
			if (!empty($params["email"])) {
				$email_exist = \think\Db::name("clients")->where("email", $params["email"])->count();
				if ($email_exist > 0) {
					active_log(sprintf($this->lang["UserManage_user_createClientPost_fail_email"]));
					return jsonrule(["status" => 400, "msg" => lang("邮箱已存在用户使用")]);
				}
			}
			$default_id = \think\Db::name("currencies")->where("default", 1)->value("id");
			$currency = isset($params["currency"]) ? intval($params["currency"]) : $default_id;
			$currencyValid = \think\Db::name("currencies")->where("id", $currency)->find();
			if (!$currencyValid) {
				active_log(sprintf($this->lang["UserManage_user_createClientPost_fail"], lang("CURRENCY_INVALID")));
				return jsonrule(["status" => 400, "msg" => lang("CURRENCY_INVALID")]);
			}
			$group = $params["groupid"] ?? 0;
			if ($group != 0) {
				$groupValid = \think\Db::name("client_groups")->where("id", $group)->find();
				if (!$groupValid) {
					active_log(sprintf($this->lang["UserManage_user_createClientPost_fail"], lang("GROUP_INVALID")));
					return jsonrule(["status" => 400, "msg" => lang("GROUP_INVALID")]);
				}
			}
			$language = isset($params["language"]) ? trim($params["language"]) : "zh-cn";
			if (!in_array($language, array_keys(get_language_list()))) {
				active_log(sprintf($this->lang["UserManage_user_createClientPost_fail"], lang("LANGUAGE_INVALID")));
				return jsonrule(["status" => 400, "msg" => lang("LANGUAGE_INVALID")]);
			}
			$params["create_time"] = time();
			if (isset($params["custom"]) && is_array($params["custom"])) {
				$customs = $params["custom"];
				unset($params["custom"]);
			}
			$defaultgateway = isset($params["defaultgateway"]) ? $params["defaultgateway"] : gateway_list()[0]["name"];
			if (!in_array($defaultgateway, array_column(gateway_list(), "name"))) {
				return jsonrule(["status" => 400, "msg" => lang("无效支付方式")]);
			}
			if (isset($params["avatar"][0])) {
				$upload = new \app\common\logic\Upload();
				$avatar = $upload->moveTo($params["avatar"], config("client_avatar"));
				if (isset($avatar["error"])) {
					active_log(sprintf($this->lang["UserManage_user_createClientPost_fail"], $avatar["error"]));
					return jsonrule(["status" => 400, "msg" => $avatar["error"]]);
				}
			} else {
				$params["avatar"] = "用户头像2-" . rand(10, 20) . ".jpg";
			}
			$params["api_password"] = aesPasswordEncode(randStrToPass(12, 0));
			$params["api_open"] = 0;
			$userid = \think\Db::name("clients")->insertGetId($params);
			updateUgp($userid);
			hook("client_add", ["userid" => $userid, "username" => $params["username"], "password" => $params["password"], "sex" => $params["sex"], "avatar" => $params["avatar"], "profession" => $params["profession"], "signature" => $params["signature"], "companyname" => $params["companyname"], "email" => $params["email"], "country" => $params["country"], "province" => $params["province"], "city" => $params["city"], "region" => $params["region"], "address1" => $params["address1"], "postcode" => $params["postcode"], "phone_code" => $params["phone_code"], "phonenumber" => $params["phonenumber"], "notes" => $params["notes"], "groupid" => $params["groupid"], "initiative_renew" => $params["initiative_renew"]]);
			if ($userid) {
				if ($customs) {
					$custom_model = new \app\common\model\CustomfieldsModel();
					$client_customs = \think\Db::name("customfields")->field("id,fieldname,fieldtype,fieldoptions,required,regexpr")->where("type", "client")->select()->toArray();
					$res = $custom_model->check($client_customs, $customs);
					if ($res["status"] == "error") {
						return json(["status" => 400, "msg" => $res["msg"]]);
					}
					$custom_model->updateCustomValue(0, $userid, $customs, "client");
				}
				active_log(sprintf($this->lang["UserManage_user_createClientPost_success"], $params["username"], $userid), $userid);
				active_log(sprintf($this->lang["UserManage_user_createClientPost_success"], $params["username"], $userid), $userid, "", 2);
				return json(["status" => 200, "msg" => lang("ADD SUCCESS")]);
			} else {
				return json(["status" => 400, "msg" => lang("ADD FAIL")]);
			}
		}
		return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 关闭(或者开启)客户
	 * @description 接口说明:关闭(或者开启)客户
	 * @param .name:uid type:int require:1 default:0 other: desc:客户ID
	 * @author wyh
	 * @url /admin/close_client/:uid
	 * @param .name:type type:string require:1 default:1 other: desc:类型：close关闭客户，open开启
	 * @method GET
	 */
	public function closeClient()
	{
		$params = $this->request->param();
		$uid = isset($params["uid"]) ? intval($params["uid"]) : "";
		if (!$uid) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		if (!$this->check1($uid)) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		$type = isset($params["type"]) ? $params["type"] : "close";
		if ($type == "close") {
			\think\Db::startTrans();
			try {
				$clients = \think\Db::name("clients")->field("status,username")->where("id", $uid)->find();
				\think\Db::name("clients")->where("id", $uid)->update(["status" => 0]);
				\think\Db::commit();
				active_log(sprintf($this->lang["UserManage_user_closeClient_success"], $clients["username"], $uid), $uid);
				hook("client_close", ["userid" => $uid]);
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsonrule(["status" => 400, "msg" => lang("UPDATE FAIL")]);
			}
			return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
		} elseif ($type == "open") {
			\think\Db::name("clients")->where("id", $uid)->update(["status" => 1]);
			$clients = \think\Db::name("clients")->field("status,username")->where("id", $uid)->find();
			active_log(sprintf($this->lang["UserManage_user_openClient_success"], $clients["username"], $uid), $uid);
			return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => "错误操作"]);
		}
	}
	/**
	 * @title 删除客户
	 * @description 接口说明:删除客户
	 * @param .name:uid type:int require:1 default:0 other: desc:客户ID
	 * @author wyh
	 * @url /admin/delete_client/:uid
	 * @method GET
	 */
	public function deleteClient()
	{
		$params = $this->request->param();
		$uid = isset($params["uid"]) ? intval($params["uid"]) : "";
		if (!$uid) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		if (!$this->check1($uid)) {
			return jsonrule(["status" => 400, "msg" => \lang("NOT_USER_ID")]);
		}
		hook("pre_client_delete", ["userid" => $uid]);
		\think\Db::startTrans();
		try {
			$clients = \think\Db::name("clients")->field("username")->where("id", $uid)->find();
			$avatar = \think\Db::name("clients")->where("id", $uid)->value("avatar");
			@unlink($this->getAvatar . $avatar);
			\think\Db::name("contacts")->where("uid", $uid)->delete();
			$hosts = \think\Db::name("host")->field("id")->where("uid", $uid)->select();
			foreach ($hosts as $host) {
				if (is_array($host)) {
					\think\Db::name("host_config_options")->where("relid", $host["id"])->delete();
				}
			}
			$customfields = \think\Db::name("customfields")->field("id")->where("type", "client")->select();
			foreach ($customfields as $customfield) {
				if (is_array($customfield)) {
					\think\Db::name("customfieldsvalues")->where("fieldid", $customfield["id"])->where("relid", $uid)->delete();
				}
			}
			$customfields2 = \think\Db::name("customfields")->field("id,relid")->where("type", "product")->select();
			foreach ($customfields2 as $customfield2) {
				$hosts2 = \think\Db::name("host")->field("id")->where("uid", $uid)->where("productid", $customfield2["relid"])->select();
				foreach ($hosts2 as $host2) {
					\think\Db::name("customfieldsvalues")->where("fieldid", $customfield2["id"])->where("relid", $host2["id"])->delete();
				}
			}
			\think\Db::name("orders")->where("uid", $uid)->delete();
			\think\Db::name("host")->where("uid", $uid)->delete();
			\think\Db::name("invoices")->where("uid", $uid)->delete();
			\think\Db::name("invoice_items")->where("uid", $uid)->delete();
			\think\Db::name("accounts")->where("uid", $uid)->delete();
			$tickets = \think\Db::name("ticket")->where("uid", $uid)->select();
			foreach ($tickets as $ticket) {
				$ticketid = $ticket["id"];
				\think\Db::name("ticket_reply")->where("tid", $ticketid)->delete();
				\think\Db::name("ticket_note")->where("tid", $ticketid)->delete();
				\think\Db::name("ticket")->where("id", $ticketid)->delete();
			}
			\think\Db::name("credit")->where("uid", $uid)->delete();
			\think\Db::name("activity_log")->where("uid", $uid)->delete();
			\think\Db::name("clients")->where("id", $uid)->delete();
			\think\Db::name("certifi_company")->where("auth_user_id", $uid)->delete();
			\think\Db::name("certifi_person")->where("auth_user_id", $uid)->delete();
			\think\Db::name("certifi_log")->where("uid", $uid)->delete();
			\think\Db::name("contract_pdf")->where("uid", $uid)->delete();
			\think\Db::name("clients_oauth")->where("uid", $uid)->delete();
			\think\Db::name("upgrades")->where("uid", $uid)->delete();
			$customs = \think\Db::name("customfields")->where("type", "client")->select()->toArray();
			\think\Db::name("customfieldsvalues")->whereIn("fieldid", array_column($customs, "id"))->where("relid", $uid)->delete();
			$custom_model = new \app\common\model\CustomfieldsModel();
			$custom_model->deleteCustomValue(0, $uid, $type = "client");
			active_log(sprintf($this->lang["UserManage_user_deleteClient_success"], $clients["username"], $uid), $uid);
			active_log(sprintf($this->lang["UserManage_user_deleteClient_success"], $clients["username"], $uid), $uid, "", 2);
			\think\Db::name("developer")->where("uid", $uid)->delete();
			hook("client_delete", ["userid" => $uid]);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title 日志记录
	 * @description 接口说明:日志记录
	 * @author 刘国栋
	 * @url log_record
	 * @method GET
	 * @time 2020-05-21
	 * @param name:page type:int  require:0  default:1 other: desc:页码
	 * @param name:limit type:int  require:0  default:1 other: desc:每页条数
	 * @param name:search_time type:int  require:0  default: other: desc:传入时间戳，返回当天日志
	 * @param name:search_desc type:string  require:0  default: other: desc:通过描述查询
	 * @param name:search_ip type:string  require:0  default: other: desc:ip地址查询
	 * @param name:orderby type:string  require:0  default:id other: desc:排序字段
	 * @param name:sorting type:string  require:0  default:asc other: desc:desc/asc，顺序或倒叙
	 * @param name:type type:string  require:0  default:asc other: desc:可选参数(值：host),服务器里的日志
	 * @param name:keywords type:string  require:0  default:asc other: desc:keywords关键字搜索
	 * @param name:uid type:int  require:0  default:1 other: desc:用户id
	 * @return log_list:日志数据@
	 * @log_list  create_time:时间
	 * @log_list  description:描述
	 * @log_list  user:用户
	 * @log_list  ipaddr:ip地址
	 * @return count:数量
	 */
	public function logRecord()
	{
		$param = $this->request->param();
		$uid = intval($param["uid"]);
		if (!$this->check1($uid)) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		$page = isset($param["page"]) ? intval($param["page"]) : config("page");
		$limit = isset($param["limit"]) ? intval($param["limit"]) : (configuration("NumRecordstoDisplay") ?: config("limit"));
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sorting = $param["sorting"] ?? "DESC";
		$fun = function (\think\db\Query $query) use($uid, $param) {
			$query->where("uid", $uid);
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("create_time", "<=", $end_time);
				$query->where("create_time", ">=", $start_time);
			}
			if (!empty($param["search_desc"])) {
				$search_desc = $param["search_desc"];
				$query->where("description", "like", "%{$search_desc}%");
			}
			if (!empty($param["search_ip"])) {
				$search_ip = $param["search_ip"];
				$query->where("ipaddr", "like", "%{$search_ip}%");
			}
		};
		$logs = \think\Db::name("activity_log")->field("create_time,id,ipaddr,description,uid,user,port")->where($fun)->withAttr("description", function ($value, $data) {
			$pattern = "/(?P<name>\\w+ ID):(?P<digit>\\d+)/";
			preg_match_all($pattern, $value, $matches);
			$name = $matches["name"];
			$digit = $matches["digit"];
			if (!empty($name)) {
				foreach ($name as $k => $v) {
					$relid = $digit[$k];
					$str = $v . ":" . $relid;
					if ($v == "Invoice ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/bill-detail?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "User ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-view/abstract?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "FlowPacket ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/dcim-traffic?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Host ID") {
						$host = \think\Db::name("host")->alias("a")->field("a.uid")->where("a.id", $relid)->find();
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-view/product-innerpage?hid=" . $relid . "&id=" . $host["uid"] . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Promo_codeID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/promo-code-add?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Order ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/order-detail?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Admin ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/admin-edit?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Contacts ID") {
					} elseif ($v == "Ticket ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/support-ticket?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Product ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/edit-product?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "IP") {
					} elseif ($v == "Service ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/add-server?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Create ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/balance-details?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Transaction ID") {
					} elseif ($v == "Role ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/permissions-edit?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Group ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-group?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Currency ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/currency-settings?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
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
		$count = \think\Db::name("activity_log")->where($fun)->count();
		$returndata = [];
		$returndata["count"] = $count;
		$returndata["log_list"] = $logs;
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $returndata]);
	}
	/**
	 * @title 认证列表
	 * @description 接口说明:认证列表
	 * @param .name:order type:string require:0 default:id other: desc:排序字段
	 * @param .name:sort type:string require:0 default:DESC other: desc:排序规则
	 * @param .name:page type:int require:0 default:1 other: desc:页码
	 * @param .name:limit type:int require:0 default:10 other: desc:每页数据量
	 * @param .name:type type:int require:1 default:1 other: desc:认证类型1=公司2=个人
	 * @return .auth_user_id:用户id
	 * @return .auth_rela_name:真实姓名
	 * @return .auth_card_type:认证方式1=大陆 0 =非大陆
	 * @return .auth_card_number:认证卡号
	 * @return .company_name:公司名称
	 * @return .company_organ_code:公司代码
	 * @return .img_one:正面照片
	 * @return .img_two:反面照片
	 * @return .img_three:公司执照
	 * @return .status:认证状态1已认证，2未通过，3待审核，4已提交资料
	 * @return .cerify_id:阿里认证id
	 * @return .auth_fail:失败原因
	 * @return .create_time:创建时间
	 * @return .update_time:修改时间
	 * @author liyongjun
	 * @url /admin/cerify_list
	 * @method GET
	 */
	public function cerify_list()
	{
		$param = $this->request->param();
		$page = isset($param["page"][0]) && $param["page"] >= 1 ? \intval($param["page"]) : 1;
		$limit = isset($param["limit"][0]) && $param["limit"] >= 10 ? \intval($param["limit"]) : 10;
		$order = isset($param["order"][0]) ? \strval($param["order"]) : "id";
		$sort = isset($param["sort"][0]) && $param["sort"] === "ASC" ? "ASC" : "DESC";
		$where = [];
		$table_name = "certifi_person";
		if (isset($param["type"]) === 2) {
			$table_name = "certifi_company";
		}
		$list = \think\Db::name($table_name)->where($where)->order($order, $sort)->page($page, $limit)->select();
		$count = \think\Db::name($table_name)->where($where)->count();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["list" => $list, "total" => $count, "page" => $page, "limit" => $limit]]);
	}
	/**
	 * @title 实名认证日志列表
	 * @description 接口说明:实名认证日志列表
	 * @param .name:order type:string require:0 default:id other: desc:排序字段
	 * @param .name:sort type:string require:0 default:DESC other: desc:排序规则
	 * @param .name:page type:int require:0 default:1 other: desc:页码
	 * @param .name:limit type:int require:0 default:10 other: desc:每页数据量
	 * @param .name:type type:int require:0 default:1 other: desc:认证类型1=公司2=个人3=个人转公司
	 * @param .name:status  type:int require:0 default:1 other: desc:1已认证，2未通过，3待审核，4已提交资料
	 * @param .name:uid  type:int require:0 default:1 other: desc:用户id
	 * @param .name:keywords  type:string require:0 default:1 other: desc:关键字
	 * @return .auth_user_id:用户id
	 * @return .auth_rela_name:真实姓名
	 * @return .auth_card_type:认证方式1=大陆 0 =非大陆
	 * @return .auth_card_number:认证卡号
	 * @return .company_name:公司名称
	 * @return .company_organ_code:公司代码
	 * @return .pic:照片集合
	 * @return .status:认证状态1已认证，2未通过，3待审核，4已提交资料
	 * @return .error:失败原因
	 * @return .create_time:创建时间
	 * @return .type:认证类型1=个人2=企业3=个人转企业
	 * @return .is_newest:是否最新false/true
	 * @author liyongjun
	 * @url /admin/cerify_log_list
	 * @method GET
	 */
	public function cerifyLogList22()
	{
		$param = $this->request->param();
		$page = isset($param["page"][0]) && $param["page"] >= 1 ? \intval($param["page"]) : 1;
		$limit = isset($param["limit"][0]) && $param["limit"] >= 10 ? \intval($param["limit"]) : 10;
		$order = isset($param["order"][0]) ? \strval($param["order"]) : "id";
		$sort = isset($param["sort"][0]) && $param["sort"] === "ASC" ? "ASC" : "DESC";
		$where = [];
		if (isset($param["type"]) && $param["type"] > 0) {
			$where[] = ["a.type", "=", $param["type"]];
		}
		if (isset($param["status"]) && $param["status"] > 0) {
			$where[] = ["a.status", "=", $param["status"]];
		}
		if (isset($param["uid"]) && $param["uid"] > 0) {
			$where[] = ["a.uid", "=", $param["uid"]];
		}
		$keywords = $param["keywords"];
		$keywords_where = function (\think\db\Query $query) use($keywords) {
			if (!empty($keywords)) {
				$query->whereOr("username", "like", "%{$keywords}%");
				$query->whereOr("certifi_name", "like", "%{$keywords}%");
				$query->whereOr("idcard", "like", "%{$keywords}%");
			}
		};
		$list = \think\Db::name("certifi_log")->alias("a")->leftJoin("clients b", "a.uid = b.id")->field("a.*,b.username")->where($where)->where($keywords_where)->order($order, $sort)->page($page, $limit)->select()->toArray();
		$count = \think\Db::name("certifi_log")->alias("a")->leftJoin("clients b", "a.uid = b.id")->field("a.*,b.username")->where($where)->where($keywords_where)->count();
		$url = $this->getImage;
		foreach ($list as &$v) {
			$v["is_newest"] = false;
			$tmp = \think\Db::name("certifi_log")->where(["uid" => $v["uid"]])->order("id", "desc")->find();
			if ($v["id"] === $tmp["id"]) {
				$v["is_newest"] = true;
			}
			if (isset($v["pic"][0])) {
				$tmp = explode(",", $v["pic"]);
				$v["pic"] = array_map(function ($v1) use($url) {
					return $url . $v1;
				}, $tmp);
			} else {
				$v["pic"] = [];
			}
			$v["user"] = (object) [];
			$tmp = \think\Db::name("clients")->field("id,username,avatar")->find($v["uid"]);
			if (isset($tmp["id"])) {
				$v["user"] = $tmp;
			}
			$name = \think\Db::name("plugin")->where("module", "certification")->where("name", $v["certifi_type"])->value("title");
			$v["certype"] = $name ?: "人工审核";
			$v["custom_fields_log"] = json_decode($v["custom_fields_log"], true) ?: [];
			$v["custom_fields_log_arr"] = [];
			if ($v["custom_fields_log"]) {
				$cname = cmf_parse_name($v["certifi_type"]);
				$customfields = getCertificationCustomFields($cname, "certification");
				$i = 0;
				foreach ($customfields as $customfield) {
					$i++;
					$i <= 10 && ($v["custom_fields_log_arr"][] = ["type" => $customfield["type"], "title" => $customfield["title"], "val" => $v["custom_fields_log"]["custom_fields" . $i] ?? ""]);
				}
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["list" => $list, "total" => $count, "page" => $page, "limit" => $limit]]);
	}
	/**
	 * @title 实名认证日志列表
	 * @description 接口说明:实名认证日志列表
	 * @param .name:order type:string require:0 default:id other: desc:排序字段
	 * @param .name:sort type:string require:0 default:DESC other: desc:排序规则
	 * @param .name:page type:int require:0 default:1 other: desc:页码
	 * @param .name:limit type:int require:0 default:10 other: desc:每页数据量
	 * @param .name:type type:int require:0 default:1 other: desc:认证类型1=公司2=个人3=个人转公司
	 * @param .name:status  type:int require:0 default:1 other: desc:1已认证，2未通过，3待审核，4已提交资料
	 * @param .name:uid  type:int require:0 default:1 other: desc:用户id
	 * @param .name:keywords  type:string require:0 default:1 other: desc:关键字
	 * @return .auth_user_id:用户id
	 * @return .auth_rela_name:真实姓名
	 * @return .auth_card_type:认证方式1=大陆 0 =非大陆
	 * @return .auth_card_number:认证卡号
	 * @return .company_name:公司名称
	 * @return .company_organ_code:公司代码
	 * @return .pic:照片集合
	 * @return .status:认证状态1已认证，2未通过，3待审核，4已提交资料
	 * @return .error:失败原因
	 * @return .create_time:创建时间
	 * @return .type:认证类型1=个人2=企业3=个人转企业
	 * @return .is_newest:是否最新false/true
	 * @author liyongjun
	 * @url /admin/cerify_log_list
	 * @method GET
	 */
	public function cerifyLogList()
	{
		$param = $this->request->param();
		$ids = \think\Db::name("certifi_log")->field("max(id) as ids")->group("uid")->order("id", "desc")->select()->toArray();
		$ids && ($ids = array_column($ids, "ids"));
		$list = \think\Db::name("certifi_log")->alias("a")->leftJoin("clients b", "a.uid = b.id")->field("a.*,b.username")->whereIn("a.id", $ids);
		$param["keywords"] = trim($param["keywords"]);
		if ($param["keywords"]) {
			$list->where("username like \"%" . $param["keywords"] . "%\" or " . "certifi_name like \"%" . $param["keywords"] . "%\" or " . "idcard like \"%" . $param["keywords"] . "%\"");
		}
		if (isset($param["type"]) && $param["type"] > 0) {
			$list->where("a.type", $param["type"]);
		}
		if (isset($param["status"]) && $param["status"] > 0) {
			$list->where("a.status", $param["status"]);
		}
		$count = $list->count();
		$model = $list->order("id", "desc")->page(max(1, $param["page"]), max(1, $param["limit"]))->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["list" => $this->getCerifyPackData($model), "total" => $count, "page" => max(1, $param["page"]), "limit" => max(1, $param["limit"])]]);
	}
	/**
	 * @title 实名认证日志历史记录列表
	 * @description 接口说明:实名认证日志历史记录列表
	 * @param .name:id  type:int require:1 default: other: desc:记录id
	 * @return .auth_user_id:用户id
	 * @return .auth_rela_name:真实姓名
	 * @return .auth_card_type:认证方式1=大陆 0 =非大陆
	 * @return .auth_card_number:认证卡号
	 * @return .company_name:公司名称
	 * @return .company_organ_code:公司代码
	 * @return .pic:照片集合
	 * @return .status:认证状态1已认证，2未通过，3待审核，4已提交资料
	 * @return .error:失败原因
	 * @return .create_time:创建时间
	 * @return .type:认证类型1=个人2=企业3=个人转企业
	 * @return .is_newest:是否最新false/true
	 * @author xue
	 * @url /admin/cerify_history_log
	 * @method GET
	 */
	public function getCerifyHistoryLog()
	{
		$param = $this->request->param();
		$ids = \think\Db::name("certifi_log")->where("id", intval($param["id"]))->value("uid");
		$list = \think\Db::name("certifi_log")->alias("a")->leftJoin("clients b", "a.uid = b.id")->field("a.*,b.username")->where("a.uid", $ids)->where("a.id", "<>", intval($param["id"]));
		$count = $list->count();
		$model = $list->order("id", "desc")->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["list" => $this->getCerifyPackData($model), "total" => $count, "page" => max(1, $param["page"]), "limit" => max(1, $param["limit"])]]);
	}
	protected function getCerifyPackData($model)
	{
		$plugin_name = \think\Db::name("plugin")->where("module", "certification")->select()->toArray();
		$certifi_type = $plugin_name ? array_column($plugin_name, "title", "name") : [];
		$url = $this->getImage;
		foreach ($model as &$v) {
			$v["is_newest"] = true;
			if (isset($v["pic"][0])) {
				$tmp = explode(",", $v["pic"]);
				$v["pic"] = array_map(function ($v1) use($url) {
					return $v1 ? $url . $v1 : "";
				}, $tmp);
			} else {
				$v["pic"] = [];
			}
			$v["certype"] = $certifi_type[$v["certifi_type"]] ?? "人工审核";
			$v["custom_fields_log"] = json_decode($v["custom_fields_log"], true) ?: [];
			$v["custom_fields_log_arr"] = [];
			if ($v["custom_fields_log"]) {
				$cname = cmf_parse_name($v["certifi_type"]);
				$customfields = getCertificationCustomFields($cname, "certification");
				$i = 0;
				foreach ($customfields as $customfield) {
					$i++;
					$i <= 10 && ($v["custom_fields_log_arr"][] = ["type" => $customfield["type"], "title" => $customfield["title"], "val" => $v["custom_fields_log"]["custom_fields" . $i] ?? ""]);
				}
			}
		}
		return $model;
	}
	/**
	 * @title 客户个人实名认证详情
	 * @description 接口说明:客户个人实名认证详情
	 * @param .name:client_id type:int require:1 default:1 other: desc:客户ID
	 * @return .uid:用户id
	 * @return .name:用户名
	 * @return .card_type:身份证类型：1大陆，0非大陆
	 * @return .idcard:身份证号码
	 * @return .status:认证状态(1已认证，2未通过，3待审核，4提交资料）
	 * @return .img_one:身份证正面地址
	 * @return .img_two:身份证反面地址
	 * @return .img_three:执照照片
	 * @return .auth_fail:认证失败原因
	 * @return .certify_id:认证ID
	 * @return .create_time:认证时间
	 * @return .company_name:企业名称
	 * @return .company_organ_code:企业代码
	 * @return .update_time:
	 * @return .type:认证类型1=个人2=企业
	 * @author wyh
	 * @url /admin/certifi_person_detail/:client_id
	 * @method GET
	 */
	public function certifiPersonDetail()
	{
		$data = ["status" => 0];
		$params = $this->request->param();
		$log = \think\Db::name("certifi_log")->where("uid", intval($params["client_id"]))->order("id", "DESC")->find();
		if (!isset($log["id"])) {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "certifi_message" => $data, "card_type" => $this->cardType]);
		}
		if (!$this->check1($params["client_id"])) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		$type = 1;
		$table = "certifi_person";
		if (isset($log["id"]) && $log["type"] > 1) {
			$type = 2;
			$table = "certifi_company";
			$status = \think\Db::name("certifi_company")->where("auth_user_id", intval($params["client_id"]))->value("status");
			if ($status == 2) {
				$type = 1;
				$table = "certifi_person";
			}
		}
		$detail = \think\Db::name($table)->where("auth_user_id", intval($params["client_id"]))->find();
		$person = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $detail);
		$data["uid"] = $person["auth_user_id"];
		$data["name"] = $person["auth_real_name"];
		$data["card_type"] = $person["auth_card_type"];
		$data["idcard"] = $person["auth_card_number"];
		$data["company_name"] = $person["company_name"] ?? "";
		$data["company_organ_code"] = $person["company_organ_code"] ?? "";
		$data["status"] = $person["status"];
		$data["auth_fail"] = $person["auth_fail"];
		$data["certify_id"] = $person["certify_id"];
		$data["create_time"] = $person["create_time"];
		$data["update_time"] = $person["update_time"];
		$data["img_one"] = $person["img_one"] ? $this->getImage . $person["img_one"] : "";
		$data["img_two"] = $person["img_two"] ? $this->getImage . $person["img_two"] : "";
		$data["img_three"] = $person["img_three"] ? $this->getImage . $person["img_three"] : "";
		$data["type"] = $type;
		if (empty($person) && $status == 2 && $table == "certifi_person") {
			$data["status"] = 2;
			$data["type"] = 2;
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "certifi_message" => $data, "card_type" => $this->cardType]);
	}
	/**
	 * @title 客户个人实名认证修改
	 * @description 接口说明:客户个人实名认证修改
	 * @param .name:client_id type:int require:1 default:1 other: desc:客户ID
	 * @param .name:real_name type:int require:1 default:1 other: desc:真实姓名
	 * @param .name:card_type type:int require:1 default:1 other: desc:卡类型：0非大陆，1大陆(默认)
	 * @param .name:idcard type:int require:1 default:1 other: desc:身份证号
	 * @param .name:idimage[] type:int require:1 default:1 other: desc:多文件上传:身份证正面照片、身份证反面照片
	 * @author wyh
	 * @url /admin/certifi_person_modify
	 * @method POST
	 */
	public function certifiPersonModify()
	{
		if ($this->request->isPost()) {
			$re = [];
			$params = $this->request->param();
			$clientid = $params["client_id"];
			if (!$this->check1($clientid)) {
				return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
			}
			$trim = array_map("trim", $params);
			$data = array_map("htmlspecialchars", $trim);
			$idcard = $data["idcard"];
			$result = checkOtherUsed($idcard, $clientid);
			if ($result["status"] == 1001) {
				return jsonrule(["status" => 400, "msg" => lang("CERTIFI_CARD_HAS_USED_BY_OTHER")]);
			}
			$certifi["auth_user_id"] = $clientid;
			$certifi["auth_real_name"] = $data["real_name"];
			$certifi["auth_card_type"] = $data["card_type"];
			$certifi["auth_card_number"] = $data["idcard"];
			$validate = new \app\admin\validate\UserManageValidate();
			$cardtype = isset($data["card_type"]) ? $data["card_type"] : 1;
			if ($cardtype == 0) {
				if (!$validate->scene("gatregion")->check($certifi)) {
					$re["status"] = 406;
					$re["msg"] = $validate->getError();
					return jsonrule($re);
				}
				if (!preg_match("/^((\\s?[A-Za-z])|([A-Za-z]{2}))\\d{6}(\\([0−9aA]\\)|[0-9aA])\$/", $idcard) && !preg_match("/^[a-zA-Z][0-9]{9}\$/", $idcard) && !preg_match("/^[1|5|7][0-9]{6}\\([0-9Aa]\\)/", $idcard)) {
					return jsonrule(["status" => 406, "msg" => lang("CERTIFI_CARD_INVALID")]);
				}
			} elseif ($cardtype == 1) {
				if (!$validate->scene("personedit")->check($certifi)) {
					$re["status"] = 406;
					$re["msg"] = $validate->getError();
					return jsonrule($re);
				}
			} else {
				return jsonrule(["status" => 400, "msg" => lang("CERTIFI_CARD_TYPE_INVALID")]);
			}
			if (empty($_FILES["idimage"]["name"][0])) {
				$re["status"] = 400;
				$re["msg"] = lang("CERTIFI_CARD_FRONT");
				return jsonrule($re);
			}
			if (empty($_FILES["idimage"]["name"][1])) {
				$re["status"] = 400;
				$re["msg"] = lang("CERTIFI_CARD_BACK");
				return jsonrule($re);
			}
			$files = $this->request->file("idimage");
			$result = $this->uploadMultiHandle($files);
			if ($result["status"] == 200) {
				$addresses = explode(",", $result["savename"]);
				$certifi["img_one"] = $addresses[0];
				$certifi["img_two"] = $addresses[1];
			} else {
				return jsonrule($result);
			}
			$certifi["status"] = 3;
			$checkuser = \think\Db::name("certifi_person")->where("auth_user_id", $clientid)->find();
			if (!empty($checkuser)) {
				$imgone = $checkuser["img_one"];
				$imgtwo = $checkuser["img_two"];
				if ($imgone) {
					unlink($this->getImage . $imgone);
				}
				if ($imgtwo) {
					unlink($this->getImage . $imgtwo);
				}
				$certifi["update_time"] = time();
				$res = \think\Db::name("certifi_person")->where("auth_user_id", $clientid)->update($certifi);
			} else {
				$certifi["create_time"] = time();
				$res = \think\Db::name("certifi_person")->insertGetId($certifi);
			}
			if ($res) {
				$description = "客户个人实名认证修改 -  User ID:" . $params["client_id"] . " - 修改前身份证:" . $certifi["auth_card_number"] . " - 真实姓名:" . $certifi["auth_real_name"] . " - 卡类型:" . $certifi["auth_card_type"] . " -- 修改后身份证:" . $data["idcard"] . " - 真实姓名:" . $data["real_name"] . " - 卡类型:" . $data["card_type"] . "- 成功";
				active_log($description, $params["client_id"]);
				$re["status"] = 200;
				$re["msg"] = lang("UPLOAD SUCCESS");
				return jsonrule($re);
			} else {
				$description = "客户个人实名认证修改 -  User ID:" . $params["client_id"] . " - 修改前身份证:" . $certifi["auth_card_number"] . " - 真实姓名:" . $certifi["auth_real_name"] . " - 卡类型:" . $certifi["auth_card_type"] . " -- 修改后身份证:" . $data["idcard"] . " - 真实姓名:" . $data["real_name"] . " - 卡类型:" . $data["card_type"] . " - " . lang("UPLOAD FAIL");
				active_log($description, $params["client_id"]);
				$re["status"] = 400;
				$re["msg"] = lang("UPLOAD FAIL");
				return jsonrule($re);
			}
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 客户企业实名认证详情
	 * @description 接口说明:客户企业实名认证详情
	 * @param .name:client_id type:int require:1 default:1 other: desc:客户ID
	 * @return .uid:用户id
	 * @return .name:用户名
	 * @return .card_type:身份证类型：1大陆，0非大陆
	 * @return .idcard:身份证号码
	 * @return .company_name:企业名称
	 * @return .company_organ_code:营业执照号码
	 * @return .status:认证状态:（空：未认证，1已认证，2未通过，3待审核，4提交资料）
	 * @return .img_one:身份证正面地址
	 * @return .img_two:身份证反面地址
	 * @return .img_three:公司营业执照地址
	 * @return .auth_fail:认证失败原因
	 * @return .certify_id:认证ID
	 * @return .create_time:
	 * @return .update_time:
	 * @author wyh
	 * @url /admin/certifi_company_detail/:client_id
	 * @method GET
	 */
	public function certifiCompanyDetail()
	{
		$params = $this->request->param();
		if (!$this->check1($params["client_id"])) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		$company = \think\Db::name("certifi_company")->field("auth_user_id,auth_real_name,auth_card_type,auth_card_number,
            company_name,company_organ_code,
            status,img_one,img_two,img_three,auth_fail,certify_id,create_time,update_time")->where("auth_user_id", intval($params["client_id"]))->find();
		$company = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $company);
		$companyFilter["uid"] = $company["auth_user_id"];
		$companyFilter["name"] = $company["auth_real_name"];
		$companyFilter["card_type"] = $company["auth_card_type"];
		$companyFilter["idcard"] = $company["auth_card_number"];
		$companyFilter["company_name"] = $company["company_name"];
		$companyFilter["company_organ_code"] = $company["company_organ_code"];
		$companyFilter["status"] = $company["status"];
		$companyFilter["img_one"] = $company["img_one"] ? $this->getImage . $company["img_one"] : "";
		$companyFilter["img_two"] = $company["img_two"] ? $this->getImage . $company["img_two"] : "";
		$companyFilter["img_three"] = $company["img_three"] ? $this->getImage . $company["img_three"] : "";
		$companyFilter["auth_fail"] = $company["auth_fail"];
		$companyFilter["create_time"] = $company["create_time"];
		$companyFilter["update_time"] = $company["update_time"];
		$description = "查看客户企业实名认证详情 -  User ID:" . $params["client_id"] . " - 成功";
		active_log($description, $params["client_id"]);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "certifi_message" => $companyFilter, "card_type" => $this->cardType]);
	}
	/**
	 * @title 客户企业实名认证修改
	 * @description 接口说明:客户企业实名认证修改
	 * @param .name:company_name type:string require:1 default:1 other: desc:企业名称
	 * @param .name:company_organ_code type:string require:1 default:1 other: desc:营业执照号码
	 * @param .name:real_name type:string require:1 default:1 other: desc:提交人姓名
	 * @param .name:card_type type:tinyint require:1 default:1 other: desc:card类型：1内地身份证(默认)；0港澳台身份证
	 * @param .name:idcard type:string require:1 default:1 other: desc:身份证号
	 * @param .name:idimage[] type:image require:1 default:1 other: desc:身份证正面、反面、公司营业执照（多文件上传）
	 * @author wyh
	 * @url /admin/certifi_company_modify
	 * @method POST
	 */
	public function certifiCompanyModify()
	{
		if ($this->request->isPost()) {
			$re = [];
			$params = $this->request->param();
			$clientid = $params["client_id"];
			$trim = array_map("trim", $params);
			$data = array_map("htmlspecialchars", $trim);
			$idcard = $data["idcard"];
			$result = checkOtherUsed($idcard, $clientid);
			if ($result["status"] == 1001) {
				return jsonrule(["status" => 400, "msg" => lang("CERTIFI_CARD_HAS_USED_BY_OTHER")]);
			}
			$certifi["auth_user_id"] = $clientid;
			if (!$this->check1($clientid)) {
				return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
			}
			$certifi["company_name"] = $data["company_name"];
			$certifi["company_organ_code"] = $data["company_organ_code"];
			$certifi["auth_real_name"] = $data["real_name"];
			$certifi["auth_card_number"] = $data["idcard"];
			$certifi["auth_card_type"] = $data["card_type"];
			$validate = new \app\admin\validate\UserManageValidate();
			$cardtype = isset($data["card_type"]) ? $data["card_type"] : 1;
			if ($cardtype == 0) {
				if (!$validate->scene("companygatregion")->check($certifi)) {
					$re["status"] = 406;
					$re["msg"] = $validate->getError();
					return jsonrule($re);
				}
				if (!preg_match("/^((\\s?[A-Za-z])|([A-Za-z]{2}))\\d{6}(\\([0−9aA]\\)|[0-9aA])\$/", $idcard) && !preg_match("/^[a-zA-Z][0-9]{9}\$/", $idcard) && !preg_match("/^[1|5|7][0-9]{6}\\([0-9Aa]\\)/", $idcard)) {
					return jsonrule(["status" => 406, "msg" => lang("CERTIFI_CARD_INVALID")]);
				}
			} elseif ($cardtype == 1) {
				if (!$validate->scene("companyedit")->check($certifi)) {
					$re["status"] = 406;
					$re["msg"] = $validate->getError();
					return jsonrule($re);
				}
			} else {
				return jsonrule(["status" => 400, "msg" => lang("CERTIFI_CARD_TYPE_INVALID")]);
			}
			if (empty($_FILES["idimage"]["name"][0])) {
				$re["status"] = 400;
				$re["msg"] = lang("CERTIFI_CARD_FRONT");
				return jsonrule($re);
			}
			if (empty($_FILES["idimage"]["name"][1])) {
				$re["status"] = 400;
				$re["msg"] = lang("CERTIFI_CARD_BACK");
				return jsonrule($re);
			}
			if (empty($_FILES["idimage"]["name"][2])) {
				$re["status"] = 400;
				$re["msg"] = lang("LICENSE_SCAN");
				return $re;
			}
			$files = $this->request->file("idimage");
			$result = $this->uploadMultiHandle($files);
			if ($result["status"] == 200) {
				$addresses = explode(",", $result["savename"]);
				$certifi["img_one"] = $addresses[0];
				$certifi["img_two"] = $addresses[1];
				$certifi["img_three"] = $addresses[2];
			} else {
				return jsonrule($result);
			}
			$certifi["status"] = 3;
			$checkuser = \think\Db::name("certifi_company")->where("auth_user_id", $clientid)->find();
			if (!empty($checkuser)) {
				$imgone = $checkuser["img_one"];
				$imgtwo = $checkuser["img_two"];
				$imgthree = $checkuser["img_three"];
				if ($imgone) {
					unlink($this->getImage . $imgone);
				}
				if ($imgtwo) {
					unlink($this->getImage . $imgtwo);
				}
				if ($imgthree) {
					unlink($this->getImage . $imgthree);
				}
				$certifi["update_time"] = time();
				$res = \think\Db::name("certifi_company")->where("auth_user_id", $clientid)->update($certifi);
			} else {
				$certifi["create_time"] = time();
				$res = \think\Db::name("certifi_company")->insertGetId($certifi);
			}
			if ($res) {
				$description = "客户企业实名认证修改成功 -  User ID:" . $params["client_id"] . " - 修改前身份证:" . $certifi["auth_card_number"] . " -- 企业名称:" . $data["company_name"] . " -- 营业执照:" . $data["company_organ_code"] . " - 真实姓名:" . $certifi["auth_real_name"] . " - 卡类型:" . $certifi["auth_card_type"] . " -- 修改后身份证:" . $data["idcard"] . " -- 企业名称:" . $data["company_name"] . " -- 营业执照:" . $data["company_organ_code"] . " - 真实姓名:" . $data["real_name"] . " - 卡类型:" . $data["card_type"] . " - 成功";
				active_log($description, $params["client_id"]);
				$re["status"] = 200;
				$re["msg"] = lang("UPLOAD SUCCESS");
				return jsonrule($re);
			} else {
				$description = "客户企业实名认证修改成功 -  User ID:" . $params["client_id"] . " - 修改前身份证:" . $certifi["auth_card_number"] . " -- 企业名称:" . $data["company_name"] . " -- 营业执照:" . $data["company_organ_code"] . " - 真实姓名:" . $certifi["auth_real_name"] . " - 卡类型:" . $certifi["auth_card_type"] . " -- 修改后身份证:" . $data["idcard"] . " -- 企业名称:" . $data["company_name"] . " -- 营业执照:" . $data["company_organ_code"] . " - 真实姓名:" . $data["real_name"] . " - 卡类型:" . $data["card_type"] . " - 错误:" . lang("UPLOAD FAIL");
				active_log($description, $params["client_id"]);
				$re["status"] = 400;
				$re["msg"] = lang("UPLOAD FAIL");
				return jsonrule($re);
			}
		}
		return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
	}
	/**
	 * @title 客户个人认证图片下载
	 * @description 接口说明:客户个人认证图片下载
	 * @param .name:client_id type:int require:1 default:1 other: desc:客户ID
	 * @param .name:type type:string require:0 default:1 other: desc:图片类型：type=idfront(身份证正面照)或者type=idback(身份证反面)
	 * @author wyh
	 * @url /admin/certifi_person_download
	 * @method GET
	 */
	public function certifiPersonDownload()
	{
		$params = $this->request->param();
		$clientId = $params["client_id"];
		if (!$this->check1($clientId)) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		$type = $params["type"];
		$imageAddress = \think\Db::name("certifi_person")->field("img_one,img_two")->where("id", $clientId)->find();
		if ($type == "idfront" && !empty($imageAddress["img_one"])) {
			$file_dir = $this->saveImage . $imageAddress["img_one"];
			$download = new \think\response\Download($file_dir);
			return $download->name($clientId . "_idfront", true);
		}
		if ($type == "idback" && !empty($imageAddress["img_two"])) {
			$file_dir = $this->saveImage . $imageAddress["img_two"];
			$download = new \think\response\Download($file_dir);
			return $download->name($clientId . "_idback", true);
		}
		$description = "下载客户个人认证图片 -  User ID:" . $params["client_id"] . "- 类型:" . $type . " - 成功";
		active_log($description, $params["client_id"]);
		return jsonrule(["status" => 400, "msg" => lang("USER_CERTIFI_FILE_NO_EXIST")]);
	}
	/**
	 * @title 客户认证图片下载
	 * @description 接口说明:客户认证图片下载
	 * @param .name:id type:int require:1 default:1 other: desc:记录id
	 * @param .name:type type:int require:1 default:1 other: desc:图片类型0=正面1=反面2=执照
	 * @author wyh
	 * @url /admin/certifi_download
	 * @method GET
	 */
	public function certifiDownload()
	{
		$params = $this->request->param();
		$id = $params["id"];
		$type = $params["type"];
		$log = \think\Db::name("certifi_log")->where("id", $id)->find();
		$pic = isset($log["pic"][0]) ? explode(",", $log["pic"]) : [];
		if (isset($pic[$type][0])) {
			return download($this->saveImage . $pic[$type], end(explode("^", $pic[$type])));
		}
		$description = "下载客户认证图片 -  认证id:" . $params["id"] . "- 类型:" . $params["type"] . " - 成功";
		active_log($description);
		return jsonrule(["status" => 400, "msg" => lang("USER_CERTIFI_FILE_NO_EXIST")]);
	}
	/**
	 * @title 修改认证状态
	 * @description 接口说明:修改认证状态
	 * @param .name:uid type:int require:1 default:1 other: desc:用户id
	 * @param .name:type type:int require:1 default:1 other: desc:认证类型1=个人2=企业
	 * @param .name:status type:int require:1 default:1 other: desc:状态1已认证，2未通过，3待审核，4已提交资料
	 * @param .name:error type:int require:0 default:1 other: desc:驳回原因
	 * @author wyh
	 * @url /admin/certifi_status
	 * @method POST
	 */
	public function certifiStatus()
	{
		$params = $this->request->param();
		$error = "";
		if (isset($params["error"][0])) {
			$error = $params["error"];
		}
		if (!isset($params["status"]) || $params["status"] >= 5 || $params["status"] <= 0) {
			return json(["status" => 400, "msg" => lang("CERTIFI_CARD_HAS_USED_BY_OTHER")]);
		}
		$uid = $params["uid"];
		if (!$this->check1($uid)) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		$table = $params["type"] == 1 ? "certifi_person" : "certifi_company";
		$certifi = \think\Db::name($table)->where("auth_user_id", $uid)->find();
		if ($params["status"] == 1) {
			$certifimodel = new \app\home\model\CertificationModel();
			if ($certifimodel->checkOtherUsed($certifi["auth_card_number"], $uid)) {
				$description = "修改认证状态 -  User ID:" . $params["uid"] . " - 类型:" . $table . " - 变化前status:" . $certifi["status"] . " - 变化后:" . $params["status"] . " - certifi_log:(status)=>" . $params["status"] . " - 错误:" . lang("CERTIFI_CARD_HAS_USED_BY_OTHER");
				active_log($description, $params["uid"]);
				return json(["status" => 400, "msg" => lang("CERTIFI_CARD_HAS_USED_BY_OTHER")]);
			}
		}
		if (!isset($certifi["id"])) {
			$description = "修改认证状态 -  User ID:" . $params["uid"] . " - 类型:" . $table . " - 变化前status:" . $certifi["status"] . " - 变化后:" . $params["status"] . " - certifi_log:(status)=>" . $params["status"] . " - 错误:" . lang("USER_UNCERTIFI");
			active_log($description, $params["uid"]);
			return jsonrule(["status" => 400, "msg" => lang("USER_UNCERTIFI")]);
		}
		if ($certifi["status"] == $params["status"]) {
			$description = "修改认证状态 -  User ID:" . $params["uid"] . " - 类型:" . $table . " - 变化前status:" . $certifi["status"] . " - 变化后:" . $params["status"] . " - certifi_log:(status)=>" . $params["status"] . " - 错误:" . lang("CERTIFI_CARD_INVALID");
			active_log($description, $params["uid"]);
			return jsonrule(["status" => 406, "msg" => lang("CERTIFI_CARD_INVALID")]);
		}
		\think\Db::startTrans();
		try {
			\think\Db::name($table)->where("id", $certifi["id"])->update(["status" => $params["status"], "auth_fail" => $error]);
			$tmp = \think\Db::name("certifi_log")->where(["uid" => $uid, "type" => $params["type"]])->order("id", "DESC")->find();
			\think\Db::name("certifi_log")->where("id", $tmp["id"])->update(["status" => $params["status"], "error" => $error]);
			if ($params["status"] == 1) {
				if ($table == "certifi_company") {
					\think\Db::name("certifi_person")->where("auth_user_id", $uid)->where("status", 1)->delete();
				}
				$this->passSendSms($tmp);
			}
			if ($params["status"] == 1 && configuration("certifi_realname") == 1) {
				\think\Db::name("clients")->where("id", $tmp["uid"])->update(["username" => $tmp["certifi_name"]]);
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			$description = "修改认证状态 -  User ID:" . $params["uid"] . " - 类型:" . $table . " - 变化前status:" . $certifi["status"] . " - 变化后:" . $params["status"] . " - certifi_log:(status)=>" . $params["status"] . " - 错误:" . $e->getMessage();
			active_log($description, $params["uid"]);
			return jsonrule(["status" => 406, "msg" => $e->getMessage()]);
		}
		unsuspendAfterCertify($uid);
		$description = "";
		switch ($params["status"]) {
			case 1:
				$description = "实名认证通过 -  User ID:" . $params["uid"];
				break;
			case 2:
				$description = "实名认证驳回 -  User ID:" . $params["uid"];
				break;
			case 3:
				$description = "实名认证待审核通过 -  User ID:" . $params["uid"];
				break;
			case 4:
				$description = "实名认证已提交资料 -  User ID:" . $params["uid"];
				break;
		}
		active_log($description, $params["uid"]);
		return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
	}
	/**
	 * @title 用户账单列表
	 * @description 接口说明:
	 * @param .name:uid type:int require:0  other: desc:用户ID
	 * @param .name:hostid type:int require:0  other: desc:产品id
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:页长
	 * @param .name:order type:mix require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序desc/sort
	 * @param .name:payment type:string require:0  other: desc:按付款方式搜索
	 * @param .name:status type:string require:0  other: desc:按支付状态搜索
	 * @param .name:create_time type:string require:0  other: desc:按账单生成日搜索
	 * @param .name:due_time type:string require:0  other: desc:按账单逾期日搜索
	 * @param .name:paid_time type:string require:0  other: desc:按账单支付日搜索
	 * @param .name:subtotal_small type:string require:0  other: desc:按总计搜索(小值)
	 * @param .name:subtotal_big type:string require:0  other: desc:按总计搜索(大值)
	 * @return  data:账单列表@
	 * @data id:账单ID
	 * @data create_time:账单生成日
	 * @data due_time:账单逾期日
	 * @data paid_time:账单支付日
	 * @data subtotal:总计
	 * @data payment:付款方式
	 * @data status:状态(Paid:已支付,Unpaid:未支付,Draft:已草稿,Overdue:已逾期,Cancelled:被取消,Refunded:已退款,Collections:已收藏)
	 * @throws
	 * @author wyh
	 * @url /admin/user_invoice
	 * @method get
	 */
	public function userInvoice()
	{
		$params = $this->request->param();
		$uid = isset($params["uid"]) ? intval($params["uid"]) : "";
		if (!$this->check1($uid)) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		if (!$uid) {
			return jsonrule(["status" => 400, "msg" => "ID_ERROR"]);
		}
		$page = input("get.page", 1, "intval");
		$limit = input("get.limit", 10, "intval");
		$order = input("get.order", "id");
		$sort = input("get.sort", "DESC");
		$page = $page >= 1 ? $page : config("page");
		$limit = $limit >= 1 ? $limit : config("limit");
		if (!in_array($order, ["id", "create_time", "due_time", "paid_time", "subtotal", "payment", "status"])) {
			$order = "id";
		}
		if (!in_array($sort, ["asc", "desc"])) {
			$sort = "asc";
		}
		$where = $this->get_search($params);
		$count = \think\Db::name("invoices")->alias("i")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("invoice_items t", "t.invoice_id = i.id")->field("i.id,i.create_time,i.due_time,i.paid_time,i.subtotal,i.payment,i.status,cu.prefix,cu.suffix,i.type")->where($where)->group("i.id")->count();
		$type_arr = config("invoice_type_all");
		$gateways = gateway_list();
		$invocies = \think\Db::name("invoices")->alias("i")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("invoice_items t", "t.invoice_id = i.id")->field("i.id,i.create_time,i.due_time,i.paid_time,i.subtotal,i.subtotal as sub,i.total,i.credit,i.payment,i.status,cu.prefix,cu.suffix,i.type,i.use_credit_limit")->where($where)->withAttr("payment", function ($value, $data) use($gateways) {
			if ($data["status"] == "Paid") {
				if ($data["use_credit_limit"] == 1) {
					return "信用额";
				} else {
					if ($data["sub"] == $data["credit"]) {
						return "余额支付";
					} else {
						if ($data["sub"] > $data["credit"] && $data["credit"] > 0) {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return "部分余额支付+" . $v["title"];
								}
							}
						} else {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return $v["title"];
								}
							}
						}
					}
				}
			} else {
				foreach ($gateways as $v) {
					if ($v["name"] == $value) {
						return $v["title"];
					}
				}
			}
		})->withAttr("paid_time", function ($value, $data) {
			if ($data["status"] == "Unpaid" || $data["status"] == "Cancelled") {
				return "";
			} else {
				return $value;
			}
		})->withAttr("subtotal", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->withAttr("type", function ($value) use($type_arr) {
			return $type_arr[$value];
		})->order($order, $sort)->page($page)->limit($limit)->group("i.id")->select()->toArray();
		$status = config("invoice_payment_status");
		$gateway = gateway_list();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "count" => $count, "invocies" => $invocies, "invoice_status" => $status]);
	}
	/**
	 * @title 交易流水列表
	 * @description 接口说明:
	 * @author 上官🔪
	 * @url /admin/user_productaccounts
	 * @method get
	 * @param .name:uid type:int require:0 default:1 other: desc:可选参数,用户ID
	 * @param .name:hid type:int require:0 default:1 other: desc:可选参数,产品ID
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段,username,create_time,gateway,description,amount_in,fees,amount_out
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @param .name:show type:string require:0  other: desc:显示类型(amount_in/amount_out)
	 * @param .name:description type:string require:0  other: desc:描述
	 * @param .name:trans_id type:int require:0  other: desc:付款流水号
	 * @param .name:start_time type:int require:0  other: desc:开始时间
	 * @param .name:end_time type:int require:0  other: desc:结束时间
	 * @param .name:amount type:int require:0  other: desc:金额
	 * @param .name:gateway type:int require:0  other: desc:支付方式
	 * @return
	 */
	public function userProductaccounts()
	{
		$data = $this->request->param();
		$order = isset($data["order"]) ? trim($data["order"]) : "id";
		$sort = isset($data["sort"]) ? trim($data["sort"]) : "DESC";
		if (!in_array($order, ["id", "username", "create_time", "gateway", "description", "amount_in", "fees", "amount_out"])) {
			return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
		$limit = isset($data["limit"]) ? intval($data["limit"]) : config("limit");
		$page = isset($data["page"]) ? intval($data["page"]) : config("page");
		$start_time = isset($data["start_time"]) && !empty($data["start_time"]) ? $data["start_time"] : 0;
		$end_time = isset($data["end_time"]) && !empty($data["end_time"]) ? $data["end_time"] : "2147483647";
		$where = [];
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$where[] = ["c.id", "in", $this->str];
		}
		$fun = function (\think\db\Query $query) use($data) {
			$query->where("a.delete_time", 0);
			if (isset($data["uid"]) && !empty($data["uid"])) {
				$query->where("a.uid", $data["uid"]);
			}
			if (isset($data["hid"]) && !empty($data["hid"])) {
				$query->where("h.id", $data["hid"]);
			}
			if (isset($data["show"]) && !empty($data["show"])) {
				$type = $data["show"];
				if (isset($data["amount"]) && !empty($data["amount"])) {
					$query->where("a.{$type}", $data["amount"]);
				}
				$query->where("a.{$type}", ">", 0);
			}
			if (isset($data["amount"]) && !empty($data["amount"])) {
				$query->where("a.amount_in", $data["amount"])->whereOr("a.amount_out", $data["amount"]);
			}
			if (isset($data["trans_id"]) && !empty($data["trans_id"])) {
				$query->where("a.trans_id", "like", "%{$data["trans_id"]}%");
			}
			if (isset($data["description"]) && !empty($data["description"])) {
				$query->where("a.description", "like", "%{$data["description"]}%");
			}
			if (isset($data["gateway"]) && !empty($data["gateway"])) {
				$query->where("a.gateway", $data["gateway"]);
			}
		};
		$sale = array_column(get_sale(), "user_nickname", "id");
		$rows = \think\Db::name("accounts")->alias("a")->leftJoin("orders o", "o.invoiceid=a.invoice_id")->leftJoin("host h", "h.orderid=o.id")->leftjoin("clients c", "c.id=a.uid")->leftjoin("currencies cu", "cu.code = a.currency")->where($fun)->whereBetweenTime("a.pay_time", $start_time, $end_time)->field("a.id,c.id as uid,c.sale_id,c.username,a.currency,cu.prefix,cu.suffix,a.pay_time,a.update_time,a.gateway,a.description,a.amount_in,a.fees,a.amount_out,a.trans_id")->withAttr("amount_in", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->withAttr("fees", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->withAttr("amount_out", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->withAttr("gateway", function ($value) {
			foreach (gateway_list() as $v) {
				if ($v["name"] == $value) {
					return $v["title"];
				}
			}
		})->withAttr("sale_id", function ($value) use($sale) {
			return $sale[$value];
		})->where($where)->order($order, $sort)->page($page)->limit($limit)->select();
		$count = db("accounts")->alias("a")->leftJoin("orders o", "o.invoiceid=a.invoice_id")->leftJoin("host h", "h.orderid=o.id")->leftjoin("clients c", "c.id=a.uid")->leftjoin("currencies cu", "cu.code = a.currency")->where($where)->where($fun)->whereBetweenTime("a.create_time", $start_time, $end_time)->count("a.id");
		$currencys = db("currencies")->distinct(true)->field("id,code,prefix,suffix")->select()->toArray();
		$currency_code = \think\Db::name("currencies")->where("default", 1)->value("code");
		$total = [];
		foreach ($currencys as $item) {
			$fun1 = function (\think\db\Query $query) use($data, $item) {
				$query->where("delete_time", 0);
				$query->where("currency", $item["code"]);
				if (isset($data["uid"]) && !empty($data["uid"])) {
					$query->where("uid", $data["uid"]);
				}
			};
			$amount_in = db("accounts")->where($fun1)->sum("amount_in");
			$amount_out = db("accounts")->where($fun1)->sum("amount_out");
			$fees = db("accounts")->where($fun1)->sum("fees");
			$surplus = bcsub(bcsub($amount_in, $amount_out, 2), $fees, 2);
			$total[$item["code"]] = ["amount_in" => $item["prefix"] . bcsub($amount_in, 0, 2) . $item["suffix"], "amount_out" => $item["prefix"] . bcsub($amount_out, 0, 2) . $item["suffix"], "fees" => $item["prefix"] . bcsub($fees, 0, 2) . $item["suffix"], "surplus" => $item["prefix"] . bcsub($surplus, 0, 2) . $item["suffix"]];
		}
		$pages = ["total" => $count];
		return jsonrule(["data" => $rows, "page" => $pages, "count" => $total, "currency_id" => $currency_code, "status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 用户账单列表
	 * @description 接口说明:
	 * @param .name:uid type:int require:0  other: desc:用户ID
	 * @param .name:hid type:int require:0  other: desc:产品ID
	 * @param .name:hostid type:int require:0  other: desc:产品id
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:页长
	 * @param .name:order type:mix require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序desc/sort
	 * @param .name:payment type:string require:0  other: desc:按付款方式搜索
	 * @param .name:status type:string require:0  other: desc:按支付状态搜索
	 * @param .name:create_time type:string require:0  other: desc:按账单生成日搜索
	 * @param .name:due_time type:string require:0  other: desc:按账单逾期日搜索
	 * @param .name:paid_time type:string require:0  other: desc:按账单支付日搜索
	 * @param .name:subtotal_small type:string require:0  other: desc:按总计搜索(小值)
	 * @param .name:subtotal_big type:string require:0  other: desc:按总计搜索(大值)
	 * @return  data:账单列表@
	 * @data id:账单ID
	 * @data create_time:账单生成日
	 * @data due_time:账单逾期日
	 * @data paid_time:账单支付日
	 * @data subtotal:总计
	 * @data payment:付款方式
	 * @data status:状态(Paid:已支付,Unpaid:未支付,Draft:已草稿,Overdue:已逾期,Cancelled:被取消,Refunded:已退款,Collections:已收藏)
	 * @throws
	 * @author wyh
	 * @url /admin/user_productinvoice
	 * @method get
	 */
	public function userProductInvoice()
	{
		$params = $this->request->param();
		$uid = isset($params["uid"]) ? intval($params["uid"]) : "";
		if (!$this->check1($uid)) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		if (!$uid) {
			return jsonrule(["status" => 400, "msg" => "ID_ERROR"]);
		}
		$page = input("get.page", 1, "intval");
		$limit = input("get.limit", 10, "intval");
		$order = input("get.order", "id");
		$sort = input("get.sort", "DESC");
		$page = $page >= 1 ? $page : config("page");
		$limit = $limit >= 1 ? $limit : config("limit");
		if (!in_array($order, ["id", "create_time", "due_time", "paid_time", "subtotal", "payment", "status"])) {
			$order = "id";
		}
		if (!in_array($sort, ["asc", "desc"])) {
			$sort = "asc";
		}
		$where = $this->get_search($params);
		$count1 = \think\Db::name("invoices")->alias("i")->leftJoin("invoice_items in", "in.invoice_id=i.id")->leftJoin("host h", "h.id=in.rel_id")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("invoice_items t", "t.invoice_id = i.id")->field("i.id,i.create_time,i.due_time,i.paid_time,i.subtotal,i.payment,i.status,cu.prefix,cu.suffix,i.type")->group("i.id")->where($where)->count();
		$count2 = \think\Db::name("invoices")->alias("i")->leftJoin("invoice_items in", "in.invoice_id=i.id")->leftJoin("upgrades up", "up.id=in.rel_id")->leftJoin("host h", "h.id=up.relid")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("invoice_items t", "t.invoice_id = i.id")->field("i.id,i.create_time,i.due_time,i.paid_time,i.subtotal,i.payment,i.status,cu.prefix,cu.suffix,i.type")->group("i.id")->where($where)->count();
		$count = $count1 + $count2;
		$type_arr = ["renew" => "续费", "product" => "产品", "recharge" => "充值", "setup" => "初装费", "upgrade" => "产品升降级", "credit_limit" => "信用额"];
		$gateways = gateway_list();
		$invocies1 = \think\Db::name("invoices")->alias("i")->leftJoin("invoice_items in", "in.invoice_id=i.id")->leftJoin("host h", "h.id=in.rel_id")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("invoice_items t", "t.invoice_id = i.id")->field("i.status,i.id,i.create_time,i.due_time,i.paid_time,i.subtotal,i.subtotal as sub,i.total,i.credit,i.payment,i.status,cu.prefix,cu.suffix,i.type,i.use_credit_limit")->where($where)->group("i.id")->withAttr("payment", function ($value, $data) use($gateways) {
			if ($data["status"] == "Paid") {
				if ($data["use_credit_limit"] == 1) {
					return "信用额";
				} else {
					if ($data["sub"] == $data["credit"]) {
						return "余额支付";
					} else {
						if ($data["sub"] > $data["credit"] && $data["credit"] > 0) {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return "部分余额支付+" . $v["title"];
								}
							}
						} else {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return $v["title"];
								}
							}
						}
					}
				}
			} else {
				foreach ($gateways as $v) {
					if ($v["name"] == $value) {
						return $v["title"];
					}
				}
			}
		})->withAttr("paid_time", function ($value, $data) {
			if ($data["status"] == "Unpaid" || $data["status"] == "Cancelled") {
				return "";
			} else {
				return $value;
			}
		})->withAttr("subtotal", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->withAttr("type", function ($value) use($type_arr) {
			return $type_arr[$value];
		})->order($order, $sort)->group("i.id")->select()->toArray();
		$invocies2 = \think\Db::name("invoices")->alias("i")->leftJoin("invoice_items in", "in.invoice_id=i.id")->leftJoin("upgrades up", "up.id=in.rel_id")->leftJoin("host h", "h.id=up.relid")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->leftJoin("invoice_items t", "t.invoice_id = i.id")->field("i.status,i.id,i.create_time,i.due_time,i.paid_time,i.subtotal,i.subtotal as sub,i.total,i.credit,i.payment,i.status,cu.prefix,cu.suffix,i.type,i.use_credit_limit")->where($where)->group("i.id")->withAttr("payment", function ($value, $data) use($gateways) {
			if ($data["status"] == "Paid") {
				if ($data["use_credit_limit"] == 1) {
					return "信用额";
				} else {
					if ($data["sub"] == $data["credit"]) {
						return "余额支付";
					} else {
						if ($data["sub"] > $data["credit"] && $data["credit"] > 0) {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return "部分余额支付+" . $v["title"];
								}
							}
						} else {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return $v["title"];
								}
							}
						}
					}
				}
			} else {
				foreach ($gateways as $v) {
					if ($v["name"] == $value) {
						return $v["title"];
					}
				}
			}
		})->withAttr("paid_time", function ($value, $data) {
			if ($data["status"] == "Unpaid" || $data["status"] == "Cancelled") {
				return "";
			} else {
				return $value;
			}
		})->withAttr("subtotal", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->withAttr("type", function ($value) use($type_arr) {
			return $type_arr[$value];
		})->order($order, $sort)->group("i.id")->select()->toArray();
		$invocies = array_merge($invocies1, $invocies2);
		$status = config("invoice_payment_status");
		$gateway = gateway_list();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "count" => $count, "invocies" => $invocies, "gateway" => $gateway, "invoice_status" => $status]);
	}
	public function get_search($param)
	{
		$where = [["i.is_delete", "=", 0], ["i.delete_time", "=", 0]];
		if (isset($param["uid"]) && $param["uid"] > 0) {
			$where[] = ["i.uid", "=", $param["uid"]];
		}
		if (isset($param["hid"]) && $param["hid"] > 0) {
			$where[] = ["h.id", "=", $param["hid"]];
		}
		if (!empty($param["payment"])) {
			if ($param["payment"] == "creditLimitPay") {
				$where[] = ["i.use_credit_limit", "=", 1];
			} elseif ($param["payment"] == "creditPay") {
				$where[] = ["i.credit", ">", 0];
			} else {
				$where[] = ["i.payment", "=", $param["payment"]];
			}
		}
		if (!empty($param["status"])) {
			$where[] = ["i.status", "=", $param["status"]];
		}
		if (!empty($params["hostid"])) {
			$where[] = ["t.rel_id", "=", $param["hostid"]];
		}
		if (isset($param["create_time"]) && $param["create_time"] > 0) {
			$where[] = ["i.create_time", ">=", $param["create_time"]];
			$where[] = ["i.create_time", "<", $param["create_time"] + 86400];
		}
		if (isset($param["due_time"])) {
			$due_time = strtotime(date("Y-m-d", $param["due_time"]));
			$where[] = ["i.due_time", ">=", $due_time];
			$where[] = ["i.due_time", "<", $due_time + 86400];
		}
		if (isset($param["paid_time"])) {
			$paid_time = strtotime(date("Y-m-d", $param["paid_time"]));
			$where[] = ["i.paid_time", ">=", $paid_time];
			$where[] = ["i.paid_time", "<", $paid_time + 86400];
		}
		if (!empty($param["lineitem_desc"])) {
			$invoice_id = \think\Db::name("invoice_items")->whereLike("description", "%" . $param["lineitem_desc"] . "%")->column("invoice_id");
			if (empty($invoice_id)) {
				$invoice_id = [0];
			}
			$where[] = ["i.id", "in", $invoice_id];
		}
		if (!empty($param["subtotal_small"]) && $param["subtotal_small"] > 0) {
			$where[] = ["i.subtotal", ">=", $param["subtotal_small"]];
		}
		if (!empty($param["subtotal_big"]) && $param["subtotal_small"] > 0) {
			$where[] = ["i.subtotal", "<=", $param["subtotal_big"]];
		}
		return $where;
	}
	/**
	 * @title  添加账单
	 * @description 接口说明: 添加当前客户的账单，状态为‘已草稿’
	 * @param .name:uid type:int require:0  other: desc:用户ID
	 * @data id:账单ID
	 * @throws
	 * @author wyh
	 * @url admin/add_user_invoice
	 * @method post
	 */
	public function addUserInvoice()
	{
		$params = $this->request->param();
		$uid = $params["uid"];
		if (!$uid) {
			return jsonrule(["status" => 400, "msg" => \lang("ID_ERROR")]);
		}
		if (!$this->check1($uid)) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		$defaultgateway = \think\Db::name("clients")->where("id", $uid)->value("defaultgateway");
		foreach (gateway_list() as $v) {
			if ($v["id"] == $defaultgateway) {
				$gateway = $v["name"];
			}
		}
		$invoice_data = ["uid" => $uid, "status" => "Unpaid", "payment" => $gateway ?? gateway_list()[0]["name"], "create_time" => time(), "due_time" => strtotime("+1 month")];
		$r = \think\Db::name("invoices")->insertGetId($invoice_data);
		if ($r) {
			active_log(sprintf($this->lang["UserManage_user_addUserInvoice_success"], $params["uid"], $r), $params["uid"]);
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS"), "invoice_id" => $r]);
		} else {
			active_log(sprintf($this->lang["UserManage_user_addUserInvoice_fail"], $params["uid"], lang("ADD FAIL")), $params["uid"]);
			return jsonrule(["status" => 40, "msg" => lang("ADD FAIL")]);
		}
	}
	/**
	 * @title  以该客户登录
	 * @description 接口说明: 以该客户登录
	 * @param .name:uid type:int require:0  other: desc:用户ID
	 * @return  data:账单列表@
	 * @data id:账单ID
	 * @throws
	 * @author wyh
	 * @url admin/login_by_user/:uid
	 * @method get
	 */
	public function loginByUser()
	{
		$param = $this->request->param();
		$uid = intval($param["uid"]);
		if (!$this->check1($uid)) {
			return jsonrule(["status" => 409, "msg" => \lang("NOT_USER_ID")]);
		}
		$result = \think\Db::name("clients")->field("id,username")->where("id", $uid)->find();
		if ($result) {
			$userinfo["id"] = $result["id"];
			$userinfo["username"] = $result["username"];
			active_log(sprintf($this->lang["UserManage_user_loginByUser_success"], $uid), $uid);
			$jwt = createJwt($userinfo);
			userSetCookie($jwt);
			return jsonrule(["jwt" => $jwt, "status" => 200, "msg" => "登录成功"]);
		}
		active_log(sprintf($this->lang["UserManage_user_loginByUser_fail"], $uid, lang("登录失败")), $uid);
		return jsonrule(["status" => 400, "msg" => lang("登录失败")]);
	}
	protected function download($file_dir)
	{
		if (!file_exists($file_dir)) {
			$re["status"] = 400;
			$re["msg"] = "文件不存在";
			return $re;
		} else {
			$file1 = fopen($file_dir, "r");
			Header("Content-type: application/octet-stream");
			Header("Accept-Ranges: bytes");
			Header("Accept-Length:" . filesize($file_dir));
			Header("Content-Disposition: attachment;filename=" . $file_dir);
			ob_clean();
			flush();
			echo fread($file1, filesize($file_dir));
			fclose($file1);
			$re["status"] = 200;
			$re["msg"] = "下载成功";
			exit;
		}
	}
	public function relationUserList()
	{
		$users = model("client")->userList();
		return $users;
	}
	/**
	 * @title  创建充值账单
	 * @description 接口说明: 创建充值账单
	 * @param .name:uid type:int require:0  other: desc:用户ID
	 * @param .name:amount type:int require:0  other: desc:金额
	 * @param .name:notes type:int require:0  other: desc:备注
	 * @throws
	 * @author wyh
	 * @url admin/add_recharge_invoice/:uid
	 * @method post
	 */
	public function addRechargeInvoice()
	{
		$params = $this->request->param();
		$uid = intval($params["uid"]);
		if (!$uid) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$amount = floatval($params["amount"]);
		if ($amount <= 0) {
			return jsonrule(["status" => 400, "msg" => lang("充值金额大于0")]);
		}
		$rate = 1;
		$userMinRecharge = configuration("addfunds_minimum") * $rate;
		if ($amount < $userMinRecharge) {
			$tmp_userMinRecharge = ceil($userMinRecharge * 100) / 100;
			return jsons(["msg" => "最小充值金额:{$tmp_userMinRecharge}", "status" => 400]);
		}
		$userMaxRecharge = configuration("addfunds_maximum") * $rate;
		if ($userMaxRecharge < $amount) {
			return jsons(["msg" => "最大充值金额:{$userMaxRecharge}", "status" => 400]);
		}
		$clients = \think\Db::name("clients")->field("defaultgateway")->where("id", $uid)->find();
		$data = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "subtotal" => $amount, "total" => $amount, "status" => "Unpaid", "payment" => $clients["defaultgateway"], "type" => "recharge", "notes" => trim($params["notes"])];
		$data2 = ["uid" => $uid, "type" => "recharge", "description" => "用户充值", "amount" => $amount, "due_time" => strtotime("+365 day")];
		\think\Db::startTrans();
		try {
			$id = \think\Db::name("invoices")->insertGetId($data);
			$data2["invoice_id"] = $id;
			\think\Db::name("invoice_items")->insert($data2);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
		}
		return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS"), "invoiceid" => $id]);
	}
	/**
	 * @title  请求取消列表
	 * @description 接口说明: 请求取消列表
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @throws
	 * @author wyh
	 * @url admin/request_cancel_list
	 * @method get
	 */
	public function requestCancelList()
	{
		$params = $data = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		$count = \think\Db::name("cancel_requests")->alias("a")->field("a.id,b.uid,b.id as hid,c.username,c.companyname,a.create_time,d.name,b.domain,b.dedicatedip,a.type,a.reason,b.domainstatus,a.delete_time as nextduedate")->leftJoin("host b", "a.relid = b.id")->leftJoin("clients c", "b.uid = c.id")->join("products d", "b.productid = d.id");
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$count->whereIn("c.id", $this->str);
		}
		$count = $count->count();
		$type = ["Immediate" => "立即", "Endofbilling" => "到期"];
		if (!in_array($order, ["username", "companyname", "create_time", "name", "domain", "dedicatedip", "type", "reason", "domainstatus", "nextduedate"])) {
			$order = "a.id";
		}
		if ($order == "create_time") {
			$order = "a.create_time";
		} elseif ($order == "type") {
			$order = "a.type";
		} elseif ($order == "name") {
			$order = "d.name";
		} elseif ($order == "reason") {
			$order = "a.reason";
		} elseif ($order == "domainstatus") {
			$order = "b.domainstatus";
		}
		$list = \think\Db::name("cancel_requests")->alias("a")->field("a.id,b.uid,b.id as hid,c.username,c.companyname,a.create_time,d.name,b.domain,b.dedicatedip,a.type,a.reason,b.domainstatus,a.delete_time as nextduedate,cg.group_colour,cc.status as company_status,cp.status as person_status,a.status as cancel_status")->leftJoin("host b", "a.relid = b.id")->leftJoin("clients c", "b.uid = c.id")->leftJoin("client_groups cg", "cg.id = c.groupid")->leftJoin("certifi_company cc", "cc.auth_user_id = c.id")->leftJoin("certifi_person cp", "cp.auth_user_id = c.id")->join("products d", "b.productid = d.id")->withAttr("type", function ($value) use($type) {
			return $type[$value] ?? "";
		})->withAttr("company_status", function ($value) {
			return config("client_certifi_status")[$value];
		})->withAttr("person_status", function ($value) {
			return config("client_certifi_status")[$value];
		})->withAttr("cancel_status", function ($value) {
			return config("cancel_requests_status")[$value];
		})->withAttr("domainstatus", function ($value) {
			return config("public.domainstatus")[$value] ?? [];
		});
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$list->whereIn("c.id", $this->str);
		}
		$list = $list->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		$billingcycle = config("billing_cycle");
		$hosts = [];
		foreach ($list as &$v) {
			$hosts = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid = b.id")->leftJoin("upgrades u", "u.relid = a.id")->leftjoin("clients c", "a.uid = c.id")->leftjoin("currencies cu", "cu.id = c.currency")->field("a.uid,a.initiative_renew,a.id as hostid,b.id as invoice_type,b.name,a.domain,a.dedicatedip,a.billingcycle,a.firstpaymentamount,a.productid,cu.prefix,cu.suffix")->distinct(true)->where("a.id", $v["hid"])->withAttr("billingcycle", function ($value) use($billingcycle) {
				return $billingcycle[$value];
			})->withAttr("firstpaymentamount", function ($value, $row) {
				return $row["prefix"] . $value . $row["suffix"];
			})->order("a.id", "desc")->select()->toArray();
			$v["hosts"] = $hosts;
		}
		$data = ["total" => $count ?? 0, "list" => $list ?? []];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title  取消请求原因管理
	 * @description 接口说明: 取消请求原因管理
	 * @throws
	 * @author wyh
	 * @url admin/request_cancel_reason
	 * @method get
	 */
	public function requestCancelReason()
	{
		$cancel_reason = \think\Db::name("cancel_reason")->select()->toArray();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $cancel_reason]);
	}
	/**
	 * @title  取消请求原因增加修改
	 * @description 接口说明: 取消请求原因增加修改
	 * @param .name:type type:int require:1 default:1 other: desc:1添加2修改
	 * @param .name:id type:int require:1 default:1 other: desc:id
	 * @param .name:reason type:int require:1 default:10 other: desc:原因
	 * @throws
	 * @author wyh
	 * @url admin/request_cancel_reason_post
	 * @method post
	 */
	public function requestCancelReasonPost()
	{
		$params = $this->request->param();
		$data = $params["reason"];
		foreach ($data as $key => $val) {
			if ($val["id"] == 0 || $val["id"] == "") {
				$id = \think\Db::name("cancel_reason")->insertGetId(["reason" => htmlspecialchars_decode($val["reason"])]);
				active_log(sprintf($this->lang["Usermanage_add_reason"], $id, $val["reason"]));
			} else {
				$cr = \think\Db::name("cancel_reason")->where("id", $val["id"])->find();
				$desc = "";
				$data1 = ["reason" => htmlspecialchars_decode($val["reason"])];
				if ($cr["reason"] != $data1["reason"]) {
					$desc .= "原因由:“" . $cr["reason"] . "”改为“" . $data1["reason"] . "”，";
				}
				$res = \think\Db::name("cancel_reason")->where("id", $val["id"])->update($data1);
				if ($desc != "") {
					active_log(sprintf($this->lang["Usermanage_edit_reason"], $res, $desc));
				}
			}
		}
		$del = $params["del"];
		foreach ($del as $key => $val) {
			$cancel = \think\Db::name("cancel_reason")->where("id", $val)->find();
			active_log(sprintf($this->lang["Usermanage_del_reason"], $val, $cancel["reason"]));
			\think\Db::name("cancel_reason")->where("id", $val)->delete();
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title 删除请求原因
	 * @description 接口说明: 删除请求原因
	 * @param .name:id type:int require:1 default:1 other: desc:id
	 * @throws
	 * @author lgd
	 * @url admin/del_reason
	 * @method get
	 */
	public function DelReason()
	{
		$params = $data = $this->request->param();
		$id = $params["id"] ?? "";
		$cancel = \think\Db::name("cancel_reason")->where("id", $id)->find();
		active_log(sprintf($this->lang["Usermanage_del_reason"], $id, $cancel["reason"]));
		\think\Db::name("cancel_reason")->where("id", $id)->delete();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title  删除取消请求
	 * @description 接口说明: 删除取消请求
	 * @param .name:id type:int require:1 default:1 other: desc:id
	 * @throws
	 * @author wyh
	 * @url admin/request_cancel_list/:id
	 * @method DELETE
	 */
	public function deleteCancelRequest()
	{
		$param = $this->request->param();
		$id = $param["id"];
		$cancel = \think\Db::name("cancel_requests")->alias("a")->field("b.domainstatus")->leftJoin("host b", "a.relid = b.id")->where("a.id", $id)->find();
		if ($cancel["domainstatus"] == "Deleted") {
			return jsonrule(["status" => 400, "msg" => "取消已被删除"]);
		}
		\think\Db::name("cancel_requests")->where("id", $id)->delete();
		return jsonrule(["status" => 200, "msg" => lang("DELETE SUCCESS")]);
	}
	/**
	 * @title  添加跟踪记录
	 * @description 接口说明: 添加跟踪记录
	 * @param .name:stime type:int require:1 default:1 other: desc:时间戳(前端除1000,用秒)
	 * @param .name:record type:string require:1 default:1 other: desc:记录
	 * @param .name:uid type:int require:1 default:1 other: desc:客户ID
	 * @throws
	 * @author wyh
	 * @time 2020-11-24
	 * @url admin/add_record_log
	 * @method POST
	 */
	public function addRecordLog()
	{
		$param = $this->request->param();
		$uid = intval($param["uid"]);
		$client = \think\Db::name("clients")->where("id", $uid)->find();
		if (empty($client)) {
			return jsonrule(["status" => 400, "msg" => "客户不存在"]);
		}
		if (empty($param["record"])) {
			return jsonrule(["status" => 400, "msg" => "请填写记录"]);
		}
		$record = trim($param["record"]);
		$time = intval($param["stime"]);
		$id = \think\Db::name("clients_track_record")->insertGetId(["uid" => $uid, "des" => $record, "create_time" => $time ?: time(), "update_time" => 0]);
		if ($id) {
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
		}
	}
	/**
	 * @title  添加跟踪记录 补充说明
	 * @description 接口说明: 添加跟踪记录 补充说明
	 * @param .name:id type:int require:1 default:1 other: desc:记录ID
	 * @param .name:remark type:string require:1 default:1 other: desc:补充说明
	 * @throws
	 * @author wyh
	 * @time 2020-11-24
	 * @url admin/add_remark_log
	 * @method POST
	 */
	public function addRemarkLog()
	{
		$param = $this->request->param();
		$id = intval($param["id"]);
		$tmp = \think\Db::name("clients_track_record")->where("id", $id)->find();
		if (empty($tmp)) {
			return jsonrule(["status" => 400, "msg" => "记录不存在"]);
		}
		if (empty($param["remark"])) {
			return jsonrule(["status" => 400, "msg" => "请填写补充说明"]);
		}
		\think\Db::name("clients_track_remark")->insert(["track_id" => $id, "remark" => trim($param["remark"]), "create_time" => time()]);
		if ($id) {
			return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		} else {
			return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
		}
	}
	/**
	 * @title  获取跟踪记录
	 * @description 接口说明: 获取跟踪记录
	 * @param .name:uid type:int require:1 default:1 other: desc:客户ID
	 * @param .name:start_time type:int require:0 default:1 other: desc:非必传 搜索 开始时间 时间戳(s 除1000)
	 * @param .name:end_time type:int require:0 default:1 other: desc:非必传 搜索 结束时间时间 时间戳(s 除1000)
	 * @return  track_status:跟踪状态,1待开始，2跟进中，3已完成
	 * @return  track_status_zh:跟踪状态,1待开始，2跟进中，3已完成
	 * @return  list:记录@
	 * @list  id:记录ID
	 * @list  des:记录
	 * @list  remark:补充说明@
	 * @remark  remark:补充说明
	 * @list  create_time:创建时间
	 * @throws
	 * @author wyh
	 * @time 2020-11-24
	 * @url admin/track_record
	 * @method GET
	 */
	public function getTrackRecord()
	{
		$data = [];
		$track_status = config("client_track_status");
		$param = $this->request->param();
		$uid = intval($param["uid"]);
		$client = \think\Db::name("clients")->where("id", $uid)->find();
		if (empty($client)) {
			return jsonrule(["status" => 400, "msg" => "客户不存在"]);
		}
		$list = \think\Db::name("clients_track_record")->field("id,des,create_time")->where("uid", $uid)->where(function (\think\db\Query $query) use($param) {
			if (isset($param["start_time"])) {
				$query->where("create_time", ">=", intval($param["start_time"]));
			}
			if (isset($param["end_time"])) {
				$query->where("create_time", "<=", intval($param["end_time"]));
			}
		})->select()->toArray();
		foreach ($list as &$v) {
			$v["remark"] = \think\Db::name("clients_track_remark")->field("id,remark,create_time")->where("track_id", $v["id"])->select()->toArray() ?: [];
		}
		$data["list"] = $list;
		$data["track_status"] = $client["track_status"];
		$data["track_status_zh"] = $track_status[$client["track_status"]];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title  修改跟踪记录状态
	 * @description 接口说明: 修改跟踪记录状态
	 * @param .name:uid type:int require:1 default:1 other: desc:客户ID
	 * @param .name:track_status type:int require:1 default:1 other: desc:跟踪状态：1待开始，2跟进中，3已完成
	 * @throws
	 * @author wyh
	 * @time 2020-11-24
	 * @url admin/track_status
	 * @method POST
	 */
	public function clientTrackStatus()
	{
		$param = $this->request->param();
		$uid = intval($param["uid"]);
		$client = \think\Db::name("clients")->where("id", $uid)->find();
		if (empty($client)) {
			return jsonrule(["status" => 400, "msg" => "客户不存在"]);
		}
		$track_status = intval($param["track_status"]);
		if (!in_array($track_status, array_keys(config("client_track_status")))) {
			return jsonrule(["status" => 400, "msg" => "状态只能为1,2,3"]);
		}
		\think\Db::name("clients")->where("id", $uid)->update(["track_status" => $track_status]);
		return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
	}
	/**
	 * @title  获取备注
	 * @description 接口说明: 获取备注
	 * @param .name:uid type:int require:1 default:1 other: desc:客户ID
	 * @throws
	 * @author wyh
	 * @time 2020-12-11
	 * @url admin/get_client_notes
	 * @method GET
	 */
	public function getClientNotes()
	{
		$param = $this->request->param();
		$uid = intval($param["uid"]);
		$notes = \think\Db::name("clients")->where("id", $uid)->value("notes");
		$data = ["notes" => $notes ?: ""];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title  获取备注
	 * @description 接口说明: 获取备注
	 * @param .name:uid type:int require:1 default:1 other: desc:客户ID
	 * @param .name:notes type:string require:1 default:1 other: desc:备注
	 * @throws
	 * @author wyh
	 * @time 2020-12-11
	 * @url admin/post_client_notes
	 * @method POST
	 */
	public function postClientNotes()
	{
		$param = $this->request->param();
		$uid = intval($param["uid"]);
		$notes = $param["notes"];
		if (strlen($notes) > 500) {
			return jsonrule(["status" => 400, "msg" => "备注不超过500个字符"]);
		}
		\think\Db::name("clients")->where("id", $uid)->update(["notes" => $notes, "update_time" => time()]);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title  后台实名认证
	 * @description 接口说明: 后台实名认证
	 * @param .name:uid type:int require:1 default:1 other: desc:客户ID
	 * @throws
	 * @author xue
	 * @time 2020-12-11
	 * @url admin/post_client_notes
	 * @method get
	 */
	public function authorInfo()
	{
		try {
			$param = $this->request->param();
			$log = \think\Db::name("certifi_log")->where("uid", $param["uid"])->order("id", "desc")->find();
			if ($log) {
				if ($log["pic"]) {
					$tmp = explode(",", $log["pic"]);
					$log["pic"] = array_map(function ($v) {
						return $v ? $this->getImage . $v : "";
					}, $tmp);
				}
				if ($log["status"] != 1) {
					$log["status"] = 2;
				}
				$log["custom_fields_log"] = json_decode($log["custom_fields_log"], true) ?: [];
				$log["custom_fields_log_arr"] = [];
				if ($log["custom_fields_log"]) {
					$cname = cmf_parse_name($log["certifi_type"]);
					$customfields = getCertificationCustomFields($cname, "certification");
					$i = 0;
					foreach ($customfields as $customfield) {
						$i++;
						$i <= 10 && ($log["custom_fields_log_arr"][$customfield["field"]] = ["type" => $customfield["type"], "title" => $customfield["title"], "val" => $log["custom_fields_log"]["custom_fields" . $i] ?? ""]);
					}
				}
			}
			$config = $this->getConfInfo();
			$config["certifi_business_is_author"] = getEdition() ? 1 : 0;
			return jsons(["status" => 200, "data" => ["log" => $log, "company" => $this->getPluginsInfo("enterprises"), "person" => $this->getPluginsInfo("personal"), "config" => $config]]);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	protected function passSendSms($param)
	{
		$conf = $this->getConfInfo();
		if (!$conf["artificial_auto_send_msg"] || !getEdition()) {
			return false;
		}
		$message_template_type = array_column(config("message_template_type"), "id", "name");
		$sms = new \app\common\logic\Sms();
		$sms_template = strtolower("realname_pass_remind");
		$client = check_type_is_use($message_template_type[$sms_template], $param["uid"], $sms);
		if ($client) {
			$sms->sendSms($message_template_type[$sms_template], $client["phone_code"] . $client["phonenumber"], ["username" => $client["username"]], false, $param["uid"]);
		}
	}
	/**
	 * @title  后台实名认证提交
	 * @description 接口说明: 后台实名认证提交
	 * @throws
	 * @author xue
	 * @time 2020-12-11
	 * @url admin/authorSubmit
	 * @method POST
	 */
	public function authorSubmit()
	{
		try {
			$param = $this->request->param();
			$user_info = \think\Db::name("clients")->where("id", $param["uid"])->find();
			if (!$user_info) {
				throw new \Exception("用户信息不存在");
			}
			$this->checkOtherUsed($param["idcard"], $param["uid"]);
			if (!$param["certifi_type"]) {
				throw new \think\Exception("认证方式不能为空!");
			}
			$this->pluginIdcsmartauthor($param["certifi_type"]);
			if ($param["type"] == 1) {
				$this->authorPersonSubmit($param);
			}
			if ($param["type"] == 2) {
				$this->authorCompanySubmit($param);
			}
			return jsons(["status" => 200, "msg" => "修改成功"]);
		} catch (\Throwable $e) {
			return jsons(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	protected function authorPersonSubmit($param)
	{
		$customField = $this->pluginCustomField($param);
		$data["auth_user_id"] = $param["uid"];
		$data["bank"] = $param["bank"] ?: "";
		$data["phone"] = $param["phone"] ?: "";
		$data["auth_real_name"] = $param["auth_real_name"] ?: "";
		$data["auth_card_type"] = $param["auth_card_type"] ?: 1;
		$data["auth_card_number"] = $param["idcard"] ?: "";
		$data["img_one"] = $param["img_one"] ?: "";
		$data["img_two"] = $param["img_two"] ?: "";
		if ($param["status"] == 1) {
			$data["status"] = 1;
		}
		$customField = array_map(function ($v) {
			return is_null($v) ? "" : $v;
		}, $customField);
		$data = array_merge($data, $customField);
		$checkuser = \think\Db::name("certifi_person")->where("auth_user_id", $param["uid"])->find();
		if (!isset($data["status"])) {
			$data["status"] = $checkuser ? $checkuser["status"] : 3;
		}
		if ($data["status"] == 1 && $checkuser && $checkuser["status"] == 1 && $param["status"] == 2) {
			$data["status"] = 3;
		}
		if ($checkuser) {
			if ($data["status"] == 1 && $checkuser["status"] != 1) {
				$this->passSendSms($param);
			}
			$data["update_time"] = time();
			\think\Db::name("certifi_person")->where("id", $checkuser["id"])->update($data);
		} else {
			if ($data["status"] == 1) {
				$this->passSendSms($param);
			}
			$data["create_time"] = time();
			\think\Db::name("certifi_person")->insertGetId($data);
		}
		if ($data["status"] == 1) {
			unsuspendAfterCertify($param["uid"]);
		}
		$data["pic"] = implode(",", [$data["img_one"], $data["img_two"]]);
		$this->save_log($data, 1, $param["certifi_type"], $data["status"], $customField);
	}
	protected function authorCompanySubmit($param)
	{
		$customField = $this->pluginCustomField($param);
		$data["auth_user_id"] = $param["uid"];
		$data["bank"] = $param["bank"] ?: "";
		$data["phone"] = $param["phone"] ?: "";
		$data["company_name"] = $param["company_name"] ?: "";
		$data["company_organ_code"] = $param["company_organ_code"] ?: "";
		$data["auth_real_name"] = $param["auth_real_name"] ?: "";
		$data["auth_card_number"] = $param["idcard"] ?: "";
		$data["img_one"] = $param["img_one"] ?: "";
		$data["img_two"] = $param["img_two"] ?: "";
		$data["img_three"] = $param["img_three"] ?: "";
		$data["img_four"] = $param["img_four"] ?: "";
		$data["auth_card_type"] = $param["card_type"] ?: 1;
		if ($param["status"] == 1) {
			$data["status"] = 1;
		}
		$customField = array_map(function ($v) {
			return is_null($v) ? "" : $v;
		}, $customField);
		$data = array_merge($data, $customField);
		$checkuser = \think\Db::name("certifi_company")->where("auth_user_id", $param["uid"])->find();
		if (!isset($data["status"])) {
			$data["status"] = $checkuser ? $checkuser["status"] : 3;
		}
		if ($data["status"] == 1 && $checkuser && $checkuser["status"] == 1 && $param["status"] == 2) {
			$data["status"] = 3;
		}
		if ($checkuser) {
			if ($data["status"] == 1 && $checkuser["status"] != 1) {
				$this->passSendSms($param);
			}
			$data["update_time"] = time();
			\think\Db::name("certifi_company")->where("id", $checkuser["id"])->update($data);
		} else {
			if ($data["status"] == 1) {
				$this->passSendSms($param);
			}
			$data["create_time"] = time();
			\think\Db::name("certifi_company")->insertGetId($data);
		}
		if ($data["status"] == 1) {
			unsuspendAfterCertify($param["uid"]);
		}
		$data["pic"] = implode(",", [$data["img_one"], $data["img_two"], $data["img_three"], $data["img_four"]]);
		$this->save_log($data, 2, $param["certifi_type"], $data["status"], $customField);
	}
	protected function pluginCustomField($param)
	{
		$data = [];
		$certifi_type = $param["certifi_type"];
		if ($certifi_type == "artificial") {
			return $data;
		}
		$cname = cmf_parse_name($certifi_type);
		$customfields = getCertificationCustomFields($cname, "certification");
		if (empty($customfields)) {
			return $data;
		}
		$i = 0;
		foreach ($customfields as $customfield) {
			$i++;
			if ($i <= 10) {
				$data["custom_fields" . $i] = $param[$customfield["field"]];
			}
		}
		return $data;
	}
	private function save_log($arr, $type, $certifi_type, $status, $custom_fields_log = [])
	{
		$data = ["uid" => $arr["auth_user_id"], "certifi_name" => $arr["auth_real_name"], "card_type" => $arr["auth_card_type"], "idcard" => $arr["auth_card_number"], "company_name" => $arr["company_name"] ?? "", "bank" => $arr["bank"] ?? "", "phone" => $arr["phone"] ?? "", "company_organ_code" => $arr["company_organ_code"] ?? "", "pic" => $arr["pic"], "status" => $status, "type" => $type, "certifi_type" => $certifi_type, "custom_fields_log" => json_encode($custom_fields_log)];
		$data["create_time"] = $this->request->time();
		return \think\Db::name("certifi_log")->insert($data);
	}
	protected function checkOtherUsed($idcard, $clientid)
	{
		$where = [["auth_card_number", "=", $idcard], ["status", "<>", 2]];
		$companyUsed = \think\Db::name("certifi_company")->where($where)->find();
		$personalUsed = \think\Db::name("certifi_person")->where($where)->find();
		if (isset($companyUsed["id"]) && $companyUsed["auth_user_id"] != $clientid) {
			throw new \think\Exception("该身份信息已被他人使用!");
		}
		if (isset($personalUsed["id"]) && $personalUsed["auth_user_id"] != $clientid) {
			throw new \think\Exception("该身份信息已被他人使用!");
		}
	}
	protected function pluginIdcsmartauthor($pluginName, $type = "personal")
	{
		$class = cmf_get_plugin_class_shd($pluginName, "certification");
		$methods = get_class_methods($class);
		if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
			$res = pluginIdcsmartauthorize($pluginName);
			if ($res["status"] != 200) {
				throw new \think\Exception($res["msg"]);
			}
		}
		$all_type = array_column(getPluginsList("certification", $type), "name") ?: [];
		array_unshift($all_type, "artificial");
		if (!in_array($pluginName, $all_type)) {
			throw new \think\Exception("认证方式错误");
		}
	}
	protected function getPluginsInfo($type = "personal")
	{
		$all_plugins = getPluginsList("certification", $type);
		if (empty($all_plugins[0])) {
			$arr[] = ["name" => "人工审核", "value" => "artificial", "custom_fields" => []];
		} else {
			foreach ($all_plugins as $all_plugin) {
				$arr[] = ["name" => $all_plugin["title"], "value" => $all_plugin["name"], "custom_fields" => $all_plugin["custom_fields"] ?: []];
			}
		}
		return $arr ?? [];
	}
	protected function getConfInfo()
	{
		$conf = configuration(["certifi_open", "artificial_auto_send_msg", "certifi_is_upload", "certifi_business_btn", "certifi_business_open", "certifi_business_is_upload", "certifi_business_is_author", "certifi_business_author_path", "certifi_isbindphone"]);
		$conf["certifi_is_upload"] = $conf["certifi_is_upload"] == 2 ? 0 : 1;
		$conf["certifi_open"] = $conf["certifi_open"] == 2 ? 0 : 1;
		if (!$conf["certifi_open"]) {
			$conf["certifi_business_open"] = 0;
		}
		$data = ["artificial_auto_send_msg" => $conf["artificial_auto_send_msg"], "certifi_isbindphone" => $conf["certifi_isbindphone"], "certifi_is_upload" => $conf["certifi_open"] ? $conf["certifi_is_upload"] : 0, "certifi_business_is_author" => $conf["certifi_business_open"] ? $conf["certifi_business_is_author"] : 0, "certifi_business_author_path" => $conf["certifi_business_author_path"] ? config("author_attachments_url") . $conf["certifi_business_author_path"] : ""];
		$data["certifi_business_is_upload"] = $conf["certifi_business_open"] ? $conf["certifi_business_is_upload"] : $data["certifi_is_upload"];
		return $data;
	}
}